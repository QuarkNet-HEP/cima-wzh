var options;
let chartList = {};
let numbins;
let suggestedMax = 5;

//debugger;

function MakeHist(datax, xmin, xmax, ybin, chartId){
	/* Updates 3Oct2018
	 * 'ybin' is the size in GeV of the individual bins.
	 *   Shouldn't that be 'xbin'?  We're binning the x-axis.
	 * 'chartId' is the value of the HTML 'id' attribute for the chart's canvas
	 * 'datax' is a string of semicolon-separated integers representing bin values
	 * 'x' will be the array of x-axis bin labels
	 */
	/* Updates 7Jan2019
	 * 2- and 4- lepton plots need different axis parameters, so have to lose the
	 * hard-coded 68 and pass parameters in as arguments
	 */
	numbins = Math.ceil((xmax-xmin)/ybin) + 1;
	var x=new Array(numbins);
	var y=datax.split(";");
	/* Find the greatest data value in order to set the height of the y-axis.
	 * '...' is the "spread operator" */
	let maxY = Math.max(...y);
	/* Establish the maximum value of the chart y-axis: */
	var ymax = Math.max(maxY, suggestedMax);
		
	// Fill the bin index array in steps of 'ybin' 
	var c=xmin;
	for(var i=0;i<numbins;i++){
		x[i]=c;
		c+=ybin;
	}
		
	var data = {
	    labels: x,
	    datasets: [
	        {
	            label: "My First dataset",
	            fillColor: "rgba(0,10,220,0.5)",
	            strokeColor: "rgba(220,220,220,0.8)",
	            highlightFill: "rgba(220,0,0,0.75)",
	            highlightStroke: "rgba(220,220,220,1)",
	            data: y
	        },
	    ]
	};

	options={
	    //Boolean - Whether the scale should start at zero, or an order of magnitude down from the lowest value
			//scaleBeginAtZero : true,

			/* Chart.js has terrible documentation
			 * I've attempted to implement the 'suggestedMax' feature of Chart.js 
			 * v.2+ by hand in order to minimize the jarring effect of the y-axis
			 * jumping when users first start entering data. - JG */
			/* Required to manually manipulate scale settings: */
			scaleOverride : true,
			/* Manually set the number of y-axis scale steps, step size, and start: */
			scaleSteps : ymax,
			scaleStepWidth : 1,
			scaleStartValue : 0, 
		
 			//Boolean - Whether grid lines are shown across the chart
    	scaleShowGridLines : true,

    	//String - Colour of the grid lines
    	scaleGridLineColor : "rgba(0,0,0,.05)",

    	//Number - Width of the grid lines
    	scaleGridLineWidth : 1,

    	//Boolean - If there is a stroke on each bar
			// I.e., a border around the edge of the bar
    	barShowStroke : true,

    	//Number - Pixel width of the bar stroke
    	barStrokeWidth : 2,

    	//Number - Spacing between each of the X value sets
    	barValueSpacing : 0,

    	//Number - Spacing between data sets within X values
    	barDatasetSpacing : 0,

    	//String - A legend template
    	legendTemplate : "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<datasets.length; i++){%><li><span style=\"background-color:<%=datasets[i].lineColor%>\"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>"

	}

	ctx = document.getElementById(chartId).getContext("2d");
	console.log(chartList);
	thisChart = new Chart(ctx).Bar(data, options);
	chartList[chartId] = thisChart;
	console.log(chartList);
}


///function uhist(datax){
function uhist(datax,chartId){

		// Global 'chartList[]' defined in MakeHist()
		let myBarChart = chartList[chartId];
		let y=datax.split(";");
		console.log(y);
		
		// Update the histogram bins; global 'numbins' defined in MakeHist()
		for(let i=0;i<numbins;i++){
				myBarChart.datasets[0].bars[i].value=y[i];
		}

		// Added Jan2019 to dynamically update y-axis max.  Same thing as done
		//   in MakeHist() for the initial chart.
		let maxY = Math.max(...y);
		/* Establish the maximum value of the chart y-axis: */
		let ymax = Math.max(maxY, suggestedMax);

		// Update the y-axis
		myBarChart.scale.steps=ymax;
		myBarChart.scale.max=ymax;
	
		// It seems like this update() is a standard chart.js function,
		//   *not* the one defined below
		myBarChart.update();
}


function update(evt){
		// Start by getting chart data we'll need:
		// evt.target.id is the 'id' attribute of the clicked canvas
		let chartId = evt.target.id;

		// Get the Chart object from the global 'chartList[]' defined in MakeHist()
		let myBarChart = chartList[chartId];

		// Get the object representing the clicked bar:
		let activeBars = myBarChart.getBarsAtEvent(evt);

		// Its label is the x-axis bin label in GeV:
		let x = activeBars[0].label;

		// 'xmin' is needed to calculate the bin index, and it can be found as
		//   the label on the very first bar of the dataset:
		let xmin = myBarChart.datasets[0].bars[0].label;

		// To update the database, we need the *array index* of the bin 'x'
		// '2' is the bin width; if we ever change that, this has to change
		let index = (x-xmin)/2

		var del=1;
		if(evt.ctrlKey || evt.metaKey){
				del=1;
				/*if(myBarChart.datasets[0].bars[index].value!=0){
					myBarChart.datasets[0].bars[index].value =
					(parseInt(activeBars[0].value)-1).toString();
					}*/
		}else{
				del=0;
				/*myBarChart.datasets[0].bars[index].value =
					(parseInt(activeBars[0].value)+1).toString();*/
		}
		
    $.ajax({
				type: "POST",
				url: "AddHistData.php",
				data: {
						x : index,
						d : del,
						id : chartId
				},
				success: function( data ) {
						uhist(data,chartId);
						///uhist(data);
				}
		});
}

