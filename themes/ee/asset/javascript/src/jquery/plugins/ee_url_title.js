/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/*jslint browser: true, onevar: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, regexp: false, strict: true, newcap: true, immed: true */

/*global jQuery, EE, window, document, console, alert */

(function ($) {

"use strict";

	EE.namespace('EE.publish');
	$.fn.ee_url_title = function (url_title, remove_periods) {

		return this.each(function () {
			var defaultTitle  = (EE.publish.default_entry_title) ? EE.publish.default_entry_title : '',
				separator     = (EE.publish.word_separator) ? EE.publish.word_separator : '_',
				foreignChars  = (EE.publish.foreignChars) ? EE.publish.foreignChars : {},
				urlLength     = (EE.publish.url_length) ? EE.publish.url_length : 200,
				newText       = $(this).val() || '',
				multiReg      = new RegExp(separator + '{2,}', 'g'),
				separatorReg  = (separator !== '_') ? (/\_/g) : (/\-/g),
				dotSeparatorReg = (separator == '_') ? /\._/g : /\.\-/g,
				newTextTemp   = '',
				prefix        = (EE.publish.url_title_prefix) ? EE.publish.url_title_prefix : '',
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

			newText = prefix + newText;
			newText = newText.toLowerCase().replace(separatorReg, separator);

			// Foreign Character Attempt
			for (pos = 0; pos < newText.length; pos++)
			{
				c = newText.charCodeAt(pos);

				if (c >= 32 && c < 128) {
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
			newText = newText.replace(/[^a-z0-9\-\._]/g, '');
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
