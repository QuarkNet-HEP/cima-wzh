<div class=row>
	<div class=col-md-12>
		<h4> Masterclass information </h4>
	</div>
</div>
<form action="MCEvents.php" method="post">

<div class=row>
	<div class=col-md-2>
		Event name:<strong> <?php echo "".$_SESSION["EventName"]; ?> </strong>
	</div>
	<div class=col-md-2>
		Event id:<strong> <?php echo "".$_SESSION["EventID"]; ?></strong>
	</div>
	<div class=col-md-2>Allow data overlab
	<?php if($_SESSION["overlab"]==1){
		echo '<input type="checkbox" name="overlab" onclick="this.form.submit() "value="o" checked="true">';
			}else{
				echo '<input type="checkbox" name="overlab" onclick="this.form.submit()" value="o">';
			}
	?> </div>
	<div class=col-md-6>
		<button type="submit" class="btn btn-default" name="finished"
						value="finished">
			finished
		</button>
	</div>
</div>

<!--
<div class=row>
	<div class=col-md-12>
		<h4> Fast configure Masterclass </h4>
	</div>
</div>
<form action="MCEvents.php" method="post">

<div class=row>
	<div class=col-md-2>
		<input type="text" name="Ntables" placeholder="Number of tables" maxlength="15" size="15">
	</div>
	<div class=col-md-2>
		<input type="text" name="NGroups" placeholder="Number of groups/table" maxlength="22" size="22">
	</div>
	<div class=col-md-8>
		<button type="submit" class="btn btn-default" name="AutoConf">Do it</div>
	</div>
</div>
</form>	
!-->
<div class=row>
	<div class=col-md-12>
		<h4> Configure Masterclass </h4>
	</div>
</div>

<div class=Cnt>
<div class=row>
	<div class=col-md-4> 
		<div class=container-fluid>

			<!-- A row for the header -->
			<div class=row>
				<div class=col-md-12>
					<strong>Enter new name -or- choose existing table</strong>
				</div>
			</div>

			<!-- A row to select a table -->
			<div class=row>
				<div class=col-md-6>
					<input type="text" name="tableName" placeholder="new table name"
								 maxlength="30" size="30">
				</div>
				<div class=col-md-2>
					<select name="Tsel">
						<?php for($i=0;$i<count($boundTables);$i++){
							echo '<option value="'.$boundTables[$i]["id"].'">'.$boundTables[$i]["displayName"].'</option>';
						}?>
					</select>
				</div>
			</div>

			<?php /* A row to select the data block for this table */ ?>
			<?php /* Awaiting time to write new JavaScript to update the
						 * "Assign Groups" table in response to this selection
						 *					   		 			 						 		- JG 25Nov2019
						 */ ?>
			<?php /*
			<div class=row>
				<div class=col-md-6>
					Select data block:
				</div>
				<div class=col-md-2>
					<select name="blockSelect">
						<option value="N5">N5</option>
						<option value="N10">N10</option>
						<option value="N25">N25</option>
						<option value="N50">N50</option>
						<option value="N100">N100</option>						
					</select>
				</div>
			</div>
			*/ ?>

			<!-- Two rows for the datagroup selection window -->
			<div class=row>
				<div class=col-md-12>
					<strong> Assign Groups </strong>
				</div>
			</div>
			<div class=row>
				<select name="Groups[]" style="width: 100%" size="10" multiple>
				<?php
					/* $freeGroups is defined in MCEvents.php */ ?>
					<?php for($i=0; $i<count($freeGroups); $i++){
							echo "<option value=".$freeGroups[$i].">".$freeGroups[$i]."</option>";
					} ?>
				</select>
			</div>

			<!-- A row for the "Create table" and "Add to table" buttons -->
			<div class=row>
				<?php
					/* TODO: UI layout
					 * Move the "Create table" button up to the "tableName" box
					 * where new tables are created.
					 */
				?>
				<div class=col-md-6>
					<button type="submit" class="btn btn-default" name="CreateT" value="CT">
						Create table
					</button>
				</div>
				<?php
					/* TODO: Error handling
					 * 1) If no table is selected in the "tableName" box, selecting
					 * "Add to table" throws an error "Undefined index: Tsel".
					 * Should instead give a "Please select a table" message.
					 * 2) If no table is selected or given in the "tableName" box,
					 * selecting "Create table" throws an error "Undefined index: Groups".
					 * Should instead give a "Please select a table, or enter a table
					 *  name" message.
					 */
				?>
				<div class=col-md-6>
					<button type="submit" class="btn btn-default" name="AddG" value="AddG">
						Add to table
					</button>
				</div>

			</div>
		</div>
	</div>

	<?php
		/* TODO: One table listed in the "Ftables" ("Free tables") window and one
		 * table listed in the "Btables" ("bound tables") window can be highlighted
		 * simultanesously.  In such a case, it isn't obvious to which table the
		 * datagroups listed in the "bg" ("Groups") window belong.
		 * UI could use a little tweaking here.
		 */
	?>
	<div class=col-md-8>
		<div class=container-fluid>
			<div class=row>
				<div class=col-md-4>
					<div class=container-fluid>
						<div class=row>
						<strong> Free tables </strong>
						</div>
						<div class=row>
							<select name="Ftables[]" id="Ftables" style="width: 100%"
											onclick="PostGroups()" size="13" multiple>
								<?php for($i=0;$i<count($indTables);$i++){
									echo "<option value=".$indTables[$i]["id"].">".$indTables[$i]["displayName"]."</option>";
								} ?>
							</select>
						</div>
						<div class=row>
							<button type="submit" class="btn btn-default" name="bind"
											value="bind">
								Add tables
							</button> 
						</div>
					</div>
				</div>

				<div class=col-md-4>
					<div class=container-fluid>
						<div class=row>
						<strong>Bound tables </strong>
						</div>
						<div class=row>
							<select name="BTables[]" id="BTables" style="width: 100%"
											onclick="PostGroups()" size="13" multiple>
								<?php for($i=0;$i<count($boundTables);$i++){
												echo "<option value=".$boundTables[$i]["id"].">".$boundTables[$i]["displayName"]."</option>";
											} ?>
							</select>
						</div>
						<div class=row>
						<button type="submit" class="btn btn-default" name="free" value="free">Remove tables</button>
						</div>
					</div>
				</div>

				<div class=col-md-4>
					<div class=container-fluid>
						<div class=row>
							<strong>Groups </strong>
						</div>
						<div class=row>
							<select name="Bgroups[]" id="bg" style="width: 100%"
											size="13" multiple>
							</select>
						</div>
						<div class=row>
							<button type="submit" class="btn btn-default" name="DelG"
											value="DelG">
								Remove Groups
							</button>
						</div>
					</div>
				</div>

			</div>
		</div>
	</div>
</form>
</div>
