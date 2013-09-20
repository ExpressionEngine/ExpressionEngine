/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.7.1
 * @filesource
 */

(function(e){function f(){try{if("localStorage"in window&&null!==window.localStorage)return localStorage.setItem("ee_ping",1),localStorage.removeItem("ee_ping"),!0}catch(a){return!1}}var j=f()?localStorage:{setItem:function(a,b){var c=new Date;c.setTime(c.getTime()+5E3);document.cookie=a+"="+escape(b)+"; expires="+c.toGMTString()+"; path=/"},removeItem:function(a){document.cookie=a+"=; expires=Thu, 01 Jan 1970 00:00:01 GMT"},getItem:function(a){return(a=(" "+document.cookie).match(RegExp("[,; ]"+
a+"=([^\\s,;]*)")))?unescape(a[1]):void 0}},g=e.now()+(65536*(1+Math.random())|0).toString(16).substring(1),h="ee_broadcast",l=f()?20:1500,k=e(window);EE.BROADCAST_UID=g;var d={_queue:[],_waiting:!1,_lastMessage:void 0,sendMessage:function(a){this._queue.push(function(b){j.setItem(h,a);d._lastMessage=a;setTimeout(function(){j.removeItem(h);d._lastMessage=void 0;b()},l)});this.dequeue()},receiveMessage:function(a){var a=JSON.parse(a),b=a.uid;b!=g&&k.trigger({type:"_broadcastMessage"+(a.ns?"."+a.ns:
""),sender:b,receiver:g},a.data)},dequeue:function(){if(!this._waiting)this._waiting=!0,this._queue.shift()(function(){d._waiting=!1;d._queue.length&&d.dequeue()})}},i={local:{setup:function(){k.on("storage",function(a){a=a.originalEvent;a.key==h&&a.newValue&&d.receiveMessage(a.newValue)})},teardown:function(){k.off("storage")}},cookie:{_timer:null,setup:function(){function a(){var c=j.getItem(h);c!=b&&(d.receiveMessage(c),b=c);i.cookie._timer=setTimeout(a,1E3)}var b=void 0;a()},teardown:function(){clearTimeout(i.cookie._timer)}}};
e.event.special.broadcast={setup:function(){i[f()?"local":"cookie"].setup()},add:function(a){e.event.add(this,"_broadcastMessage"+(a.namespace?"."+a.namespace:""),a.handler)},trigger:function(a,b){if(a.target==window){var c=JSON.stringify({ns:a.namespace,uid:g,data:e.makeArray(arguments).slice(1)});d.sendMessage(c);return!1}},teardown:function(){e(this).unbind("_broadcastMessage");i[f()?"local":"cookie"].teardown()}}})(jQuery);
