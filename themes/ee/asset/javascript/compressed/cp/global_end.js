/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
!function(t){"use strict";/**
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
t("table").each(function(){var a;t(this).data("table_config")&&(a=t(this).data("table_config"),t.isPlainObject(a)||(a=t.parseJSON(a)),t(this).table(a)),
// Apply ee_toggle_all only if it's loaded
jQuery().toggle_all&&t(this).toggle_all()})}(jQuery);