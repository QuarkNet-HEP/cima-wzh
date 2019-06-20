<?php
session_start();

/* This page updates the MakeCharts.js mass histograms when the user clicks
 *   on a bin.
 */

// Gives GetHistogramData() and UpdateHistogram()
include 'database.php';

// 'id' is sent via POST from MakeCharts.js's update()
// HTML 'id' attibutes of canvases are 'chart1', 'chart2'
$chartId=$_POST["id"];
// Obtain the existing histogram data as $data based on which chart was clicked
$temp=GetHistogramData($_SESSION["database"]);
if($chartId == "chart1"){
	$chart="data_2l";
}elseif($chartId == "chart2"){
	$chart="data_4l";
}else{
  // The original
	$chart="data";
}

$data=explode(";",$temp[$chart]);

// If we're not deleting data, increment the value at the given index.
// Otherwise, decrement it.
if($_POST["d"]!=1){
	$data[$_POST["x"]]++;
}elseif($data[$_POST["x"]]!=0){
	$data[$_POST["x"]]--;
}

// Re-plode the data into a single variable $d 
$d=implode(";",$data);
// Call the UpdateHistogram() function database.php to change the
//   recorded histogram data
UpdateHistogram($chart,$d,$_SESSION["currentHist"]["id"]);
// Set the Session's current histogram data to $d
$_SESSION["currentHist"][$chart]=$d;

// This echo sends data back to the AJAX call in MakeCharts.uhist() that is then passed to the 'success' function as 'data':
echo $d;

?>
