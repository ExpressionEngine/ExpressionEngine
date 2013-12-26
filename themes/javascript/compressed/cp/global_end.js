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

$("table").each(function(){var a;$(this).data("table_config")&&(a=$(this).data("table_config"),$.isPlainObject(a)||(a=$.parseJSON(a)),$(this).table(a));jQuery().toggle_all&&$(this).toggle_all()});
