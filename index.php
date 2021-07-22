<?php

use models\User;
use models\Collateral;
use models\Admin;
use models\Field;
use controller\Encrypt;

require_once "models/Admin.php";
require_once "models/Field.php";
require_once "models/User.php";
require_once "models/Collateral.php";
require_once 'controller/Encrypt.php';

ini_set("log_errors", 1);
ini_set("error_log", "/tmp/php-error.log");
//error_log( "Hello, errors!" );
function getRandomWord($len = 10) {
    $word = array_merge(range('a', 'z'), range('A', 'Z'));
    shuffle($word);
    return substr(implode($word), 0, $len);
}
function logP($string){
    $log = fopen("print.print","a");
    fwrite($log,$string."\n");
    fclose($log);
}

 $url = $_SERVER['REQUEST_URI'];

 switch ($url){
     case "/":
         header("Cache-Control: public");
         header("Content-Type: text/html");
         session_start();
         $microTime = microtime();
         $rand = mt_rand(10,10000);;
         $pkrystoken= password_hash("@9jdokshf0(0h%pdhfoo"."$microTime"."$rand"
             ,PASSWORD_BCRYPT,['cost'=>12]);
         $_SESSION['csrfijirToken'] = $pkrystoken;
         $randomWord = getRandomWord(8);
         $_SESSION['csrfijirTag'] = $randomWord;
         $log = fopen("log.log","a");
         fwrite($log,"Request Sender: ".$_SERVER['REQUEST_URI'].", Token: ". $pkrystoken."\n");
         fclose($log);
         $html= file_get_contents("crweb/views/index.html");
         $html = str_replace("jfpkskkf","<$randomWord style='display:none'>$pkrystoken</$randomWord>",$html
             );
         echo $html;
         break;

     case "/userman":  header("Cache-Control: public");
         header("Content-Type: text/html");
         session_start();
         $microTime = microtime();
         $rand = mt_rand(10,10000);;
         $pkrystoken= password_hash("@9jdokshf0(0h%pdhfoo"."$microTime"."$rand"
             ,PASSWORD_BCRYPT,['cost'=>12]);
         $_SESSION['csrfijirToken'] = $pkrystoken;
         $randomWord = getRandomWord(8);
         $_SESSION['csrfijirTag'] = $randomWord;
         $log = fopen("log.log","a");
         fwrite($log,"Request Sender: ".$_SERVER['REQUEST_URI'].", Token: ". $pkrystoken."\n");
         fclose($log);
         $html= file_get_contents("crweb/views/userman.html");
         $html = str_replace("jfpkskkf","<$randomWord style='display:none'>$pkrystoken</$randomWord>",$html
         );
         echo $html;
         break;


     case "/upload":{
         try {
             $targetDir = "files/";
             $target_file = $targetDir .uniqid("collateral",  false).".pdf";
             $uploadFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
             $file_type=$_FILES['colla']['type'];
             if ($file_type=="application/pdf")
             {
                 if(move_uploaded_file($_FILES['colla']['tmp_name'], $target_file))
                 {
                     echo json_encode(["status" => 200, "message" => "Okay", "file_name" => $target_file]);
                 }
                 else {
                     echo json_encode(["status" => 500, "message" => "Error Uploading File"]);
                 }
             }
             else {
                 echo json_encode(["status" => 500, "message" => "Error Uploading File"]);
             }
         } catch (Exception $e) {
             $msg = $e->getMessage();
             echo json_encode(["status" => 500, "message" => "$msg"]);
         }
         break;
     }
     case "/core":
         {
             if($_SERVER['HTTP_ORIGIN'] == "https://collaterals.ammilmfi.com"){
                 header("Cache-Control: public");
                 header("Content-Type: application/json;charset=utf-8");

                 switch($_POST['type']){
                     case "login":{
                         session_start();
                         logP($_SESSION['csrfijirTag']);
                         $content = \controller\Encrypt::encrypt($_SESSION['csrfijirToken'],utf8_decode($_POST['content']));
                         $decoded =[];
                         try{
                             $decoded  = json_decode($content,true);
                         }
                         catch (Exception $e){
                             $msg =$e->getMessage();
                             echo json_encode(["status"=>500,"message"=>"$msg"]);
                             break;
                         }

                         if(array_key_exists($_SESSION['csrfijirTag'], $decoded)){
                             if($decoded[$_SESSION['csrfijirTag']] === $_SESSION['csrfijirToken']){
                                 try{
                                     $content = $decoded;

                                     $user = User::fromDatabase($content['username'],$content['password']);
                                     $microTime = microtime();
                                     $rand = mt_rand(10,10000);;
                                     $secureKey = password_hash($user->getUserName()."$microTime"."$rand"
                                         ,PASSWORD_BCRYPT,['cost'=>12]);
                                     $_SESSION[$secureKey] = $user;
                                     echo json_encode(["status"=>200,"message"=>"login successful","sk"=> $secureKey]);
                                 }
                                 catch (Exception $e){
                                     $msg =$e->getMessage();
                                     error_log( $msg );
                                     echo json_encode(["status"=>500,"message"=>"$msg"]);
                                 }
                             }
                         }
                         break;
                     }

                     case "createCollateral":{
                         $decoded =json_decode($_POST['content'],true);
                                 try {
                                     $ctrr = Collateral::fromJson($decoded);
                                     $ctrr->persist();
                                     $ctrr->flush();
                                     echo json_encode(["status" => 200, "message" => "okay"]);
                                 }
                                 catch (Exception $e) {
                                     $msg = $e->getMessage();
                                     echo json_encode(["status" => 500, "message" => "$msg"]);
                                 }
                                 break;
                     }

                     case "getCollaterals":{
                         session_start();
                         $decoded = json_decode($_POST['content'],true);
                         if(array_key_exists($decoded['sk'], $_SESSION)){
                             try{
                                 $collaterals = Collateral::getAllCollaterals();
                                 $collaterals['status'] = 200;
                                 echo json_encode($collaterals);
                             }
                             catch (Exception $e){
                                 $msg =$e->getMessage();
                                 echo json_encode(["status"=>500,"message"=>"$msg"]);
                             }
                             break;
                         }
                     }
                     case "retrieveUser":{
                         try{
                             session_start();
                             $content = json_decode($_POST['content'],true);
                             //error_reporting(0);
                             if(array_key_exists($content['sk'], $_SESSION)){
                                 $user = $_SESSION[$content['sk']];
                                 echo json_encode(["status"=>200,"message"=>"","content"=> json_encode($user)]);
                             }
                             else{
                                 echo json_encode(["status"=>500,"message"=>"access denied"]);
                             }
                         }
                         catch(Exception $e){
                             $msg =$e->getMessage();
                             echo json_encode(["status"=>500,"message"=>"$msg"]);

                         }
                         break;
                     }

                     case "default":{
                         try{

                         }
                         catch(Exception $e){

                         }
                     }
                 }
                 break;
             }
         }

     case "/usermanager":
         {
             if(array_key_exists('HTTP_ORIGIN',$_SERVER)){
                 if($_SERVER['HTTP_ORIGIN'] == "https://collaterals.ammilmfi.com"){
                     header("Cache-Control: public");
                     header("Content-Type: application/json;charset=utf-8");
                     switch($_POST['type']){
                         case "register":{
                             session_start();
                             logP($_SESSION['csrfijirTag']);
                             $content = Encrypt::encrypt($_SESSION['csrfijirToken'],utf8_decode($_POST['content']));
                             $decoded =[];
                             try{
                                 $decoded  = json_decode($content,true);
                             }
                             catch (Exception $e){
                                 $msg =$e->getMessage();
                                 echo json_encode(["status"=>500,"message"=>"$msg"]);
                                 break;
                             }

                             if(array_key_exists($_SESSION['csrfijirTag'], $decoded)){
                                 if($decoded[$_SESSION['csrfijirTag']] === $_SESSION['csrfijirToken']){
                                     try{
                                         $admin = Admin::fromJson($decoded);
                                         $admin->persist();
                                         $admin->flush();
                                         echo json_encode(["status"=>200,"message"=>"okay"]);
                                     }
                                     catch (Exception $e){
                                         $msg =$e->getMessage();
                                         echo json_encode(["status"=>500,"message"=>"$msg"]);
                                     }

                                     break;

                                 }

                             }
                         }
                         case "checkUsername":{
                             session_start();
                             logP($_SESSION['csrfijirTag']);
                             $content = \controller\Encrypt::encrypt($_SESSION['csrfijirToken'],utf8_decode($_POST['content']));
                             $decoded =[];
                             try{
                                 $decoded  = json_decode($content,true);
                             }
                             catch (Exception $e){
                                 $msg =$e->getMessage();
                                 echo json_encode(["status"=>500,"message"=>"$msg"]);
                                 break;
                             }

                             if(array_key_exists($_SESSION['csrfijirTag'], $decoded)){
                                 if($decoded[$_SESSION['csrfijirTag']] === $_SESSION['csrfijirToken']){
                                     try{

                                         if(User::isExists(new Field('username',$decoded['username']))){
                                             echo json_encode(["status"=>200,"message"=>"username exists"]);
                                         }
                                         else{

                                             echo json_encode(["status"=>200,"message"=>"okay"]);
                                         }
                                     }
                                     catch (Exception $e){
                                         $msg =$e->getMessage();
                                         echo json_encode(["status"=>500,"message"=>"$msg"]);
                                     }
                                     break;

                                 }

                             }
                         }
                         case "checkUsername2":{
                             session_start();
                             logP($_SESSION['csrfijirTag']);
                             $content = \controller\Encrypt::encrypt($_SESSION['csrfijirToken'],utf8_decode($_POST['content']));
                             $decoded =[];
                             try{
                                 $decoded  = json_decode($content,true);
                             }
                             catch (Exception $e){
                                 $msg =$e->getMessage();
                                 echo json_encode(["status"=>500,"message"=>"$msg"]);
                                 break;
                             }

                             if(array_key_exists($_SESSION['csrfijirTag'], $decoded)){
                                 if($decoded[$_SESSION['csrfijirTag']] === $_SESSION['csrfijirToken']){
                                     try{

                                         if(User::isExists(new Field('username',$decoded['username']))){
                                             echo json_encode(["status"=>500,"message"=>"username exists"]);
                                         }
                                         else{

                                             echo json_encode(["status"=>200,"message"=>"okay"]);
                                         }
                                     }
                                     catch (Exception $e){
                                         $msg =$e->getMessage();
                                         echo json_encode(["status"=>500,"message"=>"$msg"]);
                                     }
                                     break;

                                 }

                             }
                         }
                         case "login":{
                             session_start();
                             logP($_SESSION['csrfijirTag']);
                             $content = \controller\Encrypt::encrypt($_SESSION['csrfijirToken'],utf8_decode($_POST['content']));
                             $decoded =[];
                             try{
                                 $decoded  = json_decode($content,true);
                             }
                             catch (Exception $e){
                                 $msg =$e->getMessage();
                                 echo json_encode(["status"=>500,"message"=>"$msg"]);
                                 break;
                             }

                             if(array_key_exists($_SESSION['csrfijirTag'], $decoded)){
                                 if($decoded[$_SESSION['csrfijirTag']] === $_SESSION['csrfijirToken']){
                                     try{
                                         $content = $decoded;
                                         $user = Admin::fromDatabase($content['username'],$content['password']);
                                         $microTime = microtime();
                                         $rand = mt_rand(10,10000);;
                                         $secureKey = password_hash($user->getUserName()."$microTime"."$rand"
                                             ,PASSWORD_BCRYPT,['cost'=>12]);
                                         $_SESSION[$secureKey] = $user;
                                         echo json_encode(["status"=>200,"message"=>"login successful","sk"=> $secureKey]);
                                     }
                                     catch (Exception $e){
                                         $msg =$e->getMessage();
                                         echo json_encode(["status"=>500,"message"=>"$msg"]);
                                     }
                                 }
                             }
                             break;
                         }
                         case "retrieveUser":{
                             try{
                                 session_start();
                                 $content = json_decode($_POST['content'],true);

                                 if(Admin::isEmpty()){$log = fopen("log.log","a");
                                     fwrite($log,"".Admin::isEmpty());
                                     fclose($log);
                                     echo json_encode(["status"=>1000,"message"=>"No Admin Found"]);
                                     break;
                                 }
                                 //error_reporting(0);
                                 if(array_key_exists($content['sk'], $_SESSION)){
                                     $user = $_SESSION[$content['sk']];
                                     echo json_encode(["status"=>200,"message"=>"","content"=> json_encode($user)]);
                                 }
                                 else{
                                     echo json_encode(["status"=>500,"message"=>"access denied"]);
                                 }
                             }
                             catch(Exception $e){
                                 $msg =$e->getMessage();
                                 echo json_encode(["status"=>500,"message"=>"$msg"]);

                             }
                             break;
                         }

                         case "createUser":{
                             session_start();
                             logP($_SESSION['csrfijirTag']);
                             $content = Encrypt::encrypt($_SESSION['csrfijirToken'],utf8_decode($_POST['content']));
                             $decoded =[];
                             try{
                                 $decoded  = json_decode($content,true);
                             }
                             catch (Exception $e){
                                 $msg =$e->getMessage();
                                 echo json_encode(["status"=>500,"message"=>"$msg"]);
                                 break;
                             }

                             if(array_key_exists($_SESSION['csrfijirTag'], $decoded)) {
                                 if ($decoded[$_SESSION['csrfijirTag']] === $_SESSION['csrfijirToken']) {
                                     try {
                                         $user = User::fromJson($decoded);
                                         $user->persist();
                                         $user->flush();
                                         echo json_encode(["status" => 200, "message" => "okay"]);
                                     } catch (Exception $e) {
                                         $msg = $e->getMessage();
                                         echo json_encode(["status" => 500, "message" => "$msg"]);
                                     }
                                     break;
                                 }
                             }

                         }
                         case "getUsers":{
                             session_start();
                             $decoded = json_decode($_POST['content'],true);

                             if(array_key_exists($decoded['sk'], $_SESSION)){
                                 try{
                                     $users = User::getAllUsers();
                                     $users['status'] = 200;
                                     echo json_encode($users);
                                 }
                                 catch (Exception $e){
                                     $msg =$e->getMessage();
                                     echo json_encode(["status"=>500,"message"=>"$msg"]);
                                 }
                                 break;
                             }
                         }
                         case "delUser":{
                             session_start();
                             $decoded = json_decode($_POST['content'],true);

                             if(array_key_exists($decoded['sk'], $_SESSION)){
                                 try{
                                     User::deleteUser($decoded['username']);
                                 }
                                 catch (Exception $e){
                                     $msg =$e->getMessage();
                                     echo json_encode(["status"=>500,"message"=>"$msg"]);
                                 }
                                 break;
                             }
                         }

                         case "appUser":{
                             session_start();
                             $decoded = json_decode($_POST['content'],true);

                             if(array_key_exists($decoded['sk'], $_SESSION)){
                                 try{
                                     User::setUserAccess($decoded['username'],$decoded['access']);
                                 }
                                 catch (Exception $e){
                                     $msg =$e->getMessage();
                                     echo json_encode(["status"=>500,"message"=>"$msg"]);
                                 }
                                 break;

                             }
                         }

                         case "changeUserPass":{
                             session_start();
                             $decoded = json_decode($_POST['content'],true);

                             if(array_key_exists($decoded['sk'], $_SESSION)){
                                 try{
                                     User::changePass($decoded['username'],$decoded['password']);
                                 }
                                 catch (Exception $e){
                                     $msg =$e->getMessage();
                                     echo json_encode(["status"=>500,"message"=>"$msg"]);
                                 }
                                 break;

                             }
                         }

                         case "default":{
                             try{

                             }
                             catch(Exception $e){

                             }
                         }
                     }
                     break;
                 }
             }
             break;
         }

     case "/dashboard":
         header("Cache-Control: public");
         header("Content-Type: text/html");
         header("Content-Type: text/html");
         readfile("crweb/views/webapp.html");
         break;
     //Resources
     case "/lib.js":
         header("Cache-Control: public");
         header("Content-Type: text/javascript");
         readfile("crweb/lib/awc.js");
         break;
     case "/main.js":
         header("Cache-Control: public");
         header("Content-Type: text/javascript");
         readfile("crweb/views/index.js");
         break;
     case "/webapp.js":
         header("Cache-Control: public");
         header("Content-Type: text/javascript");
         readfile("crweb/lib/webapp.js");
         break;
     case "/manager.js":
         header("Cache-Control: public");
         header("Content-Type: text/javascript");
         readfile("crweb/lib/userman.js");
         break;
     case "/icons.js":
         header("Cache-Control: public");
         header("Content-Type: text/javascript");
         session_start();
         if(array_key_exists('csrfijirToken', $_SESSION)){
             $html= file_get_contents("crweb/views/icons.js");
             $html = str_replace("ifjsgisjf",$_SESSION['csrfijirTag'],$html);
             echo $html;
         }
         break;
     case "/font-awesome.css":
         header("Cache-Control: public");
         header("Content-Type: text/css");
         readfile("crweb/css/font-awesome.css");
         break;
     case "/icons2.js":
         header("Cache-Control: public");
         header("Content-Type: text/javascript");
         session_start();
         if(array_key_exists('csrfijirToken', $_SESSION)){
             $html= file_get_contents("crweb/views/icons2.js");
             $html = str_replace("ifjsgisjf",$_SESSION['csrfijirTag'],$html);
             echo $html;
         }
         break;
     case "/getCharts.js":
         header("Cache-Control: public"); // needed for internet explorer
         header("Content-Type: text/javascript");
         readfile("assets/js/chart.min.js");
         break;
         //Fonts
     case "/fgwo.woff":
         header("Cache-Control: public");
         header("Content-Type: font/woff");
         readfile("crweb/views/fonts/fgwo.woff");
         break;
     case "/frankgo.woff":
         header("Cache-Control: public");
         header("Content-Type: font/woff");
         readfile("crweb/views/fonts/frankgo.woff");
         break;
     case "/fgc.woff":
         header("Cache-Control: public");
         header("Content-Type: font/woff");
         readfile("crweb/views/fonts/fgc.woff");
         break;
     case "/fg.woff":
         header("Cache-Control: public");
         header("Content-Type: font/woff");
         readfile("crweb/views/fonts/fg.woff");
         break;
     case "/fgi.woff":
         header("Cache-Control: public");
         header("Content-Type: font/woff");
         readfile("crweb/views/fonts/fgi.woff");
         break;
         //Images
     case "/getLogo":
         header("Cache-Control: public"); // needed for internet explorer
         header("Content-Type: image/jpeg");
         header("Content-Transfer-Encoding: Binary");
         header("Content-Length:".filesize("assets/images/logo.jpg"));
         header("Content-Disposition: attachment; filename=logo.jpg");
         readfile("assets/images/logo.jpg");
         break;
     case "/getBackground.jpg":
         header("Cache-Control: public"); // needed for internet explorer
         header("Content-Type: image/jpg");
         header("Content-Transfer-Encoding: Binary");
         header("Content-Length:".filesize("assets/images/bg.jpg"));
         header("Content-Disposition: attachment; filename=bg.jpg");
         readfile("assets/images/bg.jpg");
         break;
     case "/getBackground2.jpg":
         header("Cache-Control: public"); // needed for internet explorer
         header("Content-Type: image/jpg");
         header("Content-Transfer-Encoding: Binary");
         header("Content-Length:".filesize("assets/images/bg2.jpg"));
         header("Content-Disposition: attachment; filename=bg2.jpg");
         readfile("assets/images/bg2.jpg");
         break;
         case "/getBackground3.jpg":
         header("Cache-Control: public"); // needed for internet explorer
         header("Content-Type: image/jpg");
         header("Content-Transfer-Encoding: Binary");
         header("Content-Length:".filesize("assets/images/lock.jpg"));
         header("Content-Disposition: attachment; filename=lock.jpg");
         readfile("assets/images/lock.jpg");
         break;
     case "/getWebAppBG":
         header("Cache-Control: public"); // needed for internet explorer
         header("Content-Type: image/jpg");
         header("Content-Transfer-Encoding: Binary");
         header("Content-Length:".filesize("assets/images/webappbg.png"));
         header("Content-Disposition: attachment; filename=webappbg.png");
         readfile("assets/images/webappbg.png");
         break;
     case "/get1stImage":
         header("Cache-Control: public"); // needed for internet explorer
         header("Content-Type: image/png");
         header("Content-Transfer-Encoding: Binary");
         header("Content-Length:".filesize("assets/images/banner1.png"));
         header("Content-Disposition: attachment; filename=p1.png");
         readfile("assets/images/banner1.png");
         break;

     default:
         if( substr($url, 0, 6) == "/files"){
             header("Cache-Control: public");
             header("Content-Type: application/pdf");
             error_log($url);
             readfile(substr($url,1));
             break;
         }
         readfile("crweb/views/404.html");
         break;

 }