/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.9.0
 * @filesource
 */
!function(e){"use strict";function t(){try{if("localStorage"in window&&null!==window.localStorage)return localStorage.setItem("ee_ping",1),localStorage.removeItem("ee_ping"),!0}catch(e){return!1}}function r(e){var t=e.match(/^\t+/gm),r=e.match(/^[ ]+/gm),o=t?t.length:0,i=r?r.length:0;return i>o?!1:!0}function o(e){var t=e.height(),o=e[0].value,i=r(o),n=CodeMirror.fromTextArea(e[0],{lineNumbers:!0,autoCloseBrackets:!0,mode:"ee",smartIndent:!1,indentWithTabs:i,lint:EE.codemirror_linter});return n.setSize(null,t),n}EE.namespace("EE.design");var i=t()?localStorage:{setItem:function(e,t){var r=new Date;r.setTime(r.getTime()+5e3),document.cookie=e+"="+escape(t)+"; expires="+r.toGMTString()+"; path=/"},removeItem:function(e){document.cookie=e+"=; expires=Thu, 01 Jan 1970 00:00:01 GMT"},getItem:function(e){var t=new RegExp("[,; ]"+e+"=([^\\s,;]*)"),r=" "+document.cookie,o=r.match(t);return o?unescape(o[1]):void 0}};e.fn.toggleCodeMirror=function(){this.each(function(){var t=e(this),r=i.getItem("codemirror.disabled"),n=t.data("codemirror.initialized"),a=t.data("codemirror.editor");!n&&!r||n&&r?(a=o(t),i.removeItem("codemirror.disabled"),t.data("codemirror.editor",a)):n&&(a.toTextArea(),t.data("codemirror.editor",!1),i.setItem("codemirror.disabled",!0)),t.data("codemirror.initialized",!0)})}}(jQuery);