/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */
$(document).ready(function(){$(".light .toolbar .remove a.m-link").click(function(t){var i="."+$(this).attr("rel");$(i+" .checklist").html(""),// Reset it
$(i+" .checklist").append("<li>"+$(this).data("confirm")+"</li>"),$(i+" input[name='id']").val($(this).data("id")),t.preventDefault()})});