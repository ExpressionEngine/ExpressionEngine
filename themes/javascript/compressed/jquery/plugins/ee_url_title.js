/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

(function(f){EE.namespace("EE.publish");f.fn.ee_url_title=function(h,g){return this.each(function(){var b=EE.publish.default_entry_title?EE.publish.default_entry_title:"",c=EE.publish.word_separator?EE.publish.word_separator:"_",k=EE.publish.foreignChars?EE.publish.foreignChars:{},a=f(this).val()||"",l=RegExp(c+"{2,}","g"),d="_"!==c?/\_/g:/\-/g,e="",m=EE.publish.url_title_prefix?EE.publish.url_title_prefix:"";"boolean"!==typeof g&&(g=!1);""!==b&&"title"===f(this).attr("id")&&a.substr(0,b.length)===
b&&(a=a.substr(b.length));a=(m+a).toLowerCase().replace(d,c);for(b=0;b<a.length;b++)d=a.charCodeAt(b),32<=d&&128>d?e+=a.charAt(b):d in k&&(e+=k[d]);a=e.replace("/<(.*?)>/g","");a=a.replace(/\s+/g,c);a=a.replace(/\//g,c);a=a.replace(/[^a-z0-9\-\._]/g,"");a=a.replace(/\+/g,c);a=a.replace(l,c);a=a.replace(/^[\-\_]|[\-\_]$/g,"");a=a.replace(/\.+$/g,"");g&&(a=a.replace(/\./g,""));h&&f(h).val(a.substring(0,75))})}})(jQuery);
