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
$(document).ready(function(){$('input[name="perpage"]').on("change keyup",function(e){var a=parseInt($(this).data("threshold")),n=parseInt($(this).val());if(n>=a){if(0==$("#threshold-warning").length){var t='<div id="threshold-warning" class="alert warn">';t=t+"<p>"+$(this).data("threshold-text")+"</p>",t+='<a class="close" href=""></a>',t+="</div>",$("body").prepend(t)}}else $("#threshold-warning").remove()})});