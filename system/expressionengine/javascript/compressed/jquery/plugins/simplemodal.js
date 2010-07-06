/*
 * SimpleModal 1.3.5 - jQuery Plugin
 * http://www.ericmmartin.com/projects/simplemodal/
 * Copyright (c) 2010 Eric Martin (http://twitter.com/EricMMartin)
 * Dual licensed under the MIT and GPL licenses
 * Revision: $Id: jquery.simplemodal.js 245 2010-03-25 20:41:15Z emartin24 $
 */
/**
 * SimpleModal is a lightweight jQuery plugin that provides a simple
 * interface to create a modal dialog.
 *
 * The goal of SimpleModal is to provide developers with a cross-browser 
 * overlay and container that will be populated with data provided to
 * SimpleModal.
 *
 * There are two ways to call SimpleModal:
 * 1) As a chained function on a jQuery object, like $('#myDiv').modal();.
 * This call would place the DOM object, #myDiv, inside a modal dialog.
 * Chaining requires a jQuery object. An optional options object can be
 * passed as a parameter.
 *
 * @example $('<div>my data</div>').modal({options});
 * @example $('#myDiv').modal({options});
 * @example jQueryObject.modal({options});
 *
 * 2) As a stand-alone function, like $.modal(data). The data parameter
 * is required and an optional options object can be passed as a second
 * parameter. This method provides more flexibility in the types of data 
 * that are allowed. The data could be a DOM object, a jQuery object, HTML
 * or a string.
 * 
 * @example $.modal('<div>my data</div>', {options});
 * @example $.modal('my data', {options});
 * @example $.modal($('#myDiv'), {options});
 * @example $.modal(jQueryObject, {options});
 * @example $.modal(document.getElementById('myDiv'), {options}); 
 * 
 * A SimpleModal call can contain multiple elements, but only one modal 
 * dialog can be created at a time. Which means that all of the matched
 * elements will be displayed within the modal container.
 * 
 * SimpleModal internally sets the CSS needed to display the modal dialog
 * properly in all browsers, yet provides the developer with the flexibility
 * to easily control the look and feel. The styling for SimpleModal can be 
 * done through external stylesheets, or through SimpleModal, using the
 * overlayCss and/or containerCss options.
 *
 * SimpleModal has been tested in the following browsers:
 * - IE 6, 7, 8
 * - Firefox 2, 3
 * - Opera 9, 10
 * - Safari 3, 4
 * - Chrome 1, 2, 3, 4
 *
 * @name SimpleModal
 * @type jQuery
 * @requires jQuery v1.2.2
 * @cat Plugins/Windows and Overlays
 * @author Eric Martin (http://ericmmartin.com)
 * @version 1.3.5
 */
/*
	 * Stand-alone function to create a modal dialog.
	 * 
	 * @param {string, object} data A string, jQuery object or DOM object
	 * @param {object} [options] An optional object containing options overrides
	 */
/*
	 * Stand-alone close function to close the modal dialog
	 */
/*
	 * Chained function to create a modal dialog.
	 * 
	 * @param {object} [options] An optional object containing options overrides
	 */
/*
	 * SimpleModal default options
	 * 
	 * appendTo:		(String:'body') The jQuery selector to append the elements to. For ASP.NET, use 'form'.
	 * focus:			(Boolean:true) Forces focus to remain on the modal dialog
	 * opacity:			(Number:50) The opacity value for the overlay div, from 0 - 100
	 * overlayId:		(String:'simplemodal-overlay') The DOM element id for the overlay div
	 * overlayCss:		(Object:{}) The CSS styling for the overlay div
	 * containerId:		(String:'simplemodal-container') The DOM element id for the container div
	 * containerCss:	(Object:{}) The CSS styling for the container div
	 * dataId:			(String:'simplemodal-data') The DOM element id for the data div
	 * dataCss:			(Object:{}) The CSS styling for the data div
	 * minHeight:		(Number:null) The minimum height for the container
	 * minWidth:		(Number:null) The minimum width for the container
	 * maxHeight:		(Number:null) The maximum height for the container. If not specified, the window height is used.
	 * maxWidth:		(Number:null) The maximum width for the container. If not specified, the window width is used.
	 * autoResize:		(Boolean:false) Resize container on window resize? Use with caution - this may have undesirable side-effects.
	 * autoPosition:	(Boolean:true) Automatically position container on creation and window resize?
	 * zIndex:			(Number: 1000) Starting z-index value
	 * close:			(Boolean:true) If true, closeHTML, escClose and overClose will be used if set.
	 							If false, none of them will be used.
	 * closeHTML:		(String:'<a class="modalCloseImg" title="Close"></a>') The HTML for the 
							default close link. SimpleModal will automatically add the closeClass to this element.
	 * closeClass:		(String:'simplemodal-close') The CSS class used to bind to the close event
	 * escClose:		(Boolean:true) Allow Esc keypress to close the dialog? 
	 * overlayClose:	(Boolean:false) Allow click on overlay to close the dialog?
	 * position:		(Array:null) Position of container [top, left]. Can be number of pixels or percentage
	 * persist:			(Boolean:false) Persist the data across modal calls? Only used for existing
								DOM elements. If true, the data will be maintained across modal calls, if false,
								the data will be reverted to its original state.
	 * modal:			(Boolean:true) If false, the overlay, iframe, and certain events will be disabled
								allowing the user to interace with the page below the dialog
	 * onOpen:			(Function:null) The callback function used in place of SimpleModal's open
	 * onShow:			(Function:null) The callback function used after the modal dialog has opened
	 * onClose:			(Function:null) The callback function used in place of SimpleModal's close
	 */
/*
	 * Main modal object
	 */
/*
		 * Modal dialog options
		 */
/*
		 * Contains the modal dialog elements and is the object passed 
		 * back to the callback (onOpen, onShow, onClose) functions
		 */
/*
		 * Initialize the modal dialog
		 */
/*
		 * Create and add the modal overlay and container to the page
		 */
/*
		 * Bind events
		 */
/*
		 * Unbind events
		 */
/*
		 * Fix issues in IE6 and IE7 in quirks mode
		 */
/*
		 * Open the modal dialog elements
		 * - Note: If you use the onOpen callback, you must "show" the 
		 *	        overlay and container elements manually 
		 *         (the iframe will be handled by SimpleModal)
		 */
/*
		 * Close the modal dialog
		 * - Note: If you use an onClose callback, you must remove the 
		 *         overlay, container and iframe elements manually
		 *
		 * @param {boolean} external Indicates whether the call to this
		 *     function was internal or external. If it was external, the
		 *     onClose callback will be ignored
		 */

(function(b){var i=b.browser.msie&&parseInt(b.browser.version)==6&&typeof window.XMLHttpRequest!="object",j=null,d=[];b.modal=function(a,c){return b.modal.impl.init(a,c)};b.modal.close=function(){b.modal.impl.close()};b.fn.modal=function(a){return b.modal.impl.init(this,a)};b.modal.defaults={appendTo:"body",focus:true,opacity:50,overlayId:"simplemodal-overlay",overlayCss:{},containerId:"simplemodal-container",containerCss:{},dataId:"simplemodal-data",dataCss:{},minHeight:null,minWidth:null,maxHeight:null,
maxWidth:null,autoResize:false,autoPosition:true,zIndex:1E3,close:true,closeHTML:'<a class="modalCloseImg" title="Close"></a>',closeClass:"simplemodal-close",escClose:true,overlayClose:false,position:null,persist:false,modal:true,onOpen:null,onShow:null,onClose:null};b.modal.impl={o:null,d:{},init:function(a,c){if(this.d.data)return false;j=b.browser.msie&&!b.boxModel;this.o=b.extend({},b.modal.defaults,c);this.zIndex=this.o.zIndex;this.occb=false;if(typeof a=="object"){a=a instanceof jQuery?a:b(a);
this.d.placeholder=false;if(a.parent().parent().size()>0){a.before(b("<span></span>").attr("id","simplemodal-placeholder").css({display:"none"}));this.d.placeholder=true;this.display=a.css("display");if(!this.o.persist)this.d.orig=a.clone(true)}}else if(typeof a=="string"||typeof a=="number")a=b("<div></div>").html(a);else{alert("SimpleModal Error: Unsupported data type: "+typeof a);return this}this.create(a);this.open();b.isFunction(this.o.onShow)&&this.o.onShow.apply(this,[this.d]);return this},
create:function(a){d=this.getDimensions();if(this.o.modal&&i)this.d.iframe=b('<iframe src="javascript:false;"></iframe>').css(b.extend(this.o.iframeCss,{display:"none",opacity:0,position:"fixed",height:d[0],width:d[1],zIndex:this.o.zIndex,top:0,left:0})).appendTo(this.o.appendTo);this.d.overlay=b("<div></div>").attr("id",this.o.overlayId).addClass("simplemodal-overlay").css(b.extend(this.o.overlayCss,{display:"none",opacity:this.o.opacity/100,height:this.o.modal?d[0]:0,width:this.o.modal?d[1]:0,position:"fixed",
left:0,top:0,zIndex:this.o.zIndex+1})).appendTo(this.o.appendTo);this.d.container=b("<div></div>").attr("id",this.o.containerId).addClass("simplemodal-container").css(b.extend(this.o.containerCss,{display:"none",position:"fixed",zIndex:this.o.zIndex+2})).append(this.o.close&&this.o.closeHTML?b(this.o.closeHTML).addClass(this.o.closeClass):"").appendTo(this.o.appendTo);this.d.wrap=b("<div></div>").attr("tabIndex",-1).addClass("simplemodal-wrap").css({height:"100%",outline:0,width:"100%"}).appendTo(this.d.container);
this.d.data=a.attr("id",a.attr("id")||this.o.dataId).addClass("simplemodal-data").css(b.extend(this.o.dataCss,{display:"none"})).appendTo("body");this.setContainerDimensions();this.d.data.appendTo(this.d.wrap);if(i||j)this.fixIE()},bindEvents:function(){var a=this;b("."+a.o.closeClass).bind("click.simplemodal",function(c){c.preventDefault();a.close()});a.o.modal&&a.o.close&&a.o.overlayClose&&a.d.overlay.bind("click.simplemodal",function(c){c.preventDefault();a.close()});b(document).bind("keydown.simplemodal",
function(c){if(a.o.modal&&a.o.focus&&c.keyCode==9)a.watchTab(c);else if(a.o.close&&a.o.escClose&&c.keyCode==27){c.preventDefault();a.close()}});b(window).bind("resize.simplemodal",function(){d=a.getDimensions();a.setContainerDimensions(true);if(i||j)a.fixIE();else if(a.o.modal){a.d.iframe&&a.d.iframe.css({height:d[0],width:d[1]});a.d.overlay.css({height:d[0],width:d[1]})}})},unbindEvents:function(){b("."+this.o.closeClass).unbind("click.simplemodal");b(document).unbind("keydown.simplemodal");b(window).unbind("resize.simplemodal");
this.d.overlay.unbind("click.simplemodal")},fixIE:function(){var a=this.o.position;b.each([this.d.iframe||null,!this.o.modal?null:this.d.overlay,this.d.container],function(c,h){if(h){var e=h[0].style;e.position="absolute";if(c<2){e.removeExpression("height");e.removeExpression("width");e.setExpression("height",'document.body.scrollHeight > document.body.clientHeight ? document.body.scrollHeight : document.body.clientHeight + "px"');e.setExpression("width",'document.body.scrollWidth > document.body.clientWidth ? document.body.scrollWidth : document.body.clientWidth + "px"')}else{var f,
g;if(a&&a.constructor==Array){f=a[0]?typeof a[0]=="number"?a[0].toString():a[0].replace(/px/,""):h.css("top").replace(/px/,"");f=f.indexOf("%")==-1?f+' + (t = document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop) + "px"':parseInt(f.replace(/%/,""))+' * ((document.documentElement.clientHeight || document.body.clientHeight) / 100) + (t = document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop) + "px"';if(a[1]){g=
typeof a[1]=="number"?a[1].toString():a[1].replace(/px/,"");g=g.indexOf("%")==-1?g+' + (t = document.documentElement.scrollLeft ? document.documentElement.scrollLeft : document.body.scrollLeft) + "px"':parseInt(g.replace(/%/,""))+' * ((document.documentElement.clientWidth || document.body.clientWidth) / 100) + (t = document.documentElement.scrollLeft ? document.documentElement.scrollLeft : document.body.scrollLeft) + "px"'}}else{f='(document.documentElement.clientHeight || document.body.clientHeight) / 2 - (this.offsetHeight / 2) + (t = document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop) + "px"';
g='(document.documentElement.clientWidth || document.body.clientWidth) / 2 - (this.offsetWidth / 2) + (t = document.documentElement.scrollLeft ? document.documentElement.scrollLeft : document.body.scrollLeft) + "px"'}e.removeExpression("top");e.removeExpression("left");e.setExpression("top",f);e.setExpression("left",g)}}})},focus:function(a){a=b(":input:enabled:visible:"+(a||"first"),this.d.wrap);a.length>0?a.focus():this.d.wrap.focus()},getDimensions:function(){var a=b(window);return[b.browser.opera&&
b.browser.version>"9.5"&&b.fn.jquery<="1.2.6"?document.documentElement.clientHeight:b.browser.opera&&b.browser.version<"9.5"&&b.fn.jquery>"1.2.6"?window.innerHeight:a.height(),a.width()]},getVal:function(a){return a=="auto"?0:a.indexOf("%")>0?a:parseInt(a.replace(/px/,""))},setContainerDimensions:function(a){if(!a||a&&this.o.autoResize){a=b.browser.opera?this.d.container.height():this.getVal(this.d.container.css("height"));var c=b.browser.opera?this.d.container.width():this.getVal(this.d.container.css("width")),
h=this.d.data.outerHeight(true),e=this.d.data.outerWidth(true),f=this.o.maxHeight&&this.o.maxHeight<d[0]?this.o.maxHeight:d[0],g=this.o.maxWidth&&this.o.maxWidth<d[1]?this.o.maxWidth:d[1];a=a?a>f?f:a:h?h>f?f:h<this.o.minHeight?this.o.minHeight:h:this.o.minHeight;c=c?c>g?g:c:e?e>g?g:e<this.o.minWidth?this.o.minWidth:e:this.o.minWidth;this.d.container.css({height:a,width:c});if(h>a||e>c)this.d.wrap.css({overflow:"auto"})}this.o.autoPosition&&this.setPosition()},setPosition:function(){var a,c;a=d[0]/
2-this.d.container.outerHeight(true)/2;c=d[1]/2-this.d.container.outerWidth(true)/2;if(this.o.position&&Object.prototype.toString.call(this.o.position)==="[object Array]"){a=this.o.position[0]||a;c=this.o.position[1]||c}else{a=a;c=c}this.d.container.css({left:c,top:a})},watchTab:function(a){var c=this;if(b(a.target).parents(".simplemodal-container").length>0){c.inputs=b(":input:enabled:visible:first, :input:enabled:visible:last",c.d.data[0]);if(!a.shiftKey&&a.target==c.inputs[c.inputs.length-1]||
a.shiftKey&&a.target==c.inputs[0]||c.inputs.length==0){a.preventDefault();var h=a.shiftKey?"last":"first";setTimeout(function(){c.focus(h)},10)}}else{a.preventDefault();setTimeout(function(){c.focus()},10)}},open:function(){this.d.iframe&&this.d.iframe.show();if(b.isFunction(this.o.onOpen))this.o.onOpen.apply(this,[this.d]);else{this.d.overlay.show();this.d.container.show();this.d.data.show()}this.focus();this.bindEvents()},close:function(){if(!this.d.data)return false;this.unbindEvents();if(b.isFunction(this.o.onClose)&&
!this.occb){this.occb=true;this.o.onClose.apply(this,[this.d])}else{if(this.d.placeholder){var a=b("#simplemodal-placeholder");if(this.o.persist)a.replaceWith(this.d.data.removeClass("simplemodal-data").css("display",this.display));else{this.d.data.hide().remove();a.replaceWith(this.d.orig)}}else this.d.data.hide().remove();this.d.container.hide().remove();this.d.overlay.hide().remove();this.d.iframe&&this.d.iframe.hide().remove();this.d={}}}}})(jQuery);
