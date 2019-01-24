<?php

session_start();
$script=2;

/*
print_r('<br/>');
print_r('<br/>');
print_r($_POST);
print_r('<br/>');
print_r($_SESSION);
print_r('<br/>');
*/

include 'database.php';
include 'templates/header.tpl';

/* Keys are *code* labels for the table columns.  Values are actual HTML labels */
$tableColumns = array("datagroup" => "Group",
	"e" => "e",
	"mu" => "&mu;",
	"wplus" => "W+",
	"wminus" => "W-",
	"wplain" => "W",
	"zed" => "Z",
	"twogam" => "2&gamma;",
	"zoo" => "Zoo",
	"higgs" => "H",
	"total" => "Total"
	);
$tableHeaders = array_values($tableColumns);
$tableData = array_keys($tableColumns);

$ratioColumns = array(
	"e-mu" => "e/&mu;",
	"Wp-Wm" => "W+/W-",
	"W-Z" => "W/Z"
	);
$ratioHeaders = array_values($ratioColumns);

/* Define table array $groups[][] */
/* Rows are datagroup_id values and columns are selectable particle states.
 * Entries are the number of times users identified the given
 * particle state within the given datagroup.  Construction is based on
 * whether we're showing a single location's data or combined data.
 */
if(!isset($_SESSION["comb"])){
	/*** Single location ***/
	include 'templates/navbar.tpl';
	/* For a single location, we need tabulate only those datagroups assigned
		 to the location */
	$datagroups = GetDatagroupsById($_SESSION["databaseId"]);
	foreach($datagroups as $row){
		foreach($tableData as $column){
			$groups[$row][$column]=0;
		}
		/* Set the first column of every row to be equal to the datagroup.
	 	 * NB that rows are indexed by datagroup, which may not be equal to 
	 	 * the standard [0,1,2,...] array indexing */
		$groups[$row]["datagroup"] = $row;
	}
}else{
	/** Combined locations **/
	include 'templates/Resnav.tpl';
	/* Get the datagroups assigned to this Masterclass's Location tables */
	/* GetGroups() returns datagroup_id and postAdded from the TableGroups table */
	$g=GetGroups($_SESSION["tables"]);
	$start=$g[0]["dg_id"];
	$ng=count($g);
	for($j=0;$j<$ng;$j++){
		$i=$g[$j]["dg_id"];
		foreach($dataCols as $column){
			$groups[$i][$column]=0;
		}
	}
}


/*** Now fill $groups[][] in with data ***/
/* $_SESSION["tables"] is set in Classes.php */
if(isset($_SESSION["tables"])){
	foreach($_SESSION["tables"] as $t_id){
		// GetTableByID() returns "id", "name", and "displayName".
		// "name" is the database name of the Location table.
		$location=GetTableByID($t_id);
		// GetAllEvents() returns "id", "checked", "final", "primary", and "mass"
		// from a Location table
		$events=GetAllEvents($location["name"]);

		// As we loop over all events, we'll keep track of certain values to find
		//   ratios
		$eCount=0;
		$muCount=0;
		$WCount=0;
		$WplusCount=0;
		$WminusCount=0;
		$ZCount=0;

		// Not sure I understand having this check if there's no "else" to handle
		//   $events not being set correctly just above
		if(isset($events)){
			foreach($events as $event){
				// $event is the array ["event_id", "checked", "final", "primary", "mass"]
				// $i is the datagroup_id [1,100] that the chosen event is assigned to.
				$i=GetDatagroupId($event["id"]);
				// Increment the 'total' column for this datagroup:
				$groups[$i]["total"]++;

				// Tally the "final" states
				/* If the state contains Greek letters, then $event["final"] comes
			 	 * out of the DB as the actual Greek letter, not the HTML-encoded
			 	 * version.  To compare it to an array of HTML-encoded Greek letters,
			 	 * you have to de-convert it with htmlentities():
			 	 */
				$final=htmlentities($event["final"]);
				switch ($final) {
			 		case "e&nu;":
			 			$groups[$i]["e"]++;
						$eCount+=1;
						break;
			 		case "&mu;&nu;":
			 			$groups[$i]["mu"]++;
						$muCount+=1;
						break;
			 		case "ee":
			 			//$groups[$i]["e"]+=2;
			 			$groups[$i]["e"]++;
						$eCount+=2;
						break;
			 		case "&mu;&mu;":
			 			//$groups[$i]["mu"]+=2;
						$groups[$i]["mu"]++;
						$muCount+=2;
						break;
			 		case "4e":
			 			//$groups[$i]["e"]+=4;
			 			$groups[$i]["e"]++;
						$eCount+=4;
						break;
			 		case "4&mu;":
			 			//$groups[$i]["mu"]+=4;
			 			$groups[$i]["mu"]++;
						$muCount+=4;
						break;
			 		case "2e 2&mu;":
			 			//$groups[$i]["e"]+=2;
			 			//$groups[$i]["mu"]+=2;
			 			$groups[$i]["e"]++;
			 			$groups[$i]["mu"]++;
						$eCount+=2;
						$muCount+=2;
						break;
			 		case "2&gamma;":
			 			$groups[$i]["twogam"]++;
						break;
					case "Zoo":
			 			$groups[$i]["zoo"]++;
						break;
				}

				// Tally the "primary" states
				$primary=htmlentities($event["primary"]);
				switch ($primary) {
			 		case "W":
			 			$groups[$i]["wplain"]++;
						$WCount+=1;
						break;
			 		case "W+":
			 			$groups[$i]["wplus"]++;
						$WCount+=1;
						$WplusCount+=1;
						break;
			 		case "W-":
			 			$groups[$i]["wminus"]++;
						$WCount+=1;
						$WminusCount+=1;
						break;
			 		case "Z":
			 			$groups[$i]["zed"]++;
						$ZCount+=1;
						break;
			 		case "H":
			 			$groups[$i]["higgs"]++;
						break;
			 		//case "NP":
			 		//	$groups[$i]["np"]++;
					//	break;
			 		case "Zoo":
			 			$groups[$i]["zoo"]++;
						break;
				}
			} // End foreach(event)
		} // end if(isset($events))
	}
} elseif(isset($_SESSION["database"])){
	/*** This applies to a single location. ***/
	/* If $_SESSION["tables"] is NOT set but $_SESSION["database"] is */
	/* $_SESSION["database"] is set in sendIndData.php as the 'name' value
	 * of the Location table returned by GetTableByID() in database.php */
	$events=GetAllEvents($_SESSION["database"]);
	$eCount=0;
	$muCount=0;
	$WCount=0;
	$WplusCount=0;
	$WminusCount=0;
	$ZCount=0;

	if(isset($events)){
		foreach($events as $event){
			$i=GetDatagroupId($event["id"]);
			$groups[$i]["total"]++;

			// Tally the "final" states
			/* If the state contains Greek letters, then $event["final"] comes
		 	 * out of the DB as the actual Greek letter, not the HTML-encoded
		 	 * version.  To compare it to an array of HTML-encoded Greek letters,
		 	 * you have to de-convert it with htmlentities():
		 	 */
			$final=htmlentities($event["final"]);
			switch ($final) {
				case "e&nu;":
					$groups[$i]["e"]++;
					$eCount+=1;
					break;
				case "&mu;&nu;":
		 			$groups[$i]["mu"]++;
					$muCount+=1;
					break;
		 		case "ee":
		 			//$groups[$i]["e"]+=2;
		 			$groups[$i]["e"]++;
					$eCount+=2;
					break;
		 		case "&mu;&mu;":
		 			//$groups[$i]["mu"]+=2;
		 			$groups[$i]["mu"]++;
					$muCount+=2;
					break;
		 		case "4e":
		 			//$groups[$i]["e"]+=4;
		 			$groups[$i]["e"]++;
					$eCount+=4;
					break;
		 		case "4&mu;":
		 			//$groups[$i]["mu"]+=4;
		 			$groups[$i]["mu"]++;
					$muCount+=4;
					break;
		 		case "2e 2&mu;":
		 			//$groups[$i]["e"]+=2;
		 			//$groups[$i]["mu"]+=2;
		 			$groups[$i]["e"]++;
		 			$groups[$i]["mu"]++;
					$eCount+=2;
					$muCount+=2;
					break;
		 		case "2&gamma;":
		 			$groups[$i]["twogam"]++;
					break;
				case "Zoo":
		 			$groups[$i]["zoo"]++;
					break;
			}

			// Tally the "primary" states
			$primary=htmlentities($event["primary"]);
			switch ($primary) {
		 		case "W":
		 			$groups[$i]["wplain"]++;
					$WCount+=1;
					break;
		 		case "W+":
		 			$groups[$i]["wplain"]++;
		 			$groups[$i]["wplus"]++;
					$WCount+=1;
					$WplusCount+=1;
					break;
		 		case "W-":
		 			$groups[$i]["wplain"]++;
		 			$groups[$i]["wminus"]++;
					$WCount+=1;
					$WminusCount+=1;
					break;
		 		case "Z":
		 			$groups[$i]["zed"]++;
					$ZCount+=1;
					break;
		 		case "H":
		 			$groups[$i]["higgs"]++;
					break;
		 		//case "NP":
		 		//	$groups[$i]["np"]++;
				//	break;
		 		case "Zoo":
		 			$groups[$i]["zoo"]++;
					break;
			}
		}
	}
}

/* Table headers are here: */
include "templates/results.tpl";

/* Initialize a value $tot to zero for every column.  This is the column total,
 * displayed below the main table.  It is  not the group total diplayed as the
 * rightmost column of the main table. */
foreach($tableData as $col){
	// Includes $tot["datagroup"] and $tot["total"]
	$colTotal[$col] = 0;
}


/* Create the HTML table rows: */
/* For each row of the table, with $i being the datagroup index and $g being the
array of column values, */
foreach($groups as $i => $g){
	/* For each datagroup_id => row_array["datagroup",...,"total"] */
	foreach($g as $k => $v){
		/* For each column label => column value pair in the row_array $g, */
		/* Add the value (the number of particles entered) to the total */
		/* Keeping tally now for reporting in the Totals Table below */
		$colTotal[$k]+=$v;
		/* Print the number of particles entered for this column as table data */
		echo '<td>'.$v.'</td>';
	}
	/* End the row. */
	echo '</tr>';
}
echo '</tbody></table></div> <div class=col-md-2></div></div>';


/* Below the Results Table is a smaller Totals Table */
include "templates/tableTotals.tpl";

include "templates/floor.tpl";

?>


