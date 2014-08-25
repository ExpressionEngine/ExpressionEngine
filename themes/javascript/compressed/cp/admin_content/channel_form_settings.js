/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.7
 * @filesource
 */
!function(){var s,t=$("#channel-form-settings :radio").filter("[name^=allow_guest_posts]");s=function(){var s=(this.id,this.value),t=$(this).siblings().add(this);$(this).closest("tr").find("select").last().prop("disabled","n"==s),$(this).closest("tr").find(":radio").not(t).prop("disabled","n"==s)},t.change(s),t.filter(":checked").each(s)}(jQuery);