$(document).ready(function(){

	function zeropad(val) {
		val += '';
		return (val.length == 2) ? val : '0' + val;
	}

	// hat tip: http://stevenlevithan.com/assets/misc/date.format.js

	var date_format_regex = /%d|%D|%j|%l|%N|%S|%w|%z|%W|%F|%m|%M|%n|%t|%L|%o|%Y|%y|%a|%A|%B|%g|%G|%h|%H|%i|%s|%u|%e|%I|%O|%P|%T|%Z|%c|%r|%U|"[^"]*"|'[^']*'/g;

	function get_formatted_date(date, mask) {
		var year = date.getFullYear(),
			month = date.getMonth() + 1,
			day = date.getDate(),
			dow = date.getDay(),
			hour = date.getHours(),
			minute = date.getMinutes();

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
		diff = date - new Date(date.getFullYear(), 0, 0);
		doy = Math.ceil(diff / 86400000) - 1;

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
			d: zeropad(day),
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
			m: zeropad(month),
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
			h: zeropad(hour),
			H: zeropad(date.getHours()),
			i: zeropad(minute),
			s: zeropad(date.getSeconds()),
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

		return mask.replace(date_format_regex, function (match) {
			match = match.replace('%', '');
			return match in flags ? flags[match] : match.slice(1, match.length - 1);
		});
	}

	var Calendar = {
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

			if ($('.date-picker-wrap').length == 0) {
				$('body').append('<div class="date-picker-wrap"><div class="date-picker-clip"><div class="date-picker-clip-inner"></div></div></div>');

				// listen for clicks on elements classed with .date-picker-next
				$('.date-picker-clip-inner').on('click', '.date-picker-next', function(e){
					Month.next();

					// animate the scrolling of .date-picker-clip forwards
					// to the next .date-picker-item
					$('.date-picker-clip').animate({ scrollLeft: '+=260' }, 200);
					// stop page from reloading
					// the source window and appending # to the URI
					e.preventDefault();
				});

				// listen for clicks on elements classed with .date-picker-back
				$('.date-picker-clip-inner').on('click', '.date-picker-prev', function(e){
					Month.prev();

					// animate the scrolling of .date-picker-clip backwards
					// to the previous .date-picker-item
					$('.date-picker-clip').animate({ scrollLeft: '-=260' }, 200);
					// stop page from reloading
					// the source window and appending # to the URI
					e.preventDefault();
				});

				// listen for clicks on elements classed with .date-picker-back
				$('.date-picker-clip-inner').on('click', '.date-picker-item td a', function(e){
					$('.date-picker-item td.act').removeClass('act');
					$(this).closest('td').addClass('act');

					if ($(Calendar.element).val()) {
						var d = new Date($(Calendar.element).attr('data-timestamp') * 1000);
						d.setYear(Calendar.year);
						d.setMonth(Calendar.month);
						d.setDate($(this).text());
					} else {
						var d = new Date(Calendar.year, Calendar.month, $(this).text());
					}

					$(Calendar.element).val(get_formatted_date(d, EE.date.date_format));
					$(Calendar.element).attr('data-timestamp', get_formatted_date(d, '%U'));

					$(Calendar.element).focus();
					$('.date-picker-wrap').toggle();

					e.preventDefault();
				});
			}

			if ($(this.element).val()) {
				d = new Date($(this.element).attr('data-timestamp') * 1000);
				selected = d.getUTCDate();
				year  = d.getUTCFullYear();
				month = d.getUTCMonth();
			} else {
				d = new Date();
				year  = d.getFullYear();
				month = d.getMonth();
			}

			var html = this.generate(year, month);
			if (html != null) {
				$('.date-picker-clip-inner').html(html);
				if (selected) {
					$('.date-picker-item td:contains(' + selected + ')').each(function(){
						if ($(this).text() == selected) {
							$(this).addClass('act');
						}
					});
				}
			}
		},

		generate: function(year, month) {
			// Set variables
			this.month = month;
			this.year = year;

			if (Calendar.calendars.indexOf(year + '-' + month) > -1) {
				return null;
			}

			var total		= Month.total_days(year, month),
				total_last	= Month.total_days(year, month - 1),
				leading		= Month.first_day(year, month),
				trailing	= 7 - ((leading + total) % 7),

				prev		= (month - 1 > -1) ? month - 1 : 11,
				next		= (month + 1 < 12) ? month + 1 : 0;

			trailing = (trailing == 7) ? 0 : trailing;

			var preamble = [
				'<div class="date-picker-item">',
				'<div class="date-picker-heading">',
				'<a class="date-picker-prev" href="">' + EE.lang.date.months.abbreviated[prev] + '</a>',
				'<h3>' + EE.lang.date.months.full[month] + ' ' + year + '</h3>',
				'<a class="date-picker-next" href="">' + EE.lang.date.months.abbreviated[next] + '</a>',
				'</div>',
				'<table>',
				'<tr>',
				'<th>' + EE.lang.date.days[0] + '</th>',
				'<th>' + EE.lang.date.days[1] + '</th>',
				'<th>' + EE.lang.date.days[2] + '</th>',
				'<th>' + EE.lang.date.days[3] + '</th>',
				'<th>' + EE.lang.date.days[4] + '</th>',
				'<th>' + EE.lang.date.days[5] + '</th>',
				'<th>' + EE.lang.date.days[6] + '</th>',
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

				out[out_i++] = '<td><a href="#">';
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

			return preamble.join('') + out.join('') + closing.join('');
		}

	};

	var Month = {

		select: function(month) {
			var d = new Date(Calendar.year, month);

			return Calendar.generate(d.getFullYear(), d.getMonth());
		},

		prev: function() {
			var html = this.select(Calendar.month - 1);
			if (html != null) {
				$('.date-picker-clip-inner').prepend(html);
				var pos = $('.date-picker-clip').scrollLeft();
				$('.date-picker-clip').scrollLeft(pos + 260);
			}
		},

		next: function() {
			var html = this.select(Calendar.month + 1);
			if (html != null) {
				$('.date-picker-clip-inner').append(html);
			}
		},

		total_days: function(year, month) {
			return 32 - new Date(year, month, 32).getDate();
		},

		first_day: function(year, month) {
			return new Date(year, month, 1).getDay();
		}
	};

	var Day = {

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
	};

	// listen for clicks on inputs with rel date-picker
	$('input[rel="date-picker"]').on('click', function(){
		// find the position of the input clicked
		var pos = $(this).offset();
		Calendar.init(this);
		// position and toggle the .date-picker-wrap relative to the input clicked
		$('.date-picker-wrap').css({ 'top': pos.top + 30, 'left': pos.left }).show();
	});

	$(document).on('click',function(e){
		if ( ! ($(e.target).attr('rel') == 'date-picker')
			&&  ! $(e.target).closest('.date-picker-wrap').length) {
			$('.date-picker-wrap').hide();
		}
	});

}); // close (document).ready