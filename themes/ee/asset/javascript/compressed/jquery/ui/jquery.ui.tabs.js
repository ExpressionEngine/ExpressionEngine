/*!
 * jQuery UI Tabs @VERSION
 * http://jqueryui.com
 *
 * Copyright 2014 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/tabs/
 */
!function(t){"function"==typeof define&&define.amd?
// AMD. Register as an anonymous module.
define(["jquery","./core","./widget"],t):
// Browser globals
t(jQuery)}(function(t){return t.widget("ui.tabs",{version:"@VERSION",delay:300,options:{active:null,collapsible:!1,event:"click",heightStyle:"content",hide:null,show:null,
// callbacks
activate:null,beforeActivate:null,beforeLoad:null,load:null},_isLocal:function(){var t=/#.*$/;return function(e){var i,a;
// support: IE7
// IE7 doesn't normalize the href property when set via script (#9317)
e=e.cloneNode(!1),i=e.href.replace(t,""),a=location.href.replace(t,"");
// decoding may throw an error if the URL isn't UTF-8 (#9518)
try{i=decodeURIComponent(i)}catch(s){}try{a=decodeURIComponent(a)}catch(s){}return e.hash.length>1&&i===a}}(),_create:function(){var e=this,i=this.options;this.running=!1,this.element.addClass("ui-tabs ui-widget ui-widget-content ui-corner-all").toggleClass("ui-tabs-collapsible",i.collapsible),this._processTabs(),i.active=this._initialActive(),
// Take disabling tabs via class attribute from HTML
// into account and update option properly.
t.isArray(i.disabled)&&(i.disabled=t.unique(i.disabled.concat(t.map(this.tabs.filter(".ui-state-disabled"),function(t){return e.tabs.index(t)}))).sort()),
// check for length avoids error when initializing empty list
this.active=this.options.active!==!1&&this.anchors.length?this._findActive(i.active):t(),this._refresh(),this.active.length&&this.load(i.active)},_initialActive:function(){var e=this.options.active,i=this.options.collapsible,a=location.hash.substring(1);
// check the fragment identifier in the URL
// check for a tab marked active via a class
// no active tab, set to false
// handle numbers: negative, out of range
// don't allow collapsible: false and active: false
return null===e&&(a&&this.tabs.each(function(i,s){return t(s).attr("aria-controls")===a?(e=i,!1):void 0}),null===e&&(e=this.tabs.index(this.tabs.filter(".ui-tabs-active"))),(null===e||-1===e)&&(e=this.tabs.length?0:!1)),e!==!1&&(e=this.tabs.index(this.tabs.eq(e)),-1===e&&(e=i?!1:0)),!i&&e===!1&&this.anchors.length&&(e=0),e},_getCreateEventData:function(){return{tab:this.active,panel:this.active.length?this._getPanelForTab(this.active):t()}},_tabKeydown:function(e){var i=t(this.document[0].activeElement).closest("li"),a=this.tabs.index(i),s=!0;if(!this._handlePageNav(e)){switch(e.keyCode){case t.ui.keyCode.RIGHT:case t.ui.keyCode.DOWN:a++;break;case t.ui.keyCode.UP:case t.ui.keyCode.LEFT:s=!1,a--;break;case t.ui.keyCode.END:a=this.anchors.length-1;break;case t.ui.keyCode.HOME:a=0;break;case t.ui.keyCode.SPACE:
// Activate only, no collapsing
return e.preventDefault(),clearTimeout(this.activating),void this._activate(a);case t.ui.keyCode.ENTER:
// Toggle (cancel delayed activation, allow collapsing)
// Determine if we should collapse or activate
return e.preventDefault(),clearTimeout(this.activating),void this._activate(a===this.options.active?!1:a);default:return}
// Focus the appropriate tab, based on which key was pressed
e.preventDefault(),clearTimeout(this.activating),a=this._focusNextTab(a,s),
// Navigating with control key will prevent automatic activation
e.ctrlKey||(
// Update aria-selected immediately so that AT think the tab is already selected.
// Otherwise AT may confuse the user by stating that they need to activate the tab,
// but the tab will already be activated by the time the announcement finishes.
i.attr("aria-selected","false"),this.tabs.eq(a).attr("aria-selected","true"),this.activating=this._delay(function(){this.option("active",a)},this.delay))}},_panelKeydown:function(e){this._handlePageNav(e)||
// Ctrl+up moves focus to the current tab
e.ctrlKey&&e.keyCode===t.ui.keyCode.UP&&(e.preventDefault(),this.active.focus())},
// Alt+page up/down moves focus to the previous/next tab (and activates)
_handlePageNav:function(e){return e.altKey&&e.keyCode===t.ui.keyCode.PAGE_UP?(this._activate(this._focusNextTab(this.options.active-1,!1)),!0):e.altKey&&e.keyCode===t.ui.keyCode.PAGE_DOWN?(this._activate(this._focusNextTab(this.options.active+1,!0)),!0):void 0},_findNextTab:function(e,i){function a(){return e>s&&(e=0),0>e&&(e=s),e}for(var s=this.tabs.length-1;-1!==t.inArray(a(),this.options.disabled);)e=i?e+1:e-1;return e},_focusNextTab:function(t,e){return t=this._findNextTab(t,e),this.tabs.eq(t).focus(),t},_setOption:function(t,e){
// _activate() will handle invalid values and update this.options
// don't use the widget factory's disabled handling
// Setting collapsible: false while collapsed; open first panel
return"active"===t?void this._activate(e):"disabled"===t?void this._setupDisabled(e):(this._super(t,e),"collapsible"===t&&(this.element.toggleClass("ui-tabs-collapsible",e),e||this.options.active!==!1||this._activate(0)),"event"===t&&this._setupEvents(e),void("heightStyle"===t&&this._setupHeightStyle(e)))},_sanitizeSelector:function(t){return t?t.replace(/[!"$%&'()*+,.\/:;<=>?@\[\]\^`{|}~]/g,"\\$&"):""},refresh:function(){var e=this.options,i=this.tablist.children(":has(a[href])");
// get disabled tabs from class attribute from HTML
// this will get converted to a boolean if needed in _refresh()
e.disabled=t.map(i.filter(".ui-state-disabled"),function(t){return i.index(t)}),this._processTabs(),
// was collapsed or no tabs
e.active!==!1&&this.anchors.length?this.active.length&&!t.contains(this.tablist[0],this.active[0])?
// all remaining tabs are disabled
this.tabs.length===e.disabled.length?(e.active=!1,this.active=t()):this._activate(this._findNextTab(Math.max(0,e.active-1),!1)):
// make sure active index is correct
e.active=this.tabs.index(this.active):(e.active=!1,this.active=t()),this._refresh()},_refresh:function(){this._setupDisabled(this.options.disabled),this._setupEvents(this.options.event),this._setupHeightStyle(this.options.heightStyle),this.tabs.not(this.active).attr({"aria-selected":"false","aria-expanded":"false",tabIndex:-1}),this.panels.not(this._getPanelForTab(this.active)).hide().attr({"aria-hidden":"true"}),
// Make sure one tab is in the tab order
this.active.length?(this.active.addClass("ui-tabs-active ui-state-active").attr({"aria-selected":"true","aria-expanded":"true",tabIndex:0}),this._getPanelForTab(this.active).show().attr({"aria-hidden":"false"})):this.tabs.eq(0).attr("tabIndex",0)},_processTabs:function(){var e=this,i=this.tabs,a=this.anchors,s=this.panels;this.tablist=this._getList().addClass("ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all").attr("role","tablist").delegate("> li","mousedown"+this.eventNamespace,function(e){t(this).is(".ui-state-disabled")&&e.preventDefault()}).delegate(".ui-tabs-anchor","focus"+this.eventNamespace,function(){t(this).closest("li").is(".ui-state-disabled")&&this.blur()}),this.tabs=this.tablist.find("> li:has(a[href])").addClass("ui-state-default ui-corner-top").attr({role:"tab",tabIndex:-1}),this.anchors=this.tabs.map(function(){return t("a",this)[0]}).addClass("ui-tabs-anchor").attr({role:"presentation",tabIndex:-1}),this.panels=t(),this.anchors.each(function(i,a){var s,n,r,o=t(a).uniqueId().attr("id"),h=t(a).closest("li"),l=h.attr("aria-controls");
// inline tab
e._isLocal(a)?(s=a.hash,r=s.substring(1),n=e.element.find(e._sanitizeSelector(s))):(
// If the tab doesn't already have aria-controls,
// generate an id by using a throw-away element
r=h.attr("aria-controls")||t({}).uniqueId()[0].id,s="#"+r,n=e.element.find(s),n.length||(n=e._createPanel(r),n.insertAfter(e.panels[i-1]||e.tablist)),n.attr("aria-live","polite")),n.length&&(e.panels=e.panels.add(n)),l&&h.data("ui-tabs-aria-controls",l),h.attr({"aria-controls":r,"aria-labelledby":o}),n.attr("aria-labelledby",o)}),this.panels.addClass("ui-tabs-panel ui-widget-content ui-corner-bottom").attr("role","tabpanel"),
// Avoid memory leaks (#10056)
i&&(this._off(i.not(this.tabs)),this._off(a.not(this.anchors)),this._off(s.not(this.panels)))},
// allow overriding how to find the list for rare usage scenarios (#7715)
_getList:function(){return this.tablist||this.element.find("ol,ul").eq(0)},_createPanel:function(e){return t("<div>").attr("id",e).addClass("ui-tabs-panel ui-widget-content ui-corner-bottom").data("ui-tabs-destroy",!0)},_setupDisabled:function(e){t.isArray(e)&&(e.length?e.length===this.anchors.length&&(e=!0):e=!1);
// disable tabs
for(var i,a=0;i=this.tabs[a];a++)e===!0||-1!==t.inArray(a,e)?t(i).addClass("ui-state-disabled").attr("aria-disabled","true"):t(i).removeClass("ui-state-disabled").removeAttr("aria-disabled");this.options.disabled=e},_setupEvents:function(e){var i={};e&&t.each(e.split(" "),function(t,e){i[e]="_eventHandler"}),this._off(this.anchors.add(this.tabs).add(this.panels)),
// Always prevent the default action, even when disabled
this._on(!0,this.anchors,{click:function(t){t.preventDefault()}}),this._on(this.anchors,i),this._on(this.tabs,{keydown:"_tabKeydown"}),this._on(this.panels,{keydown:"_panelKeydown"}),this._focusable(this.tabs),this._hoverable(this.tabs)},_setupHeightStyle:function(e){var i,a=this.element.parent();"fill"===e?(i=a.height(),i-=this.element.outerHeight()-this.element.height(),this.element.siblings(":visible").each(function(){var e=t(this),a=e.css("position");"absolute"!==a&&"fixed"!==a&&(i-=e.outerHeight(!0))}),this.element.children().not(this.panels).each(function(){i-=t(this).outerHeight(!0)}),this.panels.each(function(){t(this).height(Math.max(0,i-t(this).innerHeight()+t(this).height()))}).css("overflow","auto")):"auto"===e&&(i=0,this.panels.each(function(){i=Math.max(i,t(this).height("").height())}).height(i))},_eventHandler:function(e){var i=this.options,a=this.active,s=t(e.currentTarget),n=s.closest("li"),r=n[0]===a[0],o=r&&i.collapsible,h=o?t():this._getPanelForTab(n),l=a.length?this._getPanelForTab(a):t(),c={oldTab:a,oldPanel:l,newTab:o?t():n,newPanel:h};e.preventDefault(),n.hasClass("ui-state-disabled")||
// tab is already loading
n.hasClass("ui-tabs-loading")||
// can't switch durning an animation
this.running||
// click on active header, but not collapsible
r&&!i.collapsible||
// allow canceling activation
this._trigger("beforeActivate",e,c)===!1||(i.active=o?!1:this.tabs.index(n),this.active=r?t():n,this.xhr&&this.xhr.abort(),l.length||h.length||t.error("jQuery UI Tabs: Mismatching fragment identifier."),h.length&&this.load(this.tabs.index(n),e),this._toggle(e,c))},
// handles show/hide for selecting tabs
_toggle:function(e,i){function a(){n.running=!1,n._trigger("activate",e,i)}function s(){i.newTab.closest("li").addClass("ui-tabs-active ui-state-active"),r.length&&n.options.show?n._show(r,n.options.show,a):(r.show(),a())}var n=this,r=i.newPanel,o=i.oldPanel;this.running=!0,
// start out by hiding, then showing, then completing
o.length&&this.options.hide?this._hide(o,this.options.hide,function(){i.oldTab.closest("li").removeClass("ui-tabs-active ui-state-active"),s()}):(i.oldTab.closest("li").removeClass("ui-tabs-active ui-state-active"),o.hide(),s()),o.attr("aria-hidden","true"),i.oldTab.attr({"aria-selected":"false","aria-expanded":"false"}),
// If we're switching tabs, remove the old tab from the tab order.
// If we're opening from collapsed state, remove the previous tab from the tab order.
// If we're collapsing, then keep the collapsing tab in the tab order.
r.length&&o.length?i.oldTab.attr("tabIndex",-1):r.length&&this.tabs.filter(function(){return 0===t(this).attr("tabIndex")}).attr("tabIndex",-1),r.attr("aria-hidden","false"),i.newTab.attr({"aria-selected":"true","aria-expanded":"true",tabIndex:0})},_activate:function(e){var i,a=this._findActive(e);
// trying to activate the already active panel
a[0]!==this.active[0]&&(
// trying to collapse, simulate a click on the current active header
a.length||(a=this.active),i=a.find(".ui-tabs-anchor")[0],this._eventHandler({target:i,currentTarget:i,preventDefault:t.noop}))},_findActive:function(e){return e===!1?t():this.tabs.eq(e)},_getIndex:function(t){
// meta-function to give users option to provide a href string instead of a numerical index.
return"string"==typeof t&&(t=this.anchors.index(this.anchors.filter("[href$='"+t+"']"))),t},_destroy:function(){this.xhr&&this.xhr.abort(),this.element.removeClass("ui-tabs ui-widget ui-widget-content ui-corner-all ui-tabs-collapsible"),this.tablist.removeClass("ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all").removeAttr("role"),this.anchors.removeClass("ui-tabs-anchor").removeAttr("role").removeAttr("tabIndex").removeUniqueId(),this.tablist.unbind(this.eventNamespace),this.tabs.add(this.panels).each(function(){t.data(this,"ui-tabs-destroy")?t(this).remove():t(this).removeClass("ui-state-default ui-state-active ui-state-disabled ui-corner-top ui-corner-bottom ui-widget-content ui-tabs-active ui-tabs-panel").removeAttr("tabIndex").removeAttr("aria-live").removeAttr("aria-busy").removeAttr("aria-selected").removeAttr("aria-labelledby").removeAttr("aria-hidden").removeAttr("aria-expanded").removeAttr("role")}),this.tabs.each(function(){var e=t(this),i=e.data("ui-tabs-aria-controls");i?e.attr("aria-controls",i).removeData("ui-tabs-aria-controls"):e.removeAttr("aria-controls")}),this.panels.show(),"content"!==this.options.heightStyle&&this.panels.css("height","")},enable:function(e){var i=this.options.disabled;i!==!1&&(void 0===e?i=!1:(e=this._getIndex(e),i=t.isArray(i)?t.map(i,function(t){return t!==e?t:null}):t.map(this.tabs,function(t,i){return i!==e?i:null})),this._setupDisabled(i))},disable:function(e){var i=this.options.disabled;if(i!==!0){if(void 0===e)i=!0;else{if(e=this._getIndex(e),-1!==t.inArray(e,i))return;i=t.isArray(i)?t.merge([e],i).sort():[e]}this._setupDisabled(i)}},load:function(e,i){e=this._getIndex(e);var a=this,s=this.tabs.eq(e),n=s.find(".ui-tabs-anchor"),r=this._getPanelForTab(s),o={tab:s,panel:r};
// not remote
this._isLocal(n[0])||(this.xhr=t.ajax(this._ajaxSettings(n,i,o)),
// support: jQuery <1.8
// jQuery <1.8 returns false if the request is canceled in beforeSend,
// but as of 1.8, $.ajax() always returns a jqXHR object.
this.xhr&&"canceled"!==this.xhr.statusText&&(s.addClass("ui-tabs-loading"),r.attr("aria-busy","true"),this.xhr.success(function(t){
// support: jQuery <1.8
// http://bugs.jquery.com/ticket/11778
setTimeout(function(){r.html(t),a._trigger("load",i,o)},1)}).complete(function(t,e){
// support: jQuery <1.8
// http://bugs.jquery.com/ticket/11778
setTimeout(function(){"abort"===e&&a.panels.stop(!1,!0),s.removeClass("ui-tabs-loading"),r.removeAttr("aria-busy"),t===a.xhr&&delete a.xhr},1)})))},_ajaxSettings:function(e,i,a){var s=this;return{url:e.attr("href"),beforeSend:function(e,n){return s._trigger("beforeLoad",i,t.extend({jqXHR:e,ajaxSettings:n},a))}}},_getPanelForTab:function(e){var i=t(e).attr("aria-controls");return this.element.find(this._sanitizeSelector("#"+i))}})});