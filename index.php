<?php
error_reporting(1);
ini_set('display_errors', 1);

//hacky hacky !
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST)) {
    $_POST = json_decode(file_get_contents('php://input'), true);
}

include 'config/config.php';
include 'library/pop3.php';
include 'library/utility.php';
include 'vendor/autoload.php';
include 'controller/Authenticate.php';
include 'controller/Mails.php';

//create database connection
try{
$glob_conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USERNAME, DB_PASSWORD);
// set the PDO error mode to exception
$glob_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}  catch (\Exception $e) {
    die("Connection failed: " . $e->getMessage());
}


//$connection = pop3_login(MAIL_HOST,MAIL_PORT,'dbhattacharjee@q3tech.com','2{d^iF$MJu7y',$folder="INBOX",MAIL_SSL);
//
//print_r($connection);die;
//
//$stat = pop3_stat($connection);
////$list = pop3_list($connection);
//echo '<pre>Connection : '.
//print_r($connection);
//echo '<br/>Stat:';
//print_r($stat);
//echo '<br/>List:';
//print_r($list);
//die;

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
