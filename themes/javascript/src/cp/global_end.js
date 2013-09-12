/*jslint browser: true, onevar: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, regexp: true, strict: true, newcap: true, immed: true */

/*global $, jQuery, EE, window, document, console */

"use strict";

/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

/**
 * This file always runs dead last.
 *
 * We use it to initialize optional modules
 * that are loaded by our libraries. For example,
 * the table library loads up the table plugin in
 * a datasource is used.
 *
 * That plugin is ultimately bound here.
 */

// ------------------------------------------------------------------------


// Apply ee_table and ee_toggle_all to any tables that want it
$('table').each(function() {
	var config;

	if ($(this).data('table_config')) {
		config = $(this).data('table_config');

		if ( ! $.isPlainObject(config))	{
			config = $.parseJSON(config);
		}

		$(this).table(config);
	}

	// Apply ee_toggle_all only if it's loaded
	if (jQuery().toggle_all)
	{
		$(this).toggle_all();
	}
});