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
/*
print_r('<br/>');
print_r('$_SESSION["tables"] = ');
print_r($_SESSION["tables"]);
print_r('<br/>');
*/

include 'database.php';
include 'templates/header.tpl';

/* Keys are *code* labels for the table columns.  Values are actual HTML labels */
/*
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
*/
/* For CIMA-WZH: */
$tableColumns = array("datagroup" => "Group",
	"e" => "e",
	"mu" => "&mu;",
	//"charged" => "Charged",
	"wplus" => "W+",
	"wminus" => "W-",
	"wpm" => "W&#177;",
	"neutral" => "Neutral",
	"zoo" => "Zoo",
	"total" => "Total"
	);
$tableLabels = array_keys($tableColumns);
$tableHeaders = array_values($tableColumns);

/*
$ratioColumns = array(
	"e-mu" => "e/&mu;",
	"Wp-Wm" => "W+/W-",
	"W-Z" => "W/Z"
	);
*/
/* For CIMA-WZH: */
$ratioColumns = array(
	"e-mu" => "e/&mu;",
	"Wp-Wm" => "W+/W-"
	);
$ratioHeaders = array_values($ratioColumns);

/* Define table array $tableCells[][] */
/* Rows are indexed by dataset values and columns are selectable particle states.
 * Entries are the number of times users identified the given
 * particle state within the given dataset.  Construction is based on
 * whether we're showing a single location's data or combined data.
 */
/* The dataset = X (10.6, for example) row of the table is $tableCells[X].
 * $tableCells[X] will have columns corresponding to each entry in $tableLabels
 * as well as $tableCells[X]["datagroup"] to give the leftmost column of
 * datagroup_id values as well as $tableCells[X]["total"] to give the rightmost
 * column of row totals.
 */

if(!isset($_SESSION["comb"])){
	/*** Single location ***/
	include 'templates/navbar.tpl';

	/* For a single location, we need to tabulate only those datagroups assigned
		 to the location */
	//$datagroups = GetDatagroupsById($_SESSION["databaseId"]);
	$datagroups = getDatasetsForLocation($_SESSION["databaseId"]);

	/* TODO: If there are no assigned datagroups, $tableCells may be undefined.
	 * We don't expect this in practice. */
	foreach($datagroups as $row){
		// For each row, initialize all values to zero:
		foreach($tableLabels as $column){
			$tableCells[$row][$column]=0;
		}
		/* We can go ahead and set the $tableCells[N]["datagroup"] values.
	 	 * NB that $rows is a datagroup_id and not a sequence of
		 * row numbers [1,2,3...] */
		$tableCells[$row]["datagroup"] = $row;
	}
} else{
	/** Combined locations **/
	include 'templates/Resnav.tpl';

	/* First, identify of all Location tables for this event.
	 * These are all `Tables`.`id` values assigned to the current
	 * `MclassEvents`.`id` in the `EventTables` table.
	 * $_SESSION["tables"], set in Classes.php, already contains these
	 * `Tables`.`id` values, so that's done.
	 */

	/* Get the datagroups assigned to this Masterclass's Location tables */
	/* GetGroups() returns datagroup_id (as "dg_id") and "postAdded" from
	 * the TableGroups table */
	//$g = GetGroups($_SESSION["tables"]);
	$g = getDatasetsByTable($_SESSION["tables"]);

	/* The starting value of rows is the "dg_id" of the first entry in $g */
	$start = $g[0]["dg_id"];

	$numGroups=count($g);

	for($i=0; $i<$numGroups; $i++){
		// $row is the datagroup_id, not the index of the row in the table
		$row=$g[$i]["dg_id"];
		foreach($tableLabels as $column){
			// $tableLabels = ["e", "mu", "wplus", ...]
			$tableCells[$row][$column]=0;
			$tableCells[$row]["datagroup"] = $row;
		}
	}
}


/*** Now fill $tableCells[][] with data ***/
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
				//$i=GetDatagroupId($event["id"]);
				$i=eventDataset($event["id"]);

				// Increment the 'total' column for this datagroup:
				$tableCells[$i]["total"]++;

				// Tally the "final" states
				/* If the state contains Greek letters, then $event["final"] comes
			 	 * out of the DB as the actual Greek letter, not the HTML-encoded
			 	 * version.  To compare it to an array of HTML-encoded Greek letters,
			 	 * you have to de-convert it with htmlentities():
			 	 */
				$final=htmlentities($event["final"]);
				switch ($final) {
			 		case "e_nu":
			 			$tableCells[$i]["e"]++;
						$eCount+=1;
						break;
			 		case "mu_nu":
			 			$tableCells[$i]["mu"]++;
						$muCount+=1;
						break;
			 		case "2e":
			 			//$tableCells[$i]["e"]+=2;
			 			$tableCells[$i]["e"]++;
						$eCount+=2;
						break;
			 		case "mu_mu":
			 			//$tableCells[$i]["mu"]+=2;
						$tableCells[$i]["mu"]++;
						$muCount+=2;
						break;
			 		case "4e":
			 			//$tableCells[$i]["e"]+=4;
			 			$tableCells[$i]["e"]++;
						// KC/TM: remove counting for 4-lep events - JG 14Aug2019
						//$eCount+=4;
						break;
			 		case "4mu":
			 			//$tableCells[$i]["mu"]+=4;
			 			$tableCells[$i]["mu"]++;
						// KC/TM: remove counting for 4-lep events - JG 14Aug2019
						//$muCount+=4;
						break;
			 		case "2e_2mu":
			 			//$tableCells[$i]["e"]+=2;
			 			//$tableCells[$i]["mu"]+=2;
			 			$tableCells[$i]["e"]++;
			 			$tableCells[$i]["mu"]++;
						// KC/TM: remove counting for 4-lep events - JG 14Aug2019
						//$eCount+=2;
						//$muCount+=2;
						break;
			 		/*case "2gam":
			 			$tableCells[$i]["twogam"]++;
						break;
					case "Zoo":
			 			$tableCells[$i]["zoo"]++;
						break;*/
				}

				// Tally the "primary" states
				$primary=htmlentities($event["primary"]);
				/*
				switch ($primary) {
			 		case "W":
			 			$tableCells[$i]["wplain"]++;
						$WCount+=1;
						break;
			 		case "W+":
			 			$tableCells[$i]["wplus"]++;
						$WCount+=1;
						$WplusCount+=1;
						break;
			 		case "W-":
			 			$tableCells[$i]["wminus"]++;
						$WCount+=1;
						$WminusCount+=1;
						break;
			 		case "Z":
			 			$tableCells[$i]["zed"]++;
						$ZCount+=1;
						break;
			 		case "H":
			 			$tableCells[$i]["higgs"]++;
						break;
			 		//case "NP":
			 		//	$tableCells[$i]["np"]++;
					//	break;
			 		case "Zoo":
			 			$tableCells[$i]["zoo"]++;
						break;
				}
				*/
				/* For CIMA-WZH: */
				switch ($primary) {
			 		//case "charged":
			 		//	$tableCells[$i]["charged"]++;
					//	break;
			 		case "W_pm":
			 			$tableCells[$i]["wpm"]++;
						$WCount+=1;
						break;
			 		case "W+":
			 			$tableCells[$i]["wplus"]++;
						//$WCount+=1;
						$WplusCount+=1;
						break;
			 		case "W-":
			 			$tableCells[$i]["wminus"]++;
						//$WCount+=1;
						$WminusCount+=1;
						break;
			 		case "neutral":
			 			$tableCells[$i]["neutral"]++;
						break;
			 		case "zoo":
			 			$tableCells[$i]["zoo"]++;
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
			//$i=GetDatagroupId($event["id"]);
			$i=eventDataset($event["id"]);
			$tableCells[$i]["total"]++;

			// Tally the "final" states
			/* If the state contains Greek letters, then $event["final"] comes
		 	 * out of the DB as the actual Greek letter, not the HTML-encoded
		 	 * version.  To compare it to an array of HTML-encoded Greek letters,
		 	 * you have to de-convert it with htmlentities():
		 	 */
			$final=htmlentities($event["final"]);
			switch ($final) {
				case "e_nu":
					$tableCells[$i]["e"]++;
					$eCount+=1;
					break;
				case "mu_nu":
		 			$tableCells[$i]["mu"]++;
					$muCount+=1;
					break;
		 		case "2e":
		 			//$tableCells[$i]["e"]+=2;
		 			$tableCells[$i]["e"]++;
					$eCount+=2;
					break;
		 		case "mu_mu":
		 			//$tableCells[$i]["mu"]+=2;
		 			$tableCells[$i]["mu"]++;
					$muCount+=2;
					break;
		 		case "4e":
		 			//$tableCells[$i]["e"]+=4;
		 			$tableCells[$i]["e"]++;
					// KC/TM: remove counting for 4-lep events - JG 14Aug2019
					//$eCount+=4;
					break;
		 		case "4mu":
		 			//$tableCells[$i]["mu"]+=4;
		 			$tableCells[$i]["mu"]++;
					// KC/TM: remove counting for 4-lep events - JG 14Aug2019
					//$muCount+=4;
					break;
		 		case "2e_2mu":
		 			//$tableCells[$i]["e"]+=2;
		 			//$tableCells[$i]["mu"]+=2;
		 			$tableCells[$i]["e"]++;
		 			$tableCells[$i]["mu"]++;
					// KC/TM: remove counting for 4-lep events - JG 14Aug2019
					//$eCount+=2;
					//$muCount+=2;
					break;
		 		/*case "2gam":
		 			$tableCells[$i]["twogam"]++;
					break;
				case "Zoo":
		 			$tableCells[$i]["zoo"]++;
					break;*/
			}

			// Tally the "primary" states
			$primary=htmlentities($event["primary"]);
			/*
			switch ($primary) {
		 		case "W":
		 			$tableCells[$i]["wplain"]++;
					$WCount+=1;
					break;
		 		case "W+":
		 			$tableCells[$i]["wplain"]++;
		 			$tableCells[$i]["wplus"]++;
					$WCount+=1;
					$WplusCount+=1;
					break;
		 		case "W-":
		 			$tableCells[$i]["wplain"]++;
		 			$tableCells[$i]["wminus"]++;
					$WCount+=1;
					$WminusCount+=1;
					break;
		 		case "Z":
		 			$tableCells[$i]["zed"]++;
					$ZCount+=1;
					break;
		 		case "H":
		 			$tableCells[$i]["higgs"]++;
					break;
		 		//case "NP":
		 		//	$tableCells[$i]["np"]++;
				//	break;
		 		case "Zoo":
		 			$tableCells[$i]["zoo"]++;
					break;
			}
			*/
			/* For CIMA-WZH: */
			switch ($primary) {
				//case "charged":
				//	$tableCells[$i]["charged"]++;
				//	break;
			 	case "W_pm":
			 		$tableCells[$i]["wpm"]++;
					$WCount+=1;
					break;
				case "W+":
			 		$tableCells[$i]["wplus"]++;
					//$WCount+=1;
					$WplusCount+=1;
					break;
			 	case "W-":
			 		$tableCells[$i]["wminus"]++;
					//$WCount+=1;
					$WminusCount+=1;
					break;
				case "neutral":
					$tableCells[$i]["neutral"]++;
					break;
				case "zoo":
					$tableCells[$i]["zoo"]++;
					break;
			}
		}
	}
}

/* Table headers are here: */
include "templates/results.tpl";

/* Initialize a value $tot to zero for every column.  This is the column total,
 * displayed below the main table.  It is not the group total diplayed as the
 * rightmost column of the main table. */
foreach($tableLabels as $col){
	// Includes $tot["datagroup"] and $tot["total"]
	$colTotal[$col] = 0;
}


/* Create the HTML table rows: */
/* For each row of the table, with $i being the datagroup index and $g being the
array of column values, */
foreach($tableCells as $i => $g){
	/* Open the row */
	echo '<tr>';
	/* For each datagroup_id => row_array["datagroup",...,"total"] */
	foreach($g as $k => $v){
		/* For each column label => column value pair in the row_array $g, */
		/* 1) Print the column value as table data */
		echo '<td>'.$v.'</td>';

		/* 2) Add the value (the number of particles entered) to the total */
		/* Keeping tally now for reporting in the Totals Table below */
		$colTotal[$k]+=$v;
	}
	/* End the row. */
	echo '</tr>';
}
echo '</tbody></table></div> <div class=col-md-2></div></div>';


/* Below the Results Table is a smaller Totals Table */
include "templates/tableTotals.tpl";

include "templates/floor.tpl";

?>


