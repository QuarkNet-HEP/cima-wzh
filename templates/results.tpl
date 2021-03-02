<div class="row">
	<div class="col-md-1"></div>
	<div class="col-md-10"><?php
		if(isset($_SESSION["comb"])){
			/* If we are combining data: */
			echo'<strong> Tables: </strong>  <span name="database"> '; 
			for($i=0;$i<count($_SESSION["tables"]);$i++){
				$t=GetTableByID($_SESSION["tables"][$i]);
				echo $t["displayName"]." ";
			}
		}else{
			/* If we aren't combining data: */
			echo '<strong> Masterclass: </strong>
			<span name="database"> '.$_SESSION["MasterClass"].'</span>'; 
		}
	?></div>
</div>

<?php
if(!isset($_SESSION["comb"])){
	/* If we aren't combining data: */
	echo'<div class=row>
		<div class="col-md-1"></div>
		<div class="col-md-10">
			<strong> location: </strong>
			<span name="database"> '.$_SESSION["displayLocation"].'</span>
		</div>';
  /* One unclosed <div> here */
}?>


<div class="Cnt">
	<div class="row">
		<div class="col-md-2"></div>
		<div class="col-md-8">
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

