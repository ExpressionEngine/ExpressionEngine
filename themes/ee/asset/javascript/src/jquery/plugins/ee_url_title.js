/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/*jslint browser: true, onevar: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, regexp: false, strict: true, newcap: true, immed: true */

/*global jQuery, EE, window, document, console, alert */

(function ($) {

	"use strict";

	EE.namespace('EE.publish');
	$.fn.ee_url_title = function (url_title, remove_periods, english_only = true) {
		return this.each(function () {
			var defaultTitle = (EE.publish.default_entry_title) ? EE.publish.default_entry_title : '',
				separator = (EE.publish.word_separator) ? EE.publish.word_separator : '_',
				foreignChars = (EE.publish.foreignChars) ? EE.publish.foreignChars : {},
				urlLength = (EE.publish.url_length) ? EE.publish.url_length : 200,
				newText = $(this).val() || '',
				multiReg = new RegExp(separator + '{2,}', 'g'),
				separatorReg = (separator !== '_') ? (/\_/g) : (/\-/g),
				dotSeparatorReg = (separator == '_') ? /\._/g : /\.\-/g,
				newTextTemp = '',
				prefix = (EE.publish.url_title_prefix) ? EE.publish.url_title_prefix : '',
				arabicRegex = /[\u0600-\u06FF]/g,
				letterMapper = {
					// Latin
					"\u00c0": "A",
					"\u00c1": "A",
					"\u00c2": "A",
					"\u00c3": "A",
					"\u00c4": "A",
					"\u00c5": "A",
					"\u00c6": "AE",
					"\u00c7": "C",
					"\u00c8": "E",
					"\u00c9": "E",
					"\u00ca": "E",
					"\u00cb": "E",
					"\u00cc": "I",
					"\u00cd": "I",
					"\u00ce": "I",
					"\u00cf": "I",
					"\u00d0": "D",
					"\u00d1": "N",
					"\u00d2": "O",
					"\u00d3": "O",
					"\u00d4": "O",
					"\u00d5": "O",
					"\u00d6": "O",
					"\u0150": "O",
					"\u00d8": "O",
					"\u00d9": "U",
					"\u00da": "U",
					"\u00db": "U",
					"\u00dc": "U",
					"\u0170": "U",
					"\u00dd": "Y",
					"\u00de": "TH",
					"\u00df": "ss",
					"\u00e0": "a",
					"\u00e1": "a",
					"\u00e2": "a",
					"\u00e3": "a",
					"\u00e4": "a",
					"\u00e5": "a",
					"\u00e6": "ae",
					"\u00e7": "c",
					"\u00e8": "e",
					"\u00e9": "e",
					"\u00ea": "e",
					"\u00eb": "e",
					"\u00ec": "i",
					"\u00ed": "i",
					"\u00ee": "i",
					"\u00ef": "i",
					"\u00f0": "d",
					"\u00f1": "n",
					"\u00f2": "o",
					"\u00f3": "o",
					"\u00f4": "o",
					"\u00f5": "o",
					"\u00f6": "o",
					"\u0151": "o",
					"\u00f8": "o",
					"\u00f9": "u",
					"\u00fa": "u",
					"\u00fb": "u",
					"\u00fc": "u",
					"\u0171": "u",
					"\u00fd": "y",
					"\u00fe": "th",
					"\u00ff": "y",
					"\u00a9": "(c)",

					// Greek
					"\u0391": "A",
					"\u0392": "B",
					"\u0393": "G",
					"\u0394": "D",
					"\u0395": "E",
					"\u0396": "Z",
					"\u0397": "H",
					"\u0398": "8",
					"\u0399": "I",
					"\u039a": "K",
					"\u039b": "L",
					"\u039c": "M",
					"\u039d": "N",
					"\u039e": "3",
					"\u039f": "O",
					"\u03a0": "P",
					"\u03a1": "R",
					"\u03a3": "S",
					"\u03a4": "T",
					"\u03a5": "Y",
					"\u03a6": "F",
					"\u03a7": "X",
					"\u03a8": "PS",
					"\u03a9": "W",
					"\u0386": "A",
					"\u0388": "E",
					"\u038a": "I",
					"\u038c": "O",
					"\u038e": "Y",
					"\u0389": "H",
					"\u038f": "W",
					"\u03aa": "I",
					"\u03ab": "Y",
					"\u03b1": "a",
					"\u03b2": "b",
					"\u03b3": "g",
					"\u03b4": "d",
					"\u03b5": "e",
					"\u03b6": "z",
					"\u03b7": "h",
					"\u03b8": "8",
					"\u03b9": "i",
					"\u03ba": "k",
					"\u03bb": "l",
					"\u03bc": "m",
					"\u03bd": "n",
					"\u03be": "3",
					"\u03bf": "o",
					"\u03c0": "p",
					"\u03c1": "r",
					"\u03c3": "s",
					"\u03c4": "t",
					"\u03c5": "y",
					"\u03c6": "f",
					"\u03c7": "x",
					"\u03c8": "ps",
					"\u03c9": "w",
					"\u03ac": "a",
					"\u03ad": "e",
					"\u03af": "i",
					"\u03cc": "o",
					"\u03cd": "y",
					"\u03ae": "h",
					"\u03ce": "w",
					"\u03c2": "s",
					"\u03ca": "i",
					"\u03b0": "y",
					"\u03cb": "y",
					"\u0390": "i",

					// Turkish
					"\u015e": "S",
					"\u0130": "I",
					"\u00c7": "C",
					"\u00dc": "U",
					"\u00d6": "O",
					"\u011e": "G",
					"\u015f": "s",
					"\u0131": "i",
					"\u00e7": "c",
					"\u00fc": "u",
					"\u00f6": "o",
					"\u011f": "g",

					// Russian
					"\u0410": "A",
					"\u0411": "B",
					"\u0412": "V",
					"\u0413": "G",
					"\u0414": "D",
					"\u0415": "E",
					"\u0401": "Yo",
					"\u0416": "Zh",
					"\u0417": "Z",
					"\u0418": "I",
					"\u0419": "J",
					"\u041a": "K",
					"\u041b": "L",
					"\u041c": "M",
					"\u041d": "N",
					"\u041e": "O",
					"\u041f": "P",
					"\u0420": "R",
					"\u0421": "S",
					"\u0422": "T",
					"\u0423": "U",
					"\u0424": "F",
					"\u0425": "H",
					"\u0426": "C",
					"\u0427": "Ch",
					"\u0428": "Sh",
					"\u0429": "Sh",
					"\u042a": "",
					"\u042b": "Y",
					"\u042c": "",
					"\u042d": "E",
					"\u042e": "Yu",
					"\u042f": "Ya",
					"\u0430": "a",
					"\u0431": "b",
					"\u0432": "v",
					"\u0433": "g",
					"\u0434": "d",
					"\u0435": "e",
					"\u0451": "yo",
					"\u0436": "zh",
					"\u0437": "z",
					"\u0438": "i",
					"\u0439": "j",
					"\u043a": "k",
					"\u043b": "l",
					"\u043c": "m",
					"\u043d": "n",
					"\u043e": "o",
					"\u043f": "p",
					"\u0440": "r",
					"\u0441": "s",
					"\u0442": "t",
					"\u0443": "u",
					"\u0444": "f",
					"\u0445": "h",
					"\u0446": "c",
					"\u0447": "ch",
					"\u0448": "sh",
					"\u0449": "sh",
					"\u044a": "",
					"\u044b": "y",
					"\u044c": "",
					"\u044d": "e",
					"\u044e": "yu",
					"\u044f": "ya",

					// Ukranian (❤)
					"\u0404": "Ye",
					"\u0406": "I",
					"\u0407": "Yi",
					"\u0490": "G",
					"\u0454": "ye",
					"\u0456": "i",
					"\u0457": "yi",
					"\u0491": "g",

					// Czech
					"\u010c": "C",
					"\u010e": "D",
					"\u011a": "E",
					"\u0147": "N",
					"\u0158": "R",
					"\u0160": "S",
					"\u0164": "T",
					"\u016e": "U",
					"\u017d": "Z",
					"\u010d": "c",
					"\u010f": "d",
					"\u011b": "e",
					"\u0148": "n",
					"\u0159": "r",
					"\u0161": "s",
					"\u0165": "t",
					"\u016f": "u",
					"\u017e": "z",

					// Polish
					"\u0104": "A",
					"\u0106": "C",
					"\u0118": "e",
					"\u0141": "L",
					"\u0143": "N",
					"\u00d3": "o",
					"\u015a": "S",
					"\u0179": "Z",
					"\u017b": "Z",
					"\u0105": "a",
					"\u0107": "c",
					"\u0119": "e",
					"\u0142": "l",
					"\u0144": "n",
					"\u00f3": "o",
					"\u015b": "s",
					"\u017a": "z",
					"\u017c": "z",

					// Latvian
					"\u0100": "A",
					"\u010c": "C",
					"\u0112": "E",
					"\u0122": "G",
					"\u012a": "i",
					"\u0136": "k",
					"\u013b": "L",
					"\u0145": "N",
					"\u0160": "S",
					"\u016a": "u",
					"\u017d": "Z",
					"\u0101": "a",
					"\u010d": "c",
					"\u0113": "e",
					"\u0123": "g",
					"\u012b": "i",
					"\u0137": "k",
					"\u013c": "l",
					"\u0146": "n",
					"\u0161": "s",
					"\u016b": "u",
					"\u017e": "z",

					// Armenian
					"\u0587": "ev",
					"\u0578\u0582": "u",
					"\u0531": "A",
					"\u0532": "B",
					"\u0533": "G",
					"\u0534": "D",
					"\u0535": "Ye",
					"\u0536": "Z",
					"\u0537": "E",
					"\u0538": "Y",
					"\u0539": "T",
					"\u053a": "Zh",
					"\u053b": "I",
					"\u053c": "L",
					"\u053d": "X",
					"\u053e": "Tc",
					"\u053f": "K",
					"\u0540": "H",
					"\u0541": "Dz",
					"\u0542": "Gh",
					"\u0543": "Tch",
					"\u0544": "M",
					"\u0545": "Y",
					"\u0546": "N",
					"\u0547": "Sh",
					"\u0548": "Vo",
					"\u0549": "Ch",
					"\u054a": "P",
					"\u054b": "J",
					"\u054c": "R",
					"\u054d": "S",
					"\u054e": "V",
					"\u054f": "T",
					"\u0550": "R",
					"\u0551": "C",
					"\u0553": "P",
					"\u0554": "Q",
					"\u0555": "O",
					"\u0556": "F",
					"\u0548\u0582": "U",
					"\u0561": "a",
					"\u0562": "b",
					"\u0563": "g",
					"\u0564": "d",
					"\u0565": "e",
					"\u0566": "z",
					"\u0567": "e",
					"\u0568": "y",
					"\u0569": "th",
					"\u056a": "zh",
					"\u056b": "i",
					"\u056c": "l",
					"\u056d": "x",
					"\u056e": "ts",
					"\u056f": "k",
					"\u0570": "h",
					"\u0571": "dz",
					"\u0572": "gh",
					"\u0573": "ch",
					"\u0574": "m",
					"\u0575": "y",
					"\u0576": "n",
					"\u0577": "sh",
					"\u0578": "o",
					"\u0579": "ch",
					"\u057a": "p",
					"\u057b": "j",
					"\u057c": "r",
					"\u057d": "s",
					"\u057e": "v",
					"\u057f": "t",
					"\u0580": "r",
					"\u0581": "c",
					"\u0583": "p",
					"\u0584": "q",
					"\u0585": "o",
					"\u0586": "f",
					"\u2116": "#",
					"\u2014": "-",
					"\u00ab": "",
					"\u00bb": "",
					"\u2026": "",

					// Feel free to add more
				},
				pos,
				c;

			// Make sure remove_periods has a default
			if (typeof remove_periods !== 'boolean') {
				remove_periods = false;
			};

			if (defaultTitle !== '' && $(this).attr('id') === "title") {
				if (newText.substr(0, defaultTitle.length) === defaultTitle) {
					newText = newText.substr(defaultTitle.length);
				}
			}

			if (!english_only) {
				// Normlize Arabic
				if (arabicRegex.test(newText)) {
					newText = newText.toLocaleString(/[\u0600-\u06FF\u0750-\u077F]/)
					newText = newText.replace(/([^\u0621-\u063A\u0641-\u064A\u0660-\u0669a-zA-Z 0-9])/g, '')
					newText = newText.replace(/(آ|إ|أ)/g, 'ا')
					newText = newText.replace(/(ئ|ؤ)/g, 'ء')
					newText = newText.replace(/(ى)/g, 'ي')
					var starter = 0x660;
					for (var i = 0; i < 10; i++) {
						newText = newText.replace(String.fromCharCode(starter + i), String.fromCharCode(48 + i));
					}
				}

				// Convert non-english to english
				for (var key in letterMapper) {
					newText = newText.replace(RegExp(key, 'g'), letterMapper[key])
				}
			}

			newText = prefix + newText;
			newText = newText.toLowerCase().replace(separatorReg, separator);

			// Foreign Character Attempt
			for (pos = 0; pos < newText.length; pos++) {
				c = newText.charCodeAt(pos);

				if (c >= 32 && c < 128) {
					newTextTemp += newText.charAt(pos);

					// Arabic Characters
				} else if (c >= 1536 && c <= 1791) {
					newTextTemp += newText.charAt(pos);
				}
				else if (c in foreignChars) {
					newTextTemp += foreignChars[c];
				}
			}

			newText = newTextTemp;

			newText = newText.replace(/<(.*?)>/g, ''); // Strip HTML
			newText = newText.replace(/&[a-zA-Z]+;/g, ''); // Strip HTML entites
			newText = newText.replace(/\[\/?(b|i|u|del|em|ins|strong|pre|code|abbr|span|sup|sub|color|size|strike|url|email|style)\b=?.*?\]/g, ''); // Strip valid inline BBCode
			newText = newText.replace(/\s+/g, separator);
			newText = newText.replace(/\//g, separator);
			newText = newText.replace(english_only ? /[^a-z0-9-\._]/g : /[^a-z0-9\u0600-\u06FF\-\._]/g, '');
			newText = newText.replace(dotSeparatorReg, separator);
			newText = newText.replace(/\+/g, separator);
			newText = newText.replace(multiReg, separator);
			newText = newText.replace(/^[\-\_]|[\-\_]$/g, '');
			newText = newText.replace(/\.+$/g, '');

			if (remove_periods) {
				newText = newText.replace(/\./g, '');
			};

			if (url_title) {
				$(url_title).val(newText.substring(0, urlLength));
				$(url_title).trigger('change');
			}
		});
	};
}(jQuery));
