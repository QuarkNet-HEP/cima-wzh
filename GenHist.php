<?php
session_start();

/* This page updates the MakeCharts.js mass histograms using data from the
 * database whenever the user clicks on any bin.
 * It is an alternative to AddHistData.php, which increments individual
 * histogram bars based on user clicks.
 */

// Gives GenerateHistogramData()
include 'database.php';

// 'id' is sent via POST from MakeCharts.js's update()
// HTML 'id' attibutes of canvases are 'chart1', 'chart2'
$chartId=$_POST["id"];

// Obtain the existing histogram data as $data based on which chart was clicked.
// $temp will be a 2-element keyed array whose keys are "data_2l" and "data_4l"
// and whose values are semicolon-separated strings of the respective data.
$data=GenerateHistogramData($_SESSION["database"]);

// Determine which chart was clicked
if($chartId == "chart1") {
	$chart="data_2l";
} elseif($chartId == "chart2") {
	$chart="data_4l";
}

// Call the UpdateHistogram() function database.php to change the
//   recorded histogram data
//UpdateHistogram($chart,$data,$_SESSION["currentHist"]["id"]);

// Set the Session's current histogram data to $data
$_SESSION["currentHist"][$chart]=$data;

// This echo sends data back to the AJAX call in MakeCharts.uhist() that is then passed to the 'success' function as 'data':
echo $data[$chart];

?>