/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */
$(document).ready(function(){var t=function(t){$(".wrap .col-group:nth-child(2) .box").html(t.html),$('input[name="search"]').closest("form").attr("action",t.url),window.history.pushState(null,"",t.url)};
// Submitting the search form
$('input[name="search"]').closest("form").on("submit",function(e){$.ajax({url:$(this).attr("action"),data:$(this).serialize(),type:"POST",dataType:"json",success:t}),e.preventDefault()}),
// Typing into the search form
$('input[name="search"]').on("interact",_.debounce(function(){$(this).closest("form").submit()},150)),
// Selecting a channel filter
$(".filters .sub-menu a, .filters .filter-clear a").on("click",function(e){$.ajax({url:$(this).attr("href"),type:"GET",dataType:"json",success:t}),e.preventDefault()})});