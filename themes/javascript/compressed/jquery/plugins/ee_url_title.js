/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

(function(f){EE.namespace("EE.publish");f.fn.ee_url_title=function(h,g){return this.each(function(){var b=EE.publish.default_entry_title?EE.publish.default_entry_title:"",c=EE.publish.word_separator?EE.publish.word_separator:"_",i=EE.publish.foreignChars?EE.publish.foreignChars:{},a=f(this).val()||"",j=RegExp(c+"{2,}","g"),d="_"!==c?/\_/g:/\-/g,e="",k=EE.publish.url_title_prefix?EE.publish.url_title_prefix:"";"boolean"!==typeof g&&(g=!1);""!==b&&"title"===f(this).attr("id")&&a.substr(0,b.length)===
b&&(a=a.substr(b.length));a=(k+a).toLowerCase().replace(d,c);for(b=0;b<a.length;b++)d=a.charCodeAt(b),32<=d&&128>d?e+=a.charAt(b):d in i&&(e+=i[d]);a=e.replace("/<(.*?)>/g","");a=a.replace(/\s+/g,c);a=a.replace(/\//g,c);a=a.replace(/[^a-z0-9\-\._]/g,"");a=a.replace(/\+/g,c);a=a.replace(j,c);a=a.replace(/^[\-\_]|[\-\_]$/g,"");a=a.replace(/\.+$/g,"");g&&(a=a.replace(/\./g,""));h&&f(h).val(a.substring(0,75))})}})(jQuery);
