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

(function(e){function f(){try{if("localStorage"in window&&null!==window.localStorage)return localStorage.setItem("ee_ping",1),localStorage.removeItem("ee_ping"),!0}catch(a){return!1}}var l=f()?localStorage:{setItem:function(a,c){var b=new Date;b.setTime(b.getTime()+5E3);document.cookie=a+"="+escape(c)+"; expires="+b.toGMTString()+"; path=/"},removeItem:function(a){document.cookie=a+"=; expires=Thu, 01 Jan 1970 00:00:01 GMT"},getItem:function(a){return(a=(" "+document.cookie).match(RegExp("[,; ]"+
a+"=([^\\s,;]*)")))?unescape(a[1]):void 0}},g=e.now()+(65536*(1+Math.random())|0).toString(16).substring(1),h="ee_broadcast",n=f()?20:1500,m=e(window);EE.BROADCAST_UID=g;var d={_queue:[],_waiting:!1,_lastMessage:void 0,sendMessage:function(a){this._queue.push(function(c){l.setItem(h,a);d._lastMessage=a;setTimeout(function(){l.removeItem(h);d._lastMessage=void 0;c()},n)});this.dequeue()},receiveMessage:function(a){a=JSON.parse(a);var c=a.ns?"."+a.ns:"",b=a.uid;b!=g&&m.trigger({type:"_broadcastMessage"+
c,sender:b,receiver:g},a.data)},dequeue:function(){this._waiting||(this._waiting=!0,this._queue.shift()(function(){d._waiting=!1;d._queue.length&&d.dequeue()}))}},k={local:{setup:function(){m.on("storage",function(a){a=a.originalEvent;a.key==h&&a.newValue&&d.receiveMessage(a.newValue)})},teardown:function(){m.off("storage")}},cookie:{_timer:null,setup:function(){function a(){var b=l.getItem(h);b!=c&&(d.receiveMessage(b),c=b);k.cookie._timer=setTimeout(a,1E3)}var c=void 0;a()},teardown:function(){clearTimeout(k.cookie._timer)}}};
e.event.special.broadcast={setup:function(){k[f()?"local":"cookie"].setup()},add:function(a){e.event.add(this,"_broadcastMessage"+(a.namespace?"."+a.namespace:""),a.handler)},trigger:function(a,c){if(a.target==window){var b=JSON.stringify({ns:a.namespace,uid:g,data:e.makeArray(arguments).slice(1)});d.sendMessage(b);return!1}},teardown:function(a){e(this).unbind("_broadcastMessage");k[f()?"local":"cookie"].teardown()}}})(jQuery);
