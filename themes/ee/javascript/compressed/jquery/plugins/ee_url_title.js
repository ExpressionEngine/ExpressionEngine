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
!function(e){"use strict";EE.namespace("EE.publish"),e.fn.ee_url_title=function(r,t){return this.each(function(){var l,a,i=EE.publish.default_entry_title?EE.publish.default_entry_title:"",s=EE.publish.word_separator?EE.publish.word_separator:"_",p=EE.publish.foreignChars?EE.publish.foreignChars:{},u=e(this).val()||"",E=new RegExp(s+"{2,}","g"),h="_"!==s?/\_/g:/\-/g,n="",c=EE.publish.url_title_prefix?EE.publish.url_title_prefix:"";for("boolean"!=typeof t&&(t=!1),""!==i&&"title"===e(this).attr("id")&&u.substr(0,i.length)===i&&(u=u.substr(i.length)),u=c+u,u=u.toLowerCase().replace(h,s),l=0;l<u.length;l++)a=u.charCodeAt(l),a>=32&&128>a?n+=u.charAt(l):a in p&&(n+=p[a]);u=n,u=u.replace("/<(.*?)>/g",""),u=u.replace(/\s+/g,s),u=u.replace(/\//g,s),u=u.replace(/[^a-z0-9\-\._]/g,""),u=u.replace(/\+/g,s),u=u.replace(E,s),u=u.replace(/^[\-\_]|[\-\_]$/g,""),u=u.replace(/\.+$/g,""),t&&(u=u.replace(/\./g,"")),r&&e(r).val(u.substring(0,75))})}}(jQuery);