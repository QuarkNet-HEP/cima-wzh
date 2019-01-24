<?php
session_destroy();
session_start();

include "config/mc.config";

$login = getAuthConfig();
if( isset($_POST["submit"]) && $_POST["username"]===$login["admin_login"] && $_POST["password"]===$login["admin_pw"] ){
	$_SESSION["AUTHUSER"]=true;
	header('Location: Classes.php');	
}
	
include "templates/header.tpl";
//print_r($_POST);
include "templates/auth.tpl";
include "templates/floor.tpl";
?>
