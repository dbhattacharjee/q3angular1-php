<?php
header('Access-Control-Allow-Origin: *');
header( 'Access-Control-Allow-Headers: Authorization, Content-Type' );
error_reporting(1);
ini_set('display_errors', 1);

define("BASE_DIR", __DIR__);

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


//$connection = pop3_login(MAIL_HOST,MAIL_PORT,'dbhattacharjee@q3tech.com','',$folder="INBOX",MAIL_SSL);
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
use PhpImap\Mailbox as ImapMailbox;
use PhpImap\IncomingMail;
use PhpImap\IncomingMailAttachment;

//$mailbox = new PhpImap\Mailbox('{'.MAIL_HOST.':'.MAIL_PORT.'/pop3/novalidate-cert}INBOX', 'dbhattacharjee@q3tech.com', '', __DIR__.'/attachments');
//
//echo '<pre>';print_r($mailbox->statusMailbox(array(1)));
////die;
//////
////$mailsIds = $mailbox->searchMailbox('UNSEEN');
//echo '<pre>';
////print_r($mailsIds);
//
////$mailId = reset($mailsIds);
//$mail = $mailbox->getMail(1, true);
//$mailbox->setFlag(array(1), '\\Seen');
//echo '<pre>';
//print_r($mail);
//print_r($mail->getAttachments());
////die;
////echo '<pre>';
////print_r($mailsIds);
//die;



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
