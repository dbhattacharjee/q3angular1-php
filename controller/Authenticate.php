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
        return array('status'=>403, 'success'=>false, 'message'=>'Username or password is wrong :(');
    }

    public function getMails($authkey, $offset = 0, $limit = 5){

        $auth = explode(':', base64_decode($authkey));
        $username = $auth[0];
        $password = $auth[1];
        
        if($userId = $this->_validateUser($username, $password)) {
            //fetch sync mails
            $conn = $GLOBALS['glob_conn'];
            $query = 'SELECT * FROM `mails` WHERE `user_id` = :user_id ORDER BY id ASC';
            $statement = $conn->prepare($query);
            $statement->execute(array(':user_id' => trim($userId)));
            $mailArray = array();
            if($result = $statement->fetchAll()) {
                foreach($result as $key=>$mail) {
                    $mailArray[$key]['subject'] = $mail['subject'];
                    $mailArray[$key]['dated'] = date('d M, Y H:i', $mail['dated']);
                    $mailArray[$key]['to'] = $mail['toAddress'];
                    $mailArray[$key]['from'] = $mail['fromAddress'];
                    $mailArray[$key]['mailUID'] = $mail['mailUID'];
                }
            } else {
                if ($imap = pop3_login(MAIL_HOST, MAIL_PORT, $username, $password, $folder = "INBOX", MAIL_SSL)) {
                    if($mails = pop3_fetch_emails($imap, LATEST_EMAIL_LIMIT)) {
                        foreach($mails as $key=>$mail) {
                            $query  = 'INSERT INTO `mails` (user_id, sync_at, `subject`, `toAddress`, `fromAddress`, `dated`, `mail_uid`)';
                            $query .=  'VALUES (:userId, :syncAt, :subject, :to, :from, :dated, :mailUID)';
                            $statement = $conn->prepare($query);
                            $statement->execute(array(':userId' => $userId, ':syncAt' => time(), ':subject'=>$mail['subject'], ':to'=>$mail['to'], ':from'=>$mail['from'], ':dated'=>$mail['udate'], ':mailUID'=>$mail['uid']));
                            
                            $mailArray[$key]['subject'] = $mail['subject'];
                            $mailArray[$key]['dated'] = date('d M, Y H:i', $mail['udate']);
                            $mailArray[$key]['to'] = $mail['to'];
                            $mailArray[$key]['from'] = $mail['from'];
                            $mailArray[$key]['mailUID'] = $mai['uid'];
                        }
                    }
                }
            }
        }

        return array('status'=>200, 'success'=>true, 'mails'=>$mailArray);
    }

}