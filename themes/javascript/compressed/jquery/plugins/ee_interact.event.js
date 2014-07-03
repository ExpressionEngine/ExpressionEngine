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
!function(t){function e(e,n){return t(e).map(function(){return this.elements?t.makeArray(this.elements):this}).filter(function(){return this.name}).map(n)}function n(t){if(jQuery.nodeName(t,"textarea"))return!0;if(!jQuery.nodeName(t,"input"))return!1;var e=t.type;return e?"text"==e||"password"==e||"search"==e||"url"==e||"email"==e||"tel"==e?!0:!1:!0}function i(e,n,i){i=i||0,setTimeout(function(){var i=t.data(e,"_interact_cache"),a=e.value;i!==a&&(t.event.trigger("interact",n,e),t.data(e,"_interact_cache",a))},i)}t.event.special.interact={setup:function(a,c){return t.nodeName(this,"form")?void e(this,function(){t.event.special.interact.setup.call(this,a,c)}):n(this)?(t.data(this,"_interact_cache",this.value),t.event.add(this,"keyup.specialInteract change.specialInteract",function(){i(this,a)}),void t.event.add(this,"input.specialInteract cut.specialInteract paste.specialInteract",function(){i(this,a,25)})):void t.event.add(this,"change.specialInteract",function(){t.event.trigger("interact",a,this)})},teardown:function(){t(this).unbind(".specialInteract")}}}(jQuery);