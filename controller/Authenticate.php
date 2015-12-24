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

    /**
    * @param string $username
    * @param string $password
    * return boolean
    */
    public function postAuthenticate($username, $password){
        $pop3 = eden('mail')->pop3(
                MAIL_HOST, $username, $password, MAIL_PORT, MAIL_SSL);
        if($pop3->getEmailTotal()) {
            return array('status'=>200, 'success'=>true, 'message'=>'Authenticated !');
        }
        return array('status'=>403, 'success'=>false, 'message'=>'Username or password is wrong :(');
    }

    public function getMails($authkey, $offset = 0, $limit = 5){
       // return array('status'=>200, 'success'=>true, 'mails'=>array(array('subject'=>'test'), array('subject'=>'teting')));
        $auth = explode(':', base64_decode($authkey));
        $pop3 = eden('mail')->pop3(
                MAIL_HOST, $auth[0], $auth[1], MAIL_PORT, MAIL_SSL);
        $tempMails = array();
        if($mails = $pop3->getEmails(1, 10)) {
            foreach($mails as $key=>$mail) {
                $tempMails[$key]['subject'] = $mail['subject'];
                $tempMails[$key]['from']['email'] = $mail['from']['email'];
                $tempMails[$key]['to'][0]['email'] = $mail['to'][0]['email'];
                $tempMails[$key]['date'] = date('Y-m-d H:i:s', $mail['date']);
            }
            usort($tempMails, function($a, $b) {
                return strtotime($a['date']) - strtotime($b['date']);
            });
        }
        return array('status'=>200, 'success'=>true, 'mails'=>$tempMails);
    }

}