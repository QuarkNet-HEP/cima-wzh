<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

function MakeNewDB($dbName,$eventsFile){
	/* $dbName must be valid MySQL database name */

	/* Check if Events File exists */

	/* This is the same as the connection in askdb(), but does not specify a DB */
	/* Connect to	MySQL just to create new database, then disconnect and use 
		 modified askdb() */
	/* Hard to get permissions right for this, so giving up and commenting
		 this out.  Will create the new DB manually. */
	/*
	$con=mysqli_connect("localhost","cima","cim@us3r");
	if (mysqli_connect_errno($con)){
		echo "Failed to connect to MySQL: " . mysqli_connect_error();
	}

	$q="CREATE DATABASE IF NOT EXISTS `".$dbName."`";
	$res=$con->query($q);
	mysqli_close($con);
	*/

	/* Create all tables */
	/* Order is important: establish tables in order of keys that need to 
		 be referenced */
	/* 'Events' first.  It's all external data and depends on nothing else. */
	$q="CREATE TABLE `Events` (
  `event_id` int(11) NOT NULL,
  `datagroup_id` int(11) NOT NULL,
  `g_index` int(11) NOT NULL,
  `ev_no` int(11) NOT NULL,
  `mass` double NOT NULL,
  `type` VARCHAR(10) NOT NULL,
	PRIMARY KEY (`event_id`))";
	askdb($q,$dbName);

	/* 'MclassEvents' next.  The basic unit of Masterclassing. No column 
		 depends on other tables. */
	$q="CREATE TABLE `MclassEvents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `active` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`))";
	askdb($q,$dbName);

	/* 'histograms' next.  No column depends on other tables. */
	$q="CREATE TABLE `histograms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data` varchar(600) NOT NULL,
  `data_2l` varchar(600) NOT NULL DEFAULT '',
  `data_4l` varchar(600) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`))";
	askdb($q,$dbName);
	
	/* Now 'Tables'.  Tables.histogram_id refers to that Location's
		 histograms.id */
	$q="CREATE TABLE `Tables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `histogram_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `histogram_id` (`histogram_id`),
  CONSTRAINT `Tables_ibfk_1` FOREIGN KEY (`histogram_id`) REFERENCES `histograms` (`id`))";
	askdb($q,$dbName);

	/* 'EventTables' next.  This table links MclassEvents.id to Tables.id */ 
	/* Note the added PK 'id' not in original Masterclass DB */
	$q="CREATE TABLE `EventTables` (
  `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`MclassEventID` int(11) NOT NULL,
  `tableid` int(11) NOT NULL,
	CONSTRAINT `EventTables_ibfk_1` FOREIGN KEY (`MclassEventID`) REFERENCES `MclassEvents` (`id`),
  CONSTRAINT `EventTables_ibfk_2` FOREIGN KEY (`tableid`) REFERENCES `Tables` (`id`)
)";
	askdb($q,$dbName);

	/* 'TableGroups' references Tables.id and Events.datagroup_id */
	/* Note the added PK 'id' not in original Masterclass DB */
	$q="CREATE TABLE `TableGroups` (
  `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `tableid` int(11) NOT NULL,
  `datagroup_id` int(11) NOT NULL,
  `postAdded` tinyint(4) NOT NULL,
  CONSTRAINT `TableGroups_ibfk_1` FOREIGN KEY (`tableid`) REFERENCES `Tables` (`id`))";
	askdb($q,$dbName);

	/* Finally 'groupConnect' because I still don't know exactly what it's for */
	/* Consider adding PK 'id' once I figure it out */
	$q="CREATE TABLE `groupConnect` (
  `gstd` int(11) NOT NULL,
  `gbackup` int(11) NOT NULL,
  `tableid` int(11) NOT NULL,
	  CONSTRAINT `groupConnect_ibfk_1` FOREIGN KEY (`tableid`) REFERENCES `Tables` (`id`))";
	askdb($q,$dbName);

	/* Load Events */
	/* Right now $eventsFile should be absolute path */
	$q="LOAD DATA LOCAL INFILE '".$eventsFile."'
	INTO TABLE Events FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n'
	IGNORE 1 LINES";
	askdb($q,$dbName);
}


# With new askdb():
function askdb($q,$dbName){
	$con=mysqli_connect("localhost","cima","cim@us3r",$dbName);
	if (mysqli_connect_errno($con)){
		echo "Failed to connect to MySQL: " . mysqli_connect_error();
	}
	$res=$con->query($q);
	return $res;
}

?>