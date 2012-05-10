/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

(function(e){EE.namespace("EE.publish");e.fn.ee_url_title=function(h,f){return this.each(function(){var b=EE.publish.default_entry_title?EE.publish.default_entry_title:"",c=EE.publish.word_separator?EE.publish.word_separator:"_",i=EE.publish.foreignChars?EE.publish.foreignChars:{},a=e(this).val()||"",j=RegExp(c+"{2,}","g"),d=c!=="_"?/\_/g:/\-/g,g="",k=EE.publish.url_title_prefix?EE.publish.url_title_prefix:"";typeof f!=="boolean"&&(f=!1);b!==""&&e(this).attr("id")==="title"&&a.substr(0,b.length)===
b&&(a=a.substr(b.length));a=(k+a).toLowerCase().replace(d,c);for(b=0;b<a.length;b++)d=a.charCodeAt(b),d>=32&&d<128?g+=a.charAt(b):d in i&&(g+=i[d]);a=g.replace("/<(.*?)>/g","");a=a.replace(/\s+/g,c);a=a.replace(/\//g,c);a=a.replace(/[^a-z0-9\-\._]/g,"");a=a.replace(/\+/g,c);a=a.replace(j,c);a=a.replace(/^[\-\_]|[\-\_]$/g,"");a=a.replace(/\.+$/g,"");f&&(a=a.replace(/\./g,""));h&&e(h).val(a.substring(0,75))})}})(jQuery);
