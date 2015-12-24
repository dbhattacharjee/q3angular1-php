<?php
error_reporting(1);
ini_set('display_errors', 1);

//hacky hacky !
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST)) {
    $_POST = json_decode(file_get_contents('php://input'), true);
}

include 'config/config.php';
include 'library/pop3.php';
include 'vendor/autoload.php';
include 'controller/Authenticate.php';
include 'controller/Mails.php';

$connection = pop3_login(MAIL_HOST,MAIL_PORT,'user@email.com','userpwd',$folder="INBOX",MAIL_SSL);

$stat = pop3_stat($connection);
$list = pop3_list($connection);
echo '<pre>Connection : '.
print_r($connection);
echo '<br/>Stat:';
print_r($stat);
echo '<br/>List:';
print_r($list);
die;

use RestService\Server;







try {
$s = Server::create('/api', new Angular\Authenticate)
     ->setDebugMode(true)   
    ->collectRoutes()
    ->setDebugMode(true)    
->run();

}catch(\Exception $e) {
    return json_encode(array('status'=>400, 'success'=>false, 'message'=>$e->getMessage()));
}
//Server::create('/')
//    ->addGetRoute('test', function(){
//        return 'Yay!';
//    })
//    ->addGetRoute('foo/(.*)', function($bar){
//        return $bar;
//    })
//->run();

?>
