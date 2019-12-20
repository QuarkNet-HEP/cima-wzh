var Mmass=0;

/* This prints the global variable 'Mmass' to the element with id 'mass' if
 * either the checkbox with id="H" or id="Z" is checked, circumventing the 
 * need for the user to enter it for this case.
 * Prior to CIMA-WZH, it was used only by fcns.SelP().
 * For CIMA-WZH, none of these things exist, and this is not used. */
function printMass(mass){
	if(Mmass!=0){
		mass=Mmass;
	}
	var HiggsChecked=document.getElementById("H").checked;
	var ZChecked=document.getElementById("Z").checked;
	
	if(HiggsChecked || ZChecked){
		$( "#mass" ).html( mass );
	}else{
		$( "#mass" ).html( " " );
	}

}


/* If state="primary", returns boolean of whether a final state is checked.
 * If state="final", returns boolean of whether a primary state is checked. */
function check(state){
	if(state=="primary"){
			return (document.getElementById("e").checked || document.getElementById("mu").checked);
	}
		
	if(state=="final"){
			return (document.getElementById("H").checked || document.getElementById("Z").checked || document.getElementById("W").checked
								|| document.getElementById("Wp").checked || document.getElementById("W-").checked || document.getElementById("Zoo").checked);
	}
}


/* This function is activated when a particle-state checkbox is clicked.
 * When a checkbox is clicked, other checkboxes of the same type (final/primary) 
 * are disabled.
 * If the primary-state Higgs or Zoo checkbox is clicked, we additionally 
 * uncheck and disable the final-state checkboxes.
 * The canonical mass is displayed if it's not one the user must enter.
 * If both primary and final states have been selected, or if Higgs or Zoo 
 * has been selected (which don't require final states to be selected), we 
 * activate the "Next" button to submit the selection. 
 */
function SelP(element,mass){
		// I think that 'prim' and 'fin' are reversed in usage in this function.
		// 'prim' seems to be used to indicate that a final state has been selected.
		// 'fin' seems to be used to indicate that a primary state has been selected.
		var prim=false;
		var fin=false;
		checked=element.checked;

		/* If the input checkbox is 'mu' or 'e' (a final state), set 'prim'
		 * equal to its boolean 'checked' status and set 'fin' to 'true' if a 
		 * primary state is also checked. */
		if(element.id=="mu" || element.id=="e"){
				var arr=["mu","e"];
				prim=checked;
				fin=check("final");
		}else{
				/* If the input checkbox is 'Zoo' or 'H' (both primary states),
				 * set the 'mu' and 'e' checkboxes to unchecked and disabled.
				 * Set 'prim' to the boolean 'checked' status of the input checkbox. */
				if(element.id=="Zoo" || element.id=="H"){
						/* Uncheck and disable final-state checkboxes for these primary 
						 * state selections */
						$("#mu").prop("checked",false);
						$("#e").prop("checked",false);
						$("#mu").prop("disabled",checked);
						$("#e").prop("disabled",checked);
						/* For the purposes of activating the "Next" button, we say the
						 * final state has been "checked" for Zoo or H checked, even though 
						 * they're disabled. */
						prim=checked;
				}else{
						/* If the input checkbox is not 'mu', 'e', 'Zoo', or 'H', then 
						 * it's a different primary state.
						 * Set 'prim' to 'true' if a final state is also checked. */
						prim=check("primary");
				}

				// Display the canonical mass of the state if it's not a user-entered one.
				printMass(mass);

				// Define an array of primary states
				var arr=["H","W","W-","Wp","Z","Zoo"];

				// Set 'prim' to the boolean 'checked' status of the input checkbox.
				fin=checked;
		}

		/* If the input checkbox is a final state, 'arr' is an array of final states.
		 * If the input checkbox is a primary state, 'arr' is an array of primary 
		 * states. 
		 * For each of these that is *not* the input state, we disable it if the 
		 * input checkbox was checked. */
		for(var i=0;i<arr.length;i++){
				if(element.id!=arr[i]){
						$("#"+arr[i]).prop("disabled", checked);
				}
		}

		/* If at this point we've found that both primary and final states have
		 * been selected, enable the "Next" button.  Otherwise keep it disabled. */
		if(prim && fin){
				$("#next").prop("disabled", false);
		}else{
				$("#next").prop("disabled", true);
		}
}


// Added Nov2018 for WZH upgrades.  A simpler version of SelP()
/* Update 18Dec2018: For some reason I decided against this in favor of the 
 * function that follows.  Leaving code in case it's useful in the future. - JG
 */
/*
function SelectState_unused(element){

	// Base case: an unchecked radio button is checked.
	// If it's 2-lepton or 4-lepton, input mass for histogram
	// 'element.checked' is the NEW value that the button has after the user clicks
	let isChecked=element.checked;

	let checkedList = document.querySelectorAll(".finalRadio input:checked");

	let twoLepArray=["e-e","mu-mu"];
	let fourLepArray=["4-e","4-mu","2e-2mu"];

	if ( twoLepArray.includes(element.id) ) {
		// Enable Mass Entry box
		$("#enterMass").prop("disabled",false);
		// Change Mass Entry box label styling
		document.getElementById('massInput').style.color = 'black';
		// TODO: Add selections to Data Table on "Submit"
		// TODO: Add mass to 2lep histogram or 2lep histogram data on "Submit"
	} else if ( fourLepArray.includes(element.id) ) {
		// Enable Mass Entry box
		$("#enterMass").prop("disabled",false);
		// Change Mass Entry box label styling
		document.getElementById('massInput').style.color = 'black';
		// TODO: Add selections to Data Table on "Submit"
		// TODO: Add mass to 4lep histogram or 4lep histogram data on "Submit" 
	} else {
		// User has selected something that doesn't require mass input.
		// Disable the Mass Entry box.
		$("#enterMass").prop("disabled",true);
		// Change Mass Entry box label styling
		document.getElementById('massInput').style.color = 'grey';
	}
}
*/

// Added Nov2018 for WZH upgrades.  A simpler version of SelP()
function SelectState(element){
		// Find what's already checked.  This will include any button checked as 
		// part of the click that activates this function.
		let finalCheckedList = document.querySelectorAll('input[name="finalState"]:checked');
		let primaryCheckedList = document.querySelectorAll('input[name="primaryState"]:checked');

		// Mass Entry is required for Final States that are all-charged-leptons
		// AND that have a Neutral Particle Primary State
		// We list mass-required Final States by their selector id's:
		let twoLepList = ["e-e","mu-mu"];
		let fourLepList = ["4-e","4-mu","2e-2mu"];
		let finalMassReqList = twoLepList.concat(fourLepList);
		let primaryMassReqList = ["neutral"];

		// Elements related to the Mass Entry box
		let box = document.getElementById('enterMass');
		let massSpan = document.getElementById('massInput');
		let button = document.getElementById('next');

		// Check each list
		let finalReqMass = false;
		let primaryReqMass = false;
		let activateSubmit = false;

		// Using radio buttons, finalCheckedList and primaryCheckedList can each
		// have at most one node.  Allow a list in case we want to switch to
		// checkboxes
		for(let i=0;i<finalCheckedList.length;i++) {
				if (finalMassReqList.includes(finalCheckedList[i].id)){
						finalReqMass = true;
				}	else {
						finalReqMass = false;
				}
		}
		for(let i=0;i<primaryCheckedList.length;i++) {
				if (primaryMassReqList.includes(primaryCheckedList[i].id)){
						primaryReqMass = true;
				}	else {
						primaryReqMass = false;
				}
		}

		// Iff both are Mass-Required elements, activate the Mass Entry box
		if (finalReqMass && primaryReqMass) {
			box.disabled = false;
			massSpan.style.color = 'black';
		} else {
			box.disabled = true;
			massSpan.style.color = 'grey';
		}

		// If both a final state and primary state are chosen, OR if the primary
		// state is Zoo, activate the Next button
		activateSubmit = ((finalCheckedList.length > 0) && (primaryCheckedList.length > 0)) || primaryCheckedList[0].id == "zoo";
		if ( activateSubmit ) {
	 			button.disabled = false;
		} else {
	 			button.disabled = true;
		}
}


// Added Nov2018.  Allows radio finalState radio buttons to be unchecked.
// Activates Mass Entry as appropriate.  Can replace SelectState()
/* Update 18Dec2018: For some reason I decided against this in favor of the 
 * following function.  Leaving code in case it's useful in the future. - JG
 */
/*
$(document).on("click", "input[name='finalState_unused']", function(){
	thisRadio = $(this);

	let twoLepArray=["e-e","mu-mu"];
	let fourLepArray=["4-e","4-mu","2e-2mu"];

	if (thisRadio.hasClass("imChecked")) {
		thisRadio.removeClass("imChecked");
		thisRadio.prop('checked', false);
		$("#enterMass").prop("disabled",true);
		document.getElementById('massInput').style.color = 'grey';
	} else {
		thisRadio.prop('checked', true);
		thisRadio.addClass("imChecked");
		if ( twoLepArray.includes(this.id) || fourLepArray.includes(this.id) ) {
			$("#enterMass").prop("disabled",false);
			document.getElementById('massInput').style.color = 'black';
		} else {
			$("#enterMass").prop("disabled",true);
			document.getElementById('massInput').style.color = 'grey';
		}
	};
})
*/

// Added Nov2018.  Activates "Submit" button if mass entry is valid
$('input[name=massEntry]').keyup(function() {
	let tryValue = $(this).val();
	if( $.isNumeric(tryValue) ) {
		$("#next").prop("disabled", false);
	} else {
		$("#next").prop("disabled", true);
	}
});



function GetTables(){
	var sel=document.getElementById("Eselect");
	var list=new Array;
	var k=0
	  for (var i = 0; i < sel.options.length; i++) {
   		  if(sel.options[i].selected ==true){
			list[k]=sel.options[i].value;
			k++;
      		}
  	}
	$.ajax({
	type: "POST",
	url: "showTables.php",
	data: {
	MCE : list.join(), 
	source: "Backend"},
	success: function( data ) {
	$( "#tables" ).html( data );
	}
	});

	$.ajax({
	type: "POST",
	url: "showNG.php",
	data: {
	MCE : list.join() },
	success: function( data ) {
	$( "#NG" ).html( data );
	}
	});


}


function PostGroups(){
		/* Bound Tables and Free Tables selectors on MCEvents.php */
		var selB=document.getElementById("BTables");
		var selF=document.getElementById("Ftables");
		var list=new Array;
		var k=0;
		/* For each selected option in the Bound Tables selector, add it to 
		 * 'list' and increment 'k' */
		for (var i=0; i < selB.options.length; i++) {
			if(selB.options[i].selected){
				list[k]=selB.options[i].value;
				k++;
      }
		}
		/* Do the exact same for the Free Tables selector */
		for (var i=0; i < selF.options.length; i++) {
			if(selF.options[i].selected){
				list[k]=selF.options[i].value;
				k++;
      }
  	}
		/* 'list' now contains all selected values (Free and Bound), and 'k' is
		 * its length */

		$.ajax({
				type: "POST",
				url: "showGroups.php",
				data: {
						tables : list.join(),
						source : "Backend"
				},
				success: function( data ) {
						/* Refers to the name="Bgroups[]" listing of 
						 * templates/MCEvents.tpl */
						$( "#bg" ).html( data );
				}
		});
}


function showdel(element){
	//alert(elstr);
	element.style.backgroundColor = "#AAFFAA";
	elstr="del-"+element.id;
	$( "#"+elstr ).html("<span class='glyphicon glyphicon-pencil'></span> edit");
}


function nshowdel(element){

	element.style.backgroundColor = "white";
	var elstr="del-"+element.id;
	$( "#"+elstr ).html("");

}

function OverCol(element){
	if(!(selectedE && element==selectedE)&& !(selectedT && element==selectedT)){
		element.style.backgroundColor = "#AAAAFF";
	}
}
var selectedE;
var selectedT;
var selectedG;

function OffCol(element){
	if(!(selectedE && element==selectedE)&& !(selectedT && element==selectedT)&&!(selectedG && element==selectedG)){
		element.style.backgroundColor = "white";
	}
}


function EvSel(element){
	element.style.backgroundColor = "#AAFFAA";
	if(selectedE && element!=selectedE){
		selectedE.style.backgroundColor = "white";
		$( "#Group").html("");
	}
	
	if(element!=selectedE){
		$.ajax({
		type: "POST",
		url: "showTables.php",
		data: {
		MCE : element.id,
		source: "index" },
		success: function( data ) {
		$( "#Tab" ).html( data );
		}
		});
	}

	selectedE=element;

}

function TSel(element){
	element.style.backgroundColor = "#AAFFAA";
	if(selectedT && element!=selectedT){
		selectedT.style.backgroundColor = "white";
	}
	selectedT=element;

	$.ajax({
	type: "POST",
	url: "showGroups.php",
	data: {
	tables : element.id,
	source: "index" },
	success: function( data ) {
	$( "#Group" ).html( data );
	}
	});
}

function GSel(element){

	$.ajax({
	type: "POST",
	url: "sendIndData.php",
	data: {
	SE : selectedE.id,
	ST : selectedT.id,
	SG : element.id},
	success: function() {
	window.location.href = "DataTable.php";
	}
	});
}


// This was overhauled from del_old() as part of WZH upgrades - JG Aug2019
function del(element){

		// Pull table entries as identified by their class
		let eventID = element.getElementsByClassName("event-id")[0].innerHTML;
		let datagroupID = element.getElementsByClassName("dg-id")[0].innerHTML;
		let finalState = element.getElementsByClassName("final-state")[0].innerHTML;
		let primaryState = element.getElementsByClassName("primary-state")[0].innerHTML;
		//console.log("eventID = "+eventID);
		console.log("datagroupID = "+datagroupID);
		//console.log("finalState = "+finalState);
		//console.log("primaryState = "+primaryState);

		// Derived values:
		// The dataset ID is the last three digits of the eventID
		let datasetID = eventID.toString().substr(-3);
		// There is no function to trim leading or trailing characters from
		// a string in Javascript
		datasetID = parseInt(datasetID, 10).toString();
		console.log("datasetID = "+datasetID);

		// Delete the row from the current Location table of the database.
		// If successful, replace the row's HTML with '', deleting it from the page.
		$.ajax({
				type: "POST",
				url: "delE.php",
				data: {
						row : element.id
				},
				success: function( ) {
						$( "#"+element.id ).html( "" );
				}
		});

		/* jQuery to select all radio inputs and enable them.
		 * Are they ever disabled?  This was carried over from del_old() and can
		 * probably be deleted. */
		$(":radio").prop("disabled",false);

		/* These are 'id' values of all radio buttons.
		 * Reset them all to the unselected state. */
		let radioIDs = ["e-nu","mu-nu","e-e","mu-mu","4-e","4-mu","2e-2mu","charged","neutral","zoo"];
		for(var i=0; i < radioIDs.length; i++){
		    document.getElementById(radioIDs[i]).checked = false;
		}

		/* On deletion, we replace the event that's currently selected in the
		 * 'Event index' drop-down with the just-deleted event.
		 * First, we create a new <option> element to hold the current
		 * selection when it goes back into the drop-down menu. */
		let nopt = document.createElement("option");

		/* id='SelEvent' is the currently-selected <option> in the drop-down.
		 * Set its text to the new <option>.  This should be the [1-100] 
		 * dataset number of the event. */
		nopt.text = $("#SelEvent").text();

		/* id="EvSelOver" is the event selection drop-down menu, a <select>
		 * element.  Add the new <option> to it at the position index
		 * before the <object> dropDown[1]. */
		/* This seems wrong.  dropDown[1] is the 2nd <option> in the drop-down.
		 * Placing the new <option> there makes it the new 2nd <option> in the
		 * drop-down.  Don't we want to put it wherever it goes according to the
		 * numeric ordering of the dataset numbers?  And if we don't, don't we
		 * want to make the the new *1st* <option>?  Why 2nd?
		 * Nonetheless, it works for now. */
		let dropDown = document.getElementById("EvSelOver");
		dropDown.add(nopt,dropDown[1]);

		/* Now, make the deleted event be the currently-selected option */
		//$("#SelEvent").html($.trim(eventID));
		$("#SelEvent").html($.trim(datasetID));
		/* Set the current datagroup index to match */
		$("#Eventid").html($.trim(datagroupID));
		//$("#Eventid").html($.trim(eventID));

		/* These two 'if' blocks are an updated version of what del_old() does, 
		 * but I doubt they're needed at all - JG 15Aug2019 */
		if(finalState && $.trim(finalState) !=""){
				let temp = $.trim(finalState);
				document.getElementById(temp).checked = true;
				SelP(document.getElementById(temp),0);
		}
		if(primaryState && $.trim(primaryState) !=""){
				let temp = $.trim(primaryState);
				document.getElementById(temp).checked = true;
				SelP(document.getElementById(temp),0);
		}

		/* Disable the [finish editing] button of templates/table.tpl that can 
		 * appear in DataTable.php when accessed from finish.php under certain 
		 * conditions */
		$("#fedit").prop("disabled",true);
}


// This was formerly del() before WZH upgrades - Aug2019
function del_old(element){

		// Create an array of column entries as child nodes
		// This is not recommended.  If someone unfamiliar with this function adds
		// a comment within the element, for example, this creates a new child node
		// that will throw off the ordering of the array cs[i].
		// Instead, assign a class to the cell <div> that allows it to be extracted
		// unambiguously.
		var cs = element.childNodes;
		/*for(var i=0; i < cs.length; i++){
		    console.log("cs["+i+"] = " + cs[i]);
		}*/

		/* 'checked' is the semicolon-separated string of final state,
		 * primary state, and mass */
		var checked = cs[5].innerHTML.split(";");
		var mass=cs[7].innerHTML;

		/* Delete the row from the current Location table of the database.
		 * If successful, replace the row's HTML with '', effectively deleting it 
		 * from the page. */
		$.ajax({
				type: "POST",
				url: "delE.php",
				data: {
						row : element.id
				},
				success: function( ) {
						$( "#"+element.id ).html( "" );
				}
		});

		/* jQuery to select all checkboxes and enable them.
		 * They can become disabled by SelP(), activated when a checkbox is clicked. */
		$(":checkbox").prop("disabled",false);

		/* These are 'id' values of all checkboxes.  Uncheck them. */
		var allC = ["e","mu","W","Wp","W-","Z","H","Zoo"];
		for(var i=0; i < allC.length; i++){
		    document.getElementById(allC[i]).checked = false;
		}

		// id="EvSelOver" is the event selection drop-down menu
		sel = document.getElementById("EvSelOver");
		/*console.log("sel = "+sel.value);
		console.log("sel[0] = "+sel[0].value);
		console.log("sel[1] = "+sel[1].value);
		console.log("sel[2] = "+sel[2].value);
		console.log("sel[3] = "+sel[3].value);*/

		// Create a new <option> element to put into the drop-down
		var nopt = document.createElement("option");

		// id='SelEvent' is the currently-selected <option> in the drop-down
		// Set the text of this new <option> to its text
		nopt.text = $("#SelEvent").text();

		// Add the new <option> to the drop-down menu at the position index sel[1]
		// This seems wrong.  sel[1] is the *event* index of the second option in
		// the list.  It's not a list position index.
		sel.add(nopt,sel[1]);

		$("#SelEvent").html($.trim(cs[1].innerHTML));
		$("#Eventid").html($.trim(cs[3].innerHTML));

	var s = massGlobal.split(";");
	for(var i=0;i<s.length;i++){
		var temp = s[i].split(":");
		if(temp[0] == element.id){
			Mmass = temp[1];
		}
	}

	if(checked && $.trim(checked[0])!=""){
		for(var i=0;i<checked.length;i++){
			    var temp = $.trim(checked[i]);
			    document.getElementById(temp).checked = true;
			    SelP(document.getElementById(temp),0);
		}
	}
	$("#fedit").prop("disabled",true);
}
