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
$(document).ready(function(){"use strict";var e=$("#snippet_contents"),t=e.height(),n=e[0].value,r=n.match(/^\t+/gm),a=n.match(/^[ ]+/gm),s=r?r.length:0,i=a?a.length:0,m=i>s?!1:!0,o=CodeMirror.fromTextArea(e[0],{lineNumbers:!0,autoCloseBrackets:!0,mode:"ee",smartIndent:!1,indentWithTabs:m});o.setSize(null,t)});