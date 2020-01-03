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


/* Added to handle converting the new dataset indexing for storage in the
 * `event_id` column of Location tables - JG 26Nov2019
 */
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

		$unique = ((int) $base)*1000 + (int) $parts[1];

		return $unique;
}


/* Returns the full dataset index (i.e. 10.6-3 or 50.7-45) for a given
 * unique $id */
function idToIndex($id) {

	// Convert to string
	$num = (string) $id;
	$eventNo = substr($num,-3);
	// Trim leading zeroes
	$eventNo = ltrim($eventNo, '0');

	$base = substr($num,0,-3);

	$q="SELECT dataset FROM Datasets WHERE id='".$base."'";
	$res=askdb($q);

	if($obj=$res->fetch_object()){
		$ds=$obj->dataset;
	}else{
		$ds="0";
	}

	$index = $ds."-".$eventNo;

	return $index;
}


/* Returns an event's number within its dataset [1-100] given its unique $id */
function idToDsNumber($id) {

	// Convert to string
	$num = (string) $id;
	$eventNo = substr($num,-3);
	// Trim leading zeroes
	$eventNo = ltrim($eventNo, '0');

	return $eventNo;
}


/* Added 27Nov2018 for new indexing system */
/* Returns an array of all unique event id's for the input $dataset X.Y */
function getEventsIdsForDataset($dataset) {

	$startingDsIndex = ((string) $dataset)."-1";

	$startingDsId = indexToId($startingDsIndex);

	$eventIds = array();
	for($i=0; $i<100; $i++) {
		$eventIds[] = $startingDsId + $i;
	}

	return $eventIds;
}


/* Returns event_id values for $datagroup that are not already contained
 * in the given Location $location
 */
function GetFreeEvents($datagroup,$location){

	/* Location tables don't have a 'datagroup_id' column.  The WHERE clause
	 * in the subquery doesn't throw an error, but what does it accomplish? */
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


/* Adapted from GetFreeEvents() above to work with datasets
 * JG 26Nov2019 */
/* Returns unique id values for ass events in $datagroup that are not already
 * contained in the given Location $location */
function getUncompletedEventsIds($dataset,$location){

	/* Get an array of all possible unique id values for this dataset */
	$allEventsIds = getEventsIdsForDataset($dataset);

	/* Initialize an empty array for completedEvents.  This is important
	 * so that if the DB query returns nothing (no completed events have been
	 * recorded to the Location table yet), there's still an array to diff. */
	$completedEventsIds=array();

	/* Now find what unique id's are stored in the given Location table.
	 * We're storing these in the `event_id` column now, which means that those
	 * values will no longer line up with Events.event_id.
	 */
	$q="SELECT event_id FROM `".$location."`";
	$res=askdb($q);
	while($obj=$res->fetch_object()){
		$completedEventsIds[]=$obj->event_id;
	}

	// Set-wise subtract completedEvents from allEvents:
	$uncompletedEventsIds = array_diff($allEventsIds, $completedEventsIds);

	if(isset($uncompletedEventsIds)){
		return $uncompletedEventsIds;
	}
}


/* Once a Masterclass is created, associate one or more Location tables to
 * it by creating entries in 'EventTables'.  The Location tables must
 * already exist and be registered in 'Tables'.
 */
/* Inputs: $tables is a tableid value (or array of values) that should
 *	 				 match Tables.id.
 * 				 $eventID is a MclassEventID value that should match MclassEvents.id.
 */
/* Used only in MCEvents.php */
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
 * the relevant entry in 'EventTables'.
 */
/* Used only in MCEvents.php */ 
function RemoveTablesFromEvent($tables,$eventID){

		if(isset($tables) && is_array($tables) && isset($eventID)){
				for($i=0;$i<count($tables);$i++){

						$q="DELETE FROM EventTables WHERE tableid=".$tables[$i]." AND MclassEventID=".$eventID;

						askdb($q);
				}
		}
}


/* Get all events associated with a Location table $location */
/* Used only in results.php */
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


/* For each event assigned to a Location $location, return the event_id,
 * the Location 'checked' list, and the canonical mass.
 */
/* Inputs: $datagroup is a datagroup number.
 * 				 $location is a Location table in the Masterclass database.
 */
/* Used only in DataTable.php.  Currently unused? - JG 23Dec2019 */ 
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
/* Used only in DataTable.php.  Currently unused? - JG 23Dec2019 */ 
function GetEventTableRows($datagroup,$location){

		$q="SELECT `".$location."`.event_id, Events.datagroup_id, Events.g_index, `".$location."`.final_state, `".$location."`.primary_state, `".$location."`.mass FROM `".$location."` INNER JOIN Events ON `".$location."`.event_id=Events.event_id WHERE `".$location."`.event_id IN (SELECT event_id FROM Events WHERE datagroup_id=".$datagroup.") ORDER BY `".$location."`.event_id";

		$res=askdb($q);

		while($obj=$res->fetch_object()){ 
				$temp["event_id"]=$obj->event_id;
				/* 'datagroup_id' and 'g_index' are in the table, but aren't used
				 * directly to create rows.  Uncomment these lines to make them
				 * available: */
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


/* Adapted from GetEventTableRows() for use with dataset indexing
 * - JG 27Nov2019 */
/* For each event assigned to a Location $location, return the unique
 * event_id, the dataset index, and the user-entered final state, primary
 * state, and mass.
 */
/* In CIMA-WZH, this is basically just reading out the Location table. */	 
/* Inputs: $datagroup is a datagroup number.
 *	 			 $location is a Location table in the Masterclass database.
 */
/* Used only in DataTable.php. */ 
function getEventsTableRows($datagroup,$location) {

		$q="SELECT event_id, final_state, primary_state, mass FROM `".$location."` ORDER BY event_id";
		$res=askdb($q);

		$result=array();
		while($obj=$res->fetch_object()){
				$temp["event_id"] = $obj->event_id;
				$temp["dg_label"] = idToIndex($obj->event_id);
				$temp["final"] = $obj->final_state;
				$temp["primary"] = $obj->primary_state;
				$temp["mass"] = $obj->mass;
				$result[] = $temp;
		}
		if(isset($result)){
				return $result;
		}
}


/* Used only in DataTable.php.  Currently unused? - JG 23Dec2019 */ 
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
				/* Add a conversion to dataset index */
				$temp = $obj->event_id;
				$temp = idToIndex($temp);
				$result["dset"] = $temp;
		}
		if(isset($result)){
				return $result;
		}
}


/* New, more readable version of GetNext() created for dataset indexing
 * - JG 27Nov2019 */
function getNextUncompletedEvent($tabData,$dataset) {

		/* Get and sort an array containing all expected event_id's for this
		 * dataset */
		$allEventsIds = getEventsIdsForDataset($dataset);

		/* If a location has not entered data into their Location table yet,
	 	 * then $tabData will be an empty array. */

		sort($allEventsIds);

		$firstEventsId = $allEventsIds[0];

		/* Make sure that $tabData is sorted by the value of its rows' event_id
		 * values */
		usort($tabData, function($a, $b) {
				return $a["event_id"] - $b["event_id"];
		});

		/* Step through the rows of $tabData, look for the first out-of-sequence
	 	 * event_id, and capture that row index */
		/* $k will track rows.  All rows less than $k are confirmed to
		 * be in-sequence */
		/* We want the event_id for the table.tpl drop-down menu.  If that's all
	   * we need, then $k is superfluous here and can be deleted.  Leaving it
		 * for now in case row number is needed somewhere else - JG 2Dec2019 */
		$k=0;
		$expectedEventsId=$firstEventsId;
		for($i=0; $i<count($tabData); $i++) {
				/* If there's a discrepancy, we've found the first missing row.
		 		 * If there's not, move to the next row and next expected event_id. */
				if ( !($tabData[$i]["event_id"] == $expectedEventsId) ) {
					 	$firstMissingRow = $k;
						break;
				} else {
			 			$k++;
			 			$expectedEventsId++;
				}
		}

	return $expectedEventsId;
}


/* Added Oct2018 as expansion of WriteEntry(), since removed, to handle
 * new data format */
/* Used only in DataTable.php. */ 
function WriteRow($location,$event_id,$finalState,$primaryState,$mass){

		/* Check to see if this event_id already has an entry in the Location
	 	 * table: */
		$q="SELECT event_id FROM `".$location."` WHERE event_id=".$event_id;
		$res=askdb($q);

		/* if $res is truthy, event_id already exists, and INSERT should fail */
		if(!$res->fetch_object()){
				$q="INSERT INTO `".$location."` (event_id,final_state,primary_state,mass) VALUES (".$event_id.",'".$finalState."','".$primaryState."',".$mass.")";

				askdb($q);
		}
}


/* Deletes the row identified by the given event_id from the given Location
 * table.
 */
/* Used only in DelE.php. */ 
function DelRow($id,$location){
		$q="DELETE FROM `".$location."` WHERE event_id=".$id;
		askdb($q);
}


/* Used only in Classes.php. */ 
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

				/* First check to see if the dataset has already been assigned to
				 * TableGroups: */
				$q="SELECT * FROM TableGroups WHERE tableid=".$tableid." AND dataset=".$Groups[$i];
				$res=askdb($q);

				/* If not, INSERT it. */
				if(!$res->fetch_object()){
					/* `datagroup_id` is NOT NULL, so we have to insert it whether we're
					 * using it or not.
				 	 * For consistency, insert the `Datagroups.id` value corresponding to
					 * the N.id given by $Groups */
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
		/*
		$q="CREATE TABLE `".$locPrefix.$locationName."` (event_id INT NOT NULL, checked VARCHAR(20), final_state VARCHAR(10), primary_state VARCHAR(10), mass DOUBLE, FOREIGN KEY (event_id) REFERENCES Events(event_id))";
		*/
		/* Removing FK to allow Location tables to store unique id's for datasets.
		 *   JG 2Dec2019 */
		$q="CREATE TABLE `".$locPrefix.$locationName."` (event_id INT NOT NULL, checked VARCHAR(20), final_state VARCHAR(10), primary_state VARCHAR(10), mass DOUBLE)";

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
	 * I don't think we have a case for that here; probably deletable
	 *		 	 - JG 25Oct2018 */
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
 * to Location table $location.  Return value is a 2-element array
 * [id,datastring] */
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


function getHistogramParams() {

	$params = array();
	$params['x_min_2l'] = 1;
	$params['x_max_2l'] = 111;
	$params['x_min_4l'] = 81;
	$params['x_max_4l'] = 400;
	$params['bin_2l'] = 2;
	$params['bin_4l'] = 3;

	return $params;
}


/* Pulls mass data entered by a Location on the DataTable.php page, generates a
 * semicolon-separated string of histogram data for each chart, and stores this
 * generated data to the DB.
 */
function GenerateHistogramData($location){

	$twoLeptonList = ["2e", "mu_mu"];
	$fourLeptonList = ["4e", "4mu", "2e_2mu"];

	/* This is to resolve the hard-coding issue: */
	$params = getHistogramParams();

	$x_min_2l = $params['x_min_2l'];
	$x_max_2l = $params['x_max_2l'];
	$x_min_4l = $params['x_min_4l'];
	$x_max_4l = $params['x_max_4l'];
	$bin_2l = $params['bin_2l'];
	$bin_4l = $params['bin_4l'];

	$numBins_2l = ceil( ($x_max_2l - $x_min_2l)/$bin_2l );
	$numBins_4l = ceil( ($x_max_4l - $x_min_4l)/$bin_4l );

	# Create arrays to hold data for each chart
	# Initialized to all zeroes
	$data_2l = array();
	for($i=0; $i<$numBins_2l; $i++) {
    $data_2l[$i] = 0;
	}
	$data_4l = array();
	for($i=0; $i<$numBins_4l; $i++) {
    $data_4l[$i] = 0;
	}

	/* Get masses for this location */
	$q="SELECT final_state, mass FROM `".$location."` WHERE mass IS NOT NULL";
	$result=askdb($q);

	while($row = $result->fetch_array()){
		$final = $row['final_state'];
		$mass = $row['mass'];

		/* Calculate the bin number */
		if ( in_array($final, $twoLeptonList) ) {
			# Find the bin the mass belongs in
			# bin counting is *zero-indexed*
			$binNo = floor( ($mass - $x_min_2l)/$bin_2l );
			# The bin might be outside of the bounds we've set for the chart
			if ( ($binNo >= 0) and ($binNo < $numBins_2l) ) {
				/* Add to the bin */
				$data_2l[$binNo]++;
			}
		} elseif ( in_array($final, $fourLeptonList) ) {
			$binNo = floor( ($mass - $x_min_4l)/$bin_4l );
			if ( ($binNo >= 0) and ($binNo < $numBins_4l) ) {
				/* Add to the bin */
				$data_4l[$binNo]++;
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


/* Return meaningless data constructed to fill histogram bins for testing */
function GetTestData($bins_2l, $bins_4l){

	/* We'll construct test data by repeating these seed strings until the
	 * bins are filled.  Each has 6 bins. */
	$seed_2l = "1;5;7;0;2;4";
	$seed_4l = "2;2;10;0;3;8";

	$seed_array_2l = explode(';', $seed_2l);
	$seed_array_4l = explode(';', $seed_4l);

	$fullReps_2l = intdiv($bins_2l, count($seed_2l));
	$extra_2l = $bins_2l % count($seed_2l);
	
	$fullReps_4l = intdiv($bins_4l, count($seed_4l));
	$extra_4l = $bins_4l % count($seed_4l);

	/* Construct the data strings */
	$data_2l = "";
	for($i=0; $i<$fullReps_2l; $i++) {
		$data_2l = $data_2l.";".$seed_2l;
	}
	for($i=0; $i<$extra_2l; $i++) {
		$data_2l = $data_2l.";".$seed_array_2l[$i];
	}
	
	$data_4l = "";
	for($i=0; $i<$fullReps_4l; $i++) {
		$data_4l = $data_4l.";".$seed_4l;
	}
	for($i=0; $i<$extra_4l; $i++) {
		$data_4l = $data_4l.";".$seed_array_4l[$i];
	}

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


/* Adapted from GetDatagroupsById() to return datasets instead of datagroup IDs
 * for the new indexing system for CIMA-WZH */
function getDatasetsForLocation($tableId) {

	// Can't order these like we could for datagroup_id
	$q="SELECT dataset FROM TableGroups WHERE tableid=".$tableId;
	$res=askdb($q);

	$result = array();
	while($obj = $res->fetch_object()){
		//$result[] = $obj->datagroup_id;
		$result[] = $obj->dataset;
	}
	if(isset($result)){
		return $result;
	}

	// Can define ordering function here if needed

}


/* Adapted from GetGroups() to return Dataset indexes rather than datagroup_ids */
/* JG 26Nov2019 */
/* Get all datasets (5.3, 10.6, etc.) assigned to the given Location table IDs */
/* Reads from TableGroups */
function getDatasetsByTable($Tables){
	if(isset($Tables)){
		if(is_array($Tables)){
			if(is_array($Tables[0])){
				for($i=0; $i<count($Tables); $i++){
					$tables[]=$Tables[$i]["id"];
				}

				$q="SELECT dataset,postAdded FROM TableGroups WHERE tableid IN ( ".implode(",",$tables).") AND dataset IS NOT NULL";

			}else{

				$q="SELECT dataset,postAdded FROM TableGroups WHERE tableid IN (".implode(",",$Tables).") AND dataset IS NOT NULL";

			}

		}else{
			/* If $Tables is not an array */
			$q="SELECT dataset,postAdded FROM TableGroups WHERE tableid=".$Tables." AND dataset IS NOT NULL";
		}

		$q=$q." ORDER BY dataset";
		$res=askdb($q);

		/* If a Location table or set of tables has no assigned datasets,
		 * $obj->dataset will return NULL.  If all tables have none, $result["ds_id"]
		 * will be a null array, which can cause problems. */
		while($obj = $res->fetch_object()){
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
				/* I don't see the purpose here.  Query strings don't end in semicolons,
				 * the SQL driver takes care of that. - JG 13Dec2019 */
				$q=$q.";";
		} else {
				if(isset($boundGroups) && is_array($boundGroups)){

						$q=$q." AND NOT id IN (SELECT tableid FROM TableGroups WHERE dataset IN (".$boundGroups[0];

						/* What's with the indexing here?  Why not $i=0?
						 * Assume it's an error and fix */
						//for($i=1; $i<count($boundGroups); $i++){
						for($i=1; $i<count($boundGroups); $i++){
								if(isset($boundGroups[$i]["ds_id"])){
										$q=$q.", ".$boundGroups[$i]["ds_id"];
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


/* Adapted from GetFreeTables() because I found that function difficult to parse;
 * also updating to work with datasets. - JG 19Dec2019 */
/* $boundGroups = ['5.3', '10.6', ...], for example */
function getUnassignedTables($event,$boundGroups,$overlap){

		/* The query will have the structure
		 * 	SELECT * FROM Tables WHERE NOT id IN (A) AND NOT id IN (B)
		 *
		 *	A = SELECT tableid FROM EventTables WHERE MclassEventID='1'
		 *	B = SELECT tableid FROM TableGroups WHERE dataset IN ($boundGroups["id"]'s )
		 *
		 * That is, we select all Location tables that have not been assigned to
		 * this Masterclass, and that have no data groups that have been assigned
		 * to this Masterclass.
		 * The condition B is optional, based on whether or not we allow dataset
		 * overlap as a SESSION variable.
		 */

		$q="SELECT * FROM Tables WHERE NOT id IN (SELECT tableid FROM EventTables WHERE MclassEventID='".$event."')";

		/* If overlap is NOT allowed, we have an additional restriction */
		if ( !($overlap==1) ) {
			 	/* The input $boundGroups is the array of datasets assigned to this
			 	 * Masterclass.  It might be an array of NULL values, in which case
				 * the additional condition is moot. */
				/* Construct the condition as the string $boundSet */
				/* '1' is not a valid dataset and will match nothing.  It's used
				 * here to construct the comma-separated set of datasets cleanly
				 * and to prevent an all-NULL array from causing a SQL error. */
				$boundSet = '1';
			 	if(isset($boundGroups) && is_array($boundGroups)){
						for($i=0; $i<count($boundGroups); $i++){
								if(isset($boundGroups[$i])){
										$boundSet = $boundSet.",".$boundGroups[$i];
								}
						}

						/* NULL values can cause problems with this approach: */
						/*$boundSet = "1,".implode(",",$boundGroups)*/

						$q=$q." AND NOT id IN (SELECT tableid FROM TableGroups WHERE dataset IN (".$boundSet.") )";

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


/* Change this for datasets - 25Nov2019 */
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


/* Adapted from GetFreeGroups() for use with dataset indexing - 25Nov2019 */
/* Updated 20Dec2019 to account for NULL $boundSets values */
/* $boundGroups is an array of all datagroups that have been assigned. */
function getFreeDatasets($boundSets, $overlap) {

		if( isset($boundSets) && is_array($boundSets) && $overlap==0 ) {

				/* Construct the comma-separated list of assigned datasets as the
				 * string $boundSet (it's a "set of datasets", hence the singular) */
				$boundSet = '1';
				for($i=0; $i<count($boundSets); $i++){
						if( isset($boundSets[$i]) ){
								$boundSet = $boundSet.",".$boundSets[$i];
						}
				}

				$q="SELECT DISTINCT dataset FROM Datasets WHERE NOT dataset IN ( ".$boundSet.")";

		}else{

				$q="SELECT DISTINCT dataset FROM Datasets";

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


function eventDataset($event_id) {

	$dataset_index = idToIndex($event_id);
	$dataset = explode("-", $dataset_index)[0];

	return $dataset;
}


/* Added Dec2019 to get the dataset ID [1,190] associated with a particular
 * datset */
function getDatasetId($dataset){
  $q="SELECT id FROM Datasets WHERE dataset='".$dataset."'";
	$result=askdb($q);
	if(isset($result)){
		return mysqli_fetch_assoc($result)["id"];
	}
}







?>
