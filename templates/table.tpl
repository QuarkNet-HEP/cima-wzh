<form action=DataTable.php method="post">
	<div class=row>
		<div class=col-md-4>
			<div class=container-fluid>
				<div class=row>
					<div class=col-md-1></div>
					<div class=col-md-10><strong> Masterclass: </strong>
						<?php echo
							'<span name="database">'.$_SESSION["MasterClass"].'</span>';
						?>
					</div>
				</div>
				<div class=row>
					<div class=col-md-1></div>
					<div class=col-md-10><strong> location: </strong>
						<?php echo
							'<span name="database"> '.$_SESSION["displayLocation"].'</span>';
						?>
					</div>
				</div>
				<div class=row>
					<div class=col-md-1></div>
					<div class=col-md-10><strong> Group: </strong>
						<?php echo
							'<span name="groupNo"> '.$_SESSION["groupNo"].'</span>';
							if(isset($_SESSION["backup"])){ echo " as backup";}
						?>
					</div>
					<div class=col-md-8></div>
				</div>
			</div>
		</div>
		<div class=col-md-8>
			<?php
				if(isset($_SESSION["edit"])){
					echo '<button type="submit" id="fedit"';
					if(isset($event)){echo 'disabled="true"';}
					echo 'class="btn btn-default" name="fedit" value="1">
						finish editing
					</button>';
				}
			?>
		</div>
	</div>

	<!-- Data Entry Panel -->
	<div class="panel-container container-fluid">
		<div class="col-md-3 subpanel" id="eventdata">
			<div class="panelheader">Select Event</div>
			<div id="indexSelect" style="border:1px solid transparent;">
				Event index:
				<select id="EvSelOver" name="CustomEvent" onchange="this.form.submit()">
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
				<span id="Eventid">
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
					<input type="radio" class="finalSelect"
						 		 onclick="SelectState(this)"
						 		 id="e-nu" name="finalState" value="e&nu;">
					e &nu;
				</div>
				<div class="selector-2">
					<input type="radio" class="finalSelect"
						 		 onclick="SelectState(this)"
						 		 id="mu-nu" name="finalState" value="&mu;&nu;">
					&mu; &nu;
				</div>
			</div><!-- End row 1-->

			<div class="selectorRow">
				<div class="selector-2">
					<input type="radio" class="finalSelect"
						 		 onclick="SelectState(this)"
						 		 id="e-e" name="finalState" value="ee">
					e e
				</div>
				<div class="selector-2">
					<input type="radio" class="finalSelect"
						 		 onclick="SelectState(this)"
						 		 id="mu-mu" name="finalState" value="&mu;&mu;">
					&mu; &mu;
				</div>
			</div> <!-- End row 2-->

			<div class="selectorRow">
				<div class="selector-2">
					<input type="radio" class="finalSelect"
						 		 onclick="SelectState(this)"
						 		 id="4-e" name="finalState" value="4e">
					4e
				</div>
				<div class="selector-2">
					<input type="radio" class="finalSelect"
						 		 onclick="SelectState(this)"
						 		 id="4-mu" name="finalState" value="4&mu;">
					4&mu;
				</div>
			</div> <!-- End row 3-->

			<div class="selectorRow">
				<div class="selector-2">
					<input type="radio" class="finalSelect"
						 		 onclick="SelectState(this)"
						 		 id="2e-2mu" name="finalState" value="2e 2&mu;">
					2e 2&mu;
				</div>
				<div class="selector-2">
					<input type="radio" class="finalSelect"
						 onclick="SelectState(this)"
						 id="2-gam" name="finalState" value="2&gamma;">
					2&gamma;
				</div>
			</div> <!-- End row 4-->

			<div class="selectorRow">
				<div class="selector-1">
					<input type="radio" class="finalSelect"
						 		 onclick="SelectState(this)"
						 		 id="zoo" name="finalState" value="Zoo">
					Zoo
				</div>
			</div> <!-- End row 5-->
		</div>

		<div class="divider"></div>
		<div class="col-md-3 subpanel" id="primarydata">
			<div class="panelheader">Primary State</div>
			<div class="selectorRow">
			---
				<div class="selector-3">
					<input type="radio" class="primarySelect"
						 		 onclick="SelectState(this)"
						 		 id="w-x" name="primaryState" value="W">
					W
				</div>
				<div class="selector-3">
					<input type="radio" class="primarySelect"
						 		 onclick="SelectState(this)"
						 		 id="w+" name="primaryState" value="W+">
					W+
				</div>
				<div class="selector-3">
					<input type="radio" class="primarySelect"
						 		 onclick="SelectState(this)"
						 		 id="w-" name="primaryState" value="W-">
					W-
				</div>
			</div> <!-- End row 1-->

			<div class="selectorRow">
				<div class="selector-3">
					<input type="radio" class="primarySelect"
						 		 onclick="SelectState(this)"
						 		 id="z" name="primaryState" value="Z">
					Z
				</div>
				<div class="selector-3">
					<input type="radio" class="primarySelect"
						 		 onclick="SelectState(this)"
						 		 id="h" name="primaryState" value="H">
					H
				</div>
				<div class="selector-3">
					<input type="radio" class="primarySelect"
						 		 onclick="SelectState(this)"
						 		 id="np" name="primaryState" value="NP">
					NP
				</div>
			</div><!-- End row 2-->

			<div class="selectorRow">
				<div class="selector-1">
					<input type="radio" class="primarySelect"
						 		 onclick="SelectState(this)"
						 		 id="other" name="primaryState" value="Zoo">
					Zoo
				</div>
			</div> <!-- End row 3-->

		</div><!-- End div "primarydata" -->

		<div class="divider"></div>

		<div class="col-md-3 subpanel" id="massandfinish" style="width:20%;">
			<div class="panelheader">Enter Mass</div>
			<span class="massInput" id="massInput"
						style="color:grey; border:1px solid transparent;">
				<!--Mass:-->
				<input type="text" name="massEntry" class="massEntry" id="enterMass"
							 size="3%" disabled="disabled">
				GeV/c²
				<!--<span id="mass" style="float:right;"></span>-->
			</span>
			<span style="display: inline-block; padding-top:5%; padding-bottom:5%;	
									border:1px solid transparent;">
				<button type="submit" disabled="true" id="next" name="fin"
								class="btn btn-primary btn-lg">
					Next
				</button>
			</span>
		</div>
	</div>
<!-- is this an extra close-div or did I miss one? -->
</div>
<!-- End Data Entry Panel -->

</form>
