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
$(document).ready(function(){$(".sidebar .folder-list .remove a.m-link").click(function(i){var t="."+$(this).attr("rel");$(t+" .checklist").html(""),// Reset it
$(t+" .checklist").append("<li>"+$(this).data("confirm")+"</li>"),$(t+" input[name='dir_id']").val($(this).data("dir_id")),i.preventDefault()})});