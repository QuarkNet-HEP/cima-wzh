<?php
session_destroy();
session_start();

// Include the config file with administrator login info
//include "config/mc.config";
include "../../local-settings/mc.config";

// Get the login info from the config file
$login = getAuthConfig();

// User-entered login will be self-posted and processed here.
// If the entered data matches $login, identify an authorized user to
//   the Session and redirect to Classes.php
if( isset($_POST["submit"]) && $_POST["username"]===$login["admin_login"] && $_POST["password"]===$login["admin_pw"] ){
	$_SESSION["AUTHUSER"]=true;
	header('Location: Classes.php');	
}
	
include "templates/header.tpl";

// The HTML template for the login box:
include "templates/auth.tpl";

include "templates/floor.tpl";
?>
