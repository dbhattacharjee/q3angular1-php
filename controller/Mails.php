<?php

namespace Angular;

class Mails {

    /**
    * Fetched emails of a user
    *
    * @return array
    */
    public function getMails($authkey){
       // return array('status'=>200, 'success'=>true, 'mails'=>array(array('subject'=>'test'), array('subject'=>'teting')));
        $auth = explode(':', base64_decode($authkey));
        $pop3 = eden('mail')->pop3(
                MAIL_HOST, $auth[0], $auth[1], MAIL_PORT, MAIL_SSL);
        $tempMails = array();
        if($totalEmails = $pop3->getEmailTotal()) {
            if($mails = $pop3->getEmails(0, $totalEmails)) {
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
        }
        return array('status'=>200, 'success'=>true, 'mails'=>$tempMails);
    }

    

}