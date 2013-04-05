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

(function(b){function d(c,e,a){setTimeout(function(){var a=b.data(c,"_interact_cache"),d=c.value;a!==d&&(b.event.trigger("interact",e,c),b.data(c,"_interact_cache",d))},a||0)}b.event.special.interact={setup:function(c,e){if(b.nodeName(this,"form"))b(this).map(function(){return this.elements?b.makeArray(this.elements):this}).filter(function(){return this.name}).map(function(){b.event.special.interact.setup.call(this,c,e)});else{var a;jQuery.nodeName(this,"textarea")?a=!0:jQuery.nodeName(this,"input")?
(a=this.type,a=!a||"text"==a||"password"==a||"search"==a||"url"==a||"email"==a||"tel"==a?!0:!1):a=!1;a?(b.data(this,"_interact_cache",this.value),b.event.add(this,"keyup.specialInteract change.specialInteract",function(){d(this,c)}),b.event.add(this,"input.specialInteract cut.specialInteract paste.specialInteract",function(){d(this,c,25)})):b.event.add(this,"change.specialInteract",function(){b.event.trigger("interact",c,this)})}},teardown:function(){b(this).unbind(".specialInteract")}}})(jQuery);
