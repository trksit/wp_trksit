jQuery(document).ready(function($) {

	
	var july30 = new Date().getTime() - 18000000;
	var july29 = july30 - 86400000;
	var july28 = july29 - 86400000;
	var july27 = july28 - 86400000;
	var july26 = july27 - 86400000;
	var july25 = july26 - 86400000;
	var july24 = july25 - 86400000;
	var july23 = july24 - 86400000;
	var july22 = july23 - 86400000;
	var july21 = july22 - 86400000;
	var july20 = july21 - 86400000;
	var july19 = july20 - 86400000;
	var july18 = july19 - 86400000;
	var july17 = july18 - 86400000;
	var july16 = july17 - 86400000;
	var july15 = july16 - 86400000;
	var july14 = july15 - 86400000;
	var july13 = july14 - 86400000;
	var july12 = july13 - 86400000;
	var july11 = july12 - 86400000;
	var july10 = july11 - 86400000;
	var july9 = july10 - 86400000;
	var july8 = july9 - 86400000;
	var july7 = july8 - 86400000;
	var july6 = july7 - 86400000;
	var july5 = july6 - 86400000;
	var july4 = july5 - 86400000;
	var july3 = july4 - 86400000;
	var july2 = july3 - 86400000;
	var july1 = july2 - 86400000;
	
	var graphData = [{
	        // Returning Visits
	        data: [ 
	        	[july1, 5], 
		        [july2, 23],
		        [july3, 34], 
	        	[july4, 34], 
		        [july5, 56],
		        [july6, 34], 
		        [july7, 37],
	        	[july8, 34], 
		        [july9, 15],
		        [july10, 90], 
		        [july11, 66], 
		        [july12, 45],
		     	[july13, 34], 
		        [july14, 70],
		        [july15, 34], 
		        [july16, 70],
	        	[july17, 34], 
		        [july18, 70],
		        [july19, 34], 
		        [july20, 70], 
		        [july21, 45],
	     	    [july22, 34], 
		        [july23, 70],
		        [july24, 34], 
		        [july25, 70],
	        	[july26, 34], 
		        [july27, 70],
		        [july28, 34], 
		        [july29, 70], 
		        [july30, 45] 
	        ],
	        color: '#21759b',
	        points: { radius: 3, fillColor: '#21759b' }
	    }];
	
	// Lines
	$.plot($('#graph-lines'), graphData, {
	    series: {
	        points: {
	            show: true,
	            radius: 5
	        },
	        lines: {
	            show: true
	        },
	        shadowSize: 0
	    },
	    grid: {
	        color: '#646464',
	        borderColor: 'transparent',
	        borderWidth: 20,
	        hoverable: true
	    },
	    xaxis: {
	        tickColor: 'transparent',
	        tickDecimals: 0,
	        mode: "time",  
	        timeformat: "%m/%d/%y",  
	        minTickSize: [3, "day"]
	    },
	    yaxis: {
	        tickSize: 20
	    }
	});


	// Tooltip #################################################
	function showTooltip(x, y, contents) {
		$('<div id="tooltip">' + contents + '<span class="nip"></span></div>').css({
			top: y - 50,
			left: x - 80
		}).appendTo('body').fadeIn();
	}

	var previousPoint = null;

	$('#graph-lines').bind('plothover', function (event, pos, item) {
		if (item) {
			if (previousPoint != item.dataIndex) {
				previousPoint = item.dataIndex;
				$('#tooltip').remove();
				var x = item.datapoint[0],
					y = item.datapoint[1];
				var hitdate = new Date(x);
					showTooltip(item.pageX, item.pageY, y + ' hits on ' + (hitdate.getUTCMonth() + 1) + "/" + hitdate.getUTCDate() + "/" + hitdate.getUTCFullYear());
			}
		} else {
			$('#tooltip').remove();
			previousPoint = null;
		}
	});

});