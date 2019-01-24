<!-- This file has one more <div> than it has </div>.  
		 Is this correct or an error? -->
<div class=Cnt>
	<div class=row>
		<div class=col-md-12>
			<strong> Tables: </strong>
			<span name="groupNo"> 
	<?php 
		foreach($_SESSION["tables"] as $id){ 
			$t=GetTableByID($id); 
			/* Strip the location prefix from the Location table name: */
			//echo $t["name"].' '; }
			echo DisplayLocation($t["name"]).' '; }
	?>
			</span>
		</div>
	</div>

	<!-- Histogram container 1 -->
	<div class=container style="display: inline-block;">
		<div class=row style="padding-top: 5%;">
			<div class=col-md-1 align="center">
				<strong>Events / 2GeV</strong>
			</div>
			<div class=col-md-11>
				<canvas id="chart1" width="1000" height="400"></canvas>
			</div>
		</div>
		<div class=row>
			<div class=col-md-10></div>
			<div class=col-md-2>
				<strong> Mass bin (GeV) </strong>
			</div>
		</div>
	</div>
	<!-- End histogram container 1 -->
	
	<!-- Histogram container 2 -->
	<div class=container style="display: inline-block;">
		<div class=row style="padding-top: 5%;">
			<div class=col-md-1 align="center">
				<strong>Events / 2GeV</strong>
			</div>
			<div class=col-md-11>
				<canvas id="chart2" width="1000" height="400"></canvas>
			</div>
		</div>
		<div class=row>
			<div class=col-md-10></div>
			<div class=col-md-2>
				<strong> Mass bin (GeV) </strong>
			</div>
		</div>
	</div>
	<!-- End histogram container 2 -->



	
