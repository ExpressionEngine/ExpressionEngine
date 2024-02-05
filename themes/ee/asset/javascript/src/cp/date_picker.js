/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

if (EE.cp === undefined) {
	EE.cp = {};
}

EE.cp.datePicker = {

	zeropad: function (val) {
		val += '';
		return (val.length == 2) ? val : '0' + val;
	},

	get_formatted_date: function (date, mask) {
		var year = date.getFullYear(),
			month = date.getMonth() + 1,
			day = date.getDate(),
			dow = date.getDay(),
			hour = date.getHours(),
			minute = date.getMinutes(),
      suffix = 'th',
      days_in_month;

		hour = ((hour + 11) % 12) + 1;

		// Suffix
		if (day == 1) {
			suffix = 'st';
		} else if (day == 2) {
			suffix = 'nd';
		} else if (day == 3) {
			suffix = 'rd';
		} else {
			suffix = 'th';
		}

		// Calculate day of year
		var diff = date - new Date(date.getFullYear(), 0, 0);
		var doy = Math.ceil(diff / 86400000) - 1;

		// Calculate days in this month
		if (month == 2) {
			if (new Date(year, 1, 29).getMonth() == 1) {
				days_in_month = 29;
			} else {
				days_in_month = 28;
			}
		} else if ([4, 6, 9, 11].indexOf(month) > -1) {
			days_in_month = 30;
		} else {
			days_in_month = 31;
		}

		var flags = {
			// Day
			d: this.zeropad(day),
			D: EE.lang.date.days[dow],
			j: day,
			l: EE.lang.date.days[dow],
			N: (dow == 0) ? 7 : dow,
			S: suffix,
			w: dow,
			z: doy,

			// Week
			W: Math.ceil((((date - new Date(date.getFullYear(), 0, 1)) / 86400000) + new Date(date.getFullYear(), 0, 1).getDay()+1)/7),

			// Month
			F: EE.lang.date.months.full[month-1],
			m: this.zeropad(month),
			M: EE.lang.date.months.abbreviated[month-1],
			n: month,
			t: days_in_month,

			// Year
			L: (new Date(year, 1, 29).getMonth() == 1) ? 1 : 0,
			// o: year,
			Y: year,
			y: date.getFullYear().toString().substr(2,2),

			// Time
			a: (date.getHours() < 12) ? 'am' : 'pm',
			A: (date.getHours() < 12) ? 'AM' : 'PM',
			// B: '???',
			g: hour,
			G: date.getHours(),
			h: this.zeropad(hour),
			H: this.zeropad(date.getHours()),
			i: this.zeropad(minute),
			s: this.zeropad(date.getSeconds()),
			u: date.getMilliseconds(),

			// Timezone
			// e: foo,
			// I: foo,
			// O: foo,
			// P: foo,
			// T: foo,
			Z: date.getTimezoneOffset() * 60 * -1,

			// Full Date/Time
			// c: foo,
			// r: foo,
			U: Math.floor(date.getTime() / 1000)

		};

		// hat tip: http://stevenlevithan.com/assets/misc/date.format.js

		var date_format_regex = /%d|%D|%j|%l|%N|%S|%w|%z|%W|%F|%m|%M|%n|%t|%L|%o|%Y|%y|%a|%A|%B|%g|%G|%h|%H|%i|%s|%u|%e|%I|%O|%P|%T|%Z|%c|%r|%U|"[^"]*"|'[^']*'/g;

		return mask.replace(date_format_regex, function (match) {
			match = match.replace('%', '');
			return match in flags ? flags[match] : match.slice(1, match.length - 1);
		});
	},

	Calendar: {
		calendars: [],
		element: null,

		// showing
		year: 2010,
		month: 0,

		init: function(element) {
			var d;
			var selected = null,
			    year     = null,
				month    = null;

			this.element = element;
			this.calendars = [];

			var that = this;

			if ($('.date-picker-wrap').length == 0) {
				var parent = $('body');

				// Likely front end
				if ($('input[name=ACT]').length) {
					parent = $(this.element).closest('form');
				}

				var include_seconds = EE.date.include_seconds
				var timeBlock;

				if (include_seconds == 'y') {
					timeBlock = '<input type="time" value="12:00:00" step="1">';
				} else {
					timeBlock = '<input type="time" value="12:00">';
				}
				var _picker = $('<div class="date-picker-wrap"><div class="date-picker-clip"><div class="date-picker-clip-inner"></div></div><div class="date-picker-footer"><button class="button date-picker-today-button">' + EE.lang.date.today + '</button><div id="date-picker-time-block">' + timeBlock + '</div></div></div>');

				_picker.appendTo(parent);
				var _pickerWidth = _picker.width();

				// listen for clicks on elements classed with .date-picker-next
				$('.date-picker-clip-inner').on('click', '.date-picker-next', function(e){
					EE.cp.datePicker.Month.next();

					// animate the scrolling of .date-picker-clip forwards
					// to the next .date-picker-item
					$('.date-picker-clip').animate({ scrollLeft: '+='+(_pickerWidth+10) }, 200);
					// stop page from reloading
					// the source window and appending # to the URI
					e.preventDefault();
				});

				// listen for clicks on elements classed with .date-picker-back
				$('.date-picker-clip-inner').on('click', '.date-picker-prev', function(e){
					EE.cp.datePicker.Month.prev();

					// animate the scrolling of .date-picker-clip backwards
					// to the previous .date-picker-item
					$('.date-picker-clip').animate({ scrollLeft: '-='+(_pickerWidth+10) }, 200);
					// stop page from reloading
					// the source window and appending # to the URI
					e.preventDefault();
				});

				// listen for clicks on elements classed with .date-picker-back
				$('.date-picker-clip-inner').on('click', '.date-picker-item td a', function(e){
					$('.date-picker-item td.act').removeClass('act');

					$(this).closest('td').addClass('act');

					var timeVal = $('.date-picker-wrap #date-picker-time-block input[type="time"]').val();

					if ($(that.element).val()) {
						var d = new Date($(that.element).data('timestamp') * 1000);
						var lastDayOfCurrentMonth = new Date(that.year, that.month + 1, 0).getDate()
						var dateToSet = lastDayOfCurrentMonth <= $(this).text() ? lastDayOfCurrentMonth : $(this).text()
						d = new Date(that.year, that.month, dateToSet)
					} else {
						var d = new Date(that.year, that.month, $(this).text());
					}

					var now = new Date();

					var hoursVal = timeVal.substring(0,timeVal.indexOf(':'));
					var minutesVal, secondsVal;

					if (include_seconds == 'y') {
						minutesVal = timeVal.substring(timeVal.indexOf(':')+1);
						secondsVal = minutesVal.substring(minutesVal.indexOf(':')+1);
						minutesVal = minutesVal.substring(0,minutesVal.indexOf(':'));
					} else {
						minutesVal = timeVal.substring(timeVal.indexOf(':')+1);
						secondsVal = '00';
					}

					d.setHours(hoursVal);
					d.setMinutes(minutesVal);
					d.setSeconds(secondsVal);

					var date_format = EE.date.date_format;

					// Allow custom date format via data-date-format parameter
					if ($(that.element).data('dateFormat'))
					{
						date_format = $(that.element).data('dateFormat');
					}
					$(that.element).val(EE.cp.datePicker.get_formatted_date(d, date_format)).trigger('change');
					$(that.element).data('timestamp', EE.cp.datePicker.get_formatted_date(d, '%U'));

					$(that.element).focus();
					// $('.date-picker-wrap').toggle();

					e.preventDefault();
					e.stopPropagation();
				});

				$('.date-picker-wrap #date-picker-time-block input[type="time"]').focusout(function(e){
					var timeVal = $(this).val();
					var now = new Date();
					var year = now.getFullYear();
					var month = now.getMonth();
					var day = now.getDate();

					if ($(that.element).val()) {
						d = new Date($(that.element).data('timestamp') * 1000);
					} else {
						var d = new Date(that.year, that.month, day, $(this).text());
					}

					var hoursVal = timeVal.substring(0,timeVal.indexOf(':'));
					var minutesVal, secondsVal;

					if (include_seconds == 'y') {
						minutesVal = timeVal.substring(timeVal.indexOf(':')+1);
						secondsVal = minutesVal.substring(minutesVal.indexOf(':')+1);
						minutesVal = minutesVal.substring(0,minutesVal.indexOf(':'));
					} else {
						minutesVal = timeVal.substring(timeVal.indexOf(':')+1);
						secondsVal = '00';
					}

					d.setHours(hoursVal);
					d.setMinutes(minutesVal);
					d.setSeconds(secondsVal);

					var date_format = EE.date.date_format;

					// Allow custom date format via data-date-format parameter
					if ($(that.element).data('dateFormat'))
					{
						date_format = $(that.element).data('dateFormat');
					}

					$(that.element).val(EE.cp.datePicker.get_formatted_date(d, date_format)).trigger('change');
					$(that.element).data('timestamp', EE.cp.datePicker.get_formatted_date(d, '%U'));

					$(that.element).focus();

					e.preventDefault();
					e.stopPropagation();
				});

				$('.date-picker-wrap').on('click', '.date-picker-today-button', function(e){
					$('.date-picker-item td.act').removeClass('act');
					$(this).closest('td').addClass('act');


					var d = new Date();

					var date_format = EE.date.date_format;

					// Allow custom date format via data-date-format parameter
					if ($(that.element).data('dateFormat'))
					{
						date_format = $(that.element).data('dateFormat');
					}

					$(that.element).val(EE.cp.datePicker.get_formatted_date(d, date_format)).trigger('change');
					$(that.element).data('timestamp', EE.cp.datePicker.get_formatted_date(d, '%U'));

					$(that.element).focus();
					// $('.date-picker-wrap').toggle();

					e.preventDefault();
					e.stopPropagation();
				});

				// Prevent manual scrolling of the huge inner clip div
				$('.date-picker-clip-inner').on('mousewheel', function(e){
					e.preventDefault();
				});
			}

			if ($(this.element).val()) {
				if ($(this.element).data('include_time') != undefined) {
					if ($(this.element).data('include_time')) {
						$('.date-picker-wrap .date-picker-footer').addClass('include_time');
					} else {
						if ($('.date-picker-wrap .date-picker-footer').hasClass('include_time')) {
							$('.date-picker-wrap .date-picker-footer').removeClass('include_time');
						}
					}
				}

				var timestamp = $(this.element).data('timestamp');

				var time_format = EE.date.time_format;
				var value = $(this.element).val();
				var timeHours;

				value = value.substring(value.indexOf(' ') + 1);

				if (time_format === '12') {
					timeHours = value.substring(value.indexOf(' ') + 1);
					value = value.substring(0, value.indexOf(' '));
				}

				var timevalue;
				var include_seconds = EE.date.include_seconds;

				if ( ! timestamp) {
					// this part we need to parse date formats like dd/mm/yyyy and dd-mm-yyyy
					// and don't get NAN as a result, when user put date manualy, not form date_pickare
					var date_format = $(this.element).data('dateFormat')
					// Split date to check date format without time
					var split_date = date_format.split(' ');
					var only_date = split_date[0];
					var other_date_info = split_date.splice(1);
					other_date_info.join(' ')

					var newDay, newDay_index, newMonth, newMonth_index, newYear;
					var val = $(this.element).val();

					if (only_date == '%j/%n/%Y') {
						// value without day
						val = val.substring(val.indexOf('/') + 1);

						// check if DAY has 1 or 2 numbers (1 or 01)
						newDay_index = $(this.element).val().indexOf('/');
						// get DAY
						newDay = $(this.element).val().substring(0, newDay_index);

						// check if MONTH has 1 or 2 numbers (9 or 09)
						newMonth_index = val.indexOf('/');
						// get MONTH
						newMonth = val.substring(0, newMonth_index);

						// get YEAR
						newYear = val.substring(newMonth_index+1);

						var date = [newMonth + '/' + newDay + '/' + newYear];

						date = date.toString();

						d = new Date(Date.parse(date));

					} else if (only_date == '%j-%n-%Y') {
						// value without day
						val = val.substring(val.indexOf('-') + 1);

						// check if DAY has 1 or 2 numbers (1 or 01)
						newDay_index = $(this.element).val().indexOf('-');
						// get DAY
						newDay = $(this.element).val().substring(0, newDay_index);

						// check if MONTH has 1 or 2 numbers (9 or 09)
						newMonth_index = val.indexOf('-');
						// get MONTH
						newMonth = val.substring(0, newMonth_index);

						// get YEAR
						newYear = val.substring(newMonth_index+1);

						var date = [newMonth + '-' + newDay + '-' + newYear];

						date = date.toString();

						d = new Date(Date.parse(date));
					} else {
						d = new Date(Date.parse($(this.element).val()));
					}
				} else {
					d = new Date(timestamp * 1000);
				}

				selected = d.getDate();
				year  = d.getFullYear();
				month = d.getMonth();

				var pickedHours = value.substring(0, value.indexOf(':'));

				if (timeHours == "PM" && parseInt(pickedHours) < 12) {
					pickedHours = parseInt(pickedHours) + 12;
					pickedHours = pickedHours.toString();
				}

				if (timeHours == "AM") {
					if (parseInt(pickedHours) == 12) {
						pickedHours = parseInt(pickedHours) - 12;
					}

					pickedHours = this.addZero(pickedHours);
					pickedHours = pickedHours.toString();
				}

				var pickedMinutes, pickedSeconds;

				if (include_seconds == 'y') {
					pickedMinutes = value.substring(value.indexOf(':')+1);
					pickedSeconds = pickedMinutes.substring(pickedMinutes.indexOf(':')+1);
					pickedMinutes = pickedMinutes.substring(0,pickedMinutes.indexOf(':'));
				} else {
					pickedMinutes = value.substring(value.indexOf(':')+1);
					pickedSeconds = '00';
				}

				if (include_seconds == 'y') {
					timevalue = pickedHours + ":" + pickedMinutes + ":" + pickedSeconds;
				} else {
					timevalue = pickedHours + ":" + pickedMinutes;
				}
			} else {
				d = new Date();
				year  = d.getFullYear();
				month = d.getMonth();

				if ($(this.element).data('include_time') != undefined) {
					if ($(this.element).data('include_time')) {
						$('.date-picker-wrap .date-picker-footer').addClass('include_time');
					} else {
						if ($('.date-picker-wrap .date-picker-footer').hasClass('include_time')) {
							$('.date-picker-wrap .date-picker-footer').removeClass('include_time');
						}
					}
				}

				if (include_seconds == 'y') {
					$('.date-picker-wrap .date-picker-footer input[type="time"]').val('12:00:00');
				} else {
					$('.date-picker-wrap .date-picker-footer input[type="time"]').val('12:00')
				}
			}

			var html = this.generate(year, month);
			if (html != null) {
				$('.date-picker-clip-inner').html(html);
				if (selected) {
					$('.date-picker-item td:contains(' + selected + ')').each(function(){
						if ($(this).text() == selected) {
							$('.date-picker-item td.act').removeClass('act');
							$(this).addClass('act');
						}
					});
					if (timevalue != ':') {
						$('.date-picker-wrap .date-picker-footer input[type="time"]').val(timevalue);
					}
				}
			}
		},

		generate: function(year, month) {
			// Set variables
			this.month = month;
			this.year = year;

			if (this.calendars.indexOf(year + '-' + month) > -1) {
				return null;
			}

			var total		= EE.cp.datePicker.Month.total_days(year, month),
				total_last	= EE.cp.datePicker.Month.total_days(year, month - 1),
				leading		= EE.cp.datePicker.Month.first_day(year, month),
				trailing	= 7 - ((leading + total) % 7),
				today		= new Date,

				prev		= (month - 1 > -1) ? month - 1 : 11,
				next		= (month + 1 < 12) ? month + 1 : 0;

			trailing = (trailing == 7) ? 0 : trailing;

			var daysArr = [];
			var dayIndex;

			switch (EE.date.week_start) {
				case 'sunday':
					dayIndex = 0;
					break;
				case 'monday':
					dayIndex = 1;
					break;
				case 'friday':
					dayIndex = 5;
					break;
				case 'saturday':
					dayIndex = 6;
					break;
				default:
					dayIndex = 0;
			}

			for (var i = dayIndex; i < EE.lang.date.days.length + dayIndex; i++) {
				if (i <= 6 ) {
					daysArr.push('<th>' + EE.lang.date.days[i] + '</th>');
				} else {
					daysArr.push('<th>' + EE.lang.date.days[i-7] + '</th>');
				}
			}

			var preamble = [
				'<div class="date-picker-item">',
				'<div class="date-picker-heading">',
				'<a class="date-picker-prev" href="">' + EE.lang.date.months.abbreviated[prev] + '</a>',
				'<h3>' + EE.lang.date.months.full[month] + ' ' + year + '</h3>',
				'<a class="date-picker-next" href="">' + EE.lang.date.months.abbreviated[next] + '</a>',
				'</div>',
				'<table>',
				'<tr>',
				// '<th>' + EE.lang.date.days[1] + '</th>',
				// '<th>' + EE.lang.date.days[2] + '</th>',
				// '<th>' + EE.lang.date.days[3] + '</th>',
				// '<th>' + EE.lang.date.days[4] + '</th>',
				// '<th>' + EE.lang.date.days[5] + '</th>',
				// '<th>' + EE.lang.date.days[6] + '</th>',
				// '<th>' + EE.lang.date.days[0] + '</th>',
				// '</tr>'
				],
				closeTr = [
					'</tr>'
				],
				closing = [
				'</table>',
				'</div>'
				];

			var out = ['<tr>'],
				out_i = 1,
				days_added = 0;

			// Leading dimmed
			for (var i = 0; i < leading; i++) {
				out[out_i++] = '<td class="empty"></td>';

				days_added++;
			}

			// Main calendar
			for (var j = 0; j < total; j++) {
				if (days_added && days_added % 7 === 0) {
					out[out_i++] = '</tr>';
					out[out_i++] = '<tr>';
				}
				if (today.getFullYear() == year
					&& today.getMonth() == month
					&& today.getDate() == (j + 1)
					&& ! $(this.element).data('timestamp'))
				{
					out[out_i++] = '<td class="act"><a href="#">';
				} else {
					out[out_i++] = '<td><a href="#">';
				}

				out[out_i++] = j + 1;
				out[out_i++] = '</a></td>';

				days_added++;
			}

			// Trailing dimmed
			for (var k = 0; k < trailing; k++) {
				out[out_i++] = '<td class="empty"></td>';

				days_added++;
			}

			out[out_i++] = '</tr>';

			this.calendars.push(year + '-' + month);

			return preamble.join('') + daysArr.join('') + closeTr.join('') + out.join('') + closing.join('');
		},

		addZero: function(i) {
			if (i < 10) {i = "0" + i}
			return i;
		}
	},

	Month: {

		select: function(month) {
			var d = new Date(EE.cp.datePicker.Calendar.year, month);

			return EE.cp.datePicker.Calendar.generate(d.getFullYear(), d.getMonth());
		},

		prev: function() {
			var html = this.select(EE.cp.datePicker.Calendar.month - 1);
			if (html != null) {
				$('.date-picker-clip-inner').prepend(html);
				var pos = $('.date-picker-clip').scrollLeft();
				$('.date-picker-clip').scrollLeft(pos + 280);
			}
		},

		next: function() {
			var html = this.select(EE.cp.datePicker.Calendar.month + 1);
			if (html != null) {
				$('.date-picker-clip-inner').append(html);
			}
		},

		total_days: function(year, month) {
			return 32 - new Date(year, month, 32).getDate();
		},

		first_day: function(year, month) {
			var day;
			switch (EE.date.week_start) {
				case 'sunday':
					day = 1;
					break;
				case 'monday':
					day = 0;
					break;
				case 'friday':
					day = 3;
					break;
				case 'saturday':
					day = 2;
					break;
				default:
					day = 1;
			}

			return new Date(year, month, day).getDay();
		}
	},

	Day: {

		select: function(day) {
			var days_in_month = $('.week a').not('.dim'),
				l = days_in_month.length;

			if (isNaN(day)) {
				day = days_in_month.index(day) + 1;
			}

			if (day > 0 && day <= l) {
				Calendar.select(day - 1);
			}

			return false;
		}
	},

	bind: function (elements) {
		if ( ! (elements instanceof jQuery)) {
			return
		}

		elements.on('focus', function() {
			// find the position of the input clicked
			var pos = $(this).offset();
			EE.cp.datePicker.Calendar.init(this);
			// position and toggle the .date-picker-wrap relative to the input clicked
			$('.date-picker-wrap').css({ 'top': pos.top + 45, 'left': pos.left }).show();
			$('.date-picker-clip').scrollLeft(0);
		});
	}
};

$(document).ready(function () {

	EE.cp.datePicker.bind($('input[rel="date-picker"]').not('.grid-input-form input'));

	// Date fields inside a Grid need to be bound when a new row is added
	if (typeof Grid !== 'undefined')
	{
		Grid.bind('date', 'display', function(cell)
		{
			EE.cp.datePicker.bind($('input[rel="date-picker"]', cell));
		});
	}

	// Date fields inside a Fluid Field need to be bound when a new field is added
	if (typeof FluidField === "object")
	{
		FluidField.on('date', 'add', function(field)
		{
			EE.cp.datePicker.bind($('input[rel="date-picker"]', field));
		});
	}

	$(document).on('focus', 'input,select,button', function(e) {
		EE.cp.datePicker.bind($('input[rel="date-picker"]').not('.grid-input-form input'));

		// Date fields inside a Grid need to be bound when a new row is added
		if (typeof Grid !== 'undefined')
		{
			Grid.bind('date', 'display', function(cell)
			{
				EE.cp.datePicker.bind($('input[rel="date-picker"]', cell));
			});
		}

		// Date fields inside a Fluid Field need to be bound when a new field is added
		if (typeof FluidField === "object")
		{
			FluidField.on('date', 'add', function(field)
			{
				EE.cp.datePicker.bind($('input[rel="date-picker"]', field));
			});
		}

		if ( ! ($(e.target).attr('rel') == 'date-picker')
			&&  ! $(e.target).closest('.date-picker-wrap').length) {
			$('.date-picker-wrap').hide();
		}
	});

	$(document).on('click', function(e) {
		if ( ! ($(e.target).attr('rel') == 'date-picker')
			&&  ! $(e.target).closest('.date-picker-wrap').length) {
			$('.date-picker-wrap').hide();
		}
	});
});
