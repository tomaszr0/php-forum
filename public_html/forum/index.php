<?php
error_reporting(0);
require_once("../../config.php");
$db = @mysqli_connect(db_server,db_user,db_password) or die("Database connection error.");
$db->query("set names 'utf8';"); 
require_once("functions.php");
$db->select_db(db_database) or die("Database selection error.");
//createDb();
session_start();
if(isset($_SESSION["forum_id"])){
    if($_SESSION["forum_id"]!=""){
        $id=$_SESSION["forum_id"];
        set("update forum_users set date_online=current_timestamp where id=$id");
        $perm=get("select permission from forum_users where id=$id")[0]["permission"];
    }
}
else{
    $id=0;$perm=0;
}



require_once("post.php");
require_once("data.php");
require_once("structure.php");
?>