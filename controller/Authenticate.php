<?php

namespace Angular;

class Authenticate {

    /**
    * Checks if a user is logged in.
    *
    * @return boolean
    */
    public function getLoggedIn(){
        return $this->getContainer('auth')->isLoggedIn();
    }
    
    private function _validateUser($username, $password) {
        $conn = $GLOBALS['glob_conn'];
        $query = 'SELECT * FROM `users` WHERE `email` = :email ';
        $statement = $conn->prepare($query);
        $statement->execute(array(':email' => trim($username)));
        if ($row = $statement->fetch()) {
            if (trim(decrypt($row['password'], ENCRYPTION_KEY)) == $password) {
                return $row['id'];
            }
        } 
        return false;
    }

    /**
    * @param string $username
    * @param string $password
    * return boolean
    */
    public function postAuthenticate($username, $password){
        $conn = $GLOBALS['glob_conn'];
        $query = 'SELECT * FROM `users` WHERE `email` = :email ';
        $statement = $conn->prepare($query);
        $statement->execute(array(':email' => trim($username)));
        if ($row = $statement->fetch()) {
            if (trim(decrypt($row['password'], ENCRYPTION_KEY)) == $password) {
                return array('status' => 200, 'success' => true, 'message' => 'Authenticated !');
            }
        } else {
            if (pop3_login(MAIL_HOST, MAIL_PORT, $username, $password, $folder = "INBOX", MAIL_SSL)) {
                $query = 'INSERT INTO `users` (email,`password`,registered_at)';
                $query .= ' VALUES (:email, :password, :registeredAt)';
                $statement = $conn->prepare($query);
                $statement->execute(array(':email' => trim($username), ':password' => trim(encrypt($password, ENCRYPTION_KEY)), ':registeredAt'=>time()));
                return array('status' => 200, 'success' => true, 'message' => 'Authenticated !');
            }
        }
        return array('status'=>403, 'success'=>false, 'message'=>'Email id or password is wrong :(');
    }

    public function getMails($authkey, $offset = 0, $limit = 5){

        $auth = explode(':', base64_decode($authkey));
        $username = $auth[0];
        $password = $auth[1];
        
        if($userId = $this->_validateUser($username, $password)) {
            //fetch sync mails
            $conn = $GLOBALS['glob_conn'];
            $query = 'SELECT * FROM `mails` WHERE `user_id` = :user_id ORDER BY `dated` DESC';
            $statement = $conn->prepare($query);
            $statement->execute(array(':user_id' => trim($userId)));
            $mailArray = array();
            if($result = $statement->fetchAll()) {
                foreach($result as $key=>$mail) {
                    $mailArray[$key]['id'] = $mail['id'];
                    $mailArray[$key]['subject'] = $mail['subject'];
                    //convert to milisecond
                    $mailArray[$key]['dated'] = $mail['dated'] * 1000;
                    $mailArray[$key]['to'] = $mail['toAddress'];
                    $mailArray[$key]['from'] = $mail['fromAddress'];
                    $mailArray[$key]['cc'] = $mail['ccAddress'];
                    $mailArray[$key]['mailUID'] = $mail['mail_uid'];
                    $mailArray[$key]['mailUuid'] = $mail['mail_uuid'];
                }
            } else {
                if ($imap = pop3_login(MAIL_HOST, MAIL_PORT, $username, $password, $folder = "INBOX", MAIL_SSL)) {
                    if($mails = pop3_fetch_emails($imap, LATEST_EMAIL_LIMIT)) {
                        foreach($mails as $key=>$mail) {
                            $body = pop3_get_body($mail['uid'], $imap);
                            
                            $query  = 'INSERT INTO `mails` (user_id, sync_at, `subject`, `toAddress`, `fromAddress`, `dated`, `mail_uid`,  `ccAddress`, `mail_uuid`, `body`)';
                            $query .=  'VALUES (:userId, :syncAt, :subject, :to, :from, :dated, :mailUID, :cc, :mailUuid, :body)';
                            $statement = $conn->prepare($query);
                            $statement->execute(array(':userId' => $userId, ':syncAt' => time(), ':subject'=>$mail['subject'], ':to'=>$mail['to'], ':from'=>$mail['from'], ':dated'=>$mail['udate'], ':mailUID'=>$mail['uid'], ':cc'=>$mail['cc'], ':mailUuid'=>$mail['message_id'], ':body'=>$body));
                        }
                        //call again, so it will return result from DB
                        return $this->getMails($authkey, $offset, $limit);
                    }
                }
            }
        }

        return array('status'=>200, 'success'=>true, 'mails'=>$mailArray);
    }
    
    public function getRefreshQueue($authkey){
        ignore_user_abort(true);
        set_time_limit(0);
        
        $auth = explode(':', base64_decode($authkey));
        $username = $auth[0];
        $password = $auth[1];
        $conn = $GLOBALS['glob_conn'];
        if($userId = $this->_validateUser($username, $password)) {
                if ($imap = pop3_login(MAIL_HOST, MAIL_PORT, $username, $password, $folder = "INBOX", MAIL_SSL)) {
                    $stat = pop3_stat($imap);
                    
                    if($stat['Nmsgs']) {
                        if($mails = pop3_fetch_emails($imap, $stat['Nmsgs'])) {
                            foreach($mails as $mail) {
                                $sql = 'SELECT COUNT(*) AS cnt FROM `mails` WHERE mail_uuid = :mail_uuid';
                                $statement = $conn->prepare($sql);
                                $statement->execute(array(':mail_uuid'=>$mail['message_id']));
                                $result = $statement->fetch();
                                if(!$result['cnt']) {
                                    $body = pop3_get_body($mail['uid'], $imap);
                                    $query  = 'INSERT INTO `mails` (user_id, sync_at, `subject`, `toAddress`, `fromAddress`, `dated`, `mail_uid`,  `ccAddress`, `mail_uuid`, `body`)';
                                    $query .=  'VALUES (:userId, :syncAt, :subject, :to, :from, :dated, :mailUID, :cc, :mailUuid, :body)';
                                    $statement = $conn->prepare($query);
                                    $statement->execute(array(':userId' => $userId, ':syncAt' => time(), ':subject'=>$mail['subject'], ':to'=>$mail['to'], ':from'=>$mail['from'], ':dated'=>$mail['udate'], ':mailUID'=>$mail['uid'], ':cc'=>$mail['cc'], ':mailUuid'=>$mail['message_id'], ':body'=>$body));
                                }
                            }
                        }
                    }
             }
           return $this->getMails($authkey, $offset, $limit);
        }
    }
    
    public function getSingleMail($authkey, $id){
        

        $auth = explode(':', base64_decode($authkey));
        $username = $auth[0];
        $password = $auth[1];
        $mailInfo = array();
        if($userId = $this->_validateUser($username, $password)) {
            //fetch sync mails
            $conn = $GLOBALS['glob_conn'];
            $query = 'SELECT id, fromAddress, toAddress,ccAddress, body FROM `mails` WHERE `user_id` = :user_id AND `id` = :id AND `body` IS NOT NULL';
            $statement = $conn->prepare($query);
            $statement->execute(array(':user_id' => trim($userId), ':id'=>$id));
            $mailInfo = $statement->fetch();
            $mailInfo['body'] = utf8_encode($mailInfo['body']);
            unset($mailInfo[4]);
            
        }

        return array('status'=>200, 'success'=>true, 'mailInfo'=> $mailInfo);
    }

}