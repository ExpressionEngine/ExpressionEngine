/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
!function(e){"use strict";/**
  * Namespace function that non-destructively creates "namespace" objects (e.g. EE.publish.example)
  *
  * @param {String} namespace_string The namespace string (e.g. EE.publish.example)
  * @returns The object to create
  */
EE.namespace=function(e){var t=e.split("."),a=EE;
// strip redundant leading global
"EE"===t[0]&&(t=t.slice(1));
// @todo disallow 'prototype', duh
// create a property if it doesn't exist
for(var n=0,o=t.length;o>n;n+=1)"undefined"==typeof a[t[n]]&&(a[t[n]]={}),a=a[t[n]];return a},
// Create the base cp namespace
EE.namespace("EE.cp"),/**
 * Hook into jQuery's ajax functionality to build in handling of our
 * csrf tokens and custom response headers.
 *
 * We also add a custom error handler in case the developer does not specify
 * one. This prevents silent failure.
 */
e.ajaxPrefilter(function(t,a,n){var o=EE.CSRF_TOKEN,i=t.type.toUpperCase();
// Throw all errors
_.has(t,"error")||n.error(function(e){_.defer(function(){throw[e.statusText,e.responseText]})}),
// Add CSRF TOKEN to EE POST requests
"POST"==i&&t.crossDomain===!1&&n.setRequestHeader("X-CSRF-TOKEN",o);var r={
// Refresh xids (deprecated)
eexid:function(e){e&&EE.cp.setCsrfToken(e)},
// Refresh csrf tokens
"csrf-token":function(e){e&&EE.cp.setCsrfToken(e)},
// Force redirects (e.g. logout)
"ee-redirect":function(e){window.location=EE.BASE+"&"+e.replace("//","/")},
// Trigger broadcast events
"ee-broadcast":function(t){EE.cp.broadcastEvents[t](),e(window).trigger("broadcast",t)}},s=e.merge(r,a.eeResponseHeaders||{});n.complete(function(e){t.crossDomain===!1&&_.each(s,function(t,a){var n=e.getResponseHeader("X-"+a);n&&t(n)})})}),
// Grid has become a dependency for a few fieldtypes. However, sometimes it's not
// on the page or loaded after the fieldtype. So instead of tryin to always load
// grid or doing weird dependency juggling, we're just going to cache any calls
// to grid.bind for now. Grid will override this definition and replay them if/when
// it becomes available on the page. Long term we need a better solution for js
// dependencies.
EE.grid_cache=[],window.Grid={bind:function(){EE.grid_cache.push(arguments)}},
// Setup Base EE Control Panel
e(document).ready(function(){
// call the input placeholder polyfill early so that we don't get
// weird flashes of content
!1 in document.createElement("input")&&EE.insert_placeholders(),EE.cp.cleanUrls(),EE.cp.bindCpMessageClose(),EE.cp.channelMenuFilter()}),
// Binds the channel filter text boxes in Create and Edit menus
EE.cp.channelMenuFilter=function(){var t=e(".menu-wrap form.filter input, .filter-search input");
// Bail if no filters
if(0!=t.size()){
// Create a style element where we'll input the CSS needed
// to filter the table
var a=e("<style/>");e("body").append(a),
// Watch the filter input on keyup and then filter the results
t.bind("keyup",function(){
// Text box blank? Reset table to show all results
if(!this.value)return void a.html("");
// Grab the class of the list to make sure we filter the right one
var t=e(this).parent().siblings("ul").attr("class");
// Data is indexed via a data attribute, create a CSS
// selector to filter the table
a.html("ul."+t+' li.search-channel:not([data-search*="'+this.value.toLowerCase()+'"]) { display: none; }')})}},
// Close alert modal when close button is clicked
EE.cp.bindCpMessageClose=function(){e("body").on("click","div.alert a.close",function(t){t.preventDefault(),e(this).parent().hide()})},
// Simple function to deal with csrf tokens
EE.cp.setCsrfToken=function(t,a){e('input[name="XID"]').val(t),e('input[name="csrf_token"]').val(t),EE.XID=t,EE.CSRF_TOKEN=t,a||e(window).trigger("broadcast.setCsrfToken",t)},e(window).bind("broadcast.setCsrfToken",function(e,t){EE.cp.setCsrfToken(t,!0)});
// Simple function to deal with base paths tokens
var t=/[&?](S=[A-Za-z0-9]+)/;EE.cp.setBasePath=function(a,n){var a=a.replace(/&amp;/g,"&"),o=a.match(t)||["",""],i=EE.BASE.match(t)||["",""],r=function(e,t){return t?t.replace(i[1],o[1]):void 0};e("a").attr("href",r),e("form").attr("action",r),
// Since the session id in the current url is no longer correct, a
// refresh will end up on the login page. We will replace the current
// url to avoid that issue. You still cannot use the back button after
// logging back in, but how likely are you to remember what page you
// were on before leaving this one open for 20 minutes anyways?
"function"==typeof window.history.pushState&&window.history.replaceState(null,document.title,window.location.href.replace(i[1],o[1])),
// Set it as the new base
EE.BASE=a,n||e(window).trigger("broadcast.setBasePath",a)},e(window).bind("broadcast.setBasePath",function(e,t){EE.cp.setBasePath(t,!0)}),EE.cp.refreshSessionData=function(t,a){a&&EE.cp.setBasePath(a),
// running the request will return the x-csrf-header, which will trigger
// our prefilter. We still need to replace the base though.
e.getJSON(EE.BASE+"&C=login&M=refresh_csrf_token",function(e){EE.cp.setBasePath(e.base)})};var a=/(.*?)[?](.*?&)?(D=cp(?:&C=[^&]+(?:&M=[^&]+)?)?)(?:&(.+))?$/,n=/&?[DCM]=/g,o=/^&+/,i=/&+$/,r=/(^|&)S=0(&|$)/;EE.cp.cleanUrl=function(e,t){t=t||e,t=t||"",t=t.toString().replace(/^(\S*?)S=(\S+?)&(\S*?)$/g,"$1$3&S=$2");var s=a.exec(t);if(s){
// result[1] // index.php
// result[2] // S=49204&
// result[3] // D=cp&C=foo&M=bar
// result[4] // &foobarbaz
var c=s[3].replace(n,"/"),l=s[2]||"",d=s[4]||"",u=s[1]+"?"+c,h=d.replace(r,"")+"&"+l.replace(r,"");return h=h.replace(o,"").replace(i,""),h&&(u+="&"+h),u.replace(i,"")}},EE.cp.cleanUrls=function(){e("a:not([href^=javascript])").attr("href",EE.cp.cleanUrl),e("form").attr("action",EE.cp.cleanUrl)},
// Fallback for browsers without placeholder= support
EE.insert_placeholders=function(){e('input[type="text"]').each(function(){if(this.placeholder){var t=e(this),a=this.placeholder,n=t.css("color");""==t.val()&&t.data("user_data","n"),t.focus(function(){
// Reset color & remove placeholder text
t.css("color",n),t.val()===a&&(t.val(""),t.data("user_data","y"))}).blur(function(){
// If no user content -> add placeholder text and dim
(""===t.val()||t.val===a)&&(t.val(a).css("color","#888"),t.data("user_data","n"))}).trigger("blur")}})},/**
 * Handle idle / inaction between windows
 *
 * This code relies heavily on timing. In order to reduce complexity everything is
 * handled in steps (ticks) of 15 seconds. We count for how many ticks we have been
 * in a given state and act accordingly. This gives us reasonable timing information
 * without having to set, cancel, and track multiple timeouts.
 *
 * The conditions currently are as follows:
 *
 * - If an ee tab has focus we call it idle after 20 minutes of no interaction
 * - If no ee tab has focus, we call it idle after 40 minutes of no activity
 * - If they work around the modal (inspector), all request will land on the login page.
 * - Logging out of one tab will show the modal on all other tabs.
 * - Logging into the modal on one tab, will show it on all other tabs.
 *
 * The object returned is one that allows manual triggering of an event. For
 * example, to force the modal to show you could call:
 *
 *     EE.cp.broadcastEvents['modal']();
 *
 * This is used by our ajax filter to allow triggering an event with the
 * X-EE-BROADCAST header
 *
 */
EE.cp.broadcastEvents=function(){
// Define our time limits:
var// 50 minutes: refresh if active or remember me
t,a,n=1e3,// Check state every second
o=18e5,// 30 minutes: time before modal if window focused
i=27e5,// 45 minutes: time before modal if no focus
r=3e6;
// Setup Base EE Control Panel
e(document).ready(function(){t=e("#idle-modal"),a=e(".overlay"),
// If the modal hasn't been interacted with in over 10 minutes we'll send a request for
// the current csrf token. It can flip on us during long waits due to the session timeout.
// If the session times out this will get us a cookie based csrf token, which is what you
// would normally log in with, so it's fine.
t.find("form").on("interact",_.throttle(EE.cp.refreshSessionData,6e5)),
// Bind on the modal submission
t.find("form").on("submit",function(){return e.ajax({type:"POST",url:this.action,data:e(this).serialize(),dataType:"json",success:function(t){
// Hide the dialog
// Grab the new token
return"success"!=t.messageType?void alert(t.message):(c.login(),EE.cp.refreshSessionData(null,t.base),void e(window).trigger("broadcast.idleState","login"))},error:function(e){alert(e.message)}}),!1})});/**
	 * This object tracks the current state of the page.
	 *
	 * The resolve function is called once per tick. The individual events will
	 * set hasFocus and lastActive time.
	 */
var s={hasFocus:!0,modalActive:!1,pingReceived:!1,lastActive:e.now(),lastRefresh:e.now(),setActiveTime:function(){
// Before we set someone as not idle we need to check if they've
// sneakily been idle for a long time. When you close your laptop
// the timer stops. Reopening it hours later creates a race between
// the tick timer and the non-idle events. When that happens, you're
// way past the threshold and therefore too late.
(this.modalActive||!this.modalThresholdReached())&&(
// If they're active on the page for an extend period of time
// without hitting the backend, we can sometimes run past the
// session timeout. To prevent that from happening we'll refresh
// their session last activity in the background.
this.refreshThresholdReached()&&this.doRefresh(),this.lastActive=e.now())},modalThresholdReached:function(){var t=e.now()-this.lastActive,a=this.hasFocus&&t>o||!this.hasFocus&&t>i;return this.modalActive===!1&&a===!0},refreshThresholdReached:function(){var t=e.now()-this.lastRefresh;return t>r},doRefresh:function(){this.lastRefresh=e.now(),EE.cp.refreshSessionData()},resolve:function(){
// Reset
return EE.hasRememberMe?void(this.refreshThresholdReached()&&this.doRefresh()):(this.modalThresholdReached()?(c.modal(),e(window).trigger("broadcast.idleState","modal"),e.get(EE.BASE+"&C=login&M=lock_cp")):this.hasFocus&&this.pingReceived===!1&&e(window).trigger("broadcast.idleState","active"),void(this.pingReceived=!1))}},c={
// received another window's active event, user active
active:function(){s.setActiveTime()},
// user focused, they are active
focus:function(){s.setActiveTime(),s.hasFocus=!0},
// user left, they are idle
blur:function(){s.setActiveTime(),s.hasFocus=!1},
// user typing / mousing, possibly active
interact:function(){s.hasFocus&&s.setActiveTime()},
// received another window's modal event, open it
modal:function(){s.modalActive||(t.trigger("modal:open"),t.on("modal:close",function(e){s.modalActive&&(e.preventDefault(),c.logout())}),s.modalActive=!0),s.setActiveTime()},
// received another window's login event, check and hide modal
login:function(){s.modalActive=!1,t.trigger("modal:close"),t.find(":password").val(""),s.setActiveTime()},
// received another window's logout event, leave page
logout:function(){window.location=EE.BASE+"&C=login&M=logout"}},l={_t:null,init:function(){e(window).trigger("broadcast.setBasePath",EE.BASE),e(window).trigger("broadcast.setCsrfToken",EE.CSRF_TOKEN),e(window).trigger("broadcast.idleState","login"),this._bindEvents(),this.track()},/**
		 * Bind our events
		 *
		 * We keep track of focus, blur, scrolling, clicking, etc.
		 * Some broadcast events can be fired immediately as nothing will stop
		 * them once the tick fires anyways.
		 * We have an extra throttle on interactions to keep the browser happy
		 * and not fill up the queue uselessly.
		 */
_bindEvents:function(){var t=e.proxy(this,"track");
// Bind on the broadcast event
e(window).on("broadcast.idleState",function(e,a){switch(a){case"active":s.pingReceived=!0,t(a);break;case"modal":case"login":case"logout":c[a]()}}),
// Bind on window focus and blur
e(window).bind("blur",_.partial(t,"blur")),e(window).bind("focus",_.partial(t,"focus"));
// Bind on interactions
var a="DOMMouseScroll keydown mousedown mousemove mousewheel touchmove touchstart";e(document).on(a.split(" ").join(".idleState "),// namespace the events
_.throttle(_.partial(t,"interact"),500)),
// Clicking the logout button fires "modal" on all the others
e(".logOutButton").click(function(){e(window).trigger("broadcast.idleState","modal")})},/**
		 * Helper method to record an event
		 */
track:function(t){clearTimeout(this._t),this._t=setTimeout(e.proxy(this,"track"),n),t&&c[t](),s.resolve()}};
// Go go go!
return l.init(),c}()}(jQuery);