<?php

//-------------------------------------
//  Javascript Calendar
//------------------------------------- 
	
class js_calendar {
 
 	function js_calendar()
 	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
 	}
 
	function calendar($align = 'center')
	{	
		/** -------------------------------------
		/**  Set-up our preferences
		/** -------------------------------------*/
			
		$fmt = (ee()->session->userdata['time_format'] != '') ? ee()->session->userdata['time_format'] : ee()->config->item('time_format');
		
		$days = '';
		foreach (array ('Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa') as $val)
		{
			$days .= "'".ee()->lang->line($val)."',";
		}
		$days .= substr($days, 0, -1);
		
		$months = '';
		foreach (array('January','February','March','April','May_l','June','July','August','September','October','November','December') as $val)
		{
			$months .= "'".ee()->lang->line($val)."',";		
		}
		$months .= substr($months, 0, -1);
	
		/** -------------------------------------
		/**  Write the JavaScript
		/** -------------------------------------*/
	
		ob_start();
		?>
		<script type="text/javascript"> 
		<!--
		var format		= '<?php echo $fmt; ?>';
		var days		= new Array(<?php echo $days; ?>);
		var months		= new Array(<?php echo $months; ?>);
		var last_click	= new Array();
		var current_month  = '';
		var current_year	= '';
		var last_date  = '';
			
		function calendar(id, d, highlight)
		{
			this.id			= id;
			this.highlight	= highlight;
			this.date_obj	= d;
			this.write		= build_calendar;
			this.total_days	= total_days;
			this.month		= d.getMonth();
			this.date		= d.getDate();
			this.day		= d.getDay();
			this.year		= d.getFullYear();
			this.hours		= d.getHours();
			this.minutes	= d.getMinutes();
			this.seconds	= d.getSeconds();
			this.date_str	= date_str;
						
			if (highlight == false)
			{
				this.selected_date = '';
			}
			else
			{
				this.selected_date = this.year + '' + this.month + '' + this.date; 
			}
					
					
			//	Set the "selected date"
			
			// As we toggle from month to month we need a way
			// to recall which date was originally highlighted
			// so when we return to that month the state will be
			// retained.  Well set a			// a string representing the year/month/day
													
			//get the first day of the month's day
			d.setDate(1);
			
			this.firstDay = d.getDay();
			
			//then reset the date object to the correct date
			d.setDate(this.date);
		}
				
		//	Build the body of the calendar
		
		function build_calendar()
		{
			var str = '';
			
			//	Calendar Heading
			
			str += '<div id="cal' + this.id + '">';
			str += '<table class="calendar" cellspacing="0" cellpadding="0" border="0" align="<?php echo $align; ?>">';
			str += '<tr>';
			str += '<td class="calnavleft" onclick="change_month(-1, \'' + this.id + '\')">&lt;&lt;<\/td>';
			str += '<td colspan="5" class="calheading">' + months[this.month] + ' ' + this.year + '<\/td>';
			str += '<td class="calnavright" onclick="change_month(1, \'' + this.id + '\')">&gt;&gt;<\/td>';
			str += '<\/tr>';
			
			//	Day Names
			
			str += '<tr>';
			
			for (i = 0; i < 7; i++)
			{
				str += '<td class="caldayheading">' + days[i] + '<\/td>';
			}
			
			str += '<\/tr>';
			
			//	Day Cells
				
			str += '<tr>';
			
			selDate = (last_date != '') ? last_date : this.date;
			
			for (j = 0; j < 42; j++)
			{
				var displayNum = (j - this.firstDay + 1);
				
				if (j < this.firstDay) // leading empty cells
				{
					str += '<td class="calblanktop">&nbsp;<\/td>';
				}
				else if (displayNum == selDate && this.highlight == true) // Selected date
				{
					str += '<td id="' + this.id +'selected" class="caldayselected" onclick="set_date(this,\'' + this.id + '\')">' + displayNum + '<\/td>';
				}
				else if (displayNum > this.total_days())
				{
					str += '<td class="calblankbot">&nbsp;<\/td>'; // trailing empty cells
				}
				else  // Unselected days
				{
					str += '<td id="" class="caldaycells" onclick="set_date(this,\'' + this.id + '\'); return false;"  onmouseOver="javascript:cell_highlight(this,\'' + displayNum + '\',\'' + this.id + '\');" onmouseOut="javascript:cell_reset(this,\'' + displayNum + '\',\'' + this.id + '\');" >' + displayNum + '<\/td>';
				}
				
				if (j % 7 == 6)
				{
					str += '<\/tr><tr>';
				}
			}
		
			str += '<\/tr>';	
			str += '<\/table>';
			str += '<\/div>';
			
			return str;
		}

		
		//	Total number of days in a month
		
		function total_days()
		{	
			switch(this.month)
			{
				case 1: // Check for leap year
					if ((  this.date_obj.getFullYear() % 4 == 0
						&& this.date_obj.getFullYear() % 100 != 0)
						|| this.date_obj.getFullYear() % 400 == 0)
						return 29; 
					else
						return 28;
				case 3:
					return 30;
				case 5:
					return 30;
				case 8:
					return 30;
				case 10:
					return 30
				default:
					return 31;
			}
		}

		
		
		//	Highlight Cell on Mouseover
		
		function cell_highlight(td, num, cal)
		{
			cal = eval(cal);
		
			if (last_click[cal.id]  != num)
			{
				td.className = "caldaycellhover";
			}
		}		
	
		//	Reset Cell on MouseOut
		
		function cell_reset(td, num, cal)
		{	
			cal = eval(cal);
		
			if (last_click[cal.id] == num)
			{
				td.className = "caldayselected";
			}
			else
			{
				td.className = "caldaycells";
			}
		}		
		
		//	Clear Field
		
		function clear_field(id)
		{				
			eval("document.getElementById('entryform')." + id + ".value = ''");
			
			document.getElementById(id + "selected").className = "caldaycells";
			document.getElementById(id + "selected").id = "";	
			
			cal = eval(id);
			cal.selected_date = '';		
		}		
		
		
		//	Set date to now
		
		function set_to_now(id, now, raw)
		{
			eval("document.getElementById('entryform')." + id + ".value = now");
			
			if (document.getElementById(id + "selected"))
			{			
				document.getElementById(id + "selected").className = "caldaycells";
				document.getElementById(id + "selected").id = "";	
			}
			
			document.getElementById('cal' + id).innerHTML = '<div id="tempcal'+id+'">&nbsp;<'+'/div>';				
				
			var nowDate = new Date();
			nowDate.setTime = raw;
			
			current_month	= nowDate.getMonth();
			current_year	= nowDate.getFullYear();
			current_date	= nowDate.getDate();
			
			oldcal = eval(id);
			oldcal.selected_date = current_year + '' + current_month + '' + current_date;	
			
			oldcal.date_obj.setMonth(current_month);
			oldcal.date_obj.setYear(current_year);
				
			cal = new calendar(id, nowDate, true);		
			cal.selected_date = current_year + '' + current_month + '' + current_date;			

			last_date = cal.date;
	
			document.getElementById('tempcal'+id).innerHTML = cal.write();	
		}
		
		
		//	Set date to what is in the field
		var lastDates = new Array();
	
		function update_calendar(id, dateValue)
		{
			cal = eval(id);		
		
			if (lastDates[id] == dateValue) return;
			
			lastDates[id] = dateValue;
			
			var fieldString = dateValue.replace(/\s+/g, ' ');
			
			while (fieldString.substring(0,1) == ' ')
			{
				fieldString = fieldString.substring(1, fieldString.length);
			}
			
			var dateString = fieldString.split(' ');
			var dateParts = dateString[0].split('-')
	
			if (dateParts.length < 3) return;
			var newYear  = dateParts[0];
			var newMonth = dateParts[1];
			var newDay	= dateParts[2]; 
			
			if (isNaN(newDay)  || newDay < 1 || (newDay.length != 1 && newDay.length != 2)) return;
			if (isNaN(newYear) || newYear < 1 || newYear.length != 4) return;
			if (isNaN(newMonth) || newMonth < 1 || (newMonth.length != 1 && newMonth.length != 2)) return;
			
			if (newMonth > 12) newMonth = 12;
			
			if (newDay > 28)
			{
				switch(newMonth - 1)
				{
					case 1: // Check for leap year
						if ((newYear % 4 == 0 && newYear % 100 != 0) || newYear % 400 == 0)
						{
							if (newDay > 29) newDay = 29; 
						}
						else
						{
							if (newDay > 28) newDay = 28;
						}
					case 3:
						if (newDay > 30) newDay = 30;
					case 5:
						if (newDay > 30) newDay = 30;
					case 8:
						if (newDay > 30) newDay = 30;
					case 10:
						if (newDay > 30) newDay = 30;
					default:
						if (newDay > 31) newDay = 31;
				}
			}
			
			if (document.getElementById(id + "selected"))
			{			
				document.getElementById(id + "selected").className = "caldaycells";
				document.getElementById(id + "selected").id = "";	
			}
			
			document.getElementById('cal' + id).innerHTML = '<div id="tempcal'+id+'">&nbsp;<'+'/div>';				
				
			var nowDate = new Date();
			nowDate.setDate(newDay);
			nowDate.setMonth(newMonth - 1);
			nowDate.setYear(newYear);
			nowDate.setHours(12);
			
			cal.date_obj.setMonth(newMonth - 1);
			cal.date_obj.setYear(newYear);
			
			current_month	= nowDate.getMonth();
			current_year	= nowDate.getFullYear();
			last_date		= newDay;
	
			cal = new calendar(id, nowDate, true);						
			document.getElementById('tempcal'+id).innerHTML = cal.write();	
		}
				
		
		//	Set the date
		
		function set_date(td, cal)
		{					
			cal = eval(cal);
			
			// If the user is clicking a cell that is already
			// selected we'll de-select it and clear the form field
			
			if (last_click[cal.id] == td.firstChild.nodeValue)
			{
				td.className = "caldaycells";
				last_click[cal.id] = '';
				remove_date(cal);
				cal.selected_date =  '';
				return;
			}
						
			// Onward!
		
			if (document.getElementById(cal.id + "selected"))
			{
				document.getElementById(cal.id + "selected").className = "caldaycells";
				document.getElementById(cal.id + "selected").id = "";
			}
											
			td.className = "caldayselected";
			td.id = cal.id + "selected";
	
			cal.selected_date = cal.date_obj.getFullYear() + '' + cal.date_obj.getMonth() + '' + cal.date;			
			cal.date_obj.setDate(td.firstChild.nodeValue);
			cal = new calendar(cal.id, cal.date_obj, true);
			cal.selected_date = cal.date_obj.getFullYear() + '' + cal.date_obj.getMonth() + '' + cal.date;			
			
			last_date = cal.date;
	
			//cal.date
			
			last_click[cal.id] = cal.date;
						
			// Insert the date into the form
			
			insert_date(cal);
		}
		
		
		//	Insert the date into the form field
		
		function insert_date(cal)
		{
			cal = eval(cal);
			
			fval = eval("document.getElementById('entryform')." + cal.id);	
			
			if (fval.value == '')
			{
				fval.value = cal.date_str('y');
			}
			else
			{
				time = fval.value.substring(10);
						
				new_date = cal.date_str('n') + time;
		
				fval.value = new_date;
			}	
		}
				
		//	Remove the date from the form field
		
		function remove_date(cal)
		{
			cal = eval(cal);
			
			fval = eval("document.getElementById('entryform')." + cal.id);	
			fval.value = '';
		}
		
		//	Change to a new month
		
		function change_month(mo, cal)
		{		
			cal = eval(cal);
	
			if (current_month != '')
			{
				cal.date_obj.setMonth(current_month);
				cal.date_obj.setYear(current_year);
			
				current_month	= '';
				current_year	= '';
			}
						
			var newMonth = cal.date_obj.getMonth() + mo;
			var newDate  = cal.date_obj.getDate();
			
			if (newMonth == 12) 
			{
				cal.date_obj.setYear(cal.date_obj.getFullYear() + 1)
				newMonth = 0;
			}
			else if (newMonth == -1)
			{
				cal.date_obj.setYear(cal.date_obj.getFullYear() - 1)
				newMonth = 11;
			}
			
			if (newDate > 28)
			{
				var newYear = cal.date_obj.getFullYear();
				
				switch(newMonth)
				{
					case 1: // Check for leap year
						if ((newYear % 4 == 0 && newYear % 100 != 0) || newYear % 400 == 0)
						{
							if (newDate > 29) newDate = 29; 
						}
						else
						{
							if (newDate > 28) newDate = 28;
						}
					case 3:
						if (newDate > 30) newDate = 30;
					case 5:
						if (newDate > 30) newDate = 30;
					case 8:
						if (newDate > 30) newDate = 30;
					case 10:
						if (newDate > 30) newDate = 30;
					default:
						if (newDate > 31) newDate = 31;
				}
			}
			
			cal.date_obj.setDate(newDate);
			cal.date_obj.setMonth(newMonth);
			new_mdy	= cal.date_obj.getFullYear() + '' + cal.date_obj.getMonth() + '' + cal.date;
			
			highlight = (cal.selected_date == new_mdy) ? true : false;
			
			// Changed the highlight to false until we can determine a way for
			// the month to keep the old date value when we switch the newDate value
			// because of more days in the prior month than the month being switched
			// to:  Jan 31st => March 3rd (3 days past end of Febrary)
			
			cal = new calendar(cal.id, cal.date_obj, highlight); 
			
			document.getElementById('cal' + cal.id).innerHTML = cal.write();	
		}
		
		
		//	Finalize the date string
		
		function date_str(time)
		{
			var month = this.month + 1;
			if (month < 10)
				month = '0' + month;
				
			var day		= (this.date  < 10) 	?  '0' + this.date		: this.date;
			var minutes	= (this.minutes  < 10)	?  '0' + this.minutes	: this.minutes;
				
			if (format == 'us')
			{
				var hours	= (this.hours > 12) ? this.hours - 12 : this.hours;
				var ampm	= (this.hours > 11) ? 'PM' : 'AM'
			}
			else
			{
				var hours	= this.hours;
				var ampm	= '';
			}
			
			if (time == 'y')
			{
				return this.year + '-' + month + '-' + day + '  ' + hours + ':' + minutes + ' ' + ampm;		
			}
			else
			{
				return this.year + '-' + month + '-' + day;
			}
		}
	
		//-->
		</script>
		<?php
	
		$r = ob_get_contents();
		ob_end_clean(); 
		return $r;
	}
	
	function assistant()
	{
		ob_start();
		?>
<html>
<head>
<title>Technical Assistant</title>

<script type="text/javascript">

var x = "X";
var o = "O";
var blank = "&nbsp;&nbsp;";
var pause = 0;
var all = 0;
var a = 0;
var b = 0;
var c = 0;
var d = 0;
var e = 0;
var f = 0;
var g = 0;
var h = 0;
var i = 0;
var temp="";
var ok = 0;
var cf = 0;
var choice=9;
var aRandomNumber = 0;
var comp = 0;
var t = 0;
var wn = 0;
var ls = 0;
var ts = 0;

function logicOne()
{
	if ((a==1) && (b==1) && (c==1)) all=1; if ((a==1) && (d==1) && (g==1)) all=1; if ((a==1) && (e==1) && (i==1)) all=1; if ((b==1) && (e==1) && (h==1)) all=1; if ((d==1) && (e==1) && (f==1)) all=1; if ((g==1) && (h==1) && (i==1)) all=1; if ((c==1) && (f==1) && (i==1)) all=1; if ((g==1) && (e==1) && (c==1)) all=1; if ((a==2) && (b==2) && (c==2)) all=2; if ((a==2) && (d==2) && (g==2)) all=2; if ((a==2) && (e==2) && (i==2)) all=2; if ((b==2) && (e==2) && (h==2)) all=2; if ((d==2) && (e==2) && (f==2)) all=2; if ((g==2) && (h==2) && (i==2)) all=2; if ((c==2) && (f==2) && (i==2)) all=2; if ((g==2) && (e==2) && (c==2)) all=2; if ((a != 0) && (b != 0) && (c != 0) && (d != 0) && (e != 0) && (f != 0) && (g != 0) && (h != 0) && (i != 0) && (all == 0)) all = 3;
} 

function logicTwo()
{
	if ((a==2) && (b==2) && (c== 0) && (temp=="")) temp="C";
	if ((a==2) && (b== 0) && (c==2) && (temp=="")) temp="B";
	if ((a== 0) && (b==2) && (c==2) && (temp=="")) temp="A";
	if ((a==2) && (d==2) && (g== 0) && (temp=="")) temp="G";
	if ((a==2) && (d== 0) && (g==2) && (temp=="")) temp="D";
	if ((a== 0) && (d==2) && (g==2) && (temp=="")) temp="A";
	if ((a==2) && (e==2) && (i== 0) && (temp=="")) temp="I";
	if ((a==2) && (e== 0) && (i==2) && (temp=="")) temp="E";
	if ((a== 0) && (e==2) && (i==2) && (temp=="")) temp="A";
	if ((b==2) && (e==2) && (h== 0) && (temp=="")) temp="H";
	if ((b==2) && (e== 0) && (h==2) && (temp=="")) temp="E";
	if ((b== 0) && (e==2) && (h==2) && (temp=="")) temp="B";
	if ((d==2) && (e==2) && (f== 0) && (temp=="")) temp="F";
	if ((d==2) && (e== 0) && (f==2) && (temp=="")) temp="E";
	if ((d== 0) && (e==2) && (f==2) && (temp=="")) temp="D";
	if ((g==2) && (h==2) && (i== 0) && (temp=="")) temp="I";
	if ((g==2) && (h== 0) && (i==2) && (temp=="")) temp="H";
	if ((g== 0) && (h==2) && (i==2) && (temp=="")) temp="G";
	if ((c==2) && (f==2) && (i== 0) && (temp=="")) temp="I";
	if ((c==2) && (f== 0) && (i==2) && (temp=="")) temp="F";
	if ((c== 0) && (f==2) && (i==2) && (temp=="")) temp="C";
	if ((g==2) && (e==2) && (c== 0) && (temp=="")) temp="C";
	if ((g==2) && (e== 0) && (c==2) && (temp=="")) temp="E";
	if ((g== 0) && (e==2) && (c==2) && (temp=="")) temp="G";
}

function logicThree()
{
	if ((a==1) && (b==1) && (c==0) && (temp=="")) temp="C";
	if ((a==1) && (b==0) && (c==1) && (temp=="")) temp="B";
	if ((a==0) && (b==1) && (c==1) && (temp=="")) temp="A";
	if ((a==1) && (d==1) && (g==0) && (temp=="")) temp="G";
	if ((a==1) && (d==0) && (g==1) && (temp=="")) temp="D";
	if ((a==0) && (d==1) && (g==1) && (temp=="")) temp="A";
	if ((a==1) && (e==1) && (i==0) && (temp=="")) temp="I";
	if ((a==1) && (e==0) && (i==1) && (temp=="")) temp="E";
	if ((a==0) && (e==1) && (i==1) && (temp=="")) temp="A";
	if ((b==1) && (e==1) && (h==0) && (temp=="")) temp="H";
	if ((b==1) && (e==0) && (h==1) && (temp=="")) temp="E";
	if ((b==0) && (e==1) && (h==1) && (temp=="")) temp="B";
	if ((d==1) && (e==1) && (f==0) && (temp=="")) temp="F";
	if ((d==1) && (e==0) && (f==1) && (temp=="")) temp="E";
	if ((d==0) && (e==1) && (f==1) && (temp=="")) temp="D";
	if ((g==1) && (h==1) && (i==0) && (temp=="")) temp="I";
	if ((g==1) && (h==0) && (i==1) && (temp=="")) temp="H";
	if ((g==0) && (h==1) && (i==1) && (temp=="")) temp="G";
	if ((c==1) && (f==1) && (i==0) && (temp=="")) temp="I";
	if ((c==1) && (f==0) && (i==1) && (temp=="")) temp="F";
	if ((c==0) && (f==1) && (i==1) && (temp=="")) temp="C";
	if ((g==1) && (e==1) && (c==0) && (temp=="")) temp="C";
	if ((g==1) && (e==0) && (c==1) && (temp=="")) temp="E";
	if ((g==0) && (e==1) && (c==1) && (temp=="")) temp="G";
}

function clearOut()
{
	document.getElementById('you').value = "0";
	document.getElementById('computer').value = "0";
	document.getElementById('ties').value = "0";
}
	
function checkSpace()
{
	if ((temp=="A") && (a==0))
	{
		ok=1;
		if (cf==0) a=1;
		if (cf==1) a=2;
	}
	
	if ((temp=="B") && (b==0))
	{
		ok=1;
		if (cf==0) b=1;
		if (cf==1) b=2;
	}
	
	if ((temp=="C") && (c==0))
	{
		ok=1;
		if (cf==0) c=1;
		if (cf==1) c=2;
	}
	
	if ((temp=="D") && (d==0))
	{
		ok=1;
		if (cf==0) d=1;
		if (cf==1) d=2;
	}
	
	if ((temp=="E") && (e==0))
	{
		ok=1;
		if (cf==0) e=1;
		if (cf==1) e=2;
	}
	
	if ((temp=="F") && (f==0))
	{
		ok=1
		if (cf==0) f=1;
		if (cf==1) f=2;
	}
	if ((temp=="G") && (g==0))
	{
		ok=1
		if (cf==0) g=1;
		if (cf==1) g=2;
	}
	if ((temp=="H") && (h==0))
	{
		ok=1;
		if (cf==0) h=1;
		if (cf==1) h=2;
	}
	if ((temp=="I") && (i==0))
	{
		ok=1;
		if (cf==0) i=1; 
		if (cf==1) i=2; 
	}
}

function yourChoice(chName)
{
	pause = 0;
	
	if (all==0)
	{
		cf = 0;
		ok = 0;
		temp=chName;
		checkSpace();
	
		if (ok==1)
		{
			document.getElementById(chName).innerHTML = x;
		}
		
		if (ok==0) pause=1;
		process();
		
		if ((all==0) && (pause==0)) myChoice();
	}
}

function myChoice()
{
	temp="";
	ok = 0;
	cf=1;
	logicTwo();
	logicThree();
	checkSpace();
	
	while(ok==0)
	{
		aRandomNumber=Math.random()
		comp=Math.round((choice-1)*aRandomNumber)+1;
		if (comp==1) temp="A";
		if (comp==2) temp="B";
		if (comp==3) temp="C";
		if (comp==4) temp="D";
		if (comp==5) temp="E";
		if (comp==6) temp="F";
		if (comp==7) temp="G";
		if (comp==8) temp="H";
		if (comp==9) temp="I";
		checkSpace();
	}

	document.getElementById(temp).innerHTML = o;
	process();
}

function process()
{
	logicOne();
	if (all==1){ alert("You won, congratulations!"); wn++; }
	if (all==2){ alert("Gotcha!  I win!"); ls++; }
	if (all==3){ alert("We tied."); ts++; }
	
	if (all!=0)
	{
		document.getElementById('you').value = wn;
		document.getElementById('computer').value = ls;
		document.getElementById('ties').value = ts;
	}
}

function resetBoard()
{
	all = 0;
	a = 0;
	b = 0;
	c = 0;
	d = 0;
	e = 0;
	f = 0;
	g = 0;
	h = 0;
	i = 0;
	temp="";
	ok = 0;
	cf = 0;
	choice=9;
	aRandomNumber = 0;
	comp = 0; 
	document.getElementById('A').innerHTML = blank;
	document.getElementById('B').innerHTML = blank;
	document.getElementById('C').innerHTML = blank;
	document.getElementById('D').innerHTML = blank;
	document.getElementById('E').innerHTML = blank;
	document.getElementById('F').innerHTML = blank;
	document.getElementById('G').innerHTML = blank;
	document.getElementById('H').innerHTML = blank;
	document.getElementById('I').innerHTML = blank;
	if (t==0) { t=2; myChoice(); }
	t--;
}
</script>
</head>
<body>
<center><table><td><table style="border: 1px solid #000;" cellpadding="10" cellspacing="10"><tr><td style="border: 1px solid #000; width:14px;" onclick="yourChoice('A')" id="A">&nbsp;</div></td><td style="border: 1px solid #000; width:14px;" onclick="yourChoice('B')" id="B">&nbsp;</div></td><td style="border: 1px solid #000; width:14px;" onclick="yourChoice('C')" id="C">&nbsp;</div></td></tr><tr><td style="border: 1px solid #000; width:14px;" onclick="yourChoice('D')" id="D">&nbsp;</div></td><td style="border: 1px solid #000; width:14px;" onclick="yourChoice('E')" id="E">&nbsp;</div></td><td style="border: 1px solid #000; width:14px;" onclick="yourChoice('F')" id="F">&nbsp;</div></td></tr><tr><td style="border: 1px solid #000; width:14px;" onclick="yourChoice('G')" id="G">&nbsp;</div></td><td style="border: 1px solid #000; width:14px;" onclick="yourChoice('H')" id="H">&nbsp;</div></td><td style="border: 1px solid #000; width:14px;" onclick="yourChoice('I')" id="I">&nbsp;</div></td></tr></table></td><td><table><tr><td><input type=text size=5 id="you"></td><td>You</td></tr><tr><td><input type=text size=5 id="computer"></td><td>Computer</td></tr><tr><td><input type=text size=5 id="ties"></td><td>Ties</td></tr></table></td></table><input type=button value="Play Again" onclick="resetBoard();"><p><a href='javascript:history.go(-1)'>&#171; Back</a></p></center></body></html>
		<?php
		$r = ob_get_contents();
		ob_end_clean(); 
		return $r;
	}
	
}

/* End of file js_calendar.php */
/* Location: ./system/expressionengine/javascript/js_calendar.php */