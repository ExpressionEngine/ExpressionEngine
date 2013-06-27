/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.7
 * @filesource
 */

(function(){var b=$("#channel-form-settings :radio").filter("[name^=allow_guest_posts]"),a;a=function(){var a=this.value,b=$(this).siblings().add(this);$(this).closest("tr").find("select").last().prop("disabled","n"==a);$(this).closest("tr").find(":radio").not(b).prop("disabled","n"==a)};b.change(a);b.filter(":checked").each(a)})(jQuery);
