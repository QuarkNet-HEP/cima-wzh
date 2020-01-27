<?php
include "database.php";

$tables=explode(",",$_POST["tables"]);
//$groups=GetGroups($tables);
$groups = getDatasetsByTable($tables);

if($_POST["source"]=="index"){
		echo '<div class=row align="center">
				 		<strong>Choose your data file</strong>
					</div>';
}

for($i=0;$i<count($groups);$i++){
		if($_POST["source"]=="Backend"){

				echo '<option>'.$groups[$i]["ds_id"].'</option>';

		} elseif($_POST["source"]=="index") {

				echo '<div class=row>
					 			<div class=col-md-3></div>
								<div class=col-md-6 style="cursor: pointer;" align="center"
									 	 id="'.$groups[$i]["ds_id"].'"
									 	 onmouseover="OverCol(this)" onmouseout="OffCol(this)"
									 	 onclick="GSel(this)">'.$groups[$i]["ds_id"].'</div>
							</div>';
		}

}
