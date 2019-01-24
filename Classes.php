<?php
/* This page is essentially a wrapper around templates/AA.tpl, which contains
 * the actual HTML.  The code here is to handle events when the forms in AA.tpl
 * are POSTed back to this page.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'database.php';
session_start();

/*
print_r($_POST);
print_r('<br/>');
print_r($_SESSION);
*/

/* If "AUTHUSER" isn't set, redirect to auth.php where the
	 user will be able to autheniticate */
if(!isset($_SESSION["AUTHUSER"])){
	header('Location: auth.php');
}

/* If this page was accessed in order to create a new table */
if(isset($_POST["create"])){
	CreateTable($_POST["NewName"],substr($_POST["histsel"],1));
}

/* "Results" is POSTed from templates/AA.tpl, which is included in this file */
/* If "Results" was posted as equal to R: */
if(isset($_POST["Results"]) && $_POST["Results"]=="R"){
	/* Unset "tables" from the SESSION and re-set it according
		 to the POST data: */
	unset($_SESSION["tables"]);
	if( isset($_POST["tselect"]) && $_POST["tselect"]!="" ){
		$_SESSION["tables"]=$_POST["tselect"];
	}elseif( isset($_POST["Eselect"]) && $_POST["Eselect"]!="" ){
		for($i=0;$i<count($_POST["Eselect"]);$i++){
			$tables=GetTables($_POST["Eselect"][$i]);
				for($j=0;$j<count($tables);$j++){
					$_SESSION["tables"][]=$tables[$j]["id"];
				}
		}
	}
	/* If the preceeding successfully reset "tables" to the SESSION,
		 set "comb"=1 and redirect to results.php */
	if(isset($_SESSION["tables"])){
		$_SESSION["comb"]=1;
		header("Location: results.php");
	}
}

if(isset($_POST["delete"]) && $_POST["delete"]=="d"){
	if(isset($_POST["tselect"]) && $_POST["tselect"]!=""){
		foreach($_POST["tselect"] as $t){
			DeleteTable($t);
		}
	}elseif(isset($_POST["Eselect"]) && $_POST["Eselect"]!=""){
		foreach($_POST["Eselect"] as $t){
			DeleteMClassEvent($t);
		}
	}

}
/*
	if(strcmp(substr($_POST["tselect"],-8,1),"n")==0){
		$act=1;
	}else{
		$act=0;
	}
	$tname=str_replace(" (active)","",$_POST["tselect"]);
	$tname=str_replace(" (inactive)","",$tname);*/


$MCE=GetMCEvents();

if(isset($_POST["changeA"]) && $_POST["changeA"]=="cA"){
	for($i=0;$i<count($MCE);$i++){
		for($j=0;$j<count($_POST["Eselect"]);$j++){
			if($_POST["Eselect"][$j]==$MCE[$i]["id"]){
				if($MCE[$i]["active"]==0){
					SetActivation($MCE[$i]["id"],1);
					$MCE[$i]["active"]=1;
				}else{
					SetActivation($MCE[$i]["id"],0);
					$MCE[$i]["active"]=0;

				}
			}
		}
	}
}

/* TODO: Sort this out */
/* 'chist' is set in AA.tpl when creating an Event.
 * There is no code in CIMA that sets $_POST["HistName"].
 * Also, database.php's CreateHist() doesn't take an argument.
 * Pretty sure this isn't right, but it was like this when I got here
 *	  - JG Jan2019
 */
if(isset($_POST["chist"])){
	CreateHist($_POST["HistName"]);
}

$freetables=GetIndTables();

include 'templates/header.tpl';
include 'templates/AA.tpl';
//$_SESSION["currentT"]=$tables;

include 'templates/floor.tpl';

?>
