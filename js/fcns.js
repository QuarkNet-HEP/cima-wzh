var Mmass=0;
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

function check(state){
	if(state=="primary"){
		return (document.getElementById("e").checked || document.getElementById("mu").checked);
	}
	if(state=="final"){
		return (document.getElementById("H").checked || document.getElementById("Z").checked || document.getElementById("W").checked
		|| document.getElementById("Wp").checked || document.getElementById("W-").checked || document.getElementById("Zoo").checked);
	}
}

function SelP(element,mass){
	var prim=false;
	var fin=false;
	
	checked=element.checked;
	if(element.id=="mu" || element.id=="e"){
		var arr=["mu","e"];
		prim=checked;
		fin=check("final");
	
	}else{
		if(element.id=="Zoo" || element.id=="H"){
			$("#mu").prop("checked",false);
			$("#e").prop("checked",false);
			$("#mu").prop("disabled",checked);
			$("#e").prop("disabled",checked);
			prim=checked;
		}else{
			prim=check("primary");
		}

		printMass(mass);
		var arr=["H","W","W-","Wp","Z","Zoo"];
		fin=checked;
	}	
	for(var i=0;i<arr.length;i++){
		if(element.id!=arr[i]){
			$("#"+arr[i]).prop("disabled", checked);
		}
	}
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
		// We list Mass-Required Final States by their selector id's:
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

		// Iff both a final state and primary state are chosen, activate the Submit
		//	 button
		if ( (finalCheckedList.length > 0) && (primaryCheckedList.length > 0) ) {
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
	var selB=document.getElementById("BTables");
	var selF=document.getElementById("Ftables");
	var list=new Array;
	var k=0
	 for (var i = 0; i < selB.options.length; i++) {
   		  if(selB.options[i].selected){
			list[k]=selB.options[i].value;
			k++;
      		}
  	}
	 for (var i = 0; i < selF.options.length; i++) {
   		  if(selF.options[i].selected){
			list[k]=selF.options[i].value;
			k++;
      		}
  	}
	
	$.ajax({
	type: "POST",
	url: "showGroups.php",
	data: {
	tables : list.join(),
	source : "Backend" },
	success: function( data ) {
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


// This need attention after the WZH upgrades of Nov2018
function del(element){
	var cs=element.childNodes;
	// 'checked' no longer exists. cs[5] is now the "final" value
	var checked=cs[5].innerHTML.split(";");
	// mass value is now cs[9].innerHTML	
	var mass=cs[7].innerHTML;
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

	// We now have radio buttons instead of checkboxes
	$(":checkbox").prop("disabled",false);
	// These are 'id' values of all checkboxes > radio buttons
	// These have changed.
	var allC=["e","mu","W","Wp","W-","Z","H","Zoo"];
	for(var i=0;i<allC.length;i++){
		    document.getElementById(allC[i]).checked = false;
	}
	sel=document.getElementById("EvSelOver");
	var nopt=document.createElement("option");
	nopt.text=$("#SelEvent").text();
	sel.add(nopt,sel[1]);
	$("#SelEvent").html($.trim(cs[1].innerHTML));
	$("#Eventid").html($.trim(cs[3].innerHTML));

	var s=massGlobal.split(";");
	for(var i=0;i<s.length;i++){
		var temp=s[i].split(":");
		if(temp[0]==element.id){
			Mmass=temp[1];
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
