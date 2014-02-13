/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

(function(a){a("table").each(function(){var b;a(this).data("table_config")&&(b=a(this).data("table_config"),a.isPlainObject(b)||(b=a.parseJSON(b)),a(this).table(b));jQuery().toggle_all&&a(this).toggle_all()})})(jQuery);
