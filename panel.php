
<?php
	/* To test in isolation, uncomment either this line or the following HTML block: */
	include 'templates/header.tpl';
	/* echo '<html>
  <head>
 		<title> CIMA </title>
		<meta name="author" content="Michael Soiron">
		<link href="bootstrab/css/bootstrap.min.css" rel="stylesheet">
		<link href="bootstrab/css/bootstrap-theme.min.css" rel="stylesheet">
		<link href="bootstrab/css/style.css" rel="stylesheet">
		<link rel="stylesheet" type="text/css" href="panel.css">
		<script src="js/JQuery.js" ></script>
		<script src="bootstrab/js/bootstrap.js" ></script>
		<script src="js/fcns.js" ></script>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="utf-8">
  </head>
  
  <body>'	*/
?>

<!-- Data Entry Panel -->
<div class="container container-fluid">
<div class="col-md-3 subpanel" id="eventdata">
	<div class="panelheader">Select Event</div>
	<div id="indexSelect" style="border:1px solid transparent;">
		Event index:
		<select id="EvSelOver" name="CustomEvent" onchange="this.form.submit()" style="float:right;">
		 	<option id="SelEvent" selected>
				<?php
				if(isset($event)){
					echo $event['id']."";
				}
			echo ' </option>';

			if(isset($event)){
				for($i=0;$i<count($freeEvents);$i++){
					if($freeEvents[$i]!=$event['id']){
						echo '<option> '.$freeEvents[$i].'</option>';
					}
				}
			}
				?>
		</select>
	</div><!-- End indexSelect -->
	<div id="eventNumber" style="border:1px solid transparent;">
		Event number:
		<span id="Eventid" style="float:right;">
			<?php
			if(isset($event)){
				echo calcEv($event['id'])."";
			}
			?>
		</span>
	</div><!-- End eventNumber -->
</div>
<div class="divider"></div>
<div class="col-md-3 subpanel" id="finaldata">
	<div class="panelheader">Final State</div>
	<div class="selectorRow">
		<div class="selector-2">
			<input type="checkbox"
						 <?php echo 'onclick="SelP(this,'.round($event["mass"],3).')"';?>
						 id="e-nu" name="electron-nu" value="e_nu">
				e &nu;
		</div>
		<div class="selector-2">
			<input type="checkbox"
						 <?php echo 'onclick="SelP(this,'.round($event["mass"],3).')"';?>
						 id="mu-nu" name="muon-nu" value="mu_nu">
				&mu; &nu;
		</div>
	</div><!-- End row 1-->

	<div class="selectorRow">
		<div class="selector-2">
			<input type="checkbox"
						 <?php echo 'onclick="SelP(this,'.round($event["mass"],3).')"';?>
						 id="e-e" name="electron-electron" value="e_e">
				e e
		</div>
		<div class="selector-2">
			<input type="checkbox"
						 <?php echo 'onclick="SelP(this,'.round($event["mass"],3).')"';?>
						 id="mu-mu" name="muon-muon" value="mu_mu">
				&mu; &mu;
		</div>
	</div> <!-- End row 2-->

	<div class="selectorRow">
		<div class="selector-2">
			<input type="checkbox"
						 <?php echo 'onclick="SelP(this,'.round($event["mass"],3).')"';?>
						 id="4-e" name="four-electron" value="4_e">
				4e
		</div>
		<div class="selector-2">
			<input type="checkbox"
						 <?php echo 'onclick="SelP(this,'.round($event["mass"],3).')"';?>
						 id="4-mu" name="four-muon" value="4_mu">
				4&mu;
		</div>
	</div> <!-- End row 3-->

	<div class="selectorRow">
		<div class="selector-2">
			<input type="checkbox"
						 <?php echo 'onclick="SelP(this,'.round($event["mass"],3).')"';?>
						 id="2e-2mu" name="two-electron-two-muon" value="2e_2mu">
				2e 2&mu;
		</div>
		<div class="selector-2">
			<input type="checkbox"
						 <?php echo 'onclick="SelP(this,'.round($event["mass"],3).')"';?>
						 id="2-gam" name="two-gamma" value="2_gam">
				2&gamma;
		</div>
	</div> <!-- End row 4-->

	<div class="selectorRow">
		<div class="selector-1">
			<input type="checkbox"
						 <?php echo 'onclick="SelP(this,'.round($event["mass"],3).')"';?>
						 id="other" name="other" value="other">
				other
		</div>
	</div> <!-- End row 5-->
</div>
<div class="divider"></div>
<div class="col-md-3 subpanel" id="primarydata">
	<div class="panelheader">Primary State</div>
	<div class="selectorRow">
		<div class="selector-3">
			<input type="checkbox"
						 <?php echo 'onclick="SelP(this,'.round($event["mass"],3).')"';?>
						 id="w-x" name="W" value="w_x">
				W
		</div>
		<div class="selector-3">
			<input type="checkbox"
						 <?php echo 'onclick="SelP(this,'.round($event["mass"],3).')"';?>
						 id="w+" name="W-plus" value="W+">
				W+
		</div>
		<div class="selector-3">
			<input type="checkbox"
						 <?php echo 'onclick="SelP(this,'.round($event["mass"],3).')"';?>
						 id="w-" name="W-minus" value="W-">
				W-
		</div>
	</div> <!-- End row 1-->

	<div class="selectorRow">
		<div class="selector-3">
			<input type="checkbox"
						 <?php echo 'onclick="SelP(this,'.round($event["mass"],3).')"';?>
						 id="z" name="Zed" value="Z">
				Z
		</div>
		<div class="selector-3">
			<input type="checkbox"
						 <?php echo 'onclick="SelP(this,'.round($event["mass"],3).')"';?>
						 id="h" name="Higgs" value="H">
				H
		</div>
		<div class="selector-3">
			<input type="checkbox"
						 <?php echo 'onclick="SelP(this,'.round($event["mass"],3).')"';?>
						 id="np" name="neutral-particle" value="NP">
				NP
		</div>
	</div><!-- End row 2-->

	<div class="selectorRow">
		<div class="selector-2">
			<input type="checkbox"
						 <?php echo 'onclick="SelP(this,'.round($event["mass"],3).')"';?>
						 id="J-Psi" name="JPsi" value="J_Psi">
				J/&Psi;
		</div>
		<div class="selector-2">
			<input type="checkbox"
						 <?php echo 'onclick="SelP(this,'.round($event["mass"],3).')"';?>
						 id="ups" name="Upsilon" value="upsilon">
				&Upsilon;
		</div>
	</div> <!-- End row 3-->

	<div class="selectorRow">
		<div class="selector-1">
			<input type="checkbox"
						 <?php echo 'onclick="SelP(this,'.round($event["mass"],3).')"';?>
						 id="other" name="other" value="other">
				other
		</div>
	</div> <!-- End row 4-->
</div>
<div class="divider"></div>
<div class="col-md-3 subpanel" id="massandfinish" style="width:20%;">
	<div class="panelheader">Enter Mass</div>
		<div> Mass: <span id="mass" style="float:right;"></span></div>
		<div style="float:bottom;">
			<button type="submit" disabled="true" id="next" name="fin"
							class="btn btn-primary btn-lg">
				Next
			</button>
		</div>
</div>
</div>
<!-- End Data Entry Panel -->


</body>
</html>