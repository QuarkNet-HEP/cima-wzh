<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "database.php";

session_start();

/*
print_r('<br/>');
print_r('<br/>');
print_r($_POST);
print_r('<br/>');
print_r($_SESSION);
print_r('<br/>');
*/
/*
print_r('<br/>');
print_r($_POST["finalState"]);
print_r('<br/>');
print_r($_POST["primaryState"]);
print_r('<br/>');
print_r($_POST["massEntry"]);
print_r('<br/>');
print_r('<br/>');
*/

/* We'll need a Location table "database" and a dataset "groupNo" set
 * to the SESSION.  If they aren't, redirect to index.php where these are
 * selected. */
if(!isset($_SESSION["database"]) || !isset($_SESSION["groupNo"])){
	header("Location: index.php");
}

/* "fin" and "CustomEvent" are set on submission of the table.tpl form.
 * If they're present, then we want to write the just-POSTed values to
 * the DB.  "CustomEvent" is the dataset number [1-100] of the submitted event,
 * what's shown in the "Event index" drop-down menu.
 */
if(isset($_POST["fin"]) && $_POST["CustomEvent"]!=""){
	/* New version (WZH upgrades Nov 2018) */
	$fState=$_POST["finalState"];
	$pState=$_POST["primaryState"];

	/* Set the particle mass if it was POSTed and isn't nonsense: */
	if( !isset($_POST["massEntry"]) || !is_numeric($_POST["massEntry"]) ){
		$mass='NULL';
	} else {
		$mass=$_POST["massEntry"];
	}

	/* Convert dataset number to unique event id for storage */
	$ds_no = (string) $_POST["CustomEvent"];
	$ds_no = str_pad($ds_no, 3, "0", STR_PAD_LEFT);
	$ds = (string) $_SESSION["groupNo"];
	$ds_index = $ds."-".$ds_no;
	$unique_id = indexToId($ds_index);

	/*
	print_r('<br>');
	print_r('ds_no: '.((string) $ds_no));
	print_r('<br>');
	print_r('ds: '.((string) $ds));
	print_r('<br>');
	print_r('unique_id: '.((string) $unique_id));
	print_r('<br>');
	*/
	//WriteRow($_SESSION["database"],$_POST["CustomEvent"],$fState,$pState,$mass);
	WriteRow($_SESSION["database"],$unique_id,$fState,$pState,$mass);
}

if(isset($_POST["fedit"])&&$_POST["fedit"]!=""){
	unset($_SESSION["edit"]);
}

/* $_SESSION["groupNo"] is the dataset.
 * $_SESSION["database"] is the Location table.
 */
$arr=getEventsTableRows($_SESSION["groupNo"],$_SESSION["database"]);

/* getUncompletedEventsIds() returns unique id's for events in the given
 * $dataset = $_SESSION["groupNo"] but not listed in the given Location
 * table $_SESSION["database"] */
//$freeEvents=GetFreeEvents($_SESSION["groupNo"],$_SESSION["database"]);
$freeEvents=getUncompletedEventsIds($_SESSION["groupNo"],$_SESSION["database"]);

/* If there are no more freeEvents, and if we're not editing already-existing
	 events, then redirect to finish.php */
if(!isset($freeEvents) && !isset($_SESSION["edit"])){
	header("Location: finish.php");
}

/* "CustomEvent" is the name of the drop-down event selector in the data entry
 * panel.  It submits as POST every time an event is selected.  */
/*
print_r('<br>');
print_r('$_POST["CustomEvent"]: ');
print_r('<br>');
print_r($_POST["CustomEvent"]);
print_r('<br>');
print_r('$_SESSION["current"]: ');
print_r('<br>');
print_r($_SESSION["current"]);
print_r('<br>');
print_r('$_SESSION["current"]["id"]: ');
print_r('<br>');
print_r($_SESSION["current"]["id"]);
print_r('<br>');
print_r('$_POST["fin"]: ');
print_r('<br>');
print_r($_POST["fin"]);
*/
if(isset($_POST["CustomEvent"]) && isset($_SESSION["current"]) && $_SESSION["current"]["id"]!=$_POST["CustomEvent"] && !isset($_POST["fin"])){
	//print_r("Accessing GetEvent");
	$event=GetEvent($_POST["CustomEvent"]);
}else{
	//print_r("Accessing GetNext");
	//$event=GetNext($arr,$_SESSION["groupNo"]);
	$event=getNextUncompletedEvent($arr,$_SESSION["groupNo"]);
}


/* A function to convert 'final_state' values as stored in Location tables
 * and originating as 'value' attributes in templates/table.tpl in HTML for
 * display.
 */
function htmlFilter($value) {
	$map = array(
		"e_nu" => "e&nu;",
		"mu_nu" => "&mu;&nu;",
		"mu_mu" => "&mu;&mu;",
		"4mu" => "4&mu;",
		"2e_2mu" => "2e 2&mu;",
		"2gam" => "2&gamma;",
		"W_pm" => "W&#177;"
	);

	if (array_key_exists($value, $map)) {
		return $map[$value];
	} else {
		return $value;
	}
}

include 'templates/header.tpl';
if(isset($event)){
	$_SESSION["current"]=$event;
}

$script=0;
include 'templates/navbar.tpl';

/* table.tpl is the particle state input panel */
include 'templates/table.tpl';

$tableHeaders = ["Event index","Event number","Final state","Primary state","Mass",""];
//$tableRow = GetEventTableRows($_SESSION["groupNo"],$_SESSION["database"]);
$tableRow = getEventsTableRows($_SESSION["groupNo"],$_SESSION["database"]);

/* The Events Table */
echo '<div class=row>
	<div class=col-md-2></div> <!-- Left padder column -->
	<div class=col-md-8>
		<div class=container-fluid style="border:1pt solid black; padding:10pt;">
			<div class=row style="padding-right: 3%;">';
				foreach($tableHeaders as $i => $header){
					echo '<div class=col-md-2>
						<strong>'.$header.'</strong>
					</div>';
				}
			echo '</div>
			<div class=container-fluid style="overflow-y: scroll; height: 60%;">';

				for($i=(count($tableRow)-1);$i>=0;$i--){
					echo '<div class=row id="'.$tableRow[$i]["event_id"].'"
							 			 style="cursor: pointer;"
										 onmouseover="showdel(this)"
										 onmouseout="nshowdel(this)"
										 onclick="del(this)">
						<!-- Row entries: -->
						<div class="col-md-2 event-id">'.$tableRow[$i]["event_id"].'</div>
						<div class="col-md-2 dg-id">'.$tableRow[$i]["dg_label"].'</div>
						<div class="col-md-2 final-state">'.htmlFilter($tableRow[$i]["final"]).'</div>
						<div class="col-md-2 primary-state">'.htmlFilter($tableRow[$i]["primary"]).'</div>
						<div class=col-md-2>'.$tableRow[$i]["mass"].'</div>
						<div class=col-md-2 align="right" 
								 id="del-'.$tableRow[$i]["event_id"].'"></div>
					</div> <!-- End of row -->';
				}
			echo '</div>
		</div>
	</div>
	<div class=col-md-2></div> <!-- Right padder column -->
</div>';

include 'templates/floor.tpl';

/* TODO: I don't think this is necessary for WZH.  Try deleting it - JG 15Aug2019 */
$s=0;
for($i=0;$i<count($arr);$i++){
	//$s.=$arr[$i]["id"].":".$arr[$i]['mass'].";";
	$s.=$arr[$i]["event_id"].":".$arr[$i]['mass'].";";
}

/*
print_r('<br/>');
print_r('<br/>');
print_r("End of File");
print_r('<br/>');
print_r($_POST);
print_r('<br/>');
print_r($_SESSION);
*/

?>
<!--<script> var massGlobal= '<?php echo $s ?>';</script>;-->

