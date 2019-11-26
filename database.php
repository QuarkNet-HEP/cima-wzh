<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

//include "config/mc.config";
include "../../local-settings/mc.config";



function askdb($q){
	$login = getDBConfig();
	$con = mysqli_connect($login["db_host"], $login["db_login"], $login["db_pw"], $login["db_name"]);
	if (mysqli_connect_errno($con)){
		echo "Failed to connect to MySQL: " . mysqli_connect_error();
	}
	$res=$con->query($q);
	return $res;
}


/* Not a database function but I need somewhere to put it - JG 25Nov2019 */
/* Ends up not being used, I think.  Deletable - 26Nov2019 */
function makeIndices($blockArray) {

	$datagroup_indices = array();
	for($i=0; $i<count($blockArray); $i++) {
		$N = $blocks[$i];
		for($j=0; $j<$N; $j++) {
			$datagroup_indices[] = ((string) $N) . "." . ((string) ($j+1));
		}
	}
	return $datagroup_indices;

}


/* Added to handle converting the new dataset indexing for storage in the
 * `event_id` column of Location tables - JG 26Nov2019 */
function indexToId($index) {
	/* $index is expected to be of the form N.id-X where
	 *   N is the data block [5,10,25,50,100]
	 *	 id is the dataset [1,N]
	 *	 X is the individual event number [1,100]
	 */
	$parts = explode("-", $index);

	$q="SELECT id FROM Datasets WHERE dataset='".$parts[0]."'";
	$res=askdb($q);

	if($obj=$res->fetch_object()){
		$base=$obj->id;
	}

	$unique = ((int) $base)*100 + (int) $parts[1];

	return $unique;
}

function idToIndex($id) {

	// Convert to string
	$num = (string) $id
	$eventNo = substr($num,-3)
	// Trim leading zeroes
	$eventNo = ltrim($eventNo, '0');

	$base = substr($num,0,-3)
	print_r("num: ".$num);
	print_r("eventNo: ".$eventNo);
	print_r("base: ".$base);

	$q="SELECT dataset FROM Datasets WHERE id='".$base."'";
	$res=askdb($q);

	if($obj=$res->fetch_object()){
		$ds=$obj->dataset;
	}

	$index = $base."-".$eventNo;

	return $index;
}


/* Added 26Nov2019 for new indexing system */
/* Returns Datasets.id given Datasets.dataset as a string "X.Y" */
function getDatasetId($Nid) {

	$q="SELECT id FROM Datasets WHERE dataset='".$Nid."'";
	$res=askdb($q);

	if($obj=$res->fetch_object()){
		$dg_id=$obj->id;
	}

	return $dg_id;
}


/* Returns event_id values for $datagroup that are not already contained
	 in the given Location $location */
function GetFreeEvents($datagroup,$location){
	/* Location tables don't have a 'datagroup_id' column.  The WHERE clause in
		 the subquery doesn't throw an error, but what does it accomplish? */
	/*$q="SELECT event_id FROM Events WHERE datagroup_id=".$datagroup." AND NOT event_id IN (SELECT event_id FROM `".$location."` WHERE datagroup_id=".$datagroup.")";*/
	$q="SELECT event_id FROM Events WHERE datagroup_id=".$datagroup." AND NOT event_id IN (SELECT event_id FROM `".$location."`)";
	$res=askdb($q);
	while($obj=$res->fetch_object()){ 
		$result[]=$obj->event_id;
	}
	if(isset($result)){
		return $result;
	}
}


/* Once a Masterclass is created, associate one or more Location tables to it by
	 creating entries in 'EventTables'.  The Location tables must already exist and
	 be registered in 'Tables'. */
/* Inputs: $tables is a tableid value (or array of values) that should
 *	 				 match Tables.id.
 * 				 $eventID is a MclassEventID value that should match MclassEvents.id.
 */
function AddTablesToEvent($tables,$eventID){
	if(isset($tables) && isset($eventID)){
		if(!is_array($tables)){
			$q="INSERT INTO EventTables (tableid,MclassEventID) VALUES (".$tables.",".$eventID.")";
			askdb($q);
		}else{
			for($i=0;$i<count($tables);$i++){
				$q="INSERT INTO EventTables (tableid,MclassEventID) VALUES (".$tables[$i].",".$eventID.")";
				askdb($q);
			}
		}
	}
}


/* The reverse of the above.  De-associate a given Location table or tables
 * identified by $tables from the Masterclass identified by $eventID by deleting
 * the relevant entry in 'EventTables'. */
function RemoveTablesFromEvent($tables,$eventID){
	if(isset($tables) && is_array($tables) && isset($eventID)){
		for($i=0;$i<count($tables);$i++){
			$q="DELETE FROM EventTables WHERE tableid=".$tables[$i]." AND MclassEventID=".$eventID;
			askdb($q);
		}
	}
}


/* Get all events associated with a Location table $location */
function GetAllEvents($location){
	$q="SELECT * FROM `".$location."`";
	$res=askdb($q);
	while($obj=$res->fetch_object()){ 
			$temp["id"]=$obj->event_id;
			$temp["checked"]=$obj->checked;
			/* Before the Oct2018 upgrade, Location tables had only 'event_id'
			 * (as 'o_no') and 'checked' columns.  The following were added as
			 * part of the upgrade: */
			$temp["final"]=$obj->final_state;
			$temp["primary"]=$obj->primary_state;
			$temp["mass"]=$obj->mass;
			$result[]=$temp;
		}
	if(isset($result)){
		return $result;
	}
}


/* For each event assigned to a Location $location, return the event_id, the
	 Location 'checked' list, and the canonical mass */
/* Inputs: $datagroup is a datagroup number.
	 				 $location is a Location table in the Masterclass database. */
function GetEvents($datagroup,$location){
	$q="SELECT `".$location."`.event_id, `".$location."`.checked, Events.mass FROM `".$location."` INNER JOIN Events WHERE `".$location."`.event_id IN (SELECT event_id FROM Events WHERE datagroup_id=".$datagroup.") AND `".$location."`.event_id=Events.event_id ORDER BY `".$location."`.event_id";
	$res=askdb($q);
	while($obj=$res->fetch_object()){
			$temp["id"]=$obj->event_id;
			$temp["checked"]=$obj->checked;
			$temp["mass"]=$obj->mass;
			$result[]=$temp;
	}
	if(isset($result)){
		return $result;
	}
}


/* Added Oct2018 for CIMA updates, adapted from GetEvents() */
/* For each event assigned to a Location $location, return the event_id, the
	 datagroup_id, the datagroup_index, and the user-entered final state, primary
	 state, and mass. */
/* Inputs: $datagroup is a datagroup number.
 *	 			 $location is a Location table in the Masterclass database.
 */
function GetEventTableRows($datagroup,$location){

	$q="SELECT `".$location."`.event_id, Events.datagroup_id, Events.g_index, `".$location."`.final_state, `".$location."`.primary_state, `".$location."`.mass FROM `".$location."` INNER JOIN Events ON `".$location."`.event_id=Events.event_id WHERE `".$location."`.event_id IN (SELECT event_id FROM Events WHERE datagroup_id=".$datagroup.") ORDER BY `".$location."`.event_id";
	
	$res=askdb($q);
	while($obj=$res->fetch_object()){ 
		$temp["event_id"]=$obj->event_id;
		/* 'datagroup_id' and 'g_index' are in the table, but aren't used directly
				to create rows.  Uncomment these lines to make them available: */
		//$temp["dg_id"]=$obj->datagroup_id;
		//$temp["dg_index"]=$obj->g_index;
		$temp["dg_label"]=$obj->datagroup_id."-".$obj->g_index;
		$temp["final"]=$obj->final_state;
		$temp["primary"]=$obj->primary_state;
		$temp["mass"]=$obj->mass;
		$result[]=$temp;
	}
	if(isset($result)){
		return $result;
	}
}


function GetEvent($event_id){
	$q="SELECT * FROM Events WHERE event_id=".$event_id;
	$res=askdb($q);
	if($obj = $res->fetch_object()){
		$result["id"]=$obj->event_id;
		$result["g"]=$obj->datagroup_id;
		$result["mass"]=$obj->mass;
		/* 'g_index', 'ev_no' are also available in the query output */
	}else{
		print("error");
		return 0;
	}
	return $result;
}


function GetNext($finEvents,$dg_id){
	$k=0;
	$c=0;
	if(isset($finEvents) && is_array($finEvents) && (($dg_id-1)*100+1) == $finEvents[0]["id"]){
		for($i=$finEvents[0]["id"];$c<200;$i++){
			$k=$i;
			if(!array_key_exists(($i-$finEvents[0]["id"]),$finEvents)){
				break;
			}
			if($i<$finEvents[($i-$finEvents[0]["id"])]["id"]){
				break;
			}
		}
		$q="SELECT * from Events WHERE datagroup_id=".$dg_id." AND event_id=".$k;
	}else{
		$q="SELECT * from Events WHERE datagroup_id=".$dg_id." AND event_id=".((($dg_id-1)*100)+1);
	}
	$res=askdb($q);
	if($obj = $res->fetch_object()){
		$result["id"]=$obj->event_id;
		$result["g"]=$obj->datagroup_id;
		$result["mass"]=$obj->mass;
	}
	if(isset($result)){
		return $result;
	}
}


function WriteEntry($table,$event_id,$checked){
	$q="SELECT event_id FROM `".$table."` WHERE event_id=".$event_id;
	$res=askdb($q);
	if(!$res->fetch_object()){
		$q="INSERT INTO `".$table."` (event_id,checked) VALUES (".$event_id.",'".$checked."')";
		askdb($q);
	}
}


/* Added Oct2018 as expansion of WriteEntry() to handle new data format */
function WriteRow($location,$event_id,$finalState,$primaryState,$mass){

	/* Check to see if this event_id already has an entry in the Location table: */
	$q="SELECT event_id FROM `".$location."` WHERE event_id=".$event_id;
	$res=askdb($q);
	
	/* if $res is truthy, event_id already exists, and INSERT should fail */
	if(!$res->fetch_object()){
		$q="INSERT INTO `".$location."` (event_id,final_state,primary_state,mass) VALUES (".$event_id.",'".$finalState."','".$primaryState."',".$mass.")";
		askdb($q);
	}
}


/* Deletes the row identified by the given event_id from the given Location table */
function DelRow($id,$location){
	$q="DELETE FROM `".$location."` WHERE event_id=".$id;
	askdb($q);
}


function DeleteTable($tableid){
	$locPrefix = '_LOC_';

	$q="SELECT histogram_id,name FROM Tables WHERE id=".$tableid;
	$res=askdb($q);
	if($obj = $res->fetch_object()){
		$histid=$obj->histogram_id;
		$name=$obj->name;
	}

	$q="DROP TABLE `".$name."`";
	askdb($q);

	$q="DELETE FROM Tables WHERE id='".$tableid."'";
	askdb($q);
	
	$q="DELETE FROM TableGroups WHERE tableid=".$tableid;
	askdb($q);
	$q="DELETE FROM EventTables WHERE tableid=".$tableid;
	askdb($q);
	
	$q="DELETE FROM groupConnect WHERE tableid=".$tableid;
	askdb($q);

	$q="DELETE FROM histograms WHERE id=".$histid;
	askdb($q);
}


function DeleteMClassEvent($MClassid){
	$q="DELETE FROM MclassEvents WHERE id=".$MClassid;
	askdb($q);
	$q="DELETE FROM EventTables WHERE MclassEventID=".$MClassid;
	askdb($q);
}


/* As of Oct2018, this function appears to be used nowhere */
/* This function returns Tables.name; be aware that this value will
	 include the location prefix used for Location tables. */
function GetAllTables(){
	$q="SELECT * FROM Tables";
	$res=askdb($q);
	while($obj = $res->fetch_object()){ 
		$temp["hist"]=$obj->histogram_id;
		$temp["name"]=$obj->name;
		$temp["active"]=$obj->active;
		$result[]=$temp;
	}
	if(isset($result)){
		return $result;
	}
}


function SetActivation($id,$act){
	$q="UPDATE MclassEvents SET active=".$act." WHERE id='".$id."'";
	askdb($q);
}


/* Create an empty (all-zero) string of histogram data in the 'histograms' table */
function CreateHist(){

	/* Default number of bins for the different kinds of lists */
	/* These must match the implied number of bins in the calls to MakeHist()
		 in hist.php.
		 TODO: Find a way to link these logically at the next upgrade */
	/* The old (pre-WZH) kind */
	$numBins=68;
	/* WZH 2-lepton */
	$num2lBins=55;
	/* WZH 4-lepton */
	$num4lBins=65;

	/* Construct semicolon-separated strings of zeroes for each kind: */
	$zeroes="";
	for($i=0;$i<$numBins;$i++){
		$zeroes=$zeroes."0;";
	}
	/* Remove the last semicolon: */
	$zeroes=substr($zeroes,0,-1);

	$zeroes2l="";
	for($i=0;$i<$num2lBins;$i++){
		$zeroes2l=$zeroes2l."0;";
	}
	$zeroes2l=substr($zeroes2l,0,-1);

	$zeroes4l="";
	for($i=0;$i<$num4lBins;$i++){
		$zeroes4l=$zeroes4l."0;";
	}
	$zeroes4l=substr($zeroes4l,0,-1);

	/* 'histograms.id' is a PK that auto-increments on insertion of data */
	$q="INSERT INTO histograms (data,data_2l,data_4l) VALUES ('".$zeroes."','".$zeroes2l."','".$zeroes4l."')";
	askdb($q);
}


/* Insert a Group's table ID and assigned datagroups into the TableGroups
 * table, one row per assigned datagroup.
 * Inputs: $tableid is the Tables.id value that indexes the name of the
 *	 				 Group's table.
 * 				 $Groups is the (possible array) of datagroup ID's that will be
 *					 assigned to the Group.
 */
function AddGroupsToTable($tableid,$Groups,$PostAdded=0){
	if(isset($Groups) && isset($tableid)){
		if(is_array($Groups)){
			for($i=0;$i<count($Groups);$i++){

				$q="SELECT * FROM TableGroups WHERE tableid=".$tableid." AND datagroup_id=".$Groups[$i];
				$res=askdb($q);

				if(!$res->fetch_object()){

					$q="INSERT INTO TableGroups (datagroup_id,tableid,postAdded) VALUES (".$Groups[$i].", ".$tableid.", $PostAdded)";
					askdb($q);

				}
			}
		} else {
			/* If $Groups is not an array */

			$q="SELECT * FROM TableGroups WHERE tableid=".$tableid." AND datagroup_id=".$Groups;
			$res=askdb($q);

			if(!$res->fetch_object()){
				$q="INSERT INTO TableGroups (datagroup_id,tableid,postAdded) VALUES (".$Groups.", ".$tableid.", $PostAdded)";
				askdb($q);
			}
		}
	}
}


/* Adapted from AddGroupsToTable() above - JG 25Nov2019 */
/* Insert a Location's Table ID and assigned datasets into the TableGroups
 * table, one row per assigned dataset.
 * Inputs: $tableid is the Tables.id value that indexes the name of the
 *	 				 Group's table.
 * 				 $Groups is the (possible array) of datagroup ID's that will be
 *					 assigned to the Group.
 */
function addDatasetsToLocation($tableid,$Groups,$PostAdded=0){
	if(isset($Groups) && isset($tableid)){
		if(is_array($Groups)){
			for($i=0;$i<count($Groups);$i++){

				/* First check to see if the dataset has already been assigned to TableGroups: */
				$q="SELECT * FROM TableGroups WHERE tableid=".$tableid." AND dataset=".$Groups[$i];
				$res=askdb($q);

				/* If not, INSERT it. */
				if(!$res->fetch_object()){
					/* `datagroup_id` is NOT NULL, so we have to insert it whether we're using it or not.
				 	 * For consistency, insert the `Datagroups.id` value corresponding to the N.id given by $Groups */
					$dg_id=getDatasetId($Groups[$i]);
					
					$q="INSERT INTO TableGroups (datagroup_id,dataset,tableid,postAdded) VALUES (".$dg_id.", ".$Groups[$i].", ".$tableid.", $PostAdded)";
					askdb($q);

				}
			}
		} else {
			/* If $Groups is not an array */

			$q="SELECT * FROM TableGroups WHERE tableid=".$tableid." AND dataset=".$Groups;
			$res=askdb($q);

			if(!$res->fetch_object()){
				$dg_id=getDatasetId($Groups);

				$q="INSERT INTO TableGroups (datagroup_id,dataset,tableid,postAdded) VALUES (".$dg_id.", ".$Groups.", ".$tableid.", $PostAdded)";
				askdb($q);
			}
		}
	}
}


function DelGroupsFromTables($tables,$groups){
	if(isset($tables) && isset($groups)){
		if(is_array($tables)){
			$tstr=implode(",",$tables);
		}else{
			$tstr=$tables;
		}
		if(is_array($groups)){
			$gstr=implode(",",$groups);
		}else{
			$gstr=$groups;
		}

		$q="DELETE FROM TableGroups WHERE tableid IN (".$tstr.") AND datagroup_id IN (".$gstr.")";
		askdb($q);
	}
}	


/* Adapted from DelGroupsFromTables() above to use Datasets instead of Groups - JG 26Nov2019 */
function unassignDatasets($tables,$groups){
	/* $groups should be an array of dataset indexes */
	if(isset($tables) && isset($groups)){
		if(is_array($tables)){
			$tstr=implode(",",$tables);
		}else{
			$tstr=$tables;
		}
		if(is_array($groups)){
			$gstr=implode(",",$groups);
		}else{
			$gstr=$groups;
		}

		//$q="DELETE FROM TableGroups WHERE tableid IN (".$tstr.") AND datagroup_id IN (".$gstr.")";
		$q="DELETE FROM TableGroups WHERE tableid IN (".$tstr.") AND dataset IN (".$gstr.")";
		askdb($q);
	}
}	



/* CreateTable() creates the Location tables and associated data in the
 *   Masterclass DB.
 * Inputs: $locationName is the Location table name.  A new table will be
 * 				 	created with this name, and the name will be added to 'Tables.name'.
 *				 $datagroups is the set of Events.datagroup_id values that will be
 *				 	assigned to this Masterclass Group.  It can be a single value or
 *				 	an array.
 * Procedure:
 * 1) Create the Location table
 * 2) Create an (id,data) pair in 'histograms'
 * 3) Register the (locationName, histogram.id) pair as a new row in 'Tables'
 * 4) Register the (Tables.id, datagroup_id) pair as a new row in 'TableGroups'
 */
/* 1) When created, each Location table name in the database is prefixed with
 * 		$locPrefix as given by GetLocationPrefix(), e.g. '__LOC__'.
 *	 	This indicates the role of these tables more clearly and cleanly
 *		separates them from the other database tables when listed.
 *		Whenever information is taken from the DB about a table, we create a
 *		separate "display name" parameter without this prefix to display the name
 *		to the user.
 * 2) The <Location>.'checked' column is a semicolon-separated list of
 * 		user selections on the fillOut.php page.  That's not normal-formed.
 *		Oct2018 upgrades added 'final_state', 'primary_state', and 'mass' columns
 *		to store this data atomically as part of an effort to deprecate its use.
 */
function CreateTable($locationName,$datagroups){
	/* Prefex for names of Location tables to help identify and sort them */
	$locPrefix=GetLocationPrefix();

	/* Check to see if the name is already registered in the 'Tables' table: */
	$nameNotFound = TRUE;

	// New-style names:
	$q="SELECT name FROM Tables WHERE name='".$locPrefix.$locationName."'";
	$res=askdb($q);
	if($res->fetch_object()){ $nameNotFound = FALSE; }	

	// Old-style names:
	/* This should be deletable after upgrades are complete */
	$q="SELECT name FROM Tables WHERE name='".$locationName."'";
	$res=askdb($q);
	if($res->fetch_object()){ $nameNotFound = FALSE; }

	/* If the table doesn't already exist, and if $locationName is properly
		 defined, create the Location table */
	if($nameNotFound && isset($locationName) && $locationName!=""){

		// Should final_state and primary_state be NOT NULL?
		$q="CREATE TABLE `".$locPrefix.$locationName."` (event_id INT NOT NULL, checked VARCHAR(20), final_state VARCHAR(10), primary_state VARCHAR(10), mass DOUBLE, FOREIGN KEY (event_id) REFERENCES Events(event_id))";
		askdb($q);

		/* Inserts a new row of all-zero data strings in the `histograms` table.
			 `histograms.id` AUTO_INCREMENTs. */
		CreateHist();

		/* 'histograms' MAX(id) will be the value created via AUTO_INCREMENT by
			 the call to CreateHist() immediately above. */
		$q="SELECT MAX(id) AS id FROM histograms";
		$res=askdb($q);
		$histid=$res->fetch_object()->id;

		/* Register the Location table name in 'Tables' *with* the location prefix.
			 This will AUTO_INCREMENT Tables.id */
		$q="INSERT INTO Tables (name,histogram_id) VALUES ('".$locPrefix.$locationName."', ".$histid.")";
		askdb($q);

		/* 'Tables' MAX(id) will be the value created via AUTO_INCREMENT by
			 the call to askdb() immediately above. */
		$q="SELECT MAX(id) AS id FROM Tables";
		$res=askdb($q);
		$tableid=$res->fetch_object()->id;

		/* AddGroupsToTable will add the Location table's Tables.id value and
			 the input datagroup_id values of $datagroups to the 'TableGroups' table */
		if(isset($datagroups)){
			AddGroupsToTable($tableid,$datagroups);
		}
		
		/* Return Table.id for the new Group */
		return $tableid;
	}
}


function GetMCEvents(){
	/* 'WHERE 1' is typically used so that the query can be appended to later.
		 I don't think we have a case for that here; probably deletable
		 	 - JG 25Oct2018 */
	$q="SELECT * FROM MclassEvents WHERE 1";
	$res=askdb($q);
	while($obj = $res->fetch_object()){ 
		$temp["id"]=$obj->id;
		$temp["name"]=$obj->name;
		$temp["active"]=$obj->active;
		$result[]=$temp;
	}
	if(isset($result)){
		return $result;
	}
}


/* The "name" value returned by this function will include the location prefix */
function GetTableByID($tableid){
	$q="SELECT * FROM Tables WHERE id=".$tableid;
	$res=askdb($q);
	if($obj = $res->fetch_object()){
		$result["id"]=$obj->id;
		$result["name"]=$obj->name;
		/* Added Oct2018 to accommodate Location prefix: */
		$locPrefix=GetLocationPrefix();
		$result["displayName"]=str_replace($locPrefix, '', $result["name"]);
	}
	if(isset($result)){
		return $result;
	}
}


/* Returns the histogram id and data string for the histogram belonging
	 to Location table $location.  Return value is a 2-element array
	 [id,datastring] */
function GetHistDataForTable($location){
	/* `SELECT histogram_id FROM Tables WHERE name=$location`
	 *	returns the histogram id for the input table.
	 * `SELECT id,data FROM histograms WHERE id={histogram id}`
	 * 	returns the id and corresponding histogram data in the form of a
	 *	semicolon-separated array of 68 integers
	 * 		4;38;11;14;20;15;8;5;5;9;3;2;5;1;1;1;0;0;1;0;1;0;0;0;1;1;...
	 */
	$q="SELECT id,data FROM histograms WHERE id=(SELECT histogram_id FROM Tables WHERE name='".$location."')";
	$res=askdb($q);
	if($obj = $res->fetch_object()){ 
		$result["id"]=$obj->id;
		$result["data"]=$obj->data;
	}
	return $result;
}


/* Created from GetHistDataFromTable() to accommodate 2lep/4lep histogram
upgrades.  If successful, this should replace GHDFT() entirely. */
/* Returns the histogram id, 2-lepton data string, and 4-lepton data
 * string for the histograms belonging to Location table $location.
 * Return value is a 2-element array [id,datastring]
 */
function GetHistogramData($location){
	/* `SELECT histogram_id FROM Tables WHERE name=$location`
	 *	returns the histogram id for the input table.
	 * `SELECT id,data FROM histograms WHERE id={histogram id}`
	 * 	returns the id and corresponding histogram data in the form of a
	 *	semicolon-separated array of 68 integers
	 * 		4;38;11;14;20;15;8;5;5;9;3;2;5;1;1;1;0;0;1;0;1;0;0;0;1;1;...
	 */
	$q="SELECT id,data_2l,data_4l FROM histograms WHERE id=(SELECT histogram_id FROM Tables WHERE name='".$location."')";
	$res=askdb($q);
	if($obj = $res->fetch_object()){ 
		$result["id"]=$obj->id;
		$result["data_2l"]=$obj->data_2l;
		$result["data_4l"]=$obj->data_4l;
	}
	return $result;
}


/* Replaced by UpdateHistogram() below for WZH upgrades, Dec 2018 - JG */
function UpData($data,$id){
	$q="UPDATE histograms SET data='".$data."' WHERE id=".$id;
	askdb($q);
}


/* Function to update the histogram table of a location identified by $id */
function UpdateHistogram($chart,$data,$id){
	/* $chart is 'data_2l' or 'data_4l', the one you want to update */
	$q="UPDATE histograms SET ".$chart."='".$data."' WHERE id=".$id;
	askdb($q);
}


/* Pulls mass data entered by a Location on the DataTable.php page, generates a
 * semicolon-separated string of histogram data for each chart, and stores this
 * generated data to the DB.
 */
function GenerateHistogramData($location){

	$twoLeptonList = ["2e", "mu_mu"];
	$fourLeptonList = ["4e", "4mu", "2e_2mu"];

	# TODO: find a better way to configure these than hard-coding
	# NB These values are also hard-coded in the MakeHist() calls in hist.php
	# histogram parameters:
	$x_min_2l = 51;
	$x_max_2l = 111;
	$x_min_4l = 81;
	#$x_max_4l = 211;
	$x_max_4l = 400;
	$bin_2l = 2;
	$bin_4l = 3;

	$numBins_2l = ceil( ($x_max_2l - $x_min_2l)/$bin_2l );
	$numBins_4l = ceil( ($x_max_4l - $x_min_4l)/$bin_4l );

	# Create arrays to hold data for each chart
	# Initialized to all zeroes
	$data_2l = array();
	for($i = 0;$i < $numBins_2l;$i++) {
    $data_2l[$i] = 0;
	}
	$data_4l = array();
	for($i = 0;$i < $numBins_4l;$i++) {
    $data_4l[$i] = 0;
	}

	/* Get masses for this location */
	$q="SELECT final_state, mass FROM `".$location."` WHERE mass IS NOT NULL";
	$result=askdb($q);
	if($obj = $result->fetch_assoc()){
		while($row = $result->fetch_array()){
			$final = $row['final_state'];
			$mass = $row['mass'];

			/* Calculate the bin number */
			if ( in_array($final, ["2e", "mu_mu"]) ) {
				#$chart="data_2l";
				# Find the bin the mass belongs in
				# bin counting is *zero-indexed*
				$binNo = floor( ($mass - $x_min_2l)/$bin_2l );
				# The bin might be outside of the bounds we've set for the chart
				if ( ($binNo >= 0) and ($binNo < $numBins_2l) ) {
					 $data_2l[$binNo]++;
				}
			} elseif ( in_array($final, ["4e", "4mu", "2e_2mu"]) ) {
				#$chart="data_4l";
				$binNo = floor( ($mass - $x_min_4l)/$bin_4l );
				if ( ($binNo >= 0) and ($binNo < $numBins_4l) ) {
					 $data_4l[$binNo]++;
				}
			}
		}
	}

	/* Create semicolon-separated strings out of the data */
	$newData_2l = implode(";", $data_2l);
	$newData_4l = implode(";", $data_4l);

	/* Get the histogram id value for this location */
	$q="SELECT histogram_id FROM Tables WHERE `name`='".$location."'";
	$result=askdb($q);
	if($obj = $result->fetch_object()){
		$id=$obj->histogram_id;
	}

	/* Call the UpdateHistogram() function database.php to change the
	 *   recorded histogram data */
	//UpdateHistogram("data_2l",$newData_2l,$id);
	//UpdateHistogram("data_4l",$newData_4l,$id);

	$dataList = ["data_2l" => $newData_2l,
							 "data_4l" => $newData_4l];

	return $dataList;

}


function GetTestData(){
	$data_2l = "0;0;0;1;1;2;1;3;2;1;3;5;9;12;7;4;1;3;0;2;1;0;2;3;1;0;1;0;0;0;0;0;0;0;1;2;0;3;1;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0";

	$data_4l = "0;0;0;0;0;0;0;0;0;0;0;1;0;0;2;1;1;1;4;2;3;5;9;11;10;6;3;4;3;2;2;1;0;1;0;2;0;1;0;1;2;4;2;1;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0";

	$dataList = ["data_2l" => $data_2l,
							 "data_4l" => $data_4l];

	return $dataList;

}


/* Create an entry in the MclassEvents table if it doesn't already exist */
function CreateEvent($name){
	/* See if $name already exists in the 'MclassEvents' table */
	$q="SELECT * FROM MclassEvents WHERE name='".$name."'";
	$res=askdb($q);
	/* if $res->fetch_object() returns a "truthy" value, set $test equal to
		 that value's 'name' */
	if($obj = $res->fetch_object()){ 
		$test=$obj->name;
	}
	if(!isset($test)){
		/* If $test could not be set,  must not exist in the DB.
			 Create the event's row in the 'MclassEvents' table.
			 MclassEvents.id is the PK and will auto-increment. */
		$q="INSERT INTO MclassEvents (active,name) VALUES ( 1,'".$name."')";
		askdb($q);
	}else{
		return 0;
	}
}


function GetLastEvent(){
	$q="SELECT MAX(id) AS id FROM MclassEvents";
	$res=askdb($q);
	if($obj = $res->fetch_object()){
		return GetMClassEvent($obj->id);
	}
}
	

function GetMClassEvent($id){
	$q="SELECT * FROM MclassEvents WHERE id='".$id."'";
	$res=askdb($q);
	if($obj = $res->fetch_object()){ 
		$result["name"]=$obj->name;
		$result["id"]=$obj->id;
		$result["active"]=$obj->active;
	}
	if(isset($result)){
		return $result;
	}
}


/* Inputs: $event is an 'MclassEventID' value from table 'Tables' */
/* Returns the id, name, and displayName of Tables associated with the input
 * Masterclass Event ID. */
function GetTables($event){
	/* SELECT tableid FROM EventTables WHERE MclassEventID=$event
		 returns the Tables.id value for the given MclassEventID */
	$q="SELECT * FROM Tables WHERE id IN (SELECT tableid FROM EventTables WHERE MclassEventID='".$event."')";
	$res=askdb($q);	
	while($obj = $res->fetch_object()){ 
		$temp["id"]=$obj->id;
		$temp["name"]=$obj->name;
		/* Added Oct2018 to accommodate Location prefix: */
		$locPrefix=GetLocationPrefix();
		$temp["displayName"]=str_replace($locPrefix, '', $temp["name"]);
		$result[]=$temp;
	}
	if(isset($result)){
		return $result;
	}
}



/* Get all datagroups assigned to the given Location table IDs */
/* Reads from TableGroups */
function GetGroups($Tables){
	if(isset($Tables)){
		if(is_array($Tables)){
			if(is_array($Tables[0])){
				for($i=0;$i<count($Tables);$i++){
					$tables[]=$Tables[$i]["id"];
				}
				$q="SELECT datagroup_id,postAdded FROM TableGroups WHERE tableid IN ( ".implode(",",$tables).")";
			}else{
				$q="SELECT datagroup_id,postAdded FROM TableGroups WHERE tableid IN (".implode(",",$Tables).")";
			}
		}else{
			/* If $Tables is not an array */
			$q="SELECT datagroup_id,postAdded FROM TableGroups WHERE tableid=".$Tables;
		}
		$q=$q." ORDER BY datagroup_id";
		$res=askdb($q);

		while($obj = $res->fetch_object()){
			$temp["dg_id"]=$obj->datagroup_id;
			$temp["postAdded"]=$obj->postAdded;
			$result[]=$temp;
		}
		if(isset($result)){
			return $result;
		}
	}
}



/* Same as above, but doesn't account for array input or return "postAdded" */
function GetDatagroupsById($tableId){
	$q="SELECT datagroup_id FROM TableGroups WHERE tableid=".$tableId." ORDER BY datagroup_id";
	$res=askdb($q);

	$result = array();
	while($obj = $res->fetch_object()){
		$result[] = $obj->datagroup_id;
	}
	if(isset($result)){
		return $result;
	}
}


/* Adapted from GetGroups() to return Dataset indexes rather than datagroup_ids */
/* JG 26Nov2019 */
/* Get all datagroups assigned to the given Location table IDs */
/* Reads from TableGroups */
function getDatasetsByTable($Tables){
	if(isset($Tables)){
		if(is_array($Tables)){
			if(is_array($Tables[0])){
				for($i=0;$i<count($Tables);$i++){
					$tables[]=$Tables[$i]["id"];
				}
				//$q="SELECT datagroup_id,postAdded FROM TableGroups WHERE tableid IN ( ".implode(",",$tables).")";
				$q="SELECT dataset,postAdded FROM TableGroups WHERE tableid IN ( ".implode(",",$tables).")";
			}else{
				//$q="SELECT datagroup_id,postAdded FROM TableGroups WHERE tableid IN (".implode(",",$Tables).")";
				$q="SELECT dataset,postAdded FROM TableGroups WHERE tableid IN (".implode(",",$Tables).")";
			}
		}else{
			/* If $Tables is not an array */
			//$q="SELECT datagroup_id,postAdded FROM TableGroups WHERE tableid=".$Tables;
			$q="SELECT dataset,postAdded FROM TableGroups WHERE tableid=".$Tables;
		}
		//$q=$q." ORDER BY datagroup_id";
		$q=$q." ORDER BY dataset";
		$res=askdb($q);

		while($obj = $res->fetch_object()){
			//$temp["dg_id"]=$obj->datagroup_id;
			$temp["ds_id"]=$obj->dataset;
			$temp["postAdded"]=$obj->postAdded;
			$result[]=$temp;
		}
		if(isset($result)){
			return $result;
		}
	}
}









function GetIndTables(){
	$q="SELECT * FROM Tables WHERE NOT id IN (SELECT tableid FROM EventTables WHERE 1)";
	$res=askdb($q);
	while($obj = $res->fetch_object()){ 
		$temp["id"]=$obj->id;
		$temp["name"]=$obj->name;
		/* Added Nov2018 to accommodate Location prefix: */
		$locPrefix=GetLocationPrefix();
		$temp["displayName"]=str_replace($locPrefix, '', $temp["name"]);
		$result[]=$temp;
	}
	if(isset($result)){
		return $result;
	}
}


function GetFreeTables($event,$boundGroups,$overlab){
	$q="SELECT * FROM Tables WHERE NOT id IN (SELECT tableid FROM EventTables WHERE MclassEventID='".$event."')";
	if($overlab==1){
		$q=$q.";";
	}else{
		if(isset($boundGroups) && is_array($boundGroups)){
			$q=$q." AND NOT id IN (SELECT tableid FROM TableGroups WHERE datagroup_id IN (".$boundGroups[0];
			for($i=1;$i<count($boundGroups);$i++){
				if(isset($boundGroups[$i]["id"])){
					$q=$q.", ".$boundGroups[$i]["id"];
				}
			}
			$q=$q." ) )";
		}
	}
	$res=askdb($q);	
	while($obj = $res->fetch_object()){ 
		$temp["id"]=$obj->id;
		$temp["name"]=$obj->name;
		$locPrefix=GetLocationPrefix();
		$temp["displayName"]=str_replace($locPrefix, '', $temp["name"]);
		$result[]=$temp;
	}
	if(isset($result)){
		return $result;
	}
}


/* Change this for data blocks - 25Nov2019 */
/* Altered function below */
function GetFreeGroups($boundGroups,$overlab){
	if(isset($boundGroups) && is_array($boundGroups) && $overlab==0){
		$q="SELECT DISTINCT datagroup_id FROM Events WHERE NOT datagroup_id IN ( ".implode(",",$boundGroups).")";
	}else{
		$q="SELECT DISTINCT datagroup_id FROM Events WHERE 1";
	}
	$res=askdb($q);
	while($obj = $res->fetch_object()){ 
		$result[]=$obj->datagroup_id;
	}
	if(isset($result)){
		return $result;
	}
}


/* Change this for data blocks - 25Nov2019 */
/* Original function above */
/* $boundGroups is an array of all datagroups that have been assigned. */
function getFreeDatasets($boundSets, $overlap) {
	if( isset($boundSets) && is_array($boundSets) && $overlap==0) {
	
		$q="SELECT DISTINCT dataset FROM Datasets WHERE NOT dataset IN ( ".implode(",",$boundSets).")";

	}else{

		$q="SELECT DISTINCT dataset FROM Datasets WHERE 1";

	}
	$res=askdb($q);
	while($obj = $res->fetch_object()){ 
		$result[]=$obj->dataset;
	}
	if(isset($result)){
		return $result;
	}
}


		
function connectGroups($tableid,$gstd,$gbackup){
	$q="INSERT INTO groupConnect (gstd,gbackup,tableid) VALUES (".$gstd.",".$gbackup.",".$tableid.")";
	askdb($q);
}


function GetConnection($tableid,$group){
	$q="SELECT gbackup FROM groupConnect WHERE tableid=".$tableid." AND gstd=".$group;
	$res=askdb($q);
	if($obj = $res->fetch_object()){ 
		$result=$obj->gbackup;
	}
	if(isset($result)){
		return $result;
	}

}


function isbackup($tableid,$groupid){
	$q="SELECT postAdded FROM TableGroups WHERE tableid=".$tableid." AND datagroup_id=".$groupid;
	print($q);
	$res=askdb($q);
	if($obj = $res->fetch_object()){ 
		$result=$obj->postAdded;
	}
	if(isset($result)&&$result==1){
		return true;
	}else{
		return false;
	}
}


/* Added Nov2018 to make the location prefix accessible globally */
function GetLocationPrefix(){
	return '_LOC_';
}


/* Added Dec2018 so we don't have to try to calculate datagroup_id in place */
function GetDatagroupId($event){
  $q="SELECT datagroup_id FROM Events WHERE event_id='".$event."'";
	$result=askdb($q);
	if(isset($result)){
		return mysqli_fetch_assoc($result)["datagroup_id"];
	}
}



?>
