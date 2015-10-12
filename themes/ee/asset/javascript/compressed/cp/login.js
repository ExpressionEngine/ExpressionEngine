/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
!function(t){"use strict";t(window).bind("onload",function(){
// Reset button state in case user presses the back button
// after a form submission
t("input.btn").removeClass("work")}),t(document).ready(function(){
// Bind form submission to update button text
t("form").submit(function(n){var o=t("input.btn",this);
// Add "work" class to make the buttons pulsate
o.addClass("work"),
// Update the button text to the value of its "work-text"
// data attribute
""!=o.data("work-text")&&o.attr("value",o.data("work-text"))})})}(jQuery);