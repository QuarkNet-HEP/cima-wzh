<!-- Totals footer table -->
<div class=row>
	<div class=col-md-2></div>
	<div class=col-md-10>
		<h2> Total: </h2>
	</div>
</div>

<div class=row>
	<div class=col-md-2></div>
	<div class=col-md-8>
		<table class="table">
			<thead>
				<tr>
					<?php
						/* $tableHeaders defined in results.php */
						foreach($tableHeaders as $header){
							echo "<th> ".$header." </th>";
						}
					?>
				</tr>
			</thead>
			<tbody>
				<tr>
					<?php
					/* For the "Groups" column */
					echo '<td>All</td>';
					/* For the others, $tot is defined while creating the
						 main table in results.php */
					/* Remove the "datagroup" element, since that's silly to total */
					unset($colTotal["datagroup"]);
					foreach($colTotal as $v){
						echo '<td>'.$v.'</td>';
					}
					?>
				</tr>
			</tbody>
		</table>
	</div>
	<div class=col-md-2></div>
</div>
<!-- Two extra divs? -->
</div></div>


<!-- Ratios footer table -->
<div class=row>
	<div class=col-md-2></div>
	<div class=col-md-10>
		<h2> Ratios: </h2>
	</div>
</div>

<div class=row>
	<div class=col-md-2></div>
	<div class=col-md-4>
		<table class="table">
			<thead>
				<tr>
					<th>e/&mu;</th>
					<th>W+/W-</th>
					<th>W/Z </th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<?php
					if($muCount!=0){
						echo '<td>'.round($eCount/$muCount,2).'</td>';
					}else{
						echo '<td> not defined</td>';
					}
					if($WminusCount!=0){
						echo '<td>'.round($WplusCount/$WminusCount,2).'</td>';
					}else{
						echo '<td> not defined</td>';
					}
					if($ZCount!=0){
						echo '<td>'.round($WCount/$ZCount,2).'</td>';
					}else{
						echo '<td> not defined</td>';
					}
					?>
				</tr>
			</tbody>
		</table>
	</div>
	<div class=col-md-6></div>
</div>
<!-- Two extra divs? -->
</div></div>

