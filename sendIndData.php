<?php
session_start();
include "database.php";

$_SESSION["groupNo"]=$_POST["SG"];
$t=GetTableByID($_POST["ST"]);
$_SESSION["database"]=$t["name"];
/* Added Oct2018 to accommodate Location prefix: */
$_SESSION["displayLocation"]=$t["displayName"];
$_SESSION["databaseId"]=$t["id"];

$t=GetMClassEvent($_POST["SE"]);
$_SESSION["MasterClass"]=$t["name"];
$_SESSION["MasterClassId"]=$t["id"];
if(isbackup($_SESSION["databaseId"],$_SESSION["groupNo"])){
	$_SESSION["backup"]=1;
}


?>
