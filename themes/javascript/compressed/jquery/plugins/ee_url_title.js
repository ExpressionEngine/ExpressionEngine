/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

(function(e){EE.namespace("EE.publish");e.fn.ee_url_title=function(g){return this.each(function(){var b=EE.publish.default_entry_title?EE.publish.default_entry_title:"",c=EE.publish.word_separator?EE.publish.word_separator:"_",h=EE.publish.foreignChars?EE.publish.foreignChars:{},a=e(this).val()||"",i=RegExp(c+"{2,}","g"),d=c!=="_"?/\_/g:/\-/g,f="",j=EE.publish.url_title_prefix?EE.publish.url_title_prefix:"";b!==""&&e(this).attr("id")==="title"&&a.substr(0,b.length)===b&&(a=a.substr(b.length));a=(j+
a).toLowerCase().replace(d,c);for(b=0;b<a.length;b++)d=a.charCodeAt(b),d>=32&&d<128?f+=a.charAt(b):d in h&&(f+=h[d]);a=f.replace("/<(.*?)>/g","");a=a.replace(/\s+/g,c);a=a.replace(/\//g,c);a=a.replace(/[^a-z0-9\-\._]/g,"");a=a.replace(/\+/g,c);a=a.replace(i,c);a=a.replace(/^[\-\_]|[\-\_]$/g,"");a=a.replace(/\.+$/g,"");g&&e(g).val(a.substring(0,75))})}})(jQuery);
