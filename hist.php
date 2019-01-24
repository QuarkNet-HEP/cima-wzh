<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "database.php";

session_start();
print_r($_SESSION);
print_r('<br/>');
print_r('<br/>');
print_r($_POST);
print_r('<br/>');
$script=1;

include 'templates/header.tpl';

echo '<script src="js/Chart.js"> </script>';
/*echo '<script src="js/Chart-2.7.3.js"> </script>';*/
echo '<script src="js/MakeCharts.js"> </script>';

/* $_SESSION["comb"] is set in Classes.php.
	 It indicates that we want to combine data from all sections.
	 If it's *not* set, then we want data only from the current group. */
if(!isset($_SESSION["comb"])){
		/* If no database is set, redirect to index.php (where databases are
			 selected) */
		if(!isset($_SESSION["database"])){
				header("Location: index.php");
		}
		include 'templates/navbar.tpl';
		include 'templates/hist.tpl';

		/* GetHistDataFortable() is a database.php function.
			 Argument expected to be a table name.
			 Returns id,data from histograms */
		/* $datax=GetHistDataForTable($_SESSION["database"]); */
		$datax=GetHistogramData($_SESSION["database"]);
		/* $datax is a 2-element dictionary for GetHistDataForTable()
			 $datax["id"] is the histogram id
			 $datax["data"] is the histogram datastring */
		/* $datax is a 3-element dictionary for GetHistogramData()
			 $datax["id"] is the histogram id
			 $datax["data_2l"] is the 2-lepton histogram datastring
			 $datax["data_4l"] is the 4-lepton histogram datastring */
		$_SESSION["currentHist"]=$datax;
}else{
		/* If "comb" *is* set: */
		include 'templates/Resnav.tpl';
		include 'templates/histBackend.tpl';
		if(isset($_SESSION["tables"])){
				foreach($_SESSION["tables"] as $t){
						$table=GetTableByID($t);
						//$pretemp=GetHistDataForTable($table["name"]);
						$pretemp=GetHistogramData($table["name"]);
						$temp_2l=explode(";",$pretemp["data_2l"]);
						$temp_4l=explode(";",$pretemp["data_4l"]);
						/* TODO: Would be nice to put the following into a separate function to
							 generalize to any number of datasets instead of repeating twice */
						if(!isset($data_2l)){
								$data_2l=$temp_2l;
						}else{
								for($i=0;$i<count($temp_2l);$i++){
										$data_2l[$i]=$data_2l[$i]+$temp_2l[$i];
								}
						}
						if(!isset($data_4l)){
								$data_4l=$temp_4l;
						}else{
								for($i=0;$i<count($temp_4l);$i++){
										$data_4l[$i]=$data_4l[$i]+$temp_4l[$i];
								}
						}
				}
				$datax["data_2l"]=implode(";",$data_2l);
				$datax["data_4l"]=implode(";",$data_4l);
		}
}

/* MakeHist() is defined in js/MakeCharts.js */
/* The following are general-parameter calls, if they're ever necessary.
	 Commented out because they're not, for now.
	 // Width of histogram bins in GeV
	 $binWidth=2;
	 // HTML 'id' of charts' canvas elements
	 $canvasId1='chart1';
	 $canvasId2='chart2';
	 echo '<script> MakeHist("'.$datax["data"].'",'.$binWidth.',"'.$canvasId1.'"); </script>';
	 echo '<script> MakeHist("'.$datax["data"].'",'.$binWidth.',"'.$canvasId2.'"); </script>';
 */
/* These are sufficent for now: */
/*
echo '<script> MakeHist("'.$datax["data_2l"].'",2,"chart1"); </script>';
echo '<script> MakeHist("'.$datax["data_4l"].'",2,"chart2"); </script>';
*/
echo '<script> MakeHist("'.$datax["data_2l"].'",1,111,2,"chart1"); </script>';
echo '<script> MakeHist("'.$datax["data_4l"].'",81,211,2,"chart2"); </script>';

include 'templates/floor.tpl';
?>