<?php
error_reporting(1);
ini_set('display_errors', 1);

//hacky hacky !
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST)) {
    $_POST = json_decode(file_get_contents('php://input'), true);
}

include 'config/config.php';
include 'vendor/autoload.php';
include 'controller/Authenticate.php';
include 'controller/Mails.php';
include 'vendor/eden/core/src/Control.php';


use RestService\Server;

//
//$pop3 = eden('mail')->pop3(
//    'mail.q3tech.com', 
//    'dbhattacharjee@q3tech.com', 
//    '2{d^iF$MJu7y', 
//    110, 
//    false);
//echo '<pre>';
//$total = $pop3->getEmailTotal();
//if($total) {
//    $emails = $pop3->getEmails(0, 10);
//    usort($emails, function($a, $b) {
//                return $a['date'] - $b['date'];
//            });
//    foreach($emails as $key=>$email) {
//        echo $key.PHP_EOL;
//        print_r($email);
//    }
//}
//
//die('here');




try {
Server::create('/api', new Angular\Authenticate)
     ->addSubController('/mail', new Angular\Mails)   
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
