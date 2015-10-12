/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.7.1
 * @filesource
 */
// ------------------------------------------------------------------------
/*!
 * ExpressionEngine Custom Interact jQuery Event
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
/* Usage Notes:
 *
 * This file adds a custom event to jquery. The broadcast event
 * handles interactions between windows and tabs of the current
 * browser. It is useful for communicating global events, such
 * as showing the login modal when a tab becomes idle, or hiding
 * the sidebar across all windows.
 *
 * The postmessage api requires that you already have a reference
 * to the window object you're sending to. Since these cannot be
 * grabbed, we instead use Local Storage as a proxy. When a store
 * is changed, the "storage" event is fired on all tabs/windows
 * using the same item.
 *
 * We send along a unique ID for this window as well as the event
 * namespace if it exists. The message is sent through storage as
 * a json encoded string. This gives us a 44 byte overhead that
 * will be taken from the 4kb cookie limit. It is generally not
 * wise to send large messages.
 *
 * Usage:
 *
 * $(window).bind('broadcast', function(evt, message) {...});
 * $(window).trigger('broadcast', "My Message");
 *
 */
!function(e){/**
 * Helper function to insure we have local storage support.
 *
 * Some browsers will throw a quota exceeded exception if you
 * try to write to local storage while in "private browsing" mode,
 * so we attempt to set a tiny dummy item to detect this case.
 *
 * @return bool LocalStorage available?
 */
function t(){try{if("localStorage"in window&&null!==window.localStorage)return localStorage.setItem("ee_ping",1),localStorage.removeItem("ee_ping"),!0}catch(e){return!1}}/**
 * Generate a unique-ish id by combining the current microtime with
 * an rfc4122 version 4 random field.
 *
 * @return String 17 character unique id
 */
function n(){return e.now()+(65536*(1+Math.random())|0).toString(16).substring(1)}/**
 * Grab our data store
 *
 * If local storage is full or not supported we will fall back to a cookie
 * store with the same api as local storage. This means that our message
 * size limit is much lower than just using local storage would be, but it
 * is also much more robust.
 */
var a=t()?localStorage:{setItem:function(e,t){var n=new Date;n.setTime(n.getTime()+5e3),// expire in 5 seconds
document.cookie=e+"="+escape(t)+"; expires="+n.toGMTString()+"; path=/"},removeItem:function(e){document.cookie=e+"=; expires=Thu, 01 Jan 1970 00:00:01 GMT"},getItem:function(e){var t=new RegExp("[,; ]"+e+"=([^\\s,;]*)"),n=" "+document.cookie,a=n.match(t);return a?unescape(a[1]):void 0}},i=n(),o="ee_broadcast",s=t()?20:1500,r=e(window);EE.BROADCAST_UID=i;/**
 * Conflict resolution
 *
 * Messages sent in quick succession need to be queued and sent in order
 * Messages sent by this window should not be read by it (can happen with cookies)
 */
var u={_queue:[],// message queue
_waiting:!1,// queue running
_lastMessage:void 0,// last sent
/**
	 * Add the message to the queue and dequeue if no
	 * message is currently posted.
	 */
sendMessage:function(e){this._queue.push(function(t){a.setItem(o,e),u._lastMessage=e,setTimeout(function(){a.removeItem(o),u._lastMessage=void 0,t()},s)}),this.dequeue()},/**
	 * Receive the messages. If the message was posted by this window
	 * we will ignore it. Otherwise pass it on to the correct event handlers.
	 */
receiveMessage:function(e){var t=JSON.parse(e),n=t.ns?"."+t.ns:"",a=t.uid;a!=i&&r.trigger({type:"_broadcastMessage"+n,sender:a,receiver:i},t.data)},/**
	 * Queue helper. Passes a "next" function to the
	 * callback that will continue the dequeuing process.
	 */
dequeue:function(){if(!this._waiting){this._waiting=!0;var e=this._queue.shift();e(function(){u._waiting=!1,u._queue.length&&u.dequeue()})}}},c={/**
	 * LocalStorage can use the storage event. This is pretty simple, but we
	 * do need to make sure to ignore non-broadcast data and we should not
	 * fire after blanking out the message.
	 */
local:{setup:function(){r.on("storage",function(e){var t=e.originalEvent;t.key==o&&t.newValue&&u.receiveMessage(t.newValue)})},teardown:function(){r.off("storage")}},/**
	 * For cookies we must poll for changes. We should not read the same
	 * message twice, so we keep track of the last received message.
	 */
cookie:{_timer:null,setup:function(){function e(){var n=a.getItem(o);n!=t&&(u.receiveMessage(n),t=n),
// check the cookie for changes every second
c.cookie._timer=setTimeout(e,1e3)}var t=void 0;e()},teardown:function(){clearTimeout(c.cookie._timer)}}};/**
 * Setup the jquery event interactions.
 *
 * Since our broadcast name is overloaded to trigger on the other windows, we
 * cannot use that same name to trigger our bound event handlers. To work around
 * this we bind a separate local message event.
 */
e.event.special.broadcast={/**
	 * On first bind, setup the message system on the window element
	 */
setup:function(){c[t()?"local":"cookie"].setup()},/**
	 * Bind the local messaging event for the given handler
	 */
add:function(t){var n=t.namespace?"."+t.namespace:"";e.event.add(this,"_broadcastMessage"+n,t.handler)},/**
	 * Trigger events of this name on the other tabs
	 */
trigger:function(t,n){if(t.target==window){
// This may be stored in a cookie, so we keep the keys small
var a=JSON.stringify({ns:t.namespace,uid:i,data:e.makeArray(arguments).slice(1)});return u.sendMessage(a),!1}},/**
	 * Cleanup and unbind events
	 */
teardown:function(n){e(this).unbind("_broadcastMessage"),c[t()?"local":"cookie"].teardown()}}}(jQuery);