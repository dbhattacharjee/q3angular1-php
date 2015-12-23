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

    /**
     * @param string $server
     * @url stats/([0-9]+)
     * @url stats
     * @return string
     */
    public function getStats($server = '1'){
        return $this->getServerStats($server);
    }

}