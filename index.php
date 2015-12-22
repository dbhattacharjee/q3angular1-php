<?php
error_reporting(1);
ini_set('display_errors', 1);

include 'vendor/autoload.php';
include 'controller/Authenticate.php';


use RestService\Server;

Server::create('/api', new Angular\Authenticate)
    ->collectRoutes()
    ->setDebugMode(true)    
->run();

//Server::create('/')
//    ->addGetRoute('test', function(){
//        return 'Yay!';
//    })
//    ->addGetRoute('foo/(.*)', function($bar){
//        return $bar;
//    })
//->run();
?>
