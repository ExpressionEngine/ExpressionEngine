/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

$.ee_custom_field_select=function(){$("input.input-copy").change(function(){$(this).val($(this).data("original"))});$("input.input-copy").click(function(){var a=$(this);setTimeout(function(){a.select()},1)})};$(document).ready(function(){$.ee_custom_field_select()});
