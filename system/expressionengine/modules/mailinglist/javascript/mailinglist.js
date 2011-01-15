$(document).ready(function() {
	$(".mainTable").tablesorter({
		headers: {2: {sorter: false}, 3: {sorter: false}, 4: {sorter: false}, 6: {sorter: false}},
		widgets: ["zebra"]
	});
	$(".toggle_all").toggle(
		function(){
			$("input.toggle").each(function() {
				this.checked = true;
			});
		}, function (){
			var checked_status = this.checked;
			$("input.toggle").each(function() {
				this.checked = false;
			});
		}
	);
	
	
	if (EE.datatables.enabled == 'true') {
		var oCache = {
			iCacheLower: -1
		};

		function fnSetKey( aoData, sKey, mValue )
		{
			for ( var i=0, iLen=aoData.length ; i<iLen ; i++ )
			{
				if ( aoData[i].name == sKey )
				{
					aoData[i].value = mValue;
				}
			}
		}

		function fnGetKey( aoData, sKey )
		{
			for ( var i=0, iLen=aoData.length ; i<iLen ; i++ )
			{
				if ( aoData[i].name == sKey )
				{
					return aoData[i].value;
				}
			}
			return null;
		}

		function fnDataTablesPipeline ( sSource, aoData, fnCallback ) {
			var iPipe = EE.datatables.pipe_length;  /* Ajust the pipe size */

			var bNeedServer = false;
			var sEcho = fnGetKey(aoData, "sEcho");
			var iRequestStart = fnGetKey(aoData, "iDisplayStart");
			var iRequestLength = fnGetKey(aoData, "iDisplayLength");
			var iRequestEnd = iRequestStart + iRequestLength;
			var email = document.getElementById("email");
		    var list_id = document.getElementById("list_id");

			// for browsers that don't support the placeholder
			// attribute. See global.js :: insert_placeholders()
			// for more info. -pk
			function email_value() {
				if ($(email).data('user_data') == 'n') {
					return '';
				}

				return email.value;
			}

			aoData.push( 
				 { "name": "email", "value": email_value() },
		         { "name": "list_id", "value": list_id.value }
			 );

			oCache.iDisplayStart = iRequestStart;

			/* outside pipeline? */
			if ( oCache.iCacheLower < 0 || iRequestStart < oCache.iCacheLower || iRequestEnd > oCache.iCacheUpper )
			{
				bNeedServer = true;
			}

			/* sorting etc changed? */
			if ( oCache.lastRequest && !bNeedServer )
			{
				for( var i=0, iLen=aoData.length ; i<iLen ; i++ )
				{
					if ( aoData[i].name != "iDisplayStart" && aoData[i].name != "iDisplayLength" && aoData[i].name != "sEcho" )
					{
						if ( aoData[i].value != oCache.lastRequest[i].value )
						{
							bNeedServer = true;
							break;
						}
					}
				}
			}

			/* Store the request for checking next time around */
			oCache.lastRequest = aoData.slice();

			if ( bNeedServer )
			{
				if ( iRequestStart < oCache.iCacheLower )
				{
					iRequestStart = iRequestStart - (iRequestLength*(iPipe-1));
					if ( iRequestStart < 0 )
					{
						iRequestStart = 0;
					}
				}

				oCache.iCacheLower = iRequestStart;
				oCache.iCacheUpper = iRequestStart + (iRequestLength * iPipe);
				oCache.iDisplayLength = fnGetKey( aoData, "iDisplayLength" );
				fnSetKey( aoData, "iDisplayStart", iRequestStart );
				fnSetKey( aoData, "iDisplayLength", iRequestLength*iPipe );

						aoData.push( 
						 { "name": "email", "value": email_value() },
		        	     { "name": "list_id", "value": list_id.value }
						 );

				$.getJSON( sSource, aoData, function (json) { 
					/* Callback processing */
					oCache.lastJson = jQuery.extend(true, {}, json);

					if ( oCache.iCacheLower != oCache.iDisplayStart )
					{
						json.aaData.splice( 0, oCache.iDisplayStart-oCache.iCacheLower );
					}
					json.aaData.splice( oCache.iDisplayLength, json.aaData.length );

					fnCallback(json)
				} );
			}
			else
			{
				json = jQuery.extend(true, {}, oCache.lastJson);
				json.sEcho = sEcho; /* Update the echo for each response */
				json.aaData.splice( 0, iRequestStart-oCache.iCacheLower );
				json.aaData.splice( iRequestLength, json.aaData.length );
				fnCallback(json);
				return;
			}
		}
		
		var time = new Date().getTime();
		
		oTable = $(".mainTable").dataTable( {	
				"sPaginationType": "full_numbers",
				"bLengthChange": false,
				"bFilter": false,
				"sWrapper": false,
				"sInfo": false,
				"bAutoWidth": false,
				"iDisplayLength": EE.datatables.per_page,  

			"aoColumns": [null, null, null, { "bSortable" : false } ],

			"oLanguage": {
				"sZeroRecords": EE.datatables.LANG.no_results,

				"oPaginate": {
					"sFirst": "<img src=\""+EE.THEME_URL+"images/pagination_first_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />",
					"sPrevious": "<img src=\""+EE.THEME_URL+"images/pagination_prev_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />",
					"sNext": "<img src=\""+EE.THEME_URL+"images/pagination_next_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />", 
					"sLast": "<img src=\""+EE.THEME_URL+"images/pagination_last_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />"
				}
			},

			"bProcessing": true,
			"bServerSide": true,
			"sAjaxSource": EE.BASE+"&C=addons_modules&M=show_module_cp&module=mailinglist&method=view_ajax_filter&time=" + time,
			"fnServerData": fnDataTablesPipeline
		});

		$("select#list_id").change(function () {
				oTable.fnDraw();
			});		

		var delayed;

		$("#email").keyup(function() {
		     clearTimeout(delayed);
		     var value = this.value;
		     if (value) {
		         delayed = setTimeout(function() {
			oTable.fnDraw();
		         }, 300);
		     }
		});
	}
});