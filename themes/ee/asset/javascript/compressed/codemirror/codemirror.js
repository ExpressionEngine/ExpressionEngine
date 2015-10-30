// CodeMirror, copyright (c) by Marijn Haverbeke and others
// Distributed under an MIT license: http://codemirror.net/LICENSE
// This is CodeMirror (http://codemirror.net), a code editor
// implemented in JavaScript on top of the browser's DOM.
//
// You can find some technical background for some of the code below
// at http://marijnhaverbeke.nl/blog/#cm-internals .
!function(e){if("object"==typeof exports&&"object"==typeof module)// CommonJS
module.exports=e();else{if("function"==typeof define&&define.amd)// AMD
return define([],e);// Plain browser env
this.CodeMirror=e()}}(function(){"use strict";
// EDITOR CONSTRUCTOR
// A CodeMirror instance represents an editor. This is the object
// that user code is usually dealing with.
function e(r,n){if(!(this instanceof e))return new e(r,n);this.options=n=n||{},
// Determine effective options based on given values and defaults.
ii(xo,n,!1),p(n);var i=n.value;"string"==typeof i&&(i=new Ko(i,n.mode)),this.doc=i;var o=this.display=new t(r,i);o.wrapper.CodeMirror=this,c(this),a(this),n.lineWrapping&&(this.display.wrapper.className+=" CodeMirror-wrap"),n.autofocus&&!Qi&&dt(this),this.state={keyMaps:[],// stores maps added by addKeyMap
overlays:[],// highlighting overlays, as added by addOverlay
modeGen:0,// bumped when mode/overlay changes, used to invalidate highlighting info
overwrite:!1,focused:!1,suppressEdits:!1,// used to disable editing during key handlers when in readOnly mode
pasteIncoming:!1,cutIncoming:!1,// help recognize paste/cut edits in readInput
draggingText:!1,highlight:new Zn},
// Override magic textarea content restore that IE sometimes does
// on our hidden textarea on reload
Pi&&setTimeout(oi(ht,this,!0),20),vt(this),bi();var l=this;qe(this,function(){l.curOp.forceUpdate=!0,yn(l,i),n.autofocus&&!Qi||di()==o.input?setTimeout(oi(Rt,l),20):Bt(l);for(var e in wo)wo.hasOwnProperty(e)&&wo[e](l,n[e],Co);for(var t=0;t<Mo.length;++t)Mo[t](l)})}
// DISPLAY CONSTRUCTOR
// The display handles the DOM integration, both for input reading
// and content drawing. It holds references to DOM nodes and
// display-related state.
function t(e,t){var r=this,n=r.input=ui("textarea",null,null,"position: absolute; padding: 0; width: 1px; height: 1em; outline: none");
// The textarea is kept positioned near the cursor to prevent the
// fact that it'll be scrolled into view on input from scrolling
// our fake cursor out of view. On webkit, when wrap=off, paste is
// very slow. So make the area wide instead.
Ui?n.style.width="1000px":n.setAttribute("wrap","off"),
// If border: 0; -- iOS fails to open keyboard (issue #1287)
Zi&&(n.style.border="1px solid black"),n.setAttribute("autocorrect","off"),n.setAttribute("autocapitalize","off"),n.setAttribute("spellcheck","false"),
// Wraps and hides input textarea
r.inputDiv=ui("div",[n],null,"overflow: hidden; position: relative; width: 3px; height: 0px;"),
// The fake scrollbar elements.
r.scrollbarH=ui("div",[ui("div",null,null,"height: 100%; min-height: 1px")],"CodeMirror-hscrollbar"),r.scrollbarV=ui("div",[ui("div",null,null,"min-width: 1px")],"CodeMirror-vscrollbar"),
// Covers bottom-right square when both scrollbars are present.
r.scrollbarFiller=ui("div",null,"CodeMirror-scrollbar-filler"),
// Covers bottom of gutter when coverGutterNextToScrollbar is on
// and h scrollbar is present.
r.gutterFiller=ui("div",null,"CodeMirror-gutter-filler"),
// Will contain the actual code, positioned to cover the viewport.
r.lineDiv=ui("div",null,"CodeMirror-code"),
// Elements are added to these to represent selection and cursors.
r.selectionDiv=ui("div",null,null,"position: relative; z-index: 1"),r.cursorDiv=ui("div",null,"CodeMirror-cursors"),
// A visibility: hidden element used to find the size of things.
r.measure=ui("div",null,"CodeMirror-measure"),
// When lines outside of the viewport are measured, they are drawn in this.
r.lineMeasure=ui("div",null,"CodeMirror-measure"),
// Wraps everything that needs to exist inside the vertically-padded coordinate system
r.lineSpace=ui("div",[r.measure,r.lineMeasure,r.selectionDiv,r.cursorDiv,r.lineDiv],null,"position: relative; outline: none"),
// Moved around its parent to cover visible view.
r.mover=ui("div",[ui("div",[r.lineSpace],"CodeMirror-lines")],null,"position: relative"),
// Set to the height of the document, allowing scrolling.
r.sizer=ui("div",[r.mover],"CodeMirror-sizer"),
// Behavior of elts with overflow: auto and padding is
// inconsistent across browsers. This is used to ensure the
// scrollable area is big enough.
r.heightForcer=ui("div",null,null,"position: absolute; height: "+tl+"px; width: 1px;"),
// Will contain the gutters, if any.
r.gutters=ui("div",null,"CodeMirror-gutters"),r.lineGutter=null,
// Actual scrollable element.
r.scroller=ui("div",[r.sizer,r.heightForcer,r.gutters],"CodeMirror-scroll"),r.scroller.setAttribute("tabIndex","-1"),
// The element in which the editor lives.
r.wrapper=ui("div",[r.inputDiv,r.scrollbarH,r.scrollbarV,r.scrollbarFiller,r.gutterFiller,r.scroller],"CodeMirror"),
// Work around IE7 z-index bug (not perfect, hence IE7 not really being supported)
Fi&&(r.gutters.style.zIndex=-1,r.scroller.style.paddingRight=0),
// Needed to hide big blue blinking cursor on Mobile Safari
Zi&&(n.style.width="0px"),Ui||(r.scroller.draggable=!0),
// Needed to handle Tab key in KHTML
ji&&(r.inputDiv.style.height="1px",r.inputDiv.style.position="absolute"),
// Need to set a minimum width to see the scrollbar on IE7 (but must not set it on IE8).
Fi&&(r.scrollbarH.style.minHeight=r.scrollbarV.style.minWidth="18px"),e.appendChild?e.appendChild(r.wrapper):e(r.wrapper),
// Current rendered range (may be bigger than the view window).
r.viewFrom=r.viewTo=t.first,
// Information about the rendered lines.
r.view=[],
// Holds info about a single rendered line when it was rendered
// for measurement, while not in view.
r.externalMeasured=null,
// Empty space (in pixels) above the view
r.viewOffset=0,r.lastSizeC=0,r.updateLineNumbers=null,
// Used to only resize the line number gutter when necessary (when
// the amount of lines crosses a boundary that makes its width change)
r.lineNumWidth=r.lineNumInnerWidth=r.lineNumChars=null,
// See readInput and resetInput
r.prevInput="",
// Set to true when a non-horizontal-scrolling line widget is
// added. As an optimization, line widget aligning is skipped when
// this is false.
r.alignWidgets=!1,
// Flag that indicates whether we expect input to appear real soon
// now (after some event like 'keypress' or 'input') and are
// polling intensively.
r.pollingFast=!1,
// Self-resetting timeout for the poller
r.poll=new Zn,r.cachedCharWidth=r.cachedTextHeight=r.cachedPaddingH=null,
// Tracks when resetInput has punted to just putting a short
// string into the textarea instead of the full selection.
r.inaccurateSelection=!1,
// Tracks the maximum line length so that the horizontal scrollbar
// can be kept static when scrolling.
r.maxLine=null,r.maxLineLength=0,r.maxLineChanged=!1,
// Used for measuring wheel scrolling granularity
r.wheelDX=r.wheelDY=r.wheelStartX=r.wheelStartY=null,
// True when shift is held down.
r.shift=!1,
// Used to track whether anything happened since the context menu
// was opened.
r.selForContextMenu=null}
// STATE UPDATES
// Used to get the editor into a consistent state again when options change.
function r(t){t.doc.mode=e.getMode(t.options,t.doc.modeOption),n(t)}function n(e){e.doc.iter(function(e){e.stateAfter&&(e.stateAfter=null),e.styles&&(e.styles=null)}),e.doc.frontier=e.doc.first,ye(e,100),e.state.modeGen++,e.curOp&&rt(e)}function i(e){e.options.lineWrapping?(vi(e.display.wrapper,"CodeMirror-wrap"),e.display.sizer.style.minWidth=""):(gi(e.display.wrapper,"CodeMirror-wrap"),d(e)),l(e),rt(e),Ie(e),setTimeout(function(){v(e)},100)}
// Returns a function that estimates the height of a line, to use as
// first approximation until the line becomes visible (and is thus
// properly measurable).
function o(e){var t=Xe(e.display),r=e.options.lineWrapping,n=r&&Math.max(5,e.display.scroller.clientWidth/Ye(e.display)-3);return function(i){if(Kr(e.doc,i))return 0;var o=0;if(i.widgets)for(var l=0;l<i.widgets.length;l++)i.widgets[l].height&&(o+=i.widgets[l].height);return r?o+(Math.ceil(i.text.length/n)||1)*t:o+t}}function l(e){var t=e.doc,r=o(e);t.iter(function(e){var t=r(e);t!=e.height&&Cn(e,t)})}function s(e){var t=Oo[e.options.keyMap],r=t.style;e.display.wrapper.className=e.display.wrapper.className.replace(/\s*cm-keymap-\S+/g,"")+(r?" cm-keymap-"+r:"")}function a(e){e.display.wrapper.className=e.display.wrapper.className.replace(/\s*cm-s-\S+/g,"")+e.options.theme.replace(/(^|\s)\s*/g," cm-s-"),Ie(e)}function u(e){c(e),rt(e),setTimeout(function(){y(e)},20)}
// Rebuild the gutter elements, ensure the margin to the left of the
// code matches their width.
function c(e){var t=e.display.gutters,r=e.options.gutters;ci(t);for(var n=0;n<r.length;++n){var i=r[n],o=t.appendChild(ui("div",null,"CodeMirror-gutter "+i));"CodeMirror-linenumbers"==i&&(e.display.lineGutter=o,o.style.width=(e.display.lineNumWidth||1)+"px")}t.style.display=n?"":"none",f(e)}function f(e){var t=e.display.gutters.offsetWidth;e.display.sizer.style.marginLeft=t+"px",e.display.scrollbarH.style.left=e.options.fixedGutter?t+"px":0}
// Compute the character length of a line, taking into account
// collapsed ranges (see markText) that might hide parts, and join
// other lines onto it.
function h(e){if(0==e.height)return 0;for(var t,r=e.text.length,n=e;t=Pr(n);){var i=t.find(0,!0);n=i.from.line,r+=i.from.ch-i.to.ch}for(n=e;t=Fr(n);){var i=t.find(0,!0);r-=n.text.length-i.from.ch,n=i.to.line,r+=n.text.length-i.to.ch}return r}
// Find the longest line in the document.
function d(e){var t=e.display,r=e.doc;t.maxLine=bn(r,r.first),t.maxLineLength=h(t.maxLine),t.maxLineChanged=!0,r.iter(function(e){var r=h(e);r>t.maxLineLength&&(t.maxLineLength=r,t.maxLine=e)})}
// Make sure the gutters options contains the element
// "CodeMirror-linenumbers" when the lineNumbers option is true.
function p(e){var t=ti(e.gutters,"CodeMirror-linenumbers");-1==t&&e.lineNumbers?e.gutters=e.gutters.concat(["CodeMirror-linenumbers"]):t>-1&&!e.lineNumbers&&(e.gutters=e.gutters.slice(0),e.gutters.splice(t,1))}
// SCROLLBARS
// Prepare DOM reads needed to update the scrollbars. Done in one
// shot to minimize update/measure roundtrips.
function g(e){var t=e.display.scroller;return{clientHeight:t.clientHeight,barHeight:e.display.scrollbarV.clientHeight,scrollWidth:t.scrollWidth,clientWidth:t.clientWidth,barWidth:e.display.scrollbarH.clientWidth,docHeight:Math.round(e.doc.height+Le(e.display))}}
// Re-synchronize the fake scrollbars with the actual size of the
// content.
function v(e,t){t||(t=g(e));var r=e.display,n=t.docHeight+tl,i=t.scrollWidth>t.clientWidth,o=n>t.clientHeight;if(o?(r.scrollbarV.style.display="block",r.scrollbarV.style.bottom=i?wi(r.measure)+"px":"0",
// A bug in IE8 can cause this value to be negative, so guard it.
r.scrollbarV.firstChild.style.height=Math.max(0,n-t.clientHeight+(t.barHeight||r.scrollbarV.clientHeight))+"px"):(r.scrollbarV.style.display="",r.scrollbarV.firstChild.style.height="0"),i?(r.scrollbarH.style.display="block",r.scrollbarH.style.right=o?wi(r.measure)+"px":"0",r.scrollbarH.firstChild.style.width=t.scrollWidth-t.clientWidth+(t.barWidth||r.scrollbarH.clientWidth)+"px"):(r.scrollbarH.style.display="",r.scrollbarH.firstChild.style.width="0"),i&&o?(r.scrollbarFiller.style.display="block",r.scrollbarFiller.style.height=r.scrollbarFiller.style.width=wi(r.measure)+"px"):r.scrollbarFiller.style.display="",i&&e.options.coverGutterNextToScrollbar&&e.options.fixedGutter?(r.gutterFiller.style.display="block",r.gutterFiller.style.height=wi(r.measure)+"px",r.gutterFiller.style.width=r.gutters.offsetWidth+"px"):r.gutterFiller.style.display="",!e.state.checkedOverlayScrollbar&&t.clientHeight>0){if(0===wi(r.measure)){var l=Ji&&!$i?"12px":"18px";r.scrollbarV.style.minWidth=r.scrollbarH.style.minHeight=l;var s=function(t){Un(t)!=r.scrollbarV&&Un(t)!=r.scrollbarH&&Ze(e,xt)(t)};Zo(r.scrollbarV,"mousedown",s),Zo(r.scrollbarH,"mousedown",s)}e.state.checkedOverlayScrollbar=!0}}
// Compute the lines that are visible in a given viewport (defaults
// the the current scroll position). viewPort may contain top,
// height, and ensure (see op.scrollToPos) properties.
function m(e,t,r){var n=r&&null!=r.top?Math.max(0,r.top):e.scroller.scrollTop;n=Math.floor(n-Ce(e));var i=r&&null!=r.bottom?r.bottom:n+e.wrapper.clientHeight,o=Sn(t,n),l=Sn(t,i);
// Ensure is a {from: {line, ch}, to: {line, ch}} object, and
// forces those lines into the viewport (if possible).
if(r&&r.ensure){var s=r.ensure.from.line,a=r.ensure.to.line;if(o>s)return{from:s,to:Sn(t,kn(bn(t,s))+e.wrapper.clientHeight)};if(Math.min(a,t.lastLine())>=l)return{from:Sn(t,kn(bn(t,a))-e.wrapper.clientHeight),to:a}}return{from:o,to:Math.max(l,o+1)}}
// LINE NUMBERS
// Re-align line numbers and gutter marks to compensate for
// horizontal scrolling.
function y(e){var t=e.display,r=t.view;if(t.alignWidgets||t.gutters.firstChild&&e.options.fixedGutter){for(var n=w(t)-t.scroller.scrollLeft+e.doc.scrollLeft,i=t.gutters.offsetWidth,o=n+"px",l=0;l<r.length;l++)if(!r[l].hidden){e.options.fixedGutter&&r[l].gutter&&(r[l].gutter.style.left=o);var s=r[l].alignable;if(s)for(var a=0;a<s.length;a++)s[a].style.left=o}e.options.fixedGutter&&(t.gutters.style.left=n+i+"px")}}
// Used to ensure that the line number gutter is still the right
// size for the current document size. Returns true when an update
// is needed.
function b(e){if(!e.options.lineNumbers)return!1;var t=e.doc,r=x(e.options,t.first+t.size-1),n=e.display;if(r.length!=n.lineNumChars){var i=n.measure.appendChild(ui("div",[ui("div",r)],"CodeMirror-linenumber CodeMirror-gutter-elt")),o=i.firstChild.offsetWidth,l=i.offsetWidth-o;return n.lineGutter.style.width="",n.lineNumInnerWidth=Math.max(o,n.lineGutter.offsetWidth-l),n.lineNumWidth=n.lineNumInnerWidth+l,n.lineNumChars=n.lineNumInnerWidth?r.length:-1,n.lineGutter.style.width=n.lineNumWidth+"px",f(e),!0}return!1}function x(e,t){return String(e.lineNumberFormatter(t+e.firstLineNumber))}
// Computes display.scroller.scrollLeft + display.gutters.offsetWidth,
// but using getBoundingClientRect to get a sub-pixel-accurate
// result.
function w(e){return e.scroller.getBoundingClientRect().left-e.sizer.getBoundingClientRect().left}
// DISPLAY DRAWING
// Updates the display, selection, and scrollbars, using the
// information in display.view to find out which nodes are no longer
// up-to-date. Tries to bail out early when no changes are needed,
// unless forced is true.
// Returns true if an actual update happened, false otherwise.
function C(e,t,r){for(var n,i=e.display.viewFrom,o=e.display.viewTo,l=m(e.display,e.doc,t),s=!0;;s=!1){var a=e.display.scroller.clientWidth;if(!L(e,l,r))break;n=!0,
// If the max line changed since it was last measured, measure it,
// and ensure the document's width matches it.
e.display.maxLineChanged&&!e.options.lineWrapping&&S(e);var u=g(e);// (Issue #2420)
if(pe(e),k(e,u),v(e,u),Ui&&e.options.lineWrapping&&M(e,u),s&&e.options.lineWrapping&&a!=e.display.scroller.clientWidth)r=!0;else if(r=!1,t&&null!=t.top&&(t={top:Math.min(u.docHeight-tl-u.clientHeight,t.top)}),l=m(e.display,e.doc,t),l.from>=e.display.viewFrom&&l.to<=e.display.viewTo)break}return e.display.updateLineNumbers=null,n&&(_n(e,"update",e),(e.display.viewFrom!=i||e.display.viewTo!=o)&&_n(e,"viewportChange",e,e.display.viewFrom,e.display.viewTo)),n}
// Does the actual updating of the line display. Bails out
// (returning false) when there is nothing to be done and forced is
// false.
function L(e,t,r){var n=e.display,i=e.doc;if(!n.wrapper.offsetWidth)return void it(e);
// Bail out if the visible area is already rendered and nothing changed.
if(!(!r&&t.from>=n.viewFrom&&t.to<=n.viewTo&&0==at(e))){b(e)&&it(e);var o=H(e),l=i.first+i.size,s=Math.max(t.from-e.options.viewportMargin,i.first),a=Math.min(l,t.to+e.options.viewportMargin);n.viewFrom<s&&s-n.viewFrom<20&&(s=Math.max(i.first,n.viewFrom)),n.viewTo>a&&n.viewTo-a<20&&(a=Math.min(l,n.viewTo)),oo&&(s=Vr(e.doc,s),a=Ur(e.doc,a));var u=s!=n.viewFrom||a!=n.viewTo||n.lastSizeC!=n.wrapper.clientHeight;st(e,s,a),n.viewOffset=kn(bn(e.doc,n.viewFrom)),
// Position the mover div to align with the current scroll position
e.display.mover.style.top=n.viewOffset+"px";var c=at(e);if(u||0!=c||r){
// For big changes, we hide the enclosing element during the
// update, since that speeds up the operations on most browsers.
var f=di();
// There might have been a widget with a focused element that got
// hidden or updated, if so re-focus it.
// Prevent selection and cursors from interfering with the scroll
// width.
return c>4&&(n.lineDiv.style.display="none"),A(e,n.updateLineNumbers,o),c>4&&(n.lineDiv.style.display=""),f&&di()!=f&&f.offsetHeight&&f.focus(),ci(n.cursorDiv),ci(n.selectionDiv),u&&(n.lastSizeC=n.wrapper.clientHeight,ye(e,400)),T(e),!0}}}function S(e){var t=e.display,r=Ne(e,t.maxLine,t.maxLine.text.length).left;t.maxLineChanged=!1;var n=Math.max(0,r+3),i=Math.max(0,t.sizer.offsetLeft+n+tl-t.scroller.clientWidth);t.sizer.style.minWidth=n+"px",i<e.doc.scrollLeft&&Ht(e,Math.min(t.scroller.scrollLeft,i),!0)}function k(e,t){e.display.sizer.style.minHeight=e.display.heightForcer.style.top=t.docHeight+"px",e.display.gutters.style.height=Math.max(t.docHeight,t.clientHeight-tl)+"px"}function M(e,t){
// Work around Webkit bug where it sometimes reserves space for a
// non-existing phantom scrollbar in the scroller (Issue #2420)
e.display.sizer.offsetWidth+e.display.gutters.offsetWidth<e.display.scroller.clientWidth-1&&(e.display.sizer.style.minHeight=e.display.heightForcer.style.top="0px",e.display.gutters.style.height=t.docHeight+"px")}
// Read the actual heights of the rendered lines, and update their
// stored heights to match.
function T(e){for(var t=e.display,r=t.lineDiv.offsetTop,n=0;n<t.view.length;n++){var i,o=t.view[n];if(!o.hidden){if(Fi){var l=o.node.offsetTop+o.node.offsetHeight;i=l-r,r=l}else{var s=o.node.getBoundingClientRect();i=s.bottom-s.top}var a=o.line.height-i;if(2>i&&(i=Xe(t)),(a>.001||-.001>a)&&(Cn(o.line,i),N(o.line),o.rest))for(var u=0;u<o.rest.length;u++)N(o.rest[u])}}}
// Read and store the height of line widgets associated with the
// given line.
function N(e){if(e.widgets)for(var t=0;t<e.widgets.length;++t)e.widgets[t].height=e.widgets[t].node.offsetHeight}
// Do a bulk-read of the DOM positions and sizes needed to draw the
// view, so that we don't interleave reading and writing to the DOM.
function H(e){for(var t=e.display,r={},n={},i=t.gutters.firstChild,o=0;i;i=i.nextSibling,++o)r[e.options.gutters[o]]=i.offsetLeft,n[e.options.gutters[o]]=i.offsetWidth;return{fixedPos:w(t),gutterTotalWidth:t.gutters.offsetWidth,gutterLeft:r,gutterWidth:n,wrapperWidth:t.wrapper.clientWidth}}
// Sync the actual display DOM structure with display.view, removing
// nodes for lines that are no longer in view, and creating the ones
// that are not there yet, and updating the ones that are out of
// date.
function A(e,t,r){function n(t){var r=t.nextSibling;
// Works around a throw-scroll bug in OS X Webkit
return Ui&&Ji&&e.display.currentWheelTarget==t?t.style.display="none":t.parentNode.removeChild(t),r}
// Loop over the elements in the view, syncing cur (the DOM nodes
// in display.lineDiv) with the view as we go.
for(var i=e.display,o=e.options.lineNumbers,l=i.lineDiv,s=l.firstChild,a=i.view,u=i.viewFrom,c=0;c<a.length;c++){var f=a[c];if(f.hidden);else if(f.node){// Already drawn
for(;s!=f.node;)s=n(s);var h=o&&null!=t&&u>=t&&f.lineNumber;f.changes&&(ti(f.changes,"gutter")>-1&&(h=!1),O(e,f,u,r)),h&&(ci(f.lineNumber),f.lineNumber.appendChild(document.createTextNode(x(e.options,u)))),s=f.node.nextSibling}else{// Not drawn yet
var d=R(e,f,u,r);l.insertBefore(d,s)}u+=f.size}for(;s;)s=n(s)}
// When an aspect of a line changes, a string is added to
// lineView.changes. This updates the relevant part of the line's
// DOM structure.
function O(e,t,r,n){for(var i=0;i<t.changes.length;i++){var o=t.changes[i];"text"==o?I(e,t):"gutter"==o?P(e,t,r,n):"class"==o?z(t):"widget"==o&&F(t,n)}t.changes=null}
// Lines with gutter elements, widgets or a background class need to
// be wrapped, and have the extra elements added to the wrapper div
function W(e){return e.node==e.text&&(e.node=ui("div",null,null,"position: relative"),e.text.parentNode&&e.text.parentNode.replaceChild(e.node,e.text),e.node.appendChild(e.text),Fi&&(e.node.style.zIndex=2)),e.node}function D(e){var t=e.bgClass?e.bgClass+" "+(e.line.bgClass||""):e.line.bgClass;if(t&&(t+=" CodeMirror-linebackground"),e.background)t?e.background.className=t:(e.background.parentNode.removeChild(e.background),e.background=null);else if(t){var r=W(e);e.background=r.insertBefore(ui("div",null,t),r.firstChild)}}
// Wrapper around buildLineContent which will reuse the structure
// in display.externalMeasured when possible.
function E(e,t){var r=e.display.externalMeasured;return r&&r.line==t.line?(e.display.externalMeasured=null,t.measure=r.measure,r.built):ln(e,t)}
// Redraw the line's text. Interacts with the background and text
// classes because the mode may output tokens that influence these
// classes.
function I(e,t){var r=t.text.className,n=E(e,t);t.text==t.node&&(t.node=n.pre),t.text.parentNode.replaceChild(n.pre,t.text),t.text=n.pre,n.bgClass!=t.bgClass||n.textClass!=t.textClass?(t.bgClass=n.bgClass,t.textClass=n.textClass,z(t)):r&&(t.text.className=r)}function z(e){D(e),e.line.wrapClass?W(e).className=e.line.wrapClass:e.node!=e.text&&(e.node.className="");var t=e.textClass?e.textClass+" "+(e.line.textClass||""):e.line.textClass;e.text.className=t||""}function P(e,t,r,n){t.gutter&&(t.node.removeChild(t.gutter),t.gutter=null);var i=t.line.gutterMarkers;if(e.options.lineNumbers||i){var o=W(t),l=t.gutter=o.insertBefore(ui("div",null,"CodeMirror-gutter-wrapper","position: absolute; left: "+(e.options.fixedGutter?n.fixedPos:-n.gutterTotalWidth)+"px"),t.text);if(!e.options.lineNumbers||i&&i["CodeMirror-linenumbers"]||(t.lineNumber=l.appendChild(ui("div",x(e.options,r),"CodeMirror-linenumber CodeMirror-gutter-elt","left: "+n.gutterLeft["CodeMirror-linenumbers"]+"px; width: "+e.display.lineNumInnerWidth+"px"))),i)for(var s=0;s<e.options.gutters.length;++s){var a=e.options.gutters[s],u=i.hasOwnProperty(a)&&i[a];u&&l.appendChild(ui("div",[u],"CodeMirror-gutter-elt","left: "+n.gutterLeft[a]+"px; width: "+n.gutterWidth[a]+"px"))}}}function F(e,t){e.alignable&&(e.alignable=null);for(var r,n=e.node.firstChild;n;n=r){var r=n.nextSibling;"CodeMirror-linewidget"==n.className&&e.node.removeChild(n)}B(e,t)}
// Build a line's DOM representation from scratch
function R(e,t,r,n){var i=E(e,t);return t.text=t.node=i.pre,i.bgClass&&(t.bgClass=i.bgClass),i.textClass&&(t.textClass=i.textClass),z(t),P(e,t,r,n),B(t,n),t.node}
// A lineView may contain multiple logical lines (when merged by
// collapsed spans). The widgets for all of them need to be drawn.
function B(e,t){if(G(e.line,e,t,!0),e.rest)for(var r=0;r<e.rest.length;r++)G(e.rest[r],e,t,!1)}function G(e,t,r,n){if(e.widgets)for(var i=W(t),o=0,l=e.widgets;o<l.length;++o){var s=l[o],a=ui("div",[s.node],"CodeMirror-linewidget");s.handleMouseEvents||(a.ignoreEvents=!0),V(s,a,t,r),n&&s.above?i.insertBefore(a,t.gutter||t.text):i.appendChild(a),_n(s,"redraw")}}function V(e,t,r,n){if(e.noHScroll){(r.alignable||(r.alignable=[])).push(t);var i=n.wrapperWidth;t.style.left=n.fixedPos+"px",e.coverGutter||(i-=n.gutterTotalWidth,t.style.paddingLeft=n.gutterTotalWidth+"px"),t.style.width=i+"px"}e.coverGutter&&(t.style.zIndex=5,t.style.position="relative",e.noHScroll||(t.style.marginLeft=-n.gutterTotalWidth+"px"))}function U(e){return lo(e.line,e.ch)}function K(e,t){return so(e,t)<0?t:e}function _(e,t){return so(e,t)<0?e:t}
// SELECTION / CURSOR
// Selection objects are immutable. A new one is created every time
// the selection changes. A selection is one or more non-overlapping
// (and non-touching) ranges, sorted, and an integer that indicates
// which one is the primary selection (the one that's scrolled into
// view, that getCursor returns, etc).
function X(e,t){this.ranges=e,this.primIndex=t}function Y(e,t){this.anchor=e,this.head=t}
// Take an unsorted, potentially overlapping set of ranges, and
// build a selection out of it. 'Consumes' ranges array (modifying
// it).
function j(e,t){var r=e[t];e.sort(function(e,t){return so(e.from(),t.from())}),t=ti(e,r);for(var n=1;n<e.length;n++){var i=e[n],o=e[n-1];if(so(o.to(),i.from())>=0){var l=_(o.from(),i.from()),s=K(o.to(),i.to()),a=o.empty()?i.from()==i.head:o.from()==o.head;t>=n&&--t,e.splice(--n,2,new Y(a?s:l,a?l:s))}}return new X(e,t)}function $(e,t){return new X([new Y(e,t||e)],0)}
// Most of the external API clips given positions to make sure they
// actually exist within the document.
function q(e,t){return Math.max(e.first,Math.min(t,e.first+e.size-1))}function Z(e,t){if(t.line<e.first)return lo(e.first,0);var r=e.first+e.size-1;return t.line>r?lo(r,bn(e,r).text.length):Q(t,bn(e,t.line).text.length)}function Q(e,t){var r=e.ch;return null==r||r>t?lo(e.line,t):0>r?lo(e.line,0):e}function J(e,t){return t>=e.first&&t<e.first+e.size}function ee(e,t){for(var r=[],n=0;n<t.length;n++)r[n]=Z(e,t[n]);return r}
// SELECTION UPDATES
// The 'scroll' parameter given to many of these indicated whether
// the new cursor position should be scrolled into view after
// modifying the selection.
// If shift is held or the extend flag is set, extends a range to
// include a given position (and optionally a second position).
// Otherwise, simply returns the range between the given positions.
// Used for cursor motion and such.
function te(e,t,r,n){if(e.cm&&e.cm.display.shift||e.extend){var i=t.anchor;if(n){var o=so(r,i)<0;o!=so(n,i)<0?(i=r,r=n):o!=so(r,n)<0&&(r=n)}return new Y(i,r)}return new Y(n||r,r)}
// Extend the primary selection range, discard the rest.
function re(e,t,r,n){ae(e,new X([te(e,e.sel.primary(),t,r)],0),n)}
// Extend all selections (pos is an array of selections with length
// equal the number of selections)
function ne(e,t,r){for(var n=[],i=0;i<e.sel.ranges.length;i++)n[i]=te(e,e.sel.ranges[i],t[i],null);var o=j(n,e.sel.primIndex);ae(e,o,r)}
// Updates a single range in the selection.
function ie(e,t,r,n){var i=e.sel.ranges.slice(0);i[t]=r,ae(e,j(i,e.sel.primIndex),n)}
// Reset the selection to a single range.
function oe(e,t,r,n){ae(e,$(t,r),n)}
// Give beforeSelectionChange handlers a change to influence a
// selection update.
function le(e,t){var r={ranges:t.ranges,update:function(t){this.ranges=[];for(var r=0;r<t.length;r++)this.ranges[r]=new Y(Z(e,t[r].anchor),Z(e,t[r].head))}};return Jo(e,"beforeSelectionChange",e,r),e.cm&&Jo(e.cm,"beforeSelectionChange",e.cm,r),r.ranges!=t.ranges?j(r.ranges,r.ranges.length-1):t}function se(e,t,r){var n=e.history.done,i=ei(n);i&&i.ranges?(n[n.length-1]=t,ue(e,t,r)):ae(e,t,r)}
// Set a new selection.
function ae(e,t,r){ue(e,t,r),Dn(e,e.sel,e.cm?e.cm.curOp.id:NaN,r)}function ue(e,t,r){($n(e,"beforeSelectionChange")||e.cm&&$n(e.cm,"beforeSelectionChange"))&&(t=le(e,t));var n=r&&r.bias||(so(t.primary().head,e.sel.primary().head)<0?-1:1);ce(e,he(e,t,n,!0)),r&&r.scroll===!1||!e.cm||lr(e.cm)}function ce(e,t){t.equals(e.sel)||(e.sel=t,e.cm&&(e.cm.curOp.updateInput=e.cm.curOp.selectionChanged=!0,jn(e.cm)),_n(e,"cursorActivity",e))}
// Verify that the selection does not partially select any atomic
// marked ranges.
function fe(e){ce(e,he(e,e.sel,null,!1),nl)}
// Return a selection that does not partially select any atomic
// ranges.
function he(e,t,r,n){for(var i,o=0;o<t.ranges.length;o++){var l=t.ranges[o],s=de(e,l.anchor,r,n),a=de(e,l.head,r,n);(i||s!=l.anchor||a!=l.head)&&(i||(i=t.ranges.slice(0,o)),i[o]=new Y(s,a))}return i?j(i,t.primIndex):t}
// Ensure a given position is not inside an atomic range.
function de(e,t,r,n){var i=!1,o=t,l=r||1;e.cantEdit=!1;e:for(;;){var s=bn(e,o.line);if(s.markedSpans)for(var a=0;a<s.markedSpans.length;++a){var u=s.markedSpans[a],c=u.marker;if((null==u.from||(c.inclusiveLeft?u.from<=o.ch:u.from<o.ch))&&(null==u.to||(c.inclusiveRight?u.to>=o.ch:u.to>o.ch))){if(n&&(Jo(c,"beforeCursorEnter"),c.explicitlyCleared)){if(s.markedSpans){--a;continue}break}if(!c.atomic)continue;var f=c.find(0>l?-1:1);if(0==so(f,o)&&(f.ch+=l,f.ch<0?f=f.line>e.first?Z(e,lo(f.line-1)):null:f.ch>s.text.length&&(f=f.line<e.first+e.size-1?lo(f.line+1,0):null),!f)){if(i)
// Driven in a corner -- no valid cursor position found at all
// -- try again *with* clearing, if we didn't already
// Driven in a corner -- no valid cursor position found at all
// -- try again *with* clearing, if we didn't already
// Otherwise, turn off editing until further notice, and return the start of the doc
return n?(e.cantEdit=!0,lo(e.first,0)):de(e,t,r,!0);i=!0,f=t,l=-l}o=f;continue e}}return o}}
// SELECTION DRAWING
// Redraw the selection and/or cursor
function pe(e){for(var t=e.display,r=e.doc,n=document.createDocumentFragment(),i=document.createDocumentFragment(),o=0;o<r.sel.ranges.length;o++){var l=r.sel.ranges[o],s=l.empty();(s||e.options.showCursorWhenSelecting)&&ge(e,l,n),s||ve(e,l,i)}
// Move the hidden textarea near the cursor to prevent scrolling artifacts
if(e.options.moveInputWithCursor){var a=Ge(e,r.sel.primary().head,"div"),u=t.wrapper.getBoundingClientRect(),c=t.lineDiv.getBoundingClientRect(),f=Math.max(0,Math.min(t.wrapper.clientHeight-10,a.top+c.top-u.top)),h=Math.max(0,Math.min(t.wrapper.clientWidth-10,a.left+c.left-u.left));t.inputDiv.style.top=f+"px",t.inputDiv.style.left=h+"px"}fi(t.cursorDiv,n),fi(t.selectionDiv,i)}
// Draws a cursor for the given range
function ge(e,t,r){var n=Ge(e,t.head,"div"),i=r.appendChild(ui("div"," ","CodeMirror-cursor"));if(i.style.left=n.left+"px",i.style.top=n.top+"px",i.style.height=Math.max(0,n.bottom-n.top)*e.options.cursorHeight+"px",n.other){
// Secondary cursor, shown when on a 'jump' in bi-directional text
var o=r.appendChild(ui("div"," ","CodeMirror-cursor CodeMirror-secondarycursor"));o.style.display="",o.style.left=n.other.left+"px",o.style.top=n.other.top+"px",o.style.height=.85*(n.other.bottom-n.other.top)+"px"}}
// Draws the given range as a highlighted selection
function ve(e,t,r){function n(e,t,r,n){0>t&&(t=0),t=Math.round(t),n=Math.round(n),s.appendChild(ui("div",null,"CodeMirror-selected","position: absolute; left: "+e+"px; top: "+t+"px; width: "+(null==r?c-e:r)+"px; height: "+(n-t)+"px"))}function i(t,r,i){function o(r,n){return Be(e,lo(t,r),"div",f,n)}var s,a,f=bn(l,t),h=f.text.length;return Si(Mn(f),r||0,null==i?h:i,function(e,t,l){var f,d,p,g=o(e,"left");if(e==t)f=g,d=p=g.left;else{if(f=o(t-1,"right"),"rtl"==l){var v=g;g=f,f=v}d=g.left,p=f.right}null==r&&0==e&&(d=u),f.top-g.top>3&&(// Different lines, draw top part
n(d,g.top,null,g.bottom),d=u,g.bottom<f.top&&n(d,g.bottom,null,f.top)),null==i&&t==h&&(p=c),(!s||g.top<s.top||g.top==s.top&&g.left<s.left)&&(s=g),(!a||f.bottom>a.bottom||f.bottom==a.bottom&&f.right>a.right)&&(a=f),u+1>d&&(d=u),n(d,f.top,p-d,f.bottom)}),{start:s,end:a}}var o=e.display,l=e.doc,s=document.createDocumentFragment(),a=Se(e.display),u=a.left,c=o.lineSpace.offsetWidth-a.right,f=t.from(),h=t.to();if(f.line==h.line)i(f.line,f.ch,h.ch);else{var d=bn(l,f.line),p=bn(l,h.line),g=Br(d)==Br(p),v=i(f.line,f.ch,g?d.text.length+1:null).end,m=i(h.line,g?0:null,h.ch).start;g&&(v.top<m.top-2?(n(v.right,v.top,null,v.bottom),n(u,m.top,m.left,m.bottom)):n(v.right,v.top,m.left-v.right,v.bottom)),v.bottom<m.top&&n(u,v.bottom,null,m.top)}r.appendChild(s)}
// Cursor-blinking
function me(e){if(e.state.focused){var t=e.display;clearInterval(t.blinker);var r=!0;t.cursorDiv.style.visibility="",e.options.cursorBlinkRate>0&&(t.blinker=setInterval(function(){t.cursorDiv.style.visibility=(r=!r)?"":"hidden"},e.options.cursorBlinkRate))}}
// HIGHLIGHT WORKER
function ye(e,t){e.doc.mode.startState&&e.doc.frontier<e.display.viewTo&&e.state.highlight.set(t,oi(be,e))}function be(e){var t=e.doc;if(t.frontier<t.first&&(t.frontier=t.first),!(t.frontier>=e.display.viewTo)){var r=+new Date+e.options.workTime,n=No(t.mode,we(e,t.frontier));qe(e,function(){t.iter(t.frontier,Math.min(t.first+t.size,e.display.viewTo+500),function(i){if(t.frontier>=e.display.viewFrom){// Visible
var o=i.styles,l=tn(e,i,n,!0);i.styles=l.styles,l.classes?i.styleClasses=l.classes:i.styleClasses&&(i.styleClasses=null);for(var s=!o||o.length!=i.styles.length,a=0;!s&&a<o.length;++a)s=o[a]!=i.styles[a];s&&nt(e,t.frontier,"text"),i.stateAfter=No(t.mode,n)}else nn(e,i.text,n),i.stateAfter=t.frontier%5==0?No(t.mode,n):null;return++t.frontier,+new Date>r?(ye(e,e.options.workDelay),!0):void 0})})}}
// Finds the line to start with when starting a parse. Tries to
// find a line with a stateAfter, so that it can start with a
// valid state. If that fails, it returns the line with the
// smallest indentation, which tends to need the least context to
// parse correctly.
function xe(e,t,r){for(var n,i,o=e.doc,l=r?-1:t-(e.doc.mode.innerMode?1e3:100),s=t;s>l;--s){if(s<=o.first)return o.first;var a=bn(o,s-1);if(a.stateAfter&&(!r||s<=o.frontier))return s;var u=ll(a.text,null,e.options.tabSize);(null==i||n>u)&&(i=s-1,n=u)}return i}function we(e,t,r){var n=e.doc,i=e.display;if(!n.mode.startState)return!0;var o=xe(e,t,r),l=o>n.first&&bn(n,o-1).stateAfter;return l=l?No(n.mode,l):Ho(n.mode),n.iter(o,t,function(r){nn(e,r.text,l);var s=o==t-1||o%5==0||o>=i.viewFrom&&o<i.viewTo;r.stateAfter=s?No(n.mode,l):null,++o}),r&&(n.frontier=o),l}
// POSITION MEASUREMENT
function Ce(e){return e.lineSpace.offsetTop}function Le(e){return e.mover.offsetHeight-e.lineSpace.offsetHeight}function Se(e){if(e.cachedPaddingH)return e.cachedPaddingH;var t=fi(e.measure,ui("pre","x")),r=window.getComputedStyle?window.getComputedStyle(t):t.currentStyle,n={left:parseInt(r.paddingLeft),right:parseInt(r.paddingRight)};return isNaN(n.left)||isNaN(n.right)||(e.cachedPaddingH=n),n}
// Ensure the lineView.wrapping.heights array is populated. This is
// an array of bottom offsets for the lines that make up a drawn
// line. When lineWrapping is on, there might be more than one
// height.
function ke(e,t,r){var n=e.options.lineWrapping,i=n&&e.display.scroller.clientWidth;if(!t.measure.heights||n&&t.measure.width!=i){var o=t.measure.heights=[];if(n){t.measure.width=i;for(var l=t.text.firstChild.getClientRects(),s=0;s<l.length-1;s++){var a=l[s],u=l[s+1];Math.abs(a.bottom-u.bottom)>2&&o.push((a.bottom+u.top)/2-r.top)}}o.push(r.bottom-r.top)}}
// Find a line map (mapping character offsets to text nodes) and a
// measurement cache for the given line number. (A line view might
// contain multiple lines when collapsed ranges are present.)
function Me(e,t,r){if(e.line==t)return{map:e.measure.map,cache:e.measure.cache};for(var n=0;n<e.rest.length;n++)if(e.rest[n]==t)return{map:e.measure.maps[n],cache:e.measure.caches[n]};for(var n=0;n<e.rest.length;n++)if(Ln(e.rest[n])>r)return{map:e.measure.maps[n],cache:e.measure.caches[n],before:!0}}
// Render a line into the hidden node display.externalMeasured. Used
// when measurement is needed for a line that's not in the viewport.
function Te(e,t){t=Br(t);var r=Ln(t),n=e.display.externalMeasured=new et(e.doc,t,r);n.lineN=r;var i=n.built=ln(e,n);return n.text=i.pre,fi(e.display.lineMeasure,i.pre),n}
// Get a {top, bottom, left, right} box (in line-local coordinates)
// for a given character.
function Ne(e,t,r,n){return Oe(e,Ae(e,t),r,n)}
// Find a line view that corresponds to the given line number.
function He(e,t){if(t>=e.display.viewFrom&&t<e.display.viewTo)return e.display.view[ot(e,t)];var r=e.display.externalMeasured;return r&&t>=r.lineN&&t<r.lineN+r.size?r:void 0}
// Measurement can be split in two steps, the set-up work that
// applies to the whole line, and the measurement of the actual
// character. Functions like coordsChar, that need to do a lot of
// measurements in a row, can thus ensure that the set-up work is
// only done once.
function Ae(e,t){var r=Ln(t),n=He(e,r);n&&!n.text?n=null:n&&n.changes&&O(e,n,r,H(e)),n||(n=Te(e,t));var i=Me(n,t,r);return{line:t,view:n,rect:null,map:i.map,cache:i.cache,before:i.before,hasHeights:!1}}
// Given a prepared measurement object, measures the position of an
// actual character (or fetches it from the cache).
function Oe(e,t,r,n){t.before&&(r=-1);var i,o=r+(n||"");return t.cache.hasOwnProperty(o)?i=t.cache[o]:(t.rect||(t.rect=t.view.text.getBoundingClientRect()),t.hasHeights||(ke(e,t.view,t.rect),t.hasHeights=!0),i=We(e,t,r,n),i.bogus||(t.cache[o]=i)),{left:i.left,right:i.right,top:i.top,bottom:i.bottom}}function We(e,t,r,n){
// First, search the line map for the text node corresponding to,
// or closest to, the target character.
for(var i,o,l,s,a=t.map,u=0;u<a.length;u+=3){var c=a[u],f=a[u+1];if(c>r?(o=0,l=1,s="left"):f>r?(o=r-c,l=o+1):(u==a.length-3||r==f&&a[u+3]>r)&&(l=f-c,o=l-1,r>=f&&(s="right")),null!=o){if(i=a[u+2],c==f&&n==(i.insertLeft?"left":"right")&&(s=n),"left"==n&&0==o)for(;u&&a[u-2]==a[u-3]&&a[u-1].insertLeft;)i=a[(u-=3)+2],s="left";if("right"==n&&o==f-c)for(;u<a.length-3&&a[u+3]==a[u+4]&&!a[u+5].insertLeft;)i=a[(u+=3)+2],s="right";break}}var h;if(3==i.nodeType){// If it is a text node, use a range to retrieve the coordinates.
for(;o&&ai(t.line.text.charAt(c+o));)--o;for(;f>c+l&&ai(t.line.text.charAt(c+l));)++l;if(Ri&&0==o&&l==f-c)h=i.parentNode.getBoundingClientRect();else if(Vi&&e.options.lineWrapping){var d=ul(i,o,l).getClientRects();h=d.length?d["right"==n?d.length-1:0]:fo}else h=ul(i,o,l).getBoundingClientRect()||fo}else{// If it is a widget, simply get the box for the whole widget.
o>0&&(s=n="right");var d;h=e.options.lineWrapping&&(d=i.getClientRects()).length>1?d["right"==n?d.length-1:0]:i.getBoundingClientRect()}if(Ri&&!o&&(!h||!h.left&&!h.right)){var p=i.parentNode.getClientRects()[0];h=p?{left:p.left,right:p.left+Ye(e.display),top:p.top,bottom:p.bottom}:fo}for(var g,v=(h.bottom+h.top)/2-t.rect.top,m=t.view.measure.heights,u=0;u<m.length-1&&!(v<m[u]);u++);g=u?m[u-1]:0,v=m[u];var y={left:("right"==s?h.right:h.left)-t.rect.left,right:("left"==s?h.left:h.right)-t.rect.left,top:g,bottom:v};return h.left||h.right||(y.bogus=!0),y}function De(e){if(e.measure&&(e.measure.cache={},e.measure.heights=null,e.rest))for(var t=0;t<e.rest.length;t++)e.measure.caches[t]={}}function Ee(e){e.display.externalMeasure=null,ci(e.display.lineMeasure);for(var t=0;t<e.display.view.length;t++)De(e.display.view[t])}function Ie(e){Ee(e),e.display.cachedCharWidth=e.display.cachedTextHeight=e.display.cachedPaddingH=null,e.options.lineWrapping||(e.display.maxLineChanged=!0),e.display.lineNumChars=null}function ze(){return window.pageXOffset||(document.documentElement||document.body).scrollLeft}function Pe(){return window.pageYOffset||(document.documentElement||document.body).scrollTop}
// Converts a {top, bottom, left, right} box from line-local
// coordinates into another coordinate system. Context may be one of
// "line", "div" (display.lineDiv), "local"/null (editor), or "page".
function Fe(e,t,r,n){if(t.widgets)for(var i=0;i<t.widgets.length;++i)if(t.widgets[i].above){var o=Yr(t.widgets[i]);r.top+=o,r.bottom+=o}if("line"==n)return r;n||(n="local");var l=kn(t);if("local"==n?l+=Ce(e.display):l-=e.display.viewOffset,"page"==n||"window"==n){var s=e.display.lineSpace.getBoundingClientRect();l+=s.top+("window"==n?0:Pe());var a=s.left+("window"==n?0:ze());r.left+=a,r.right+=a}return r.top+=l,r.bottom+=l,r}
// Coverts a box from "div" coords to another coordinate system.
// Context may be "window", "page", "div", or "local"/null.
function Re(e,t,r){if("div"==r)return t;var n=t.left,i=t.top;
// First move into "page" coordinate system
if("page"==r)n-=ze(),i-=Pe();else if("local"==r||!r){var o=e.display.sizer.getBoundingClientRect();n+=o.left,i+=o.top}var l=e.display.lineSpace.getBoundingClientRect();return{left:n-l.left,top:i-l.top}}function Be(e,t,r,n,i){return n||(n=bn(e.doc,t.line)),Fe(e,n,Ne(e,n,t.ch,i),r)}
// Returns a box for a given cursor position, which may have an
// 'other' property containing the position of the secondary cursor
// on a bidi boundary.
function Ge(e,t,r,n,i){function o(t,o){var l=Oe(e,i,t,o?"right":"left");return o?l.left=l.right:l.right=l.left,Fe(e,n,l,r)}function l(e,t){var r=s[t],n=r.level%2;return e==ki(r)&&t&&r.level<s[t-1].level?(r=s[--t],e=Mi(r)-(r.level%2?0:1),n=!0):e==Mi(r)&&t<s.length-1&&r.level<s[t+1].level&&(r=s[++t],e=ki(r)-r.level%2,n=!1),n&&e==r.to&&e>r.from?o(e-1):o(e,n)}n=n||bn(e.doc,t.line),i||(i=Ae(e,n));var s=Mn(n),a=t.ch;if(!s)return o(a);var u=Wi(s,a),c=l(a,u);return null!=Cl&&(c.other=l(a,Cl)),c}
// Used to cheaply estimate the coordinates for a position. Used for
// intermediate scroll updates.
function Ve(e,t){var r=0,t=Z(e.doc,t);e.options.lineWrapping||(r=Ye(e.display)*t.ch);var n=bn(e.doc,t.line),i=kn(n)+Ce(e.display);return{left:r,right:r,top:i,bottom:i+n.height}}
// Positions returned by coordsChar contain some extra information.
// xRel is the relative x position of the input coordinates compared
// to the found position (so xRel > 0 means the coordinates are to
// the right of the character position, for example). When outside
// is true, that means the coordinates lie outside the line's
// vertical range.
function Ue(e,t,r,n){var i=lo(e,t);return i.xRel=n,r&&(i.outside=!0),i}
// Compute the character position closest to the given coordinates.
// Input must be lineSpace-local ("div" coordinate system).
function Ke(e,t,r){var n=e.doc;if(r+=e.display.viewOffset,0>r)return Ue(n.first,0,!0,-1);var i=Sn(n,r),o=n.first+n.size-1;if(i>o)return Ue(n.first+n.size-1,bn(n,o).text.length,!0,1);0>t&&(t=0);for(var l=bn(n,i);;){var s=_e(e,l,i,t,r),a=Fr(l),u=a&&a.find(0,!0);if(!a||!(s.ch>u.from.ch||s.ch==u.from.ch&&s.xRel>0))return s;i=Ln(l=u.to.line)}}function _e(e,t,r,n,i){function o(n){var i=Ge(e,lo(r,n),"line",t,u);return s=!0,l>i.bottom?i.left-a:l<i.top?i.left+a:(s=!1,i.left)}var l=i-kn(t),s=!1,a=2*e.display.wrapper.clientWidth,u=Ae(e,t),c=Mn(t),f=t.text.length,h=Ti(t),d=Ni(t),p=o(h),g=s,v=o(d),m=s;if(n>v)return Ue(r,d,m,1);
// Do a binary search between these bounds.
for(;;){if(c?d==h||d==Ei(t,h,1):1>=d-h){for(var y=p>n||v-n>=n-p?h:d,b=n-(y==h?p:v);ai(t.text.charAt(y));)++y;var x=Ue(r,y,y==h?g:m,-1>b?-1:b>1?1:0);return x}var w=Math.ceil(f/2),C=h+w;if(c){C=h;for(var L=0;w>L;++L)C=Ei(t,C,1)}var S=o(C);S>n?(d=C,v=S,(m=s)&&(v+=1e3),f=w):(h=C,p=S,g=s,f-=w)}}
// Compute the default text height.
function Xe(e){if(null!=e.cachedTextHeight)return e.cachedTextHeight;if(null==ao){ao=ui("pre");
// Measure a bunch of lines, for browsers that compute
// fractional heights.
for(var t=0;49>t;++t)ao.appendChild(document.createTextNode("x")),ao.appendChild(ui("br"));ao.appendChild(document.createTextNode("x"))}fi(e.measure,ao);var r=ao.offsetHeight/50;return r>3&&(e.cachedTextHeight=r),ci(e.measure),r||1}
// Compute the default character width.
function Ye(e){if(null!=e.cachedCharWidth)return e.cachedCharWidth;var t=ui("span","xxxxxxxxxx"),r=ui("pre",[t]);fi(e.measure,r);var n=t.getBoundingClientRect(),i=(n.right-n.left)/10;return i>2&&(e.cachedCharWidth=i),i||10}
// Start a new operation.
function je(e){e.curOp={viewChanged:!1,// Flag that indicates that lines might need to be redrawn
startHeight:e.doc.height,// Used to detect need to update scrollbar
forceUpdate:!1,// Used to force a redraw
updateInput:null,// Whether to reset the input textarea
typing:!1,// Whether this reset should be careful to leave existing text (for compositing)
changeObjs:null,// Accumulated changes, for firing change events
cursorActivityHandlers:null,// Set of handlers to fire cursorActivity on
selectionChanged:!1,// Whether the selection needs to be redrawn
updateMaxLine:!1,// Set when the widest line needs to be determined anew
scrollLeft:null,scrollTop:null,// Intermediate scroll position, not pushed to DOM yet
scrollToPos:null,// Used to scroll to a specific position
id:++ho},el++||(Yo=[])}
// Finish an operation, updating the display and signalling delayed events
function $e(e){var t=e.curOp,r=e.doc,n=e.display;
// If it looks like an update might be needed, call updateDisplay
if(e.curOp=null,t.updateMaxLine&&d(e),t.viewChanged||t.forceUpdate||null!=t.scrollTop||t.scrollToPos&&(t.scrollToPos.from.line<n.viewFrom||t.scrollToPos.to.line>=n.viewTo)||n.maxLineChanged&&e.options.lineWrapping){var i=C(e,{top:t.scrollTop,ensure:t.scrollToPos},t.forceUpdate);e.display.scroller.offsetHeight&&(e.doc.scrollTop=e.display.scroller.scrollTop)}
// Propagate the scroll position to the actual DOM scroller
if(
// If no update was run, but the selection changed, redraw that.
!i&&t.selectionChanged&&pe(e),i||t.startHeight==e.doc.height||v(e),
// Abort mouse wheel delta measurement, when scrolling explicitly
null==n.wheelStartX||null==t.scrollTop&&null==t.scrollLeft&&!t.scrollToPos||(n.wheelStartX=n.wheelStartY=null),null!=t.scrollTop&&n.scroller.scrollTop!=t.scrollTop){var o=Math.max(0,Math.min(n.scroller.scrollHeight-n.scroller.clientHeight,t.scrollTop));n.scroller.scrollTop=n.scrollbarV.scrollTop=r.scrollTop=o}if(null!=t.scrollLeft&&n.scroller.scrollLeft!=t.scrollLeft){var l=Math.max(0,Math.min(n.scroller.scrollWidth-n.scroller.clientWidth,t.scrollLeft));n.scroller.scrollLeft=n.scrollbarH.scrollLeft=r.scrollLeft=l,y(e)}
// If we need to scroll a specific position into view, do so.
if(t.scrollToPos){var s=rr(e,Z(e.doc,t.scrollToPos.from),Z(e.doc,t.scrollToPos.to),t.scrollToPos.margin);t.scrollToPos.isCursor&&e.state.focused&&tr(e,s)}t.selectionChanged&&me(e),e.state.focused&&t.updateInput&&ht(e,t.typing);
// Fire events for markers that are hidden/unidden by editing or
// undoing
var a=t.maybeHiddenMarkers,u=t.maybeUnhiddenMarkers;if(a)for(var c=0;c<a.length;++c)a[c].lines.length||Jo(a[c],"hide");if(u)for(var c=0;c<u.length;++c)u[c].lines.length&&Jo(u[c],"unhide");var f;if(--el||(f=Yo,Yo=null),
// Fire change events, and delayed event handlers
t.changeObjs&&Jo(e,"changes",e,t.changeObjs),f)for(var c=0;c<f.length;++c)f[c]();if(t.cursorActivityHandlers)for(var c=0;c<t.cursorActivityHandlers.length;c++)t.cursorActivityHandlers[c](e)}
// Run the given function in an operation
function qe(e,t){if(e.curOp)return t();je(e);try{return t()}finally{$e(e)}}
// Wraps a function in an operation. Returns the wrapped function.
function Ze(e,t){return function(){if(e.curOp)return t.apply(e,arguments);je(e);try{return t.apply(e,arguments)}finally{$e(e)}}}
// Used to add methods to editor and doc instances, wrapping them in
// operations.
function Qe(e){return function(){if(this.curOp)return e.apply(this,arguments);je(this);try{return e.apply(this,arguments)}finally{$e(this)}}}function Je(e){return function(){var t=this.cm;if(!t||t.curOp)return e.apply(this,arguments);je(t);try{return e.apply(this,arguments)}finally{$e(t)}}}
// VIEW TRACKING
// These objects are used to represent the visible (currently drawn)
// part of the document. A LineView may correspond to multiple
// logical lines, if those are connected by collapsed ranges.
function et(e,t,r){
// The starting line
this.line=t,
// Continuing lines, if any
this.rest=Gr(t),
// Number of logical lines in this visual line
this.size=this.rest?Ln(ei(this.rest))-r+1:1,this.node=this.text=null,this.hidden=Kr(e,t)}
// Create a range of LineView objects for the given lines.
function tt(e,t,r){for(var n,i=[],o=t;r>o;o=n){var l=new et(e.doc,bn(e.doc,o),o);n=o+l.size,i.push(l)}return i}
// Updates the display.view data structure for a given change to the
// document. From and to are in pre-change coordinates. Lendiff is
// the amount of lines added or subtracted by the change. This is
// used for changes that span multiple lines, or change the way
// lines are divided into visual lines. regLineChange (below)
// registers single-line changes.
function rt(e,t,r,n){null==t&&(t=e.doc.first),null==r&&(r=e.doc.first+e.doc.size),n||(n=0);var i=e.display;if(n&&r<i.viewTo&&(null==i.updateLineNumbers||i.updateLineNumbers>t)&&(i.updateLineNumbers=t),e.curOp.viewChanged=!0,t>=i.viewTo)// Change after
oo&&Vr(e.doc,t)<i.viewTo&&it(e);else if(r<=i.viewFrom)// Change before
oo&&Ur(e.doc,r+n)>i.viewFrom?it(e):(i.viewFrom+=n,i.viewTo+=n);else if(t<=i.viewFrom&&r>=i.viewTo)// Full overlap
it(e);else if(t<=i.viewFrom){// Top overlap
var o=lt(e,r,r+n,1);o?(i.view=i.view.slice(o.index),i.viewFrom=o.lineN,i.viewTo+=n):it(e)}else if(r>=i.viewTo){// Bottom overlap
var o=lt(e,t,t,-1);o?(i.view=i.view.slice(0,o.index),i.viewTo=o.lineN):it(e)}else{// Gap in the middle
var l=lt(e,t,t,-1),s=lt(e,r,r+n,1);l&&s?(i.view=i.view.slice(0,l.index).concat(tt(e,l.lineN,s.lineN)).concat(i.view.slice(s.index)),i.viewTo+=n):it(e)}var a=i.externalMeasured;a&&(r<a.lineN?a.lineN+=n:t<a.lineN+a.size&&(i.externalMeasured=null))}
// Register a change to a single line. Type must be one of "text",
// "gutter", "class", "widget"
function nt(e,t,r){e.curOp.viewChanged=!0;var n=e.display,i=e.display.externalMeasured;if(i&&t>=i.lineN&&t<i.lineN+i.size&&(n.externalMeasured=null),!(t<n.viewFrom||t>=n.viewTo)){var o=n.view[ot(e,t)];if(null!=o.node){var l=o.changes||(o.changes=[]);-1==ti(l,r)&&l.push(r)}}}
// Clear the view.
function it(e){e.display.viewFrom=e.display.viewTo=e.doc.first,e.display.view=[],e.display.viewOffset=0}
// Find the view element corresponding to a given line. Return null
// when the line isn't visible.
function ot(e,t){if(t>=e.display.viewTo)return null;if(t-=e.display.viewFrom,0>t)return null;for(var r=e.display.view,n=0;n<r.length;n++)if(t-=r[n].size,0>t)return n}function lt(e,t,r,n){var i,o=ot(e,t),l=e.display.view;if(!oo||r==e.doc.first+e.doc.size)return{index:o,lineN:r};for(var s=0,a=e.display.viewFrom;o>s;s++)a+=l[s].size;if(a!=t){if(n>0){if(o==l.length-1)return null;i=a+l[o].size-t,o++}else i=a-t;t+=i,r+=i}for(;Vr(e.doc,r)!=r;){if(o==(0>n?0:l.length-1))return null;r+=n*l[o-(0>n?1:0)].size,o+=n}return{index:o,lineN:r}}
// Force the view to cover a given range, adding empty view element
// or clipping off existing ones as needed.
function st(e,t,r){var n=e.display,i=n.view;0==i.length||t>=n.viewTo||r<=n.viewFrom?(n.view=tt(e,t,r),n.viewFrom=t):(n.viewFrom>t?n.view=tt(e,t,n.viewFrom).concat(n.view):n.viewFrom<t&&(n.view=n.view.slice(ot(e,t))),n.viewFrom=t,n.viewTo<r?n.view=n.view.concat(tt(e,n.viewTo,r)):n.viewTo>r&&(n.view=n.view.slice(0,ot(e,r)))),n.viewTo=r}
// Count the number of lines in the view whose DOM representation is
// out of date (or nonexistent).
function at(e){for(var t=e.display.view,r=0,n=0;n<t.length;n++){var i=t[n];i.hidden||i.node&&!i.changes||++r}return r}
// INPUT HANDLING
// Poll for input changes, using the normal rate of polling. This
// runs as long as the editor is focused.
function ut(e){e.display.pollingFast||e.display.poll.set(e.options.pollInterval,function(){ft(e),e.state.focused&&ut(e)})}
// When an event has just come in that is likely to add or change
// something in the input textarea, we poll faster, to ensure that
// the change appears on the screen quickly.
function ct(e){function t(){var n=ft(e);n||r?(e.display.pollingFast=!1,ut(e)):(r=!0,e.display.poll.set(60,t))}var r=!1;e.display.pollingFast=!0,e.display.poll.set(20,t)}
// Read input from the textarea, and update the document to match.
// When something is selected, it is present in the textarea, and
// selected (unless it is huge, in which case a placeholder is
// used). When nothing is selected, the cursor sits after previously
// seen text (can be empty), which is stored in prevInput (we must
// not reset the textarea when typing, because that breaks IME).
function ft(e){var t=e.display.input,r=e.display.prevInput,n=e.doc;
// Since this is called a *lot*, try to bail out as cheaply as
// possible when it is clear that nothing happened. hasSelection
// will be the case when there is a lot of text in the textarea,
// in which case reading its value would be expensive.
if(!e.state.focused||bl(t)&&!r||gt(e)||e.options.disableInput)return!1;
// See paste handler for more on the fakedLastChar kludge
e.state.pasteIncoming&&e.state.fakedLastChar&&(t.value=t.value.substring(0,t.value.length-1),e.state.fakedLastChar=!1);var i=t.value;
// If nothing changed, bail.
if(i==r&&!e.somethingSelected())return!1;
// Work around nonsensical selection resetting in IE9/10
if(Vi&&!Ri&&e.display.inputHasSelection===i)return ht(e),!1;var o=!e.curOp;o&&je(e),e.display.shift=!1,8203!=i.charCodeAt(0)||n.sel!=e.display.selForContextMenu||r||(r="​");for(
// Find the part of the input that is actually new
var l=0,s=Math.min(r.length,i.length);s>l&&r.charCodeAt(l)==i.charCodeAt(l);)++l;
// Normal behavior is to insert the new text into every selection
for(var a=i.slice(l),u=yl(a),c=e.state.pasteIncoming&&u.length>1&&n.sel.ranges.length==u.length,f=n.sel.ranges.length-1;f>=0;f--){var h=n.sel.ranges[f],d=h.from(),p=h.to();
// Handle deletion
l<r.length?d=lo(d.line,d.ch-(r.length-l)):e.state.overwrite&&h.empty()&&!e.state.pasteIncoming&&(p=lo(p.line,Math.min(bn(n,p.line).text.length,p.ch+ei(u).length)));var g=e.curOp.updateInput,v={from:d,to:p,text:c?[u[f]]:u,origin:e.state.pasteIncoming?"paste":e.state.cutIncoming?"cut":"+input"};
// When an 'electric' character is inserted, immediately trigger a reindent
if(jt(e.doc,v),_n(e,"inputRead",e,v),a&&!e.state.pasteIncoming&&e.options.electricChars&&e.options.smartIndent&&h.head.ch<100&&(!f||n.sel.ranges[f-1].head.line!=h.head.line)){var m=e.getModeAt(h.head);if(m.electricChars){for(var y=0;y<m.electricChars.length;y++)if(a.indexOf(m.electricChars.charAt(y))>-1){ar(e,h.head.line,"smart");break}}else if(m.electricInput){var b=bo(v);m.electricInput.test(bn(n,b.line).text.slice(0,b.ch))&&ar(e,h.head.line,"smart")}}}
// Don't leave long text in the textarea, since it makes further polling slow
return lr(e),e.curOp.updateInput=g,e.curOp.typing=!0,i.length>1e3||i.indexOf("\n")>-1?t.value=e.display.prevInput="":e.display.prevInput=i,o&&$e(e),e.state.pasteIncoming=e.state.cutIncoming=!1,!0}
// Reset the input to correspond to the selection (or to be empty,
// when not typing and nothing is selected)
function ht(e,t){var r,n,i=e.doc;if(e.somethingSelected()){e.display.prevInput="";var o=i.sel.primary();r=xl&&(o.to().line-o.from().line>100||(n=e.getSelection()).length>1e3);var l=r?"-":n||e.getSelection();e.display.input.value=l,e.state.focused&&al(e.display.input),Vi&&!Ri&&(e.display.inputHasSelection=l)}else t||(e.display.prevInput=e.display.input.value="",Vi&&!Ri&&(e.display.inputHasSelection=null));e.display.inaccurateSelection=r}function dt(e){"nocursor"==e.options.readOnly||Qi&&di()==e.display.input||e.display.input.focus()}function pt(e){e.state.focused||(dt(e),Rt(e))}function gt(e){return e.options.readOnly||e.doc.cantEdit}
// EVENT HANDLERS
// Attach the necessary event handlers when initializing the editor
function vt(e){
// Prevent clicks in the scrollbars from killing focus
function t(){e.state.focused&&setTimeout(oi(dt,e),0)}function r(t){Yn(e,t)||qo(t)}function n(t){if(e.somethingSelected())i.inaccurateSelection&&(i.prevInput="",i.inaccurateSelection=!1,i.input.value=e.getSelection(),al(i.input));else{for(var r="",n=[],o=0;o<e.doc.sel.ranges.length;o++){var l=e.doc.sel.ranges[o].head.line,s={anchor:lo(l,0),head:lo(l+1,0)};n.push(s),r+=e.getRange(s.anchor,s.head)}"cut"==t.type?e.setSelections(n,null,nl):(i.prevInput="",i.input.value=r,al(i.input))}"cut"==t.type&&(e.state.cutIncoming=!0)}var i=e.display;Zo(i.scroller,"mousedown",Ze(e,xt)),
// Older IE's will not fire a second mousedown for a double click
Pi?Zo(i.scroller,"dblclick",Ze(e,function(t){if(!Yn(e,t)){var r=bt(e,t);if(r&&!kt(e,t)&&!yt(e.display,t)){jo(t);var n=dr(e,r);re(e.doc,n.anchor,n.head)}}})):Zo(i.scroller,"dblclick",function(t){Yn(e,t)||jo(t)}),
// Prevent normal selection in the editor (we handle our own)
Zo(i.lineSpace,"selectstart",function(e){yt(i,e)||jo(e)}),
// Some browsers fire contextmenu *after* opening the menu, at
// which point we can't mess with it anymore. Context menu is
// handled in onMouseDown for these browsers.
no||Zo(i.scroller,"contextmenu",function(t){Gt(e,t)}),
// Sync scrolling between fake scrollbars and real scrollable
// area, ensure viewport is updated when scrolling.
Zo(i.scroller,"scroll",function(){i.scroller.clientHeight&&(Nt(e,i.scroller.scrollTop),Ht(e,i.scroller.scrollLeft,!0),Jo(e,"scroll",e))}),Zo(i.scrollbarV,"scroll",function(){i.scroller.clientHeight&&Nt(e,i.scrollbarV.scrollTop)}),Zo(i.scrollbarH,"scroll",function(){i.scroller.clientHeight&&Ht(e,i.scrollbarH.scrollLeft)}),
// Listen to wheel events in order to try and update the viewport on time.
Zo(i.scroller,"mousewheel",function(t){At(e,t)}),Zo(i.scroller,"DOMMouseScroll",function(t){At(e,t)}),Zo(i.scrollbarH,"mousedown",t),Zo(i.scrollbarV,"mousedown",t),
// Prevent wrapper from ever scrolling
Zo(i.wrapper,"scroll",function(){i.wrapper.scrollTop=i.wrapper.scrollLeft=0}),Zo(i.input,"keyup",Ze(e,Pt)),Zo(i.input,"input",function(){Vi&&!Ri&&e.display.inputHasSelection&&(e.display.inputHasSelection=null),ct(e)}),Zo(i.input,"keydown",Ze(e,It)),Zo(i.input,"keypress",Ze(e,Ft)),Zo(i.input,"focus",oi(Rt,e)),Zo(i.input,"blur",oi(Bt,e)),e.options.dragDrop&&(Zo(i.scroller,"dragstart",function(t){Tt(e,t)}),Zo(i.scroller,"dragenter",r),Zo(i.scroller,"dragover",r),Zo(i.scroller,"drop",Ze(e,Mt))),Zo(i.scroller,"paste",function(t){yt(i,t)||(e.state.pasteIncoming=!0,dt(e),ct(e))}),Zo(i.input,"paste",function(){
// Workaround for webkit bug https://bugs.webkit.org/show_bug.cgi?id=90206
// Add a char to the end of textarea before paste occur so that
// selection doesn't span to the end of textarea.
if(Ui&&!e.state.fakedLastChar&&!(new Date-e.state.lastMiddleDown<200)){var t=i.input.selectionStart,r=i.input.selectionEnd;i.input.value+="$",i.input.selectionStart=t,i.input.selectionEnd=r,e.state.fakedLastChar=!0}e.state.pasteIncoming=!0,ct(e)}),Zo(i.input,"cut",n),Zo(i.input,"copy",n),
// Needed to handle Tab key in KHTML
ji&&Zo(i.sizer,"mouseup",function(){di()==i.input&&i.input.blur(),dt(e)})}
// Called when the window resizes
function mt(e){
// Might be a text scaling operation, clear size caches.
var t=e.display;t.cachedCharWidth=t.cachedTextHeight=t.cachedPaddingH=null,e.setSize()}
// MOUSE EVENTS
// Return true when the given mouse event happened in a widget
function yt(e,t){for(var r=Un(t);r!=e.wrapper;r=r.parentNode)if(!r||r.ignoreEvents||r.parentNode==e.sizer&&r!=e.mover)return!0}
// Given a mouse event, find the corresponding position. If liberal
// is false, it checks whether a gutter or scrollbar was clicked,
// and returns null if it was. forRect is used by rectangular
// selections, and tries to estimate a character position even for
// coordinates beyond the right of the text.
function bt(e,t,r,n){var i=e.display;if(!r){var o=Un(t);if(o==i.scrollbarH||o==i.scrollbarV||o==i.scrollbarFiller||o==i.gutterFiller)return null}var l,s,a=i.lineSpace.getBoundingClientRect();
// Fails unpredictably on IE[67] when mouse is dragged around quickly.
try{l=t.clientX-a.left,s=t.clientY-a.top}catch(t){return null}var u,c=Ke(e,l,s);if(n&&1==c.xRel&&(u=bn(e.doc,c.line).text).length==c.ch){var f=ll(u,u.length,e.options.tabSize)-u.length;c=lo(c.line,Math.max(0,Math.round((l-Se(e.display).left)/Ye(e.display))-f))}return c}
// A mouse down can be a single click, double click, triple click,
// start of selection drag, start of text drag, new cursor
// (ctrl-click), rectangle drag (alt-drag), or xwin
// middle-click-paste. Or it might be a click on something we should
// not interfere with, such as a scrollbar or widget.
function xt(e){if(!Yn(this,e)){var t=this,r=t.display;if(r.shift=e.shiftKey,yt(r,e))
// Briefly turn off draggability, to allow widgets to do
// normal dragging things.
return void(Ui||(r.scroller.draggable=!1,setTimeout(function(){r.scroller.draggable=!0},100)));if(!kt(t,e)){var n=bt(t,e);switch(window.focus(),Kn(e)){case 1:n?wt(t,e,n):Un(e)==r.scroller&&jo(e);break;case 2:Ui&&(t.state.lastMiddleDown=+new Date),n&&re(t.doc,n),setTimeout(oi(dt,t),20),jo(e);break;case 3:no&&Gt(t,e)}}}}function wt(e,t,r){setTimeout(oi(pt,e),0);var n,i=+new Date;co&&co.time>i-400&&0==so(co.pos,r)?n="triple":uo&&uo.time>i-400&&0==so(uo.pos,r)?(n="double",co={time:i,pos:r}):(n="single",uo={time:i,pos:r});var o=e.doc.sel,l=Ji?t.metaKey:t.ctrlKey;e.options.dragDrop&&ml&&!gt(e)&&"single"==n&&o.contains(r)>-1&&o.somethingSelected()?Ct(e,t,r,l):Lt(e,t,r,n,l)}
// Start a text drag. When it ends, see if any dragging actually
// happen, and treat as a click if it didn't.
function Ct(e,t,r,n){var i=e.display,o=Ze(e,function(l){Ui&&(i.scroller.draggable=!1),e.state.draggingText=!1,Qo(document,"mouseup",o),Qo(i.scroller,"drop",o),Math.abs(t.clientX-l.clientX)+Math.abs(t.clientY-l.clientY)<10&&(jo(l),n||re(e.doc,r),dt(e),
// Work around unexplainable focus problem in IE9 (#2127)
Pi&&!Ri&&setTimeout(function(){document.body.focus(),dt(e)},20))});
// Let the drag handler handle this.
Ui&&(i.scroller.draggable=!0),e.state.draggingText=o,
// IE's approach to draggable
i.scroller.dragDrop&&i.scroller.dragDrop(),Zo(document,"mouseup",o),Zo(i.scroller,"drop",o)}
// Normal selection, as opposed to text dragging.
function Lt(e,t,r,n,i){function o(t){if(0!=so(g,t))if(g=t,"rect"==n){for(var i=[],o=e.options.tabSize,l=ll(bn(u,r.line).text,r.ch,o),s=ll(bn(u,t.line).text,t.ch,o),a=Math.min(l,s),d=Math.max(l,s),p=Math.min(r.line,t.line),v=Math.min(e.lastLine(),Math.max(r.line,t.line));v>=p;p++){var m=bn(u,p).text,y=Qn(m,a,o);a==d?i.push(new Y(lo(p,y),lo(p,y))):m.length>y&&i.push(new Y(lo(p,y),lo(p,Qn(m,d,o))))}i.length||i.push(new Y(r,r)),ae(u,j(h.ranges.slice(0,f).concat(i),f),{origin:"*mouse",scroll:!1}),e.scrollIntoView(t)}else{var b=c,x=b.anchor,w=t;if("single"!=n){if("double"==n)var C=dr(e,t);else var C=new Y(lo(t.line,0),Z(u,lo(t.line+1,0)));so(C.anchor,x)>0?(w=C.head,x=_(b.from(),C.anchor)):(w=C.anchor,x=K(b.to(),C.head))}var i=h.ranges.slice(0);i[f]=new Y(Z(u,x),w),ae(u,j(i,f),il)}}function l(t){var r=++y,i=bt(e,t,!0,"rect"==n);if(i)if(0!=so(i,g)){pt(e),o(i);var s=m(a,u);(i.line>=s.to||i.line<s.from)&&setTimeout(Ze(e,function(){y==r&&l(t)}),150)}else{var c=t.clientY<v.top?-20:t.clientY>v.bottom?20:0;c&&setTimeout(Ze(e,function(){y==r&&(a.scroller.scrollTop+=c,l(t))}),50)}}function s(t){y=1/0,jo(t),dt(e),Qo(document,"mousemove",b),Qo(document,"mouseup",x),u.history.lastSelOrigin=null}var a=e.display,u=e.doc;jo(t);var c,f,h=u.sel;if(i&&!t.shiftKey?(f=u.sel.contains(r),c=f>-1?u.sel.ranges[f]:new Y(r,r)):c=u.sel.primary(),t.altKey)n="rect",i||(c=new Y(r,r)),r=bt(e,t,!0,!0),f=-1;else if("double"==n){var d=dr(e,r);c=e.display.shift||u.extend?te(u,c,d.anchor,d.head):d}else if("triple"==n){var p=new Y(lo(r.line,0),Z(u,lo(r.line+1,0)));c=e.display.shift||u.extend?te(u,c,p.anchor,p.head):p}else c=te(u,c,r);i?f>-1?ie(u,f,c,il):(f=u.sel.ranges.length,ae(u,j(u.sel.ranges.concat([c]),f),{scroll:!1,origin:"*mouse"})):(f=0,ae(u,new X([c],0),il),h=u.sel);var g=r,v=a.wrapper.getBoundingClientRect(),y=0,b=Ze(e,function(e){(Vi&&!Bi?e.buttons:Kn(e))?l(e):s(e)}),x=Ze(e,s);Zo(document,"mousemove",b),Zo(document,"mouseup",x)}
// Determines whether an event happened in the gutter, and fires the
// handlers for the corresponding event.
function St(e,t,r,n,i){try{var o=t.clientX,l=t.clientY}catch(t){return!1}if(o>=Math.floor(e.display.gutters.getBoundingClientRect().right))return!1;n&&jo(t);var s=e.display,a=s.lineDiv.getBoundingClientRect();if(l>a.bottom||!$n(e,r))return Vn(t);l-=a.top-s.viewOffset;for(var u=0;u<e.options.gutters.length;++u){var c=s.gutters.childNodes[u];if(c&&c.getBoundingClientRect().right>=o){var f=Sn(e.doc,l),h=e.options.gutters[u];return i(e,r,e,f,h,t),Vn(t)}}}function kt(e,t){return St(e,t,"gutterClick",!0,_n)}function Mt(e){var t=this;if(!Yn(t,e)&&!yt(t.display,e)){jo(e),Vi&&(po=+new Date);var r=bt(t,e,!0),n=e.dataTransfer.files;if(r&&!gt(t))
// Might be a file drop, in which case we simply extract the text
// and insert it.
if(n&&n.length&&window.FileReader&&window.File)for(var i=n.length,o=Array(i),l=0,s=function(e,n){var s=new FileReader;s.onload=Ze(t,function(){if(o[n]=s.result,++l==i){r=Z(t.doc,r);var e={from:r,to:r,text:yl(o.join("\n")),origin:"paste"};jt(t.doc,e),se(t.doc,$(r,bo(e)))}}),s.readAsText(e)},a=0;i>a;++a)s(n[a],a);else{// Normal drop
// Don't do a replace if the drop happened inside of the selected text.
if(t.state.draggingText&&t.doc.sel.contains(r)>-1)
// Ensure the editor is re-focused
return t.state.draggingText(e),void setTimeout(oi(dt,t),20);try{var o=e.dataTransfer.getData("Text");if(o){if(t.state.draggingText&&!(Ji?e.metaKey:e.ctrlKey))var u=t.listSelections();if(ue(t.doc,$(r,r)),u)for(var a=0;a<u.length;++a)er(t.doc,"",u[a].anchor,u[a].head,"drag");t.replaceSelection(o,"around","paste"),dt(t)}}catch(e){}}}}function Tt(e,t){if(Vi&&(!e.state.draggingText||+new Date-po<100))return void qo(t);if(!Yn(e,t)&&!yt(e.display,t)&&(t.dataTransfer.setData("Text",e.getSelection()),t.dataTransfer.setDragImage&&!Yi)){var r=ui("img",null,null,"position: fixed; left: 0; top: 0;");r.src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==",Xi&&(r.width=r.height=1,e.display.wrapper.appendChild(r),
// Force a relayout, or Opera won't use our image for some obscure reason
r._top=r.offsetTop),t.dataTransfer.setDragImage(r,0,0),Xi&&r.parentNode.removeChild(r)}}
// SCROLL EVENTS
// Sync the scrollable area and scrollbars, ensure the viewport
// covers the visible area.
function Nt(e,t){Math.abs(e.doc.scrollTop-t)<2||(e.doc.scrollTop=t,zi||C(e,{top:t}),e.display.scroller.scrollTop!=t&&(e.display.scroller.scrollTop=t),e.display.scrollbarV.scrollTop!=t&&(e.display.scrollbarV.scrollTop=t),zi&&C(e),ye(e,100))}
// Sync scroller and scrollbar, ensure the gutter elements are
// aligned.
function Ht(e,t,r){(r?t==e.doc.scrollLeft:Math.abs(e.doc.scrollLeft-t)<2)||(t=Math.min(t,e.display.scroller.scrollWidth-e.display.scroller.clientWidth),e.doc.scrollLeft=t,y(e),e.display.scroller.scrollLeft!=t&&(e.display.scroller.scrollLeft=t),e.display.scrollbarH.scrollLeft!=t&&(e.display.scrollbarH.scrollLeft=t))}function At(e,t){var r=t.wheelDeltaX,n=t.wheelDeltaY;null==r&&t.detail&&t.axis==t.HORIZONTAL_AXIS&&(r=t.detail),null==n&&t.detail&&t.axis==t.VERTICAL_AXIS?n=t.detail:null==n&&(n=t.wheelDelta);var i=e.display,o=i.scroller;
// Quit if there's nothing to scroll here
if(r&&o.scrollWidth>o.clientWidth||n&&o.scrollHeight>o.clientHeight){
// Webkit browsers on OS X abort momentum scrolls when the target
// of the scroll event is removed from the scrollable element.
// This hack (see related code in patchDisplay) makes sure the
// element is kept around.
if(n&&Ji&&Ui)e:for(var l=t.target,s=i.view;l!=o;l=l.parentNode)for(var a=0;a<s.length;a++)if(s[a].node==l){e.display.currentWheelTarget=l;break e}
// On some browsers, horizontal scrolling will cause redraws to
// happen before the gutter has been realigned, causing it to
// wriggle around in a most unseemly way. When we have an
// estimated pixels/delta value, we just handle horizontal
// scrolling entirely here. It'll be slightly off from native, but
// better than glitching out.
if(r&&!zi&&!Xi&&null!=vo)// Abort measurement, if in progress
return n&&Nt(e,Math.max(0,Math.min(o.scrollTop+n*vo,o.scrollHeight-o.clientHeight))),Ht(e,Math.max(0,Math.min(o.scrollLeft+r*vo,o.scrollWidth-o.clientWidth))),jo(t),void(i.wheelStartX=null);
// 'Project' the visible viewport to cover the area that is being
// scrolled into view (if we know enough to estimate it).
if(n&&null!=vo){var u=n*vo,c=e.doc.scrollTop,f=c+i.wrapper.clientHeight;0>u?c=Math.max(0,c+u-50):f=Math.min(e.doc.height,f+u+50),C(e,{top:c,bottom:f})}20>go&&(null==i.wheelStartX?(i.wheelStartX=o.scrollLeft,i.wheelStartY=o.scrollTop,i.wheelDX=r,i.wheelDY=n,setTimeout(function(){if(null!=i.wheelStartX){var e=o.scrollLeft-i.wheelStartX,t=o.scrollTop-i.wheelStartY,r=t&&i.wheelDY&&t/i.wheelDY||e&&i.wheelDX&&e/i.wheelDX;i.wheelStartX=i.wheelStartY=null,r&&(vo=(vo*go+r)/(go+1),++go)}},200)):(i.wheelDX+=r,i.wheelDY+=n))}}
// KEY EVENTS
// Run a handler that was bound to a key.
function Ot(e,t,r){if("string"==typeof t&&(t=Ao[t],!t))return!1;
// Ensure previous input has been read, so that the handler sees a
// consistent view of the document
e.display.pollingFast&&ft(e)&&(e.display.pollingFast=!1);var n=e.display.shift,i=!1;try{gt(e)&&(e.state.suppressEdits=!0),r&&(e.display.shift=!1),i=t(e)!=rl}finally{e.display.shift=n,e.state.suppressEdits=!1}return i}
// Collect the currently active keymaps.
function Wt(e){var t=e.state.keyMaps.slice(0);return e.options.extraKeys&&t.push(e.options.extraKeys),t.push(e.options.keyMap),t}
// Handle a key from the keydown event.
function Dt(e,t){
// Handle automatic keymap transitions
var r=gr(e.options.keyMap),n=r.auto;clearTimeout(mo),n&&!Do(t)&&(mo=setTimeout(function(){gr(e.options.keyMap)==r&&(e.options.keyMap=n.call?n.call(null,e):n,s(e))},50));var i=Eo(t,!0),o=!1;if(!i)return!1;var l=Wt(e);
// First try to resolve full name (including 'Shift-'). Failing
// that, see if there is a cursor-motion command (starting with
// 'go') bound to the keyname without 'Shift-'.
return o=t.shiftKey?Wo("Shift-"+i,l,function(t){return Ot(e,t,!0)})||Wo(i,l,function(t){return("string"==typeof t?/^go[A-Z]/.test(t):t.motion)?Ot(e,t):void 0}):Wo(i,l,function(t){return Ot(e,t)}),o&&(jo(t),me(e),_n(e,"keyHandled",e,i,t)),o}
// Handle a key from the keypress event
function Et(e,t,r){var n=Wo("'"+r+"'",Wt(e),function(t){return Ot(e,t,!0)});return n&&(jo(t),me(e),_n(e,"keyHandled",e,"'"+r+"'",t)),n}function It(e){var t=this;if(pt(t),!Yn(t,e)){
// IE does strange things with escape.
Pi&&27==e.keyCode&&(e.returnValue=!1);var r=e.keyCode;t.display.shift=16==r||e.shiftKey;var n=Dt(t,e);Xi&&(yo=n?r:null,!n&&88==r&&!xl&&(Ji?e.metaKey:e.ctrlKey)&&t.replaceSelection("",null,"cut")),
// Turn mouse into crosshair when Alt is held on Mac.
18!=r||/\bCodeMirror-crosshair\b/.test(t.display.lineDiv.className)||zt(t)}}function zt(e){function t(e){18!=e.keyCode&&e.altKey||(gi(r,"CodeMirror-crosshair"),Qo(document,"keyup",t),Qo(document,"mouseover",t))}var r=e.display.lineDiv;vi(r,"CodeMirror-crosshair"),Zo(document,"keyup",t),Zo(document,"mouseover",t)}function Pt(e){Yn(this,e)||16==e.keyCode&&(this.doc.sel.shift=!1)}function Ft(e){var t=this;if(!Yn(t,e)){var r=e.keyCode,n=e.charCode;if(Xi&&r==yo)return yo=null,void jo(e);if(!(Xi&&(!e.which||e.which<10)||ji)||!Dt(t,e)){var i=String.fromCharCode(null==n?r:n);Et(t,e,i)||(Vi&&!Ri&&(t.display.inputHasSelection=null),ct(t))}}}
// FOCUS/BLUR EVENTS
function Rt(e){"nocursor"!=e.options.readOnly&&(e.state.focused||(Jo(e,"focus",e),e.state.focused=!0,vi(e.display.wrapper,"CodeMirror-focused"),
// The prevInput test prevents this from firing when a context
// menu is closed (since the resetInput would kill the
// select-all detection hack)
e.curOp||e.display.selForContextMenu==e.doc.sel||(ht(e),Ui&&setTimeout(oi(ht,e,!0),0))),ut(e),me(e))}function Bt(e){e.state.focused&&(Jo(e,"blur",e),e.state.focused=!1,gi(e.display.wrapper,"CodeMirror-focused")),clearInterval(e.display.blinker),setTimeout(function(){e.state.focused||(e.display.shift=!1)},150)}
// CONTEXT MENU HANDLING
// To make the context menu work, we need to briefly unhide the
// textarea (making it as unobtrusive as possible) to let the
// right-click take effect on it.
function Gt(e,t){
// Select-all will be greyed out if there's nothing to select, so
// this adds a zero-width space so that we can later check whether
// it got selected.
function r(){if(null!=i.input.selectionStart){var t=e.somethingSelected(),r=i.input.value="​"+(t?i.input.value:"");i.prevInput=t?"":"​",i.input.selectionStart=1,i.input.selectionEnd=r.length,
// Re-set this, in case some other handler touched the
// selection in the meantime.
i.selForContextMenu=e.doc.sel}}function n(){
// Try to detect the user choosing select-all
if(i.inputDiv.style.position="relative",i.input.style.cssText=a,Ri&&(i.scrollbarV.scrollTop=i.scroller.scrollTop=l),ut(e),null!=i.input.selectionStart){(!Vi||Ri)&&r();var t=0,n=function(){i.selForContextMenu==e.doc.sel&&0==i.input.selectionStart?Ze(e,Ao.selectAll)(e):t++<10?i.detectingSelectAll=setTimeout(n,500):ht(e)};i.detectingSelectAll=setTimeout(n,200)}}if(!Yn(e,t,"contextmenu")){var i=e.display;if(!yt(i,t)&&!Vt(e,t)){var o=bt(e,t),l=i.scroller.scrollTop;if(o&&!Xi){// Opera is difficult.
// Reset the current text selection only if the click is done outside of the selection
// and 'resetSelectionOnContextMenu' option is true.
var s=e.options.resetSelectionOnContextMenu;s&&-1==e.doc.sel.contains(o)&&Ze(e,ae)(e.doc,$(o),nl);var a=i.input.style.cssText;if(i.inputDiv.style.position="absolute",i.input.style.cssText="position: fixed; width: 30px; height: 30px; top: "+(t.clientY-5)+"px; left: "+(t.clientX-5)+"px; z-index: 1000; background: "+(Vi?"rgba(255, 255, 255, .05)":"transparent")+"; outline: none; border-width: 0; outline: none; overflow: hidden; opacity: .05; filter: alpha(opacity=5);",dt(e),ht(e),
// Adds "Select all" to context menu in FF
e.somethingSelected()||(i.input.value=i.prevInput=" "),i.selForContextMenu=e.doc.sel,clearTimeout(i.detectingSelectAll),Vi&&!Ri&&r(),no){qo(t);var u=function(){Qo(window,"mouseup",u),setTimeout(n,20)};Zo(window,"mouseup",u)}else setTimeout(n,50)}}}}function Vt(e,t){return $n(e,"gutterContextMenu")?St(e,t,"gutterContextMenu",!1,Jo):!1}
// Adjust a position to refer to the post-change position of the
// same text, or the end of the change if the change covers it.
function Ut(e,t){if(so(e,t.from)<0)return e;if(so(e,t.to)<=0)return bo(t);var r=e.line+t.text.length-(t.to.line-t.from.line)-1,n=e.ch;return e.line==t.to.line&&(n+=bo(t).ch-t.to.ch),lo(r,n)}function Kt(e,t){for(var r=[],n=0;n<e.sel.ranges.length;n++){var i=e.sel.ranges[n];r.push(new Y(Ut(i.anchor,t),Ut(i.head,t)))}return j(r,e.sel.primIndex)}function _t(e,t,r){return e.line==t.line?lo(r.line,e.ch-t.ch+r.ch):lo(r.line+(e.line-t.line),e.ch)}
// Used by replaceSelections to allow moving the selection to the
// start or around the replaced test. Hint may be "start" or "around".
function Xt(e,t,r){for(var n=[],i=lo(e.first,0),o=i,l=0;l<t.length;l++){var s=t[l],a=_t(s.from,i,o),u=_t(bo(s),i,o);if(i=s.to,o=u,"around"==r){var c=e.sel.ranges[l],f=so(c.head,c.anchor)<0;n[l]=new Y(f?u:a,f?a:u)}else n[l]=new Y(a,a)}return new X(n,e.sel.primIndex)}
// Allow "beforeChange" event handlers to influence a change
function Yt(e,t,r){var n={canceled:!1,from:t.from,to:t.to,text:t.text,origin:t.origin,cancel:function(){this.canceled=!0}};return r&&(n.update=function(t,r,n,i){t&&(this.from=Z(e,t)),r&&(this.to=Z(e,r)),n&&(this.text=n),void 0!==i&&(this.origin=i)}),Jo(e,"beforeChange",e,n),e.cm&&Jo(e.cm,"beforeChange",e.cm,n),n.canceled?null:{from:n.from,to:n.to,text:n.text,origin:n.origin}}
// Apply a change to a document, and add it to the document's
// history, and propagating it to all linked documents.
function jt(e,t,r){if(e.cm){if(!e.cm.curOp)return Ze(e.cm,jt)(e,t,r);if(e.cm.state.suppressEdits)return}if(!($n(e,"beforeChange")||e.cm&&$n(e.cm,"beforeChange"))||(t=Yt(e,t,!0))){
// Possibly split or suppress the update based on the presence
// of read-only spans in its range.
var n=io&&!r&&Ar(e,t.from,t.to);if(n)for(var i=n.length-1;i>=0;--i)$t(e,{from:n[i].from,to:n[i].to,text:i?[""]:t.text});else $t(e,t)}}function $t(e,t){if(1!=t.text.length||""!=t.text[0]||0!=so(t.from,t.to)){var r=Kt(e,t);On(e,t,r,e.cm?e.cm.curOp.id:NaN),Qt(e,t,r,Tr(e,t));var n=[];mn(e,function(e,r){r||-1!=ti(n,e.history)||(Gn(e.history,t),n.push(e.history)),Qt(e,t,null,Tr(e,t))})}}
// Revert a change stored in a document's history.
function qt(e,t,r){if(!e.cm||!e.cm.state.suppressEdits){
// Verify that there is a useable event (so that ctrl-z won't
// needlessly clear selection events)
for(var n,i=e.history,o=e.sel,l="undo"==t?i.done:i.undone,s="undo"==t?i.undone:i.done,a=0;a<l.length&&(n=l[a],r?!n.ranges||n.equals(e.sel):n.ranges);a++);if(a!=l.length){for(i.lastOrigin=i.lastSelOrigin=null;n=l.pop(),n.ranges;){if(En(n,s),r&&!n.equals(e.sel))return void ae(e,n,{clearRedo:!1});o=n}
// Build up a reverse change object to add to the opposite history
// stack (redo when undoing, and vice versa).
var u=[];En(o,s),s.push({changes:u,generation:i.generation}),i.generation=n.generation||++i.maxGeneration;for(var c=$n(e,"beforeChange")||e.cm&&$n(e.cm,"beforeChange"),a=n.changes.length-1;a>=0;--a){var f=n.changes[a];if(f.origin=t,c&&!Yt(e,f,!1))return void(l.length=0);u.push(Nn(e,f));var h=a?Kt(e,f,null):ei(l);Qt(e,f,h,Hr(e,f)),!a&&e.cm&&e.cm.scrollIntoView(f);var d=[];
// Propagate to the linked documents
mn(e,function(e,t){t||-1!=ti(d,e.history)||(Gn(e.history,f),d.push(e.history)),Qt(e,f,null,Hr(e,f))})}}}}
// Sub-views need their line numbers shifted when text is added
// above or below them in the parent document.
function Zt(e,t){if(0!=t&&(e.first+=t,e.sel=new X(ri(e.sel.ranges,function(e){return new Y(lo(e.anchor.line+t,e.anchor.ch),lo(e.head.line+t,e.head.ch))}),e.sel.primIndex),e.cm)){rt(e.cm,e.first,e.first-t,t);for(var r=e.cm.display,n=r.viewFrom;n<r.viewTo;n++)nt(e.cm,n,"gutter")}}
// More lower-level change function, handling only a single document
// (not linked ones).
function Qt(e,t,r,n){if(e.cm&&!e.cm.curOp)return Ze(e.cm,Qt)(e,t,r,n);if(t.to.line<e.first)return void Zt(e,t.text.length-1-(t.to.line-t.from.line));if(!(t.from.line>e.lastLine())){
// Clip the change to the size of this doc
if(t.from.line<e.first){var i=t.text.length-1-(e.first-t.from.line);Zt(e,i),t={from:lo(e.first,0),to:lo(t.to.line+i,t.to.ch),text:[ei(t.text)],origin:t.origin}}var o=e.lastLine();t.to.line>o&&(t={from:t.from,to:lo(o,bn(e,o).text.length),text:[t.text[0]],origin:t.origin}),t.removed=xn(e,t.from,t.to),r||(r=Kt(e,t,null)),e.cm?Jt(e.cm,t,n):pn(e,t,n),ue(e,r,nl)}}
// Handle the interaction of a change to a document with the editor
// that this document is part of.
function Jt(e,t,r){var n=e.doc,i=e.display,l=t.from,s=t.to,a=!1,u=l.line;e.options.lineWrapping||(u=Ln(Br(bn(n,l.line))),n.iter(u,s.line+1,function(e){return e==i.maxLine?(a=!0,!0):void 0})),n.sel.contains(t.from,t.to)>-1&&jn(e),pn(n,t,r,o(e)),e.options.lineWrapping||(n.iter(u,l.line+t.text.length,function(e){var t=h(e);t>i.maxLineLength&&(i.maxLine=e,i.maxLineLength=t,i.maxLineChanged=!0,a=!1)}),a&&(e.curOp.updateMaxLine=!0)),
// Adjust frontier, schedule worker
n.frontier=Math.min(n.frontier,l.line),ye(e,400);var c=t.text.length-(s.line-l.line)-1;
// Remember that these lines changed, for updating the display
l.line!=s.line||1!=t.text.length||dn(e.doc,t)?rt(e,l.line,s.line+1,c):nt(e,l.line,"text");var f=$n(e,"changes"),d=$n(e,"change");if(d||f){var p={from:l,to:s,text:t.text,removed:t.removed,origin:t.origin};d&&_n(e,"change",e,p),f&&(e.curOp.changeObjs||(e.curOp.changeObjs=[])).push(p)}e.display.selForContextMenu=null}function er(e,t,r,n,i){if(n||(n=r),so(n,r)<0){var o=n;n=r,r=o}"string"==typeof t&&(t=yl(t)),jt(e,{from:r,to:n,text:t,origin:i})}
// SCROLLING THINGS INTO VIEW
// If an editor sits on the top or bottom of the window, partially
// scrolled out of view, this ensures that the cursor is visible.
function tr(e,t){var r=e.display,n=r.sizer.getBoundingClientRect(),i=null;if(t.top+n.top<0?i=!0:t.bottom+n.top>(window.innerHeight||document.documentElement.clientHeight)&&(i=!1),null!=i&&!qi){var o=ui("div","​",null,"position: absolute; top: "+(t.top-r.viewOffset-Ce(e.display))+"px; height: "+(t.bottom-t.top+tl)+"px; left: "+t.left+"px; width: 2px;");e.display.lineSpace.appendChild(o),o.scrollIntoView(i),e.display.lineSpace.removeChild(o)}}
// Scroll a given position into view (immediately), verifying that
// it actually became visible (as line heights are accurately
// measured, the position of something may 'drift' during drawing).
function rr(e,t,r,n){for(null==n&&(n=0);;){var i=!1,o=Ge(e,t),l=r&&r!=t?Ge(e,r):o,s=ir(e,Math.min(o.left,l.left),Math.min(o.top,l.top)-n,Math.max(o.left,l.left),Math.max(o.bottom,l.bottom)+n),a=e.doc.scrollTop,u=e.doc.scrollLeft;if(null!=s.scrollTop&&(Nt(e,s.scrollTop),Math.abs(e.doc.scrollTop-a)>1&&(i=!0)),null!=s.scrollLeft&&(Ht(e,s.scrollLeft),Math.abs(e.doc.scrollLeft-u)>1&&(i=!0)),!i)return o}}
// Scroll a given set of coordinates into view (immediately).
function nr(e,t,r,n,i){var o=ir(e,t,r,n,i);null!=o.scrollTop&&Nt(e,o.scrollTop),null!=o.scrollLeft&&Ht(e,o.scrollLeft)}
// Calculate a new scroll position needed to scroll the given
// rectangle into view. Returns an object with scrollTop and
// scrollLeft properties. When these are undefined, the
// vertical/horizontal position does not need to be adjusted.
function ir(e,t,r,n,i){var o=e.display,l=Xe(e.display);0>r&&(r=0);var s=e.curOp&&null!=e.curOp.scrollTop?e.curOp.scrollTop:o.scroller.scrollTop,a=o.scroller.clientHeight-tl,u={},c=e.doc.height+Le(o),f=l>r,h=i>c-l;if(s>r)u.scrollTop=f?0:r;else if(i>s+a){var d=Math.min(r,(h?c:i)-a);d!=s&&(u.scrollTop=d)}var p=e.curOp&&null!=e.curOp.scrollLeft?e.curOp.scrollLeft:o.scroller.scrollLeft,g=o.scroller.clientWidth-tl;t+=o.gutters.offsetWidth,n+=o.gutters.offsetWidth;var v=o.gutters.offsetWidth,m=v+10>t;return p+v>t||m?(m&&(t=0),u.scrollLeft=Math.max(0,t-10-v)):n>g+p-3&&(u.scrollLeft=n+10-g),u}
// Store a relative adjustment to the scroll position in the current
// operation (to be applied when the operation finishes).
function or(e,t,r){(null!=t||null!=r)&&sr(e),null!=t&&(e.curOp.scrollLeft=(null==e.curOp.scrollLeft?e.doc.scrollLeft:e.curOp.scrollLeft)+t),null!=r&&(e.curOp.scrollTop=(null==e.curOp.scrollTop?e.doc.scrollTop:e.curOp.scrollTop)+r)}
// Make sure that at the end of the operation the current cursor is
// shown.
function lr(e){sr(e);var t=e.getCursor(),r=t,n=t;e.options.lineWrapping||(r=t.ch?lo(t.line,t.ch-1):t,n=lo(t.line,t.ch+1)),e.curOp.scrollToPos={from:r,to:n,margin:e.options.cursorScrollMargin,isCursor:!0}}
// When an operation has its scrollToPos property set, and another
// scroll action is applied before the end of the operation, this
// 'simulates' scrolling that position into view in a cheap way, so
// that the effect of intermediate scroll commands is not ignored.
function sr(e){var t=e.curOp.scrollToPos;if(t){e.curOp.scrollToPos=null;var r=Ve(e,t.from),n=Ve(e,t.to),i=ir(e,Math.min(r.left,n.left),Math.min(r.top,n.top)-t.margin,Math.max(r.right,n.right),Math.max(r.bottom,n.bottom)+t.margin);e.scrollTo(i.scrollLeft,i.scrollTop)}}
// API UTILITIES
// Indent the given line. The how parameter can be "smart",
// "add"/null, "subtract", or "prev". When aggressive is false
// (typically set to true for forced single-line indents), empty
// lines are not indented, and places where the mode returns Pass
// are left alone.
function ar(e,t,r,n){var i,o=e.doc;null==r&&(r="add"),"smart"==r&&(
// Fall back to "prev" when the mode doesn't have an indentation
// method.
e.doc.mode.indent?i=we(e,t):r="prev");var l=e.options.tabSize,s=bn(o,t),a=ll(s.text,null,l);s.stateAfter&&(s.stateAfter=null);var u,c=s.text.match(/^\s*/)[0];if(n||/\S/.test(s.text)){if("smart"==r&&(u=e.doc.mode.indent(i,s.text.slice(c.length),s.text),u==rl)){if(!n)return;r="prev"}}else u=0,r="not";"prev"==r?u=t>o.first?ll(bn(o,t-1).text,null,l):0:"add"==r?u=a+e.options.indentUnit:"subtract"==r?u=a-e.options.indentUnit:"number"==typeof r&&(u=a+r),u=Math.max(0,u);var f="",h=0;if(e.options.indentWithTabs)for(var d=Math.floor(u/l);d;--d)h+=l,f+="	";if(u>h&&(f+=Jn(u-h)),f!=c)er(e.doc,f,lo(t,0),lo(t,c.length),"+input");else
// Ensure that, if the cursor was in the whitespace at the start
// of the line, it is moved to the end of that space.
for(var d=0;d<o.sel.ranges.length;d++){var p=o.sel.ranges[d];if(p.head.line==t&&p.head.ch<c.length){var h=lo(t,c.length);ie(o,d,new Y(h,h));break}}s.stateAfter=null}
// Utility for applying a change to a line by handle or number,
// returning the number and optionally registering the line as
// changed.
function ur(e,t,r,n){var i=t,o=t,l=e.doc;return"number"==typeof t?o=bn(l,q(l,t)):i=Ln(t),null==i?null:(n(o,i)&&nt(e,i,r),o)}
// Helper for deleting text near the selection(s), used to implement
// backspace, delete, and similar functionality.
function cr(e,t){
// Build up a set of ranges to kill first, merging overlapping
// ranges.
for(var r=e.doc.sel.ranges,n=[],i=0;i<r.length;i++){for(var o=t(r[i]);n.length&&so(o.from,ei(n).to)<=0;){var l=n.pop();if(so(l.from,o.from)<0){o.from=l.from;break}}n.push(o)}
// Next, remove those actual ranges.
qe(e,function(){for(var t=n.length-1;t>=0;t--)er(e.doc,"",n[t].from,n[t].to,"+delete");lr(e)})}
// Used for horizontal relative motion. Dir is -1 or 1 (left or
// right), unit can be "char", "column" (like char, but doesn't
// cross line boundaries), "word" (across next word), or "group" (to
// the start of next group of word or non-word-non-whitespace
// chars). The visually param controls whether, in right-to-left
// text, direction 1 means to move towards the next index in the
// string, or towards the character to the right of the current
// position. The resulting position will have a hitSide=true
// property if it reached the end of the document.
function fr(e,t,r,n,i){function o(){var t=s+r;return t<e.first||t>=e.first+e.size?f=!1:(s=t,c=bn(e,t))}function l(e){var t=(i?Ei:Ii)(c,a,r,!0);if(null==t){if(e||!o())return f=!1;a=i?(0>r?Ni:Ti)(c):0>r?c.text.length:0}else a=t;return!0}var s=t.line,a=t.ch,u=r,c=bn(e,s),f=!0;if("char"==n)l();else if("column"==n)l(!0);else if("word"==n||"group"==n)for(var h=null,d="group"==n,p=e.cm&&e.cm.getHelper(t,"wordChars"),g=!0;!(0>r)||l(!g);g=!1){var v=c.text.charAt(a)||"\n",m=li(v,p)?"w":d&&"\n"==v?"n":!d||/\s/.test(v)?null:"p";if(!d||g||m||(m="s"),h&&h!=m){0>r&&(r=1,l());break}if(m&&(h=m),r>0&&!l(!g))break}var y=de(e,lo(s,a),u,!0);return f||(y.hitSide=!0),y}
// For relative vertical movement. Dir may be -1 or 1. Unit can be
// "page" or "line". The resulting position will have a hitSide=true
// property if it reached the end of the document.
function hr(e,t,r,n){var i,o=e.doc,l=t.left;if("page"==n){var s=Math.min(e.display.wrapper.clientHeight,window.innerHeight||document.documentElement.clientHeight);i=t.top+r*(s-(0>r?1.5:.5)*Xe(e.display))}else"line"==n&&(i=r>0?t.bottom+3:t.top-3);for(;;){var a=Ke(e,l,i);if(!a.outside)break;if(0>r?0>=i:i>=o.height){a.hitSide=!0;break}i+=5*r}return a}
// Find the word at the given position (as returned by coordsChar).
function dr(e,t){var r=e.doc,n=bn(r,t.line).text,i=t.ch,o=t.ch;if(n){var l=e.getHelper(t,"wordChars");(t.xRel<0||o==n.length)&&i?--i:++o;for(var s=n.charAt(i),a=li(s,l)?function(e){return li(e,l)}:/\s/.test(s)?function(e){return/\s/.test(e)}:function(e){return!/\s/.test(e)&&!li(e)};i>0&&a(n.charAt(i-1));)--i;for(;o<n.length&&a(n.charAt(o));)++o}return new Y(lo(t.line,i),lo(t.line,o))}function pr(t,r,n,i){e.defaults[t]=r,n&&(wo[t]=i?function(e,t,r){r!=Co&&n(e,t,r)}:n)}
// KEYMAP DISPATCH
function gr(e){return"string"==typeof e?Oo[e]:e}
// Create a marker, wire it up to the right lines, and
function vr(e,t,r,n,i){
// Shared markers (across linked documents) are handled separately
// (markTextShared will call out to this again, once per
// document).
if(n&&n.shared)return mr(e,t,r,n,i);
// Ensure we are in an operation.
if(e.cm&&!e.cm.curOp)return Ze(e.cm,vr)(e,t,r,n,i);var o=new zo(e,i),l=so(t,r);
// Don't connect empty markers unless clearWhenEmpty is false
if(n&&ii(n,o,!1),l>0||0==l&&o.clearWhenEmpty!==!1)return o;if(o.replacedWith&&(
// Showing up as a widget implies collapsed (widget replaces text)
o.collapsed=!0,o.widgetNode=ui("span",[o.replacedWith],"CodeMirror-widget"),n.handleMouseEvents||(o.widgetNode.ignoreEvents=!0),n.insertLeft&&(o.widgetNode.insertLeft=!0)),o.collapsed){if(Rr(e,t.line,t,r,o)||t.line!=r.line&&Rr(e,r.line,t,r,o))throw new Error("Inserting collapsed marker partially overlapping an existing one");oo=!0}o.addToHistory&&On(e,{from:t,to:r,origin:"markText"},e.sel,NaN);var s,a=t.line,u=e.cm;if(e.iter(a,r.line+1,function(e){u&&o.collapsed&&!u.options.lineWrapping&&Br(e)==u.display.maxLine&&(s=!0),o.collapsed&&a!=t.line&&Cn(e,0),Sr(e,new wr(o,a==t.line?t.ch:null,a==r.line?r.ch:null)),++a}),
// lineIsHidden depends on the presence of the spans, so needs a second pass
o.collapsed&&e.iter(t.line,r.line+1,function(t){Kr(e,t)&&Cn(t,0)}),o.clearOnEnter&&Zo(o,"beforeCursorEnter",function(){o.clear()}),o.readOnly&&(io=!0,(e.history.done.length||e.history.undone.length)&&e.clearHistory()),o.collapsed&&(o.id=++Po,o.atomic=!0),u){if(
// Sync editor state
s&&(u.curOp.updateMaxLine=!0),o.collapsed)rt(u,t.line,r.line+1);else if(o.className||o.title||o.startStyle||o.endStyle)for(var c=t.line;c<=r.line;c++)nt(u,c,"text");o.atomic&&fe(u.doc),_n(u,"markerAdded",u,o)}return o}function mr(e,t,r,n,i){n=ii(n),n.shared=!1;var o=[vr(e,t,r,n,i)],l=o[0],s=n.widgetNode;return mn(e,function(e){s&&(n.widgetNode=s.cloneNode(!0)),o.push(vr(e,Z(e,t),Z(e,r),n,i));for(var a=0;a<e.linked.length;++a)if(e.linked[a].isParent)return;l=ei(o)}),new Fo(o,l)}function yr(e){return e.findMarks(lo(e.first,0),e.clipPos(lo(e.lastLine())),function(e){return e.parent})}function br(e,t){for(var r=0;r<t.length;r++){var n=t[r],i=n.find(),o=e.clipPos(i.from),l=e.clipPos(i.to);if(so(o,l)){var s=vr(e,o,l,n.primary,n.primary.type);n.markers.push(s),s.parent=n}}}function xr(e){for(var t=0;t<e.length;t++){var r=e[t],n=[r.primary.doc];mn(r.primary.doc,function(e){n.push(e)});for(var i=0;i<r.markers.length;i++){var o=r.markers[i];-1==ti(n,o.doc)&&(o.parent=null,r.markers.splice(i--,1))}}}
// TEXTMARKER SPANS
function wr(e,t,r){this.marker=e,this.from=t,this.to=r}
// Search an array of spans for a span matching the given marker.
function Cr(e,t){if(e)for(var r=0;r<e.length;++r){var n=e[r];if(n.marker==t)return n}}
// Remove a span from an array, returning undefined if no spans are
// left (we don't store arrays for lines without spans).
function Lr(e,t){for(var r,n=0;n<e.length;++n)e[n]!=t&&(r||(r=[])).push(e[n]);return r}
// Add a span to a line.
function Sr(e,t){e.markedSpans=e.markedSpans?e.markedSpans.concat([t]):[t],t.marker.attachLine(e)}
// Used for the algorithm that adjusts markers for a change in the
// document. These functions cut an array of spans at a given
// character position, returning an array of remaining chunks (or
// undefined if nothing remains).
function kr(e,t,r){if(e)for(var n,i=0;i<e.length;++i){var o=e[i],l=o.marker,s=null==o.from||(l.inclusiveLeft?o.from<=t:o.from<t);if(s||o.from==t&&"bookmark"==l.type&&(!r||!o.marker.insertLeft)){var a=null==o.to||(l.inclusiveRight?o.to>=t:o.to>t);(n||(n=[])).push(new wr(l,o.from,a?null:o.to))}}return n}function Mr(e,t,r){if(e)for(var n,i=0;i<e.length;++i){var o=e[i],l=o.marker,s=null==o.to||(l.inclusiveRight?o.to>=t:o.to>t);if(s||o.from==t&&"bookmark"==l.type&&(!r||o.marker.insertLeft)){var a=null==o.from||(l.inclusiveLeft?o.from<=t:o.from<t);(n||(n=[])).push(new wr(l,a?null:o.from-t,null==o.to?null:o.to-t))}}return n}
// Given a change object, compute the new set of marker spans that
// cover the line in which the change took place. Removes spans
// entirely within the change, reconnects spans belonging to the
// same marker that appear on both sides of the change, and cuts off
// spans partially within the change. Returns an array of span
// arrays with one element for each line in (after) the change.
function Tr(e,t){var r=J(e,t.from.line)&&bn(e,t.from.line).markedSpans,n=J(e,t.to.line)&&bn(e,t.to.line).markedSpans;if(!r&&!n)return null;var i=t.from.ch,o=t.to.ch,l=0==so(t.from,t.to),s=kr(r,i,l),a=Mr(n,o,l),u=1==t.text.length,c=ei(t.text).length+(u?i:0);if(s)
// Fix up .to properties of first
for(var f=0;f<s.length;++f){var h=s[f];if(null==h.to){var d=Cr(a,h.marker);d?u&&(h.to=null==d.to?null:d.to+c):h.to=i}}if(a)
// Fix up .from in last (or move them into first in case of sameLine)
for(var f=0;f<a.length;++f){var h=a[f];if(null!=h.to&&(h.to+=c),null==h.from){var d=Cr(s,h.marker);d||(h.from=c,u&&(s||(s=[])).push(h))}else h.from+=c,u&&(s||(s=[])).push(h)}
// Make sure we didn't create any zero-length spans
s&&(s=Nr(s)),a&&a!=s&&(a=Nr(a));var p=[s];if(!u){
// Fill gap with whole-line-spans
var g,v=t.text.length-2;if(v>0&&s)for(var f=0;f<s.length;++f)null==s[f].to&&(g||(g=[])).push(new wr(s[f].marker,null,null));for(var f=0;v>f;++f)p.push(g);p.push(a)}return p}
// Remove spans that are empty and don't have a clearWhenEmpty
// option of false.
function Nr(e){for(var t=0;t<e.length;++t){var r=e[t];null!=r.from&&r.from==r.to&&r.marker.clearWhenEmpty!==!1&&e.splice(t--,1)}return e.length?e:null}
// Used for un/re-doing changes from the history. Combines the
// result of computing the existing spans with the set of spans that
// existed in the history (so that deleting around a span and then
// undoing brings back the span).
function Hr(e,t){var r=Pn(e,t),n=Tr(e,t);if(!r)return n;if(!n)return r;for(var i=0;i<r.length;++i){var o=r[i],l=n[i];if(o&&l)e:for(var s=0;s<l.length;++s){for(var a=l[s],u=0;u<o.length;++u)if(o[u].marker==a.marker)continue e;o.push(a)}else l&&(r[i]=l)}return r}
// Used to 'clip' out readOnly ranges when making a change.
function Ar(e,t,r){var n=null;if(e.iter(t.line,r.line+1,function(e){if(e.markedSpans)for(var t=0;t<e.markedSpans.length;++t){var r=e.markedSpans[t].marker;!r.readOnly||n&&-1!=ti(n,r)||(n||(n=[])).push(r)}}),!n)return null;for(var i=[{from:t,to:r}],o=0;o<n.length;++o)for(var l=n[o],s=l.find(0),a=0;a<i.length;++a){var u=i[a];if(!(so(u.to,s.from)<0||so(u.from,s.to)>0)){var c=[a,1],f=so(u.from,s.from),h=so(u.to,s.to);(0>f||!l.inclusiveLeft&&!f)&&c.push({from:u.from,to:s.from}),(h>0||!l.inclusiveRight&&!h)&&c.push({from:s.to,to:u.to}),i.splice.apply(i,c),a+=c.length-1}}return i}
// Connect or disconnect spans from a line.
function Or(e){var t=e.markedSpans;if(t){for(var r=0;r<t.length;++r)t[r].marker.detachLine(e);e.markedSpans=null}}function Wr(e,t){if(t){for(var r=0;r<t.length;++r)t[r].marker.attachLine(e);e.markedSpans=t}}
// Helpers used when computing which overlapping collapsed span
// counts as the larger one.
function Dr(e){return e.inclusiveLeft?-1:0}function Er(e){return e.inclusiveRight?1:0}
// Returns a number indicating which of two overlapping collapsed
// spans is larger (and thus includes the other). Falls back to
// comparing ids when the spans cover exactly the same range.
function Ir(e,t){var r=e.lines.length-t.lines.length;if(0!=r)return r;var n=e.find(),i=t.find(),o=so(n.from,i.from)||Dr(e)-Dr(t);if(o)return-o;var l=so(n.to,i.to)||Er(e)-Er(t);return l?l:t.id-e.id}
// Find out whether a line ends or starts in a collapsed span. If
// so, return the marker for that span.
function zr(e,t){var r,n=oo&&e.markedSpans;if(n)for(var i,o=0;o<n.length;++o)i=n[o],i.marker.collapsed&&null==(t?i.from:i.to)&&(!r||Ir(r,i.marker)<0)&&(r=i.marker);return r}function Pr(e){return zr(e,!0)}function Fr(e){return zr(e,!1)}
// Test whether there exists a collapsed span that partially
// overlaps (covers the start or end, but not both) of a new span.
// Such overlap is not allowed.
function Rr(e,t,r,n,i){var o=bn(e,t),l=oo&&o.markedSpans;if(l)for(var s=0;s<l.length;++s){var a=l[s];if(a.marker.collapsed){var u=a.marker.find(0),c=so(u.from,r)||Dr(a.marker)-Dr(i),f=so(u.to,n)||Er(a.marker)-Er(i);if(!(c>=0&&0>=f||0>=c&&f>=0)&&(0>=c&&(so(u.to,r)||Er(a.marker)-Dr(i))>0||c>=0&&(so(u.from,n)||Dr(a.marker)-Er(i))<0))return!0}}}
// A visual line is a line as drawn on the screen. Folding, for
// example, can cause multiple logical lines to appear on the same
// visual line. This finds the start of the visual line that the
// given line is part of (usually that is the line itself).
function Br(e){for(var t;t=Pr(e);)e=t.find(-1,!0).line;return e}
// Returns an array of logical lines that continue the visual line
// started by the argument, or undefined if there are no such lines.
function Gr(e){for(var t,r;t=Fr(e);)e=t.find(1,!0).line,(r||(r=[])).push(e);return r}
// Get the line number of the start of the visual line that the
// given line number is part of.
function Vr(e,t){var r=bn(e,t),n=Br(r);return r==n?t:Ln(n)}
// Get the line number of the start of the next visual line after
// the given line.
function Ur(e,t){if(t>e.lastLine())return t;var r,n=bn(e,t);if(!Kr(e,n))return t;for(;r=Fr(n);)n=r.find(1,!0).line;return Ln(n)+1}
// Compute whether a line is hidden. Lines count as hidden when they
// are part of a visual line that starts with another line, or when
// they are entirely covered by collapsed, non-widget span.
function Kr(e,t){var r=oo&&t.markedSpans;if(r)for(var n,i=0;i<r.length;++i)if(n=r[i],n.marker.collapsed){if(null==n.from)return!0;if(!n.marker.widgetNode&&0==n.from&&n.marker.inclusiveLeft&&_r(e,t,n))return!0}}function _r(e,t,r){if(null==r.to){var n=r.marker.find(1,!0);return _r(e,n.line,Cr(n.line.markedSpans,r.marker))}if(r.marker.inclusiveRight&&r.to==t.text.length)return!0;for(var i,o=0;o<t.markedSpans.length;++o)if(i=t.markedSpans[o],i.marker.collapsed&&!i.marker.widgetNode&&i.from==r.to&&(null==i.to||i.to!=r.from)&&(i.marker.inclusiveLeft||r.marker.inclusiveRight)&&_r(e,t,i))return!0}function Xr(e,t,r){kn(t)<(e.curOp&&e.curOp.scrollTop||e.doc.scrollTop)&&or(e,null,r)}function Yr(e){return null!=e.height?e.height:(hi(document.body,e.node)||fi(e.cm.display.measure,ui("div",[e.node],null,"position: relative")),e.height=e.node.offsetHeight)}function jr(e,t,r,n){var i=new Ro(e,r,n);return i.noHScroll&&(e.display.alignWidgets=!0),ur(e,t,"widget",function(t){var r=t.widgets||(t.widgets=[]);if(null==i.insertAt?r.push(i):r.splice(Math.min(r.length-1,Math.max(0,i.insertAt)),0,i),i.line=t,!Kr(e.doc,t)){var n=kn(t)<e.doc.scrollTop;Cn(t,t.height+Yr(i)),n&&or(e,null,i.height),e.curOp.forceUpdate=!0}return!0}),i}
// Change the content (text, markers) of a line. Automatically
// invalidates cached information and tries to re-estimate the
// line's height.
function $r(e,t,r,n){e.text=t,e.stateAfter&&(e.stateAfter=null),e.styles&&(e.styles=null),null!=e.order&&(e.order=null),Or(e),Wr(e,r);var i=n?n(e):1;i!=e.height&&Cn(e,i)}
// Detach a line from the document tree and its markers.
function qr(e){e.parent=null,Or(e)}function Zr(e,t){if(e)for(;;){var r=e.match(/(?:^|\s+)line-(background-)?(\S+)/);if(!r)break;e=e.slice(0,r.index)+e.slice(r.index+r[0].length);var n=r[1]?"bgClass":"textClass";null==t[n]?t[n]=r[2]:new RegExp("(?:^|s)"+r[2]+"(?:$|s)").test(t[n])||(t[n]+=" "+r[2])}return e}function Qr(t,r){if(t.blankLine)return t.blankLine(r);if(t.innerMode){var n=e.innerMode(t,r);return n.mode.blankLine?n.mode.blankLine(n.state):void 0}}function Jr(e,t,r){for(var n=0;10>n;n++){var i=e.token(t,r);if(t.pos>t.start)return i}throw new Error("Mode "+e.name+" failed to advance stream.")}
// Run the given mode's parser over a line, calling f for each token.
function en(t,r,n,i,o,l,s){var a=n.flattenSpans;null==a&&(a=t.options.flattenSpans);var u,c=0,f=null,h=new Io(r,t.options.tabSize);for(""==r&&Zr(Qr(n,i),l);!h.eol();){if(h.pos>t.options.maxHighlightLength?(a=!1,s&&nn(t,r,i,h.pos),h.pos=r.length,u=null):u=Zr(Jr(n,h,i),l),t.options.addModeClass){var d=e.innerMode(n,i).mode.name;d&&(u="m-"+(u?d+" "+u:d))}a&&f==u||(c<h.start&&o(h.start,f),c=h.start,f=u),h.start=h.pos}for(;c<h.pos;){
// Webkit seems to refuse to render text nodes longer than 57444 characters
var p=Math.min(h.pos,c+5e4);o(p,f),c=p}}
// Compute a style array (an array starting with a mode generation
// -- for invalidation -- followed by pairs of end positions and
// style strings), which is used to highlight the tokens on the
// line.
function tn(e,t,r,n){
// A styles array always starts with a number identifying the
// mode/overlays that it is based on (for easy invalidation).
var i=[e.state.modeGen],o={};
// Compute the base array of styles
en(e,t.text,e.doc.mode,r,function(e,t){i.push(e,t)},o,n);
// Run overlays, adjust style array.
for(var l=0;l<e.state.overlays.length;++l){var s=e.state.overlays[l],a=1,u=0;en(e,t.text,s.mode,!0,function(e,t){
// Ensure there's a token end at the current position, and that i points at it
for(var r=a;e>u;){var n=i[a];n>e&&i.splice(a,1,e,i[a+1],n),a+=2,u=Math.min(e,n)}if(t)if(s.opaque)i.splice(r,a-r,e,"cm-overlay "+t),a=r+2;else for(;a>r;r+=2){var o=i[r+1];i[r+1]=(o?o+" ":"")+"cm-overlay "+t}},o)}return{styles:i,classes:o.bgClass||o.textClass?o:null}}function rn(e,t){if(!t.styles||t.styles[0]!=e.state.modeGen){var r=tn(e,t,t.stateAfter=we(e,Ln(t)));t.styles=r.styles,r.classes?t.styleClasses=r.classes:t.styleClasses&&(t.styleClasses=null)}return t.styles}
// Lightweight form of highlight -- proceed over this line and
// update state, but don't save a style array. Used for lines that
// aren't currently visible.
function nn(e,t,r,n){var i=e.doc.mode,o=new Io(t,e.options.tabSize);for(o.start=o.pos=n||0,""==t&&Qr(i,r);!o.eol()&&o.pos<=e.options.maxHighlightLength;)Jr(i,o,r),o.start=o.pos}function on(e,t){if(!e||/^\s*$/.test(e))return null;var r=t.addModeClass?Vo:Go;return r[e]||(r[e]=e.replace(/\S+/g,"cm-$&"))}
// Render the DOM representation of the text of a line. Also builds
// up a 'line map', which points at the DOM nodes that represent
// specific stretches of text, and is used by the measuring code.
// The returned object contains the DOM node, this map, and
// information about line-wide styles that were set by the mode.
function ln(e,t){
// The padding-right forces the element to have a 'border', which
// is needed on Webkit to be able to get line-level bounding
// rectangles for it (in measureChar).
var r=ui("span",null,null,Ui?"padding-right: .1px":null),n={pre:ui("pre",[r]),content:r,col:0,pos:0,cm:e};t.measure={};
// Iterate over the logical lines that make up this visual line.
for(var i=0;i<=(t.rest?t.rest.length:0);i++){var o,l=i?t.rest[i-1]:t.line;n.pos=0,n.addToken=an,
// Optionally wire in some hacks into the token-rendering
// algorithm, to deal with browser quirks.
(Vi||Ui)&&e.getOption("lineWrapping")&&(n.addToken=un(n.addToken)),Li(e.display.measure)&&(o=Mn(l))&&(n.addToken=cn(n.addToken,o)),n.map=[],hn(l,n,rn(e,l)),l.styleClasses&&(l.styleClasses.bgClass&&(n.bgClass=mi(l.styleClasses.bgClass,n.bgClass||"")),l.styleClasses.textClass&&(n.textClass=mi(l.styleClasses.textClass,n.textClass||""))),
// Ensure at least a single node is present, for measuring.
0==n.map.length&&n.map.push(0,0,n.content.appendChild(Ci(e.display.measure))),
// Store the map and a cache object for the current logical line
0==i?(t.measure.map=n.map,t.measure.cache={}):((t.measure.maps||(t.measure.maps=[])).push(n.map),(t.measure.caches||(t.measure.caches=[])).push({}))}return Jo(e,"renderLine",e,t.line,n.pre),n.pre.className&&(n.textClass=mi(n.pre.className,n.textClass||"")),n}function sn(e){var t=ui("span","•","cm-invalidchar");return t.title="\\u"+e.charCodeAt(0).toString(16),t}
// Build up the DOM representation for a single token, and add it to
// the line map. Takes care to render special characters separately.
function an(e,t,r,n,i,o){if(t){var l=e.cm.options.specialChars,s=!1;if(l.test(t))for(var a=document.createDocumentFragment(),u=0;;){l.lastIndex=u;var c=l.exec(t),f=c?c.index-u:t.length-u;if(f){var h=document.createTextNode(t.slice(u,u+f));Ri?a.appendChild(ui("span",[h])):a.appendChild(h),e.map.push(e.pos,e.pos+f,h),e.col+=f,e.pos+=f}if(!c)break;if(u+=f+1,"	"==c[0]){var d=e.cm.options.tabSize,p=d-e.col%d,h=a.appendChild(ui("span",Jn(p),"cm-tab"));e.col+=p}else{var h=e.cm.options.specialCharPlaceholder(c[0]);Ri?a.appendChild(ui("span",[h])):a.appendChild(h),e.col+=1}e.map.push(e.pos,e.pos+1,h),e.pos++}else{e.col+=t.length;var a=document.createTextNode(t);e.map.push(e.pos,e.pos+t.length,a),Ri&&(s=!0),e.pos+=t.length}if(r||n||i||s){var g=r||"";n&&(g+=n),i&&(g+=i);var v=ui("span",[a],g);return o&&(v.title=o),e.content.appendChild(v)}e.content.appendChild(a)}}function un(e){function t(e){for(var t=" ",r=0;r<e.length-2;++r)t+=r%2?" ":" ";return t+=" "}return function(r,n,i,o,l,s){e(r,n.replace(/ {3,}/g,t),i,o,l,s)}}
// Work around nonsense dimensions being reported for stretches of
// right-to-left text.
function cn(e,t){return function(r,n,i,o,l,s){i=i?i+" cm-force-border":"cm-force-border";for(var a=r.pos,u=a+n.length;;){
// Find the part that overlaps with the start of this text
for(var c=0;c<t.length;c++){var f=t[c];if(f.to>a&&f.from<=a)break}if(f.to>=u)return e(r,n,i,o,l,s);e(r,n.slice(0,f.to-a),i,o,null,s),o=null,n=n.slice(f.to-a),a=f.to}}}function fn(e,t,r,n){var i=!n&&r.widgetNode;i&&(e.map.push(e.pos,e.pos+t,i),e.content.appendChild(i)),e.pos+=t}
// Outputs a number of spans to make up a line, taking highlighting
// and marked text into account.
function hn(e,t,r){var n=e.markedSpans,i=e.text,o=0;if(n)for(var l,s,a,u,c,f,h=i.length,d=0,p=1,g="",v=0;;){if(v==d){// Update current marker set
s=a=u=c="",f=null,v=1/0;for(var m=[],y=0;y<n.length;++y){var b=n[y],x=b.marker;b.from<=d&&(null==b.to||b.to>d)?(null!=b.to&&v>b.to&&(v=b.to,a=""),x.className&&(s+=" "+x.className),x.startStyle&&b.from==d&&(u+=" "+x.startStyle),x.endStyle&&b.to==v&&(a+=" "+x.endStyle),x.title&&!c&&(c=x.title),x.collapsed&&(!f||Ir(f.marker,x)<0)&&(f=b)):b.from>d&&v>b.from&&(v=b.from),"bookmark"==x.type&&b.from==d&&x.widgetNode&&m.push(x)}if(f&&(f.from||0)==d&&(fn(t,(null==f.to?h+1:f.to)-d,f.marker,null==f.from),null==f.to))return;if(!f&&m.length)for(var y=0;y<m.length;++y)fn(t,0,m[y])}if(d>=h)break;for(var w=Math.min(h,v);;){if(g){var C=d+g.length;if(!f){var L=C>w?g.slice(0,w-d):g;t.addToken(t,L,l?l+s:s,u,d+L.length==v?a:"",c)}if(C>=w){g=g.slice(w-d),d=w;break}d=C,u=""}g=i.slice(o,o=r[p++]),l=on(r[p++],t.cm.options)}}else for(var p=1;p<r.length;p+=2)t.addToken(t,i.slice(o,o=r[p]),on(r[p+1],t.cm.options))}
// DOCUMENT DATA STRUCTURE
// By default, updates that start and end at the beginning of a line
// are treated specially, in order to make the association of line
// widgets and marker elements with the text behave more intuitive.
function dn(e,t){return 0==t.from.ch&&0==t.to.ch&&""==ei(t.text)&&(!e.cm||e.cm.options.wholeLineUpdateBefore)}
// Perform a change on the document data structure.
function pn(e,t,r,n){function i(e){return r?r[e]:null}function o(e,r,i){$r(e,r,i,n),_n(e,"change",e,t)}var l=t.from,s=t.to,a=t.text,u=bn(e,l.line),c=bn(e,s.line),f=ei(a),h=i(a.length-1),d=s.line-l.line;
// Adjust the line structure
if(dn(e,t)){
// This is a whole-line replace. Treated specially to make
// sure line objects move the way they are supposed to.
for(var p=0,g=[];p<a.length-1;++p)g.push(new Bo(a[p],i(p),n));o(c,c.text,h),d&&e.remove(l.line,d),g.length&&e.insert(l.line,g)}else if(u==c)if(1==a.length)o(u,u.text.slice(0,l.ch)+f+u.text.slice(s.ch),h);else{for(var g=[],p=1;p<a.length-1;++p)g.push(new Bo(a[p],i(p),n));g.push(new Bo(f+u.text.slice(s.ch),h,n)),o(u,u.text.slice(0,l.ch)+a[0],i(0)),e.insert(l.line+1,g)}else if(1==a.length)o(u,u.text.slice(0,l.ch)+a[0]+c.text.slice(s.ch),i(0)),e.remove(l.line+1,d);else{o(u,u.text.slice(0,l.ch)+a[0],i(0)),o(c,f+c.text.slice(s.ch),h);for(var p=1,g=[];p<a.length-1;++p)g.push(new Bo(a[p],i(p),n));d>1&&e.remove(l.line+1,d-1),e.insert(l.line+1,g)}_n(e,"change",e,t)}
// The document is represented as a BTree consisting of leaves, with
// chunk of lines in them, and branches, with up to ten leaves or
// other branch nodes below them. The top node is always a branch
// node, and is the document object itself (meaning it has
// additional methods and properties).
//
// All nodes have parent links. The tree is used both to go from
// line numbers to line objects, and to go from objects to numbers.
// It also indexes by height, and is used to convert between height
// and line object, and to find the total height of the document.
//
// See also http://marijnhaverbeke.nl/blog/codemirror-line-tree.html
function gn(e){this.lines=e,this.parent=null;for(var t=0,r=0;t<e.length;++t)e[t].parent=this,r+=e[t].height;this.height=r}function vn(e){this.children=e;for(var t=0,r=0,n=0;n<e.length;++n){var i=e[n];t+=i.chunkSize(),r+=i.height,i.parent=this}this.size=t,this.height=r,this.parent=null}
// Call f for all linked documents.
function mn(e,t,r){function n(e,i,o){if(e.linked)for(var l=0;l<e.linked.length;++l){var s=e.linked[l];if(s.doc!=i){var a=o&&s.sharedHist;(!r||a)&&(t(s.doc,a),n(s.doc,e,a))}}}n(e,null,!0)}
// Attach a document to an editor.
function yn(e,t){if(t.cm)throw new Error("This document is already in use.");e.doc=t,t.cm=e,l(e),r(e),e.options.lineWrapping||d(e),e.options.mode=t.modeOption,rt(e)}
// LINE UTILITIES
// Find the line object corresponding to the given line number.
function bn(e,t){if(t-=e.first,0>t||t>=e.size)throw new Error("There is no line "+(t+e.first)+" in the document.");for(var r=e;!r.lines;)for(var n=0;;++n){var i=r.children[n],o=i.chunkSize();if(o>t){r=i;break}t-=o}return r.lines[t]}
// Get the part of a document between two positions, as an array of
// strings.
function xn(e,t,r){var n=[],i=t.line;return e.iter(t.line,r.line+1,function(e){var o=e.text;i==r.line&&(o=o.slice(0,r.ch)),i==t.line&&(o=o.slice(t.ch)),n.push(o),++i}),n}
// Get the lines between from and to, as array of strings.
function wn(e,t,r){var n=[];return e.iter(t,r,function(e){n.push(e.text)}),n}
// Update the height of a line, propagating the height change
// upwards to parent nodes.
function Cn(e,t){var r=t-e.height;if(r)for(var n=e;n;n=n.parent)n.height+=r}
// Given a line object, find its line number by walking up through
// its parent links.
function Ln(e){if(null==e.parent)return null;for(var t=e.parent,r=ti(t.lines,e),n=t.parent;n;t=n,n=n.parent)for(var i=0;n.children[i]!=t;++i)r+=n.children[i].chunkSize();return r+t.first}
// Find the line at the given vertical position, using the height
// information in the document tree.
function Sn(e,t){var r=e.first;e:do{for(var n=0;n<e.children.length;++n){var i=e.children[n],o=i.height;if(o>t){e=i;continue e}t-=o,r+=i.chunkSize()}return r}while(!e.lines);for(var n=0;n<e.lines.length;++n){var l=e.lines[n],s=l.height;if(s>t)break;t-=s}return r+n}
// Find the height above the given line.
function kn(e){e=Br(e);for(var t=0,r=e.parent,n=0;n<r.lines.length;++n){var i=r.lines[n];if(i==e)break;t+=i.height}for(var o=r.parent;o;r=o,o=r.parent)for(var n=0;n<o.children.length;++n){var l=o.children[n];if(l==r)break;t+=l.height}return t}
// Get the bidi ordering for the given line (and cache it). Returns
// false for lines that are fully left-to-right, and an array of
// BidiSpan objects otherwise.
function Mn(e){var t=e.order;return null==t&&(t=e.order=Ll(e.text)),t}
// HISTORY
function Tn(e){
// Arrays of change events and selections. Doing something adds an
// event to done and clears undo. Undoing moves events from done
// to undone, redoing moves them in the other direction.
this.done=[],this.undone=[],this.undoDepth=1/0,
// Used to track when changes can be merged into a single undo
// event
this.lastModTime=this.lastSelTime=0,this.lastOp=null,this.lastOrigin=this.lastSelOrigin=null,
// Used by the isClean() method
this.generation=this.maxGeneration=e||1}
// Create a history change event from an updateDoc-style change
// object.
function Nn(e,t){var r={from:U(t.from),to:bo(t),text:xn(e,t.from,t.to)};return In(e,r,t.from.line,t.to.line+1),mn(e,function(e){In(e,r,t.from.line,t.to.line+1)},!0),r}
// Pop all selection events off the end of a history array. Stop at
// a change event.
function Hn(e){for(;e.length;){var t=ei(e);if(!t.ranges)break;e.pop()}}
// Find the top change event in the history. Pop off selection
// events that are in the way.
function An(e,t){return t?(Hn(e.done),ei(e.done)):e.done.length&&!ei(e.done).ranges?ei(e.done):e.done.length>1&&!e.done[e.done.length-2].ranges?(e.done.pop(),ei(e.done)):void 0}
// Register a change in the history. Merges changes that are within
// a single operation, ore are close together with an origin that
// allows merging (starting with "+") into a single event.
function On(e,t,r,n){var i=e.history;i.undone.length=0;var o,l=+new Date;if((i.lastOp==n||i.lastOrigin==t.origin&&t.origin&&("+"==t.origin.charAt(0)&&e.cm&&i.lastModTime>l-e.cm.options.historyEventDelay||"*"==t.origin.charAt(0)))&&(o=An(i,i.lastOp==n))){
// Merge this change into the last event
var s=ei(o.changes);0==so(t.from,t.to)&&0==so(t.from,s.to)?
// Optimized case for simple insertion -- don't want to add
// new changesets for every character typed
s.to=bo(t):
// Add new sub-event
o.changes.push(Nn(e,t))}else{
// Can not be merged, start a new event.
var a=ei(i.done);for(a&&a.ranges||En(e.sel,i.done),o={changes:[Nn(e,t)],generation:i.generation},i.done.push(o);i.done.length>i.undoDepth;)i.done.shift(),i.done[0].ranges||i.done.shift()}i.done.push(r),i.generation=++i.maxGeneration,i.lastModTime=i.lastSelTime=l,i.lastOp=n,i.lastOrigin=i.lastSelOrigin=t.origin,s||Jo(e,"historyAdded")}function Wn(e,t,r,n){var i=t.charAt(0);return"*"==i||"+"==i&&r.ranges.length==n.ranges.length&&r.somethingSelected()==n.somethingSelected()&&new Date-e.history.lastSelTime<=(e.cm?e.cm.options.historyEventDelay:500)}
// Called whenever the selection changes, sets the new selection as
// the pending selection in the history, and pushes the old pending
// selection into the 'done' array when it was significantly
// different (in number of selected ranges, emptiness, or time).
function Dn(e,t,r,n){var i=e.history,o=n&&n.origin;
// A new event is started when the previous origin does not match
// the current, or the origins don't allow matching. Origins
// starting with * are always merged, those starting with + are
// merged when similar and close together in time.
r==i.lastOp||o&&i.lastSelOrigin==o&&(i.lastModTime==i.lastSelTime&&i.lastOrigin==o||Wn(e,o,ei(i.done),t))?i.done[i.done.length-1]=t:En(t,i.done),i.lastSelTime=+new Date,i.lastSelOrigin=o,i.lastOp=r,n&&n.clearRedo!==!1&&Hn(i.undone)}function En(e,t){var r=ei(t);r&&r.ranges&&r.equals(e)||t.push(e)}
// Used to store marked span information in the history.
function In(e,t,r,n){var i=t["spans_"+e.id],o=0;e.iter(Math.max(e.first,r),Math.min(e.first+e.size,n),function(r){r.markedSpans&&((i||(i=t["spans_"+e.id]={}))[o]=r.markedSpans),++o})}
// When un/re-doing restores text containing marked spans, those
// that have been explicitly cleared should not be restored.
function zn(e){if(!e)return null;for(var t,r=0;r<e.length;++r)e[r].marker.explicitlyCleared?t||(t=e.slice(0,r)):t&&t.push(e[r]);return t?t.length?t:null:e}
// Retrieve and filter the old marked spans stored in a change event.
function Pn(e,t){var r=t["spans_"+e.id];if(!r)return null;for(var n=0,i=[];n<t.text.length;++n)i.push(zn(r[n]));return i}
// Used both to provide a JSON-safe object in .getHistory, and, when
// detaching a document, to split the history in two
function Fn(e,t,r){for(var n=0,i=[];n<e.length;++n){var o=e[n];if(o.ranges)i.push(r?X.prototype.deepCopy.call(o):o);else{var l=o.changes,s=[];i.push({changes:s});for(var a=0;a<l.length;++a){var u,c=l[a];if(s.push({from:c.from,to:c.to,text:c.text}),t)for(var f in c)(u=f.match(/^spans_(\d+)$/))&&ti(t,Number(u[1]))>-1&&(ei(s)[f]=c[f],delete c[f])}}}return i}
// Rebasing/resetting history to deal with externally-sourced changes
function Rn(e,t,r,n){r<e.line?e.line+=n:t<e.line&&(e.line=t,e.ch=0)}
// Tries to rebase an array of history events given a change in the
// document. If the change touches the same lines as the event, the
// event, and everything 'behind' it, is discarded. If the change is
// before the event, the event's positions are updated. Uses a
// copy-on-write scheme for the positions, to avoid having to
// reallocate them all on every rebase, but also avoid problems with
// shared position objects being unsafely updated.
function Bn(e,t,r,n){for(var i=0;i<e.length;++i){var o=e[i],l=!0;if(o.ranges){o.copied||(o=e[i]=o.deepCopy(),o.copied=!0);for(var s=0;s<o.ranges.length;s++)Rn(o.ranges[s].anchor,t,r,n),Rn(o.ranges[s].head,t,r,n)}else{for(var s=0;s<o.changes.length;++s){var a=o.changes[s];if(r<a.from.line)a.from=lo(a.from.line+n,a.from.ch),a.to=lo(a.to.line+n,a.to.ch);else if(t<=a.to.line){l=!1;break}}l||(e.splice(0,i+1),i=0)}}}function Gn(e,t){var r=t.from.line,n=t.to.line,i=t.text.length-(n-r)-1;Bn(e.done,r,n,i),Bn(e.undone,r,n,i)}function Vn(e){return null!=e.defaultPrevented?e.defaultPrevented:0==e.returnValue}function Un(e){return e.target||e.srcElement}function Kn(e){var t=e.which;return null==t&&(1&e.button?t=1:2&e.button?t=3:4&e.button&&(t=2)),Ji&&e.ctrlKey&&1==t&&(t=3),t}function _n(e,t){function r(e){return function(){e.apply(null,i)}}var n=e._handlers&&e._handlers[t];if(n){var i=Array.prototype.slice.call(arguments,2);Yo||(++el,Yo=[],setTimeout(Xn,0));for(var o=0;o<n.length;++o)Yo.push(r(n[o]))}}function Xn(){--el;var e=Yo;Yo=null;for(var t=0;t<e.length;++t)e[t]()}
// The DOM events that CodeMirror handles can be overridden by
// registering a (non-DOM) handler on the editor for the event name,
// and preventDefault-ing the event in that handler.
function Yn(e,t,r){return Jo(e,r||t.type,e,t),Vn(t)||t.codemirrorIgnore}function jn(e){var t=e._handlers&&e._handlers.cursorActivity;if(t)for(var r=e.curOp.cursorActivityHandlers||(e.curOp.cursorActivityHandlers=[]),n=0;n<t.length;++n)-1==ti(r,t[n])&&r.push(t[n])}function $n(e,t){var r=e._handlers&&e._handlers[t];return r&&r.length>0}
// Add on and off methods to a constructor's prototype, to make
// registering events on such objects more convenient.
function qn(e){e.prototype.on=function(e,t){Zo(this,e,t)},e.prototype.off=function(e,t){Qo(this,e,t)}}function Zn(){this.id=null}
// The inverse of countColumn -- find the offset that corresponds to
// a particular column.
function Qn(e,t,r){for(var n=0,i=0;;){var o=e.indexOf("	",n);-1==o&&(o=e.length);var l=o-n;if(o==e.length||i+l>=t)return n+Math.min(l,t-i);if(i+=o-n,i+=r-i%r,n=o+1,i>=t)return n}}function Jn(e){for(;sl.length<=e;)sl.push(ei(sl)+" ");return sl[e]}function ei(e){return e[e.length-1]}function ti(e,t){for(var r=0;r<e.length;++r)if(e[r]==t)return r;return-1}function ri(e,t){for(var r=[],n=0;n<e.length;n++)r[n]=t(e[n],n);return r}function ni(e,t){var r;if(Object.create)r=Object.create(e);else{var n=function(){};n.prototype=e,r=new n}return t&&ii(t,r),r}function ii(e,t,r){t||(t={});for(var n in e)!e.hasOwnProperty(n)||r===!1&&t.hasOwnProperty(n)||(t[n]=e[n]);return t}function oi(e){var t=Array.prototype.slice.call(arguments,1);return function(){return e.apply(null,t)}}function li(e,t){return t?t.source.indexOf("\\w")>-1&&fl(e)?!0:t.test(e):fl(e)}function si(e){for(var t in e)if(e.hasOwnProperty(t)&&e[t])return!1;return!0}function ai(e){return e.charCodeAt(0)>=768&&hl.test(e)}
// DOM UTILITIES
function ui(e,t,r,n){var i=document.createElement(e);if(r&&(i.className=r),n&&(i.style.cssText=n),"string"==typeof t)i.appendChild(document.createTextNode(t));else if(t)for(var o=0;o<t.length;++o)i.appendChild(t[o]);return i}function ci(e){for(var t=e.childNodes.length;t>0;--t)e.removeChild(e.firstChild);return e}function fi(e,t){return ci(e).appendChild(t)}function hi(e,t){if(e.contains)return e.contains(t);for(;t=t.parentNode;)if(t==e)return!0}function di(){return document.activeElement}function pi(e){return new RegExp("\\b"+e+"\\b\\s*")}function gi(e,t){var r=pi(t);r.test(e.className)&&(e.className=e.className.replace(r,""))}function vi(e,t){pi(t).test(e.className)||(e.className+=" "+t)}function mi(e,t){for(var r=e.split(" "),n=0;n<r.length;n++)r[n]&&!pi(r[n]).test(t)&&(t+=" "+r[n]);return t}
// WINDOW-WIDE EVENTS
// These must be handled carefully, because naively registering a
// handler for each editor will cause the editors to never be
// garbage collected.
function yi(e){if(document.body.getElementsByClassName)for(var t=document.body.getElementsByClassName("CodeMirror"),r=0;r<t.length;r++){var n=t[r].CodeMirror;n&&e(n)}}function bi(){vl||(xi(),vl=!0)}function xi(){
// When the window resizes, we need to refresh active editors.
var e;Zo(window,"resize",function(){null==e&&(e=setTimeout(function(){e=null,dl=null,yi(mt)},100))}),
// When the window loses focus, we want to show the editor as blurred
Zo(window,"blur",function(){yi(Bt)})}function wi(e){if(null!=dl)return dl;var t=ui("div",null,null,"width: 50px; height: 50px; overflow-x: scroll");return fi(e,t),t.offsetWidth&&(dl=t.offsetHeight-t.clientHeight),dl||0}function Ci(e){if(null==pl){var t=ui("span","​");fi(e,ui("span",[t,document.createTextNode("x")])),0!=e.firstChild.offsetHeight&&(pl=t.offsetWidth<=1&&t.offsetHeight>2&&!Fi)}return pl?ui("span","​"):ui("span"," ",null,"display: inline-block; width: 1px; margin-right: -1px")}function Li(e){if(null!=gl)return gl;var t=fi(e,document.createTextNode("AخA")),r=ul(t,0,1).getBoundingClientRect();if(r.left==r.right)return!1;var n=ul(t,1,2).getBoundingClientRect();return gl=n.right-r.right<3}
// BIDI HELPERS
function Si(e,t,r,n){if(!e)return n(t,r,"ltr");for(var i=!1,o=0;o<e.length;++o){var l=e[o];(l.from<r&&l.to>t||t==r&&l.to==t)&&(n(Math.max(l.from,t),Math.min(l.to,r),1==l.level?"rtl":"ltr"),i=!0)}i||n(t,r,"ltr")}function ki(e){return e.level%2?e.to:e.from}function Mi(e){return e.level%2?e.from:e.to}function Ti(e){var t=Mn(e);return t?ki(t[0]):0}function Ni(e){var t=Mn(e);return t?Mi(ei(t)):e.text.length}function Hi(e,t){var r=bn(e.doc,t),n=Br(r);n!=r&&(t=Ln(n));var i=Mn(n),o=i?i[0].level%2?Ni(n):Ti(n):0;return lo(t,o)}function Ai(e,t){for(var r,n=bn(e.doc,t);r=Fr(n);)n=r.find(1,!0).line,t=null;var i=Mn(n),o=i?i[0].level%2?Ti(n):Ni(n):n.text.length;return lo(null==t?Ln(n):t,o)}function Oi(e,t,r){var n=e[0].level;return t==n?!0:r==n?!1:r>t}function Wi(e,t){Cl=null;for(var r,n=0;n<e.length;++n){var i=e[n];if(i.from<t&&i.to>t)return n;if(i.from==t||i.to==t){if(null!=r)return Oi(e,i.level,e[r].level)?(i.from!=i.to&&(Cl=r),n):(i.from!=i.to&&(Cl=n),r);r=n}}return r}function Di(e,t,r,n){if(!n)return t+r;do t+=r;while(t>0&&ai(e.text.charAt(t)));return t}
// This is needed in order to move 'visually' through bi-directional
// text -- i.e., pressing left should make the cursor go left, even
// when in RTL text. The tricky part is the 'jumps', where RTL and
// LTR text touch each other. This often requires the cursor offset
// to move more than one unit, in order to visually move one unit.
function Ei(e,t,r,n){var i=Mn(e);if(!i)return Ii(e,t,r,n);for(var o=Wi(i,t),l=i[o],s=Di(e,t,l.level%2?-r:r,n);;){if(s>l.from&&s<l.to)return s;if(s==l.from||s==l.to)return Wi(i,s)==o?s:(l=i[o+=r],r>0==l.level%2?l.to:l.from);if(l=i[o+=r],!l)return null;s=r>0==l.level%2?Di(e,l.to,-1,n):Di(e,l.from,1,n)}}function Ii(e,t,r,n){var i=t+r;if(n)for(;i>0&&ai(e.text.charAt(i));)i+=r;return 0>i||i>e.text.length?null:i}
// BROWSER SNIFFING
// Kludges for bugs and behavior differences that can't be feature
// detected are enabled based on userAgent etc sniffing.
var zi=/gecko\/\d/i.test(navigator.userAgent),Pi=/MSIE \d/.test(navigator.userAgent),Fi=Pi&&(null==document.documentMode||document.documentMode<8),Ri=Pi&&(null==document.documentMode||document.documentMode<9),Bi=Pi&&(null==document.documentMode||document.documentMode<10),Gi=/Trident\/([7-9]|\d{2,})\./.test(navigator.userAgent),Vi=Pi||Gi,Ui=/WebKit\//.test(navigator.userAgent),Ki=Ui&&/Qt\/\d+\.\d+/.test(navigator.userAgent),_i=/Chrome\//.test(navigator.userAgent),Xi=/Opera\//.test(navigator.userAgent),Yi=/Apple Computer/.test(navigator.vendor),ji=/KHTML\//.test(navigator.userAgent),$i=/Mac OS X 1\d\D([8-9]|\d\d)\D/.test(navigator.userAgent),qi=/PhantomJS/.test(navigator.userAgent),Zi=/AppleWebKit/.test(navigator.userAgent)&&/Mobile\/\w+/.test(navigator.userAgent),Qi=Zi||/Android|webOS|BlackBerry|Opera Mini|Opera Mobi|IEMobile/i.test(navigator.userAgent),Ji=Zi||/Mac/.test(navigator.platform),eo=/win/i.test(navigator.platform),to=Xi&&navigator.userAgent.match(/Version\/(\d*\.\d*)/);to&&(to=Number(to[1])),to&&to>=15&&(Xi=!1,Ui=!0);
// Some browsers use the wrong event properties to signal cmd/ctrl on OS X
var ro=Ji&&(Ki||Xi&&(null==to||12.11>to)),no=zi||Vi&&!Ri,io=!1,oo=!1,lo=e.Pos=function(e,t){return this instanceof lo?(this.line=e,void(this.ch=t)):new lo(e,t)},so=e.cmpPos=function(e,t){return e.line-t.line||e.ch-t.ch};X.prototype={primary:function(){return this.ranges[this.primIndex]},equals:function(e){if(e==this)return!0;if(e.primIndex!=this.primIndex||e.ranges.length!=this.ranges.length)return!1;for(var t=0;t<this.ranges.length;t++){var r=this.ranges[t],n=e.ranges[t];if(0!=so(r.anchor,n.anchor)||0!=so(r.head,n.head))return!1}return!0},deepCopy:function(){for(var e=[],t=0;t<this.ranges.length;t++)e[t]=new Y(U(this.ranges[t].anchor),U(this.ranges[t].head));return new X(e,this.primIndex)},somethingSelected:function(){for(var e=0;e<this.ranges.length;e++)if(!this.ranges[e].empty())return!0;return!1},contains:function(e,t){t||(t=e);for(var r=0;r<this.ranges.length;r++){var n=this.ranges[r];if(so(t,n.from())>=0&&so(e,n.to())<=0)return r}return-1}},Y.prototype={from:function(){return _(this.anchor,this.head)},to:function(){return K(this.anchor,this.head)},empty:function(){return this.head.line==this.anchor.line&&this.head.ch==this.anchor.ch}};var ao,uo,co,fo={left:0,right:0,top:0,bottom:0},ho=0,po=0,go=0,vo=null;
// Fill in a browser-detected starting value on browsers where we
// know one. These don't have to be accurate -- the result of them
// being wrong would just be a slight flicker on the first wheel
// scroll (if it is large enough).
Vi?vo=-.53:zi?vo=15:_i?vo=-.7:Yi&&(vo=-1/3);var mo,yo=null,bo=e.changeEnd=function(e){return e.text?lo(e.from.line+e.text.length-1,ei(e.text).length+(1==e.text.length?e.from.ch:0)):e.to};
// EDITOR METHODS
// The publicly visible API. Note that methodOp(f) means
// 'wrap f in an operation, performed on its `this` parameter'.
// This is not the complete set of editor methods. Most of the
// methods defined on the Doc type are also injected into
// CodeMirror.prototype, for backwards compatibility and
// convenience.
e.prototype={constructor:e,focus:function(){window.focus(),dt(this),ct(this)},setOption:function(e,t){var r=this.options,n=r[e];(r[e]!=t||"mode"==e)&&(r[e]=t,wo.hasOwnProperty(e)&&Ze(this,wo[e])(this,t,n))},getOption:function(e){return this.options[e]},getDoc:function(){return this.doc},addKeyMap:function(e,t){this.state.keyMaps[t?"push":"unshift"](e)},removeKeyMap:function(e){for(var t=this.state.keyMaps,r=0;r<t.length;++r)if(t[r]==e||"string"!=typeof t[r]&&t[r].name==e)return t.splice(r,1),!0},addOverlay:Qe(function(t,r){var n=t.token?t:e.getMode(this.options,t);if(n.startState)throw new Error("Overlays may not be stateful.");this.state.overlays.push({mode:n,modeSpec:t,opaque:r&&r.opaque}),this.state.modeGen++,rt(this)}),removeOverlay:Qe(function(e){for(var t=this.state.overlays,r=0;r<t.length;++r){var n=t[r].modeSpec;if(n==e||"string"==typeof e&&n.name==e)return t.splice(r,1),this.state.modeGen++,void rt(this)}}),indentLine:Qe(function(e,t,r){"string"!=typeof t&&"number"!=typeof t&&(t=null==t?this.options.smartIndent?"smart":"prev":t?"add":"subtract"),J(this.doc,e)&&ar(this,e,t,r)}),indentSelection:Qe(function(e){for(var t=this.doc.sel.ranges,r=-1,n=0;n<t.length;n++){var i=t[n];if(i.empty())i.head.line>r&&(ar(this,i.head.line,e,!0),r=i.head.line,n==this.doc.sel.primIndex&&lr(this));else{var o=Math.max(r,i.from().line),l=i.to();r=Math.min(this.lastLine(),l.line-(l.ch?0:1))+1;for(var s=o;r>s;++s)ar(this,s,e)}}}),
// Fetch the parser token for a given character. Useful for hacks
// that want to inspect the mode state (say, for completion).
getTokenAt:function(e,t){var r=this.doc;e=Z(r,e);for(var n=we(this,e.line,t),i=this.doc.mode,o=bn(r,e.line),l=new Io(o.text,this.options.tabSize);l.pos<e.ch&&!l.eol();){l.start=l.pos;var s=Jr(i,l,n)}return{start:l.start,end:l.pos,string:l.current(),type:s||null,state:n}},getTokenTypeAt:function(e){e=Z(this.doc,e);var t,r=rn(this,bn(this.doc,e.line)),n=0,i=(r.length-1)/2,o=e.ch;if(0==o)t=r[2];else for(;;){var l=n+i>>1;if((l?r[2*l-1]:0)>=o)i=l;else{if(!(r[2*l+1]<o)){t=r[2*l+2];break}n=l+1}}var s=t?t.indexOf("cm-overlay "):-1;return 0>s?t:0==s?null:t.slice(0,s-1)},getModeAt:function(t){var r=this.doc.mode;return r.innerMode?e.innerMode(r,this.getTokenAt(t).state).mode:r},getHelper:function(e,t){return this.getHelpers(e,t)[0]},getHelpers:function(e,t){var r=[];if(!To.hasOwnProperty(t))return To;var n=To[t],i=this.getModeAt(e);if("string"==typeof i[t])n[i[t]]&&r.push(n[i[t]]);else if(i[t])for(var o=0;o<i[t].length;o++){var l=n[i[t][o]];l&&r.push(l)}else i.helperType&&n[i.helperType]?r.push(n[i.helperType]):n[i.name]&&r.push(n[i.name]);for(var o=0;o<n._global.length;o++){var s=n._global[o];s.pred(i,this)&&-1==ti(r,s.val)&&r.push(s.val)}return r},getStateAfter:function(e,t){var r=this.doc;return e=q(r,null==e?r.first+r.size-1:e),we(this,e+1,t)},cursorCoords:function(e,t){var r,n=this.doc.sel.primary();return r=null==e?n.head:"object"==typeof e?Z(this.doc,e):e?n.from():n.to(),Ge(this,r,t||"page")},charCoords:function(e,t){return Be(this,Z(this.doc,e),t||"page")},coordsChar:function(e,t){return e=Re(this,e,t||"page"),Ke(this,e.left,e.top)},lineAtHeight:function(e,t){return e=Re(this,{top:e,left:0},t||"page").top,Sn(this.doc,e+this.display.viewOffset)},heightAtLine:function(e,t){var r=!1,n=this.doc.first+this.doc.size-1;e<this.doc.first?e=this.doc.first:e>n&&(e=n,r=!0);var i=bn(this.doc,e);return Fe(this,i,{top:0,left:0},t||"page").top+(r?this.doc.height-kn(i):0)},defaultTextHeight:function(){return Xe(this.display)},defaultCharWidth:function(){return Ye(this.display)},setGutterMarker:Qe(function(e,t,r){return ur(this,e,"gutter",function(e){var n=e.gutterMarkers||(e.gutterMarkers={});return n[t]=r,!r&&si(n)&&(e.gutterMarkers=null),!0})}),clearGutter:Qe(function(e){var t=this,r=t.doc,n=r.first;r.iter(function(r){r.gutterMarkers&&r.gutterMarkers[e]&&(r.gutterMarkers[e]=null,nt(t,n,"gutter"),si(r.gutterMarkers)&&(r.gutterMarkers=null)),++n})}),addLineClass:Qe(function(e,t,r){return ur(this,e,"class",function(e){var n="text"==t?"textClass":"background"==t?"bgClass":"wrapClass";if(e[n]){if(new RegExp("(?:^|\\s)"+r+"(?:$|\\s)").test(e[n]))return!1;e[n]+=" "+r}else e[n]=r;return!0})}),removeLineClass:Qe(function(e,t,r){return ur(this,e,"class",function(e){var n="text"==t?"textClass":"background"==t?"bgClass":"wrapClass",i=e[n];if(!i)return!1;if(null==r)e[n]=null;else{var o=i.match(new RegExp("(?:^|\\s+)"+r+"(?:$|\\s+)"));if(!o)return!1;var l=o.index+o[0].length;e[n]=i.slice(0,o.index)+(o.index&&l!=i.length?" ":"")+i.slice(l)||null}return!0})}),addLineWidget:Qe(function(e,t,r){return jr(this,e,t,r)}),removeLineWidget:function(e){e.clear()},lineInfo:function(e){if("number"==typeof e){if(!J(this.doc,e))return null;var t=e;if(e=bn(this.doc,e),!e)return null}else{var t=Ln(e);if(null==t)return null}return{line:t,handle:e,text:e.text,gutterMarkers:e.gutterMarkers,textClass:e.textClass,bgClass:e.bgClass,wrapClass:e.wrapClass,widgets:e.widgets}},getViewport:function(){return{from:this.display.viewFrom,to:this.display.viewTo}},addWidget:function(e,t,r,n,i){var o=this.display;e=Ge(this,Z(this.doc,e));var l=e.bottom,s=e.left;if(t.style.position="absolute",o.sizer.appendChild(t),"over"==n)l=e.top;else if("above"==n||"near"==n){var a=Math.max(o.wrapper.clientHeight,this.doc.height),u=Math.max(o.sizer.clientWidth,o.lineSpace.clientWidth);
// Default to positioning above (if specified and possible); otherwise default to positioning below
("above"==n||e.bottom+t.offsetHeight>a)&&e.top>t.offsetHeight?l=e.top-t.offsetHeight:e.bottom+t.offsetHeight<=a&&(l=e.bottom),s+t.offsetWidth>u&&(s=u-t.offsetWidth)}t.style.top=l+"px",t.style.left=t.style.right="","right"==i?(s=o.sizer.clientWidth-t.offsetWidth,t.style.right="0px"):("left"==i?s=0:"middle"==i&&(s=(o.sizer.clientWidth-t.offsetWidth)/2),t.style.left=s+"px"),r&&nr(this,s,l,s+t.offsetWidth,l+t.offsetHeight)},triggerOnKeyDown:Qe(It),triggerOnKeyPress:Qe(Ft),triggerOnKeyUp:Qe(Pt),execCommand:function(e){return Ao.hasOwnProperty(e)?Ao[e](this):void 0},findPosH:function(e,t,r,n){var i=1;0>t&&(i=-1,t=-t);for(var o=0,l=Z(this.doc,e);t>o&&(l=fr(this.doc,l,i,r,n),!l.hitSide);++o);return l},moveH:Qe(function(e,t){var r=this;r.extendSelectionsBy(function(n){return r.display.shift||r.doc.extend||n.empty()?fr(r.doc,n.head,e,t,r.options.rtlMoveVisually):0>e?n.from():n.to()},ol)}),deleteH:Qe(function(e,t){var r=this.doc.sel,n=this.doc;r.somethingSelected()?n.replaceSelection("",null,"+delete"):cr(this,function(r){var i=fr(n,r.head,e,t,!1);return 0>e?{from:i,to:r.head}:{from:r.head,to:i}})}),findPosV:function(e,t,r,n){var i=1,o=n;0>t&&(i=-1,t=-t);for(var l=0,s=Z(this.doc,e);t>l;++l){var a=Ge(this,s,"div");if(null==o?o=a.left:a.left=o,s=hr(this,a,i,r),s.hitSide)break}return s},moveV:Qe(function(e,t){var r=this,n=this.doc,i=[],o=!r.display.shift&&!n.extend&&n.sel.somethingSelected();if(n.extendSelectionsBy(function(l){if(o)return 0>e?l.from():l.to();var s=Ge(r,l.head,"div");null!=l.goalColumn&&(s.left=l.goalColumn),i.push(s.left);var a=hr(r,s,e,t);return"page"==t&&l==n.sel.primary()&&or(r,null,Be(r,a,"div").top-s.top),a},ol),i.length)for(var l=0;l<n.sel.ranges.length;l++)n.sel.ranges[l].goalColumn=i[l]}),toggleOverwrite:function(e){(null==e||e!=this.state.overwrite)&&((this.state.overwrite=!this.state.overwrite)?vi(this.display.cursorDiv,"CodeMirror-overwrite"):gi(this.display.cursorDiv,"CodeMirror-overwrite"),Jo(this,"overwriteToggle",this,this.state.overwrite))},hasFocus:function(){return di()==this.display.input},scrollTo:Qe(function(e,t){(null!=e||null!=t)&&sr(this),null!=e&&(this.curOp.scrollLeft=e),null!=t&&(this.curOp.scrollTop=t)}),getScrollInfo:function(){var e=this.display.scroller,t=tl;return{left:e.scrollLeft,top:e.scrollTop,height:e.scrollHeight-t,width:e.scrollWidth-t,clientHeight:e.clientHeight-t,clientWidth:e.clientWidth-t}},scrollIntoView:Qe(function(e,t){if(null==e?(e={from:this.doc.sel.primary().head,to:null},null==t&&(t=this.options.cursorScrollMargin)):"number"==typeof e?e={from:lo(e,0),to:null}:null==e.from&&(e={from:e,to:null}),e.to||(e.to=e.from),e.margin=t||0,null!=e.from.line)sr(this),this.curOp.scrollToPos=e;else{var r=ir(this,Math.min(e.from.left,e.to.left),Math.min(e.from.top,e.to.top)-e.margin,Math.max(e.from.right,e.to.right),Math.max(e.from.bottom,e.to.bottom)+e.margin);this.scrollTo(r.scrollLeft,r.scrollTop)}}),setSize:Qe(function(e,t){function r(e){return"number"==typeof e||/^\d+$/.test(String(e))?e+"px":e}null!=e&&(this.display.wrapper.style.width=r(e)),null!=t&&(this.display.wrapper.style.height=r(t)),this.options.lineWrapping&&Ee(this),this.curOp.forceUpdate=!0,Jo(this,"refresh",this)}),operation:function(e){return qe(this,e)},refresh:Qe(function(){var e=this.display.cachedTextHeight;rt(this),this.curOp.forceUpdate=!0,Ie(this),this.scrollTo(this.doc.scrollLeft,this.doc.scrollTop),f(this),(null==e||Math.abs(e-Xe(this.display))>.5)&&l(this),Jo(this,"refresh",this)}),swapDoc:Qe(function(e){var t=this.doc;return t.cm=null,yn(this,e),Ie(this),ht(this),this.scrollTo(e.scrollLeft,e.scrollTop),_n(this,"swapDoc",this,t),t}),getInputField:function(){return this.display.input},getWrapperElement:function(){return this.display.wrapper},getScrollerElement:function(){return this.display.scroller},getGutterElement:function(){return this.display.gutters}},qn(e);
// OPTION DEFAULTS
// The default configuration options.
var xo=e.defaults={},wo=e.optionHandlers={},Co=e.Init={toString:function(){return"CodeMirror.Init"}};
// These two are, on init, called from the constructor because they
// have to be initialized before the editor can start at all.
pr("value","",function(e,t){e.setValue(t)},!0),pr("mode",null,function(e,t){e.doc.modeOption=t,r(e)},!0),pr("indentUnit",2,r,!0),pr("indentWithTabs",!1),pr("smartIndent",!0),pr("tabSize",4,function(e){n(e),Ie(e),rt(e)},!0),pr("specialChars",/[\t\u0000-\u0019\u00ad\u200b\u2028\u2029\ufeff]/g,function(e,t){e.options.specialChars=new RegExp(t.source+(t.test("	")?"":"|	"),"g"),e.refresh()},!0),pr("specialCharPlaceholder",sn,function(e){e.refresh()},!0),pr("electricChars",!0),pr("rtlMoveVisually",!eo),pr("wholeLineUpdateBefore",!0),pr("theme","default",function(e){a(e),u(e)},!0),pr("keyMap","default",s),pr("extraKeys",null),pr("lineWrapping",!1,i,!0),pr("gutters",[],function(e){p(e.options),u(e)},!0),pr("fixedGutter",!0,function(e,t){e.display.gutters.style.left=t?w(e.display)+"px":"0",e.refresh()},!0),pr("coverGutterNextToScrollbar",!1,v,!0),pr("lineNumbers",!1,function(e){p(e.options),u(e)},!0),pr("firstLineNumber",1,u,!0),pr("lineNumberFormatter",function(e){return e},u,!0),pr("showCursorWhenSelecting",!1,pe,!0),pr("resetSelectionOnContextMenu",!0),pr("readOnly",!1,function(e,t){"nocursor"==t?(Bt(e),e.display.input.blur(),e.display.disabled=!0):(e.display.disabled=!1,t||ht(e))}),pr("disableInput",!1,function(e,t){t||ht(e)},!0),pr("dragDrop",!0),pr("cursorBlinkRate",530),pr("cursorScrollMargin",0),pr("cursorHeight",1),pr("workTime",100),pr("workDelay",100),pr("flattenSpans",!0,n,!0),pr("addModeClass",!1,n,!0),pr("pollInterval",100),pr("undoDepth",200,function(e,t){e.doc.history.undoDepth=t}),pr("historyEventDelay",1250),pr("viewportMargin",10,function(e){e.refresh()},!0),pr("maxHighlightLength",1e4,n,!0),pr("moveInputWithCursor",!0,function(e,t){t||(e.display.inputDiv.style.top=e.display.inputDiv.style.left=0)}),pr("tabindex",null,function(e,t){e.display.input.tabIndex=t||""}),pr("autofocus",null);
// MODE DEFINITION AND QUERYING
// Known modes, by name and by MIME
var Lo=e.modes={},So=e.mimeModes={};
// Extra arguments are stored as the mode's dependencies, which is
// used by (legacy) mechanisms like loadmode.js to automatically
// load a mode. (Preferred mechanism is the require/define calls.)
e.defineMode=function(t,r){if(e.defaults.mode||"null"==t||(e.defaults.mode=t),arguments.length>2){r.dependencies=[];for(var n=2;n<arguments.length;++n)r.dependencies.push(arguments[n])}Lo[t]=r},e.defineMIME=function(e,t){So[e]=t},
// Given a MIME type, a {name, ...options} config object, or a name
// string, return a mode config object.
e.resolveMode=function(t){if("string"==typeof t&&So.hasOwnProperty(t))t=So[t];else if(t&&"string"==typeof t.name&&So.hasOwnProperty(t.name)){var r=So[t.name];"string"==typeof r&&(r={name:r}),t=ni(r,t),t.name=r.name}else if("string"==typeof t&&/^[\w\-]+\/[\w\-]+\+xml$/.test(t))return e.resolveMode("application/xml");return"string"==typeof t?{name:t}:t||{name:"null"}},
// Given a mode spec (anything that resolveMode accepts), find and
// initialize an actual mode object.
e.getMode=function(t,r){var r=e.resolveMode(r),n=Lo[r.name];if(!n)return e.getMode(t,"text/plain");var i=n(t,r);if(ko.hasOwnProperty(r.name)){var o=ko[r.name];for(var l in o)o.hasOwnProperty(l)&&(i.hasOwnProperty(l)&&(i["_"+l]=i[l]),i[l]=o[l])}if(i.name=r.name,r.helperType&&(i.helperType=r.helperType),r.modeProps)for(var l in r.modeProps)i[l]=r.modeProps[l];return i},
// Minimal default mode.
e.defineMode("null",function(){return{token:function(e){e.skipToEnd()}}}),e.defineMIME("text/plain","null");
// This can be used to attach properties to mode objects from
// outside the actual mode definition.
var ko=e.modeExtensions={};e.extendMode=function(e,t){var r=ko.hasOwnProperty(e)?ko[e]:ko[e]={};ii(t,r)},
// EXTENSIONS
e.defineExtension=function(t,r){e.prototype[t]=r},e.defineDocExtension=function(e,t){Ko.prototype[e]=t},e.defineOption=pr;var Mo=[];e.defineInitHook=function(e){Mo.push(e)};var To=e.helpers={};e.registerHelper=function(t,r,n){To.hasOwnProperty(t)||(To[t]=e[t]={_global:[]}),To[t][r]=n},e.registerGlobalHelper=function(t,r,n,i){e.registerHelper(t,r,i),To[t]._global.push({pred:n,val:i})};
// MODE STATE HANDLING
// Utility functions for working with state. Exported because nested
// modes need to do this for their inner modes.
var No=e.copyState=function(e,t){if(t===!0)return t;if(e.copyState)return e.copyState(t);var r={};for(var n in t){var i=t[n];i instanceof Array&&(i=i.concat([])),r[n]=i}return r},Ho=e.startState=function(e,t,r){return e.startState?e.startState(t,r):!0};
// Given a mode and a state (for that mode), find the inner mode and
// state at the position that the state refers to.
e.innerMode=function(e,t){for(;e.innerMode;){var r=e.innerMode(t);if(!r||r.mode==e)break;t=r.state,e=r.mode}return r||{mode:e,state:t}};
// STANDARD COMMANDS
// Commands are parameter-less actions that can be performed on an
// editor, mostly used for keybindings.
var Ao=e.commands={selectAll:function(e){e.setSelection(lo(e.firstLine(),0),lo(e.lastLine()),nl)},singleSelection:function(e){e.setSelection(e.getCursor("anchor"),e.getCursor("head"),nl)},killLine:function(e){cr(e,function(t){if(t.empty()){var r=bn(e.doc,t.head.line).text.length;return t.head.ch==r&&t.head.line<e.lastLine()?{from:t.head,to:lo(t.head.line+1,0)}:{from:t.head,to:lo(t.head.line,r)}}return{from:t.from(),to:t.to()}})},deleteLine:function(e){cr(e,function(t){return{from:lo(t.from().line,0),to:Z(e.doc,lo(t.to().line+1,0))}})},delLineLeft:function(e){cr(e,function(e){return{from:lo(e.from().line,0),to:e.from()}})},undo:function(e){e.undo()},redo:function(e){e.redo()},undoSelection:function(e){e.undoSelection()},redoSelection:function(e){e.redoSelection()},goDocStart:function(e){e.extendSelection(lo(e.firstLine(),0))},goDocEnd:function(e){e.extendSelection(lo(e.lastLine()))},goLineStart:function(e){e.extendSelectionsBy(function(t){return Hi(e,t.head.line)},ol)},goLineStartSmart:function(e){e.extendSelectionsBy(function(t){var r=Hi(e,t.head.line),n=e.getLineHandle(r.line),i=Mn(n);if(!i||0==i[0].level){var o=Math.max(0,n.text.search(/\S/)),l=t.head.line==r.line&&t.head.ch<=o&&t.head.ch;return lo(r.line,l?0:o)}return r},ol)},goLineEnd:function(e){e.extendSelectionsBy(function(t){return Ai(e,t.head.line)},ol)},goLineRight:function(e){e.extendSelectionsBy(function(t){var r=e.charCoords(t.head,"div").top+5;return e.coordsChar({left:e.display.lineDiv.offsetWidth+100,top:r},"div")},ol)},goLineLeft:function(e){e.extendSelectionsBy(function(t){var r=e.charCoords(t.head,"div").top+5;return e.coordsChar({left:0,top:r},"div")},ol)},goLineUp:function(e){e.moveV(-1,"line")},goLineDown:function(e){e.moveV(1,"line")},goPageUp:function(e){e.moveV(-1,"page")},goPageDown:function(e){e.moveV(1,"page")},goCharLeft:function(e){e.moveH(-1,"char")},goCharRight:function(e){e.moveH(1,"char")},goColumnLeft:function(e){e.moveH(-1,"column")},goColumnRight:function(e){e.moveH(1,"column")},goWordLeft:function(e){e.moveH(-1,"word")},goGroupRight:function(e){e.moveH(1,"group")},goGroupLeft:function(e){e.moveH(-1,"group")},goWordRight:function(e){e.moveH(1,"word")},delCharBefore:function(e){e.deleteH(-1,"char")},delCharAfter:function(e){e.deleteH(1,"char")},delWordBefore:function(e){e.deleteH(-1,"word")},delWordAfter:function(e){e.deleteH(1,"word")},delGroupBefore:function(e){e.deleteH(-1,"group")},delGroupAfter:function(e){e.deleteH(1,"group")},indentAuto:function(e){e.indentSelection("smart")},indentMore:function(e){e.indentSelection("add")},indentLess:function(e){e.indentSelection("subtract")},insertTab:function(e){e.replaceSelection("	")},insertSoftTab:function(e){for(var t=[],r=e.listSelections(),n=e.options.tabSize,i=0;i<r.length;i++){var o=r[i].from(),l=ll(e.getLine(o.line),o.ch,n);t.push(new Array(n-l%n+1).join(" "))}e.replaceSelections(t)},defaultTab:function(e){e.somethingSelected()?e.indentSelection("add"):e.execCommand("insertTab")},transposeChars:function(e){qe(e,function(){for(var t=e.listSelections(),r=[],n=0;n<t.length;n++){var i=t[n].head,o=bn(e.doc,i.line).text;if(o)if(i.ch==o.length&&(i=new lo(i.line,i.ch-1)),i.ch>0)i=new lo(i.line,i.ch+1),e.replaceRange(o.charAt(i.ch-1)+o.charAt(i.ch-2),lo(i.line,i.ch-2),i,"+transpose");else if(i.line>e.doc.first){var l=bn(e.doc,i.line-1).text;l&&e.replaceRange(o.charAt(0)+"\n"+l.charAt(l.length-1),lo(i.line-1,l.length-1),lo(i.line,1),"+transpose")}r.push(new Y(i,i))}e.setSelections(r)})},newlineAndIndent:function(e){qe(e,function(){for(var t=e.listSelections().length,r=0;t>r;r++){var n=e.listSelections()[r];e.replaceRange("\n",n.anchor,n.head,"+input"),e.indentLine(n.from().line+1,null,!0),lr(e)}})},toggleOverwrite:function(e){e.toggleOverwrite()}},Oo=e.keyMap={};Oo.basic={Left:"goCharLeft",Right:"goCharRight",Up:"goLineUp",Down:"goLineDown",End:"goLineEnd",Home:"goLineStartSmart",PageUp:"goPageUp",PageDown:"goPageDown",Delete:"delCharAfter",Backspace:"delCharBefore","Shift-Backspace":"delCharBefore",Tab:"defaultTab","Shift-Tab":"indentAuto",Enter:"newlineAndIndent",Insert:"toggleOverwrite",Esc:"singleSelection"},
// Note that the save and find-related commands aren't defined by
// default. User code or addons can define them. Unknown commands
// are simply ignored.
Oo.pcDefault={"Ctrl-A":"selectAll","Ctrl-D":"deleteLine","Ctrl-Z":"undo","Shift-Ctrl-Z":"redo","Ctrl-Y":"redo","Ctrl-Home":"goDocStart","Ctrl-Up":"goDocStart","Ctrl-End":"goDocEnd","Ctrl-Down":"goDocEnd","Ctrl-Left":"goGroupLeft","Ctrl-Right":"goGroupRight","Alt-Left":"goLineStart","Alt-Right":"goLineEnd","Ctrl-Backspace":"delGroupBefore","Ctrl-Delete":"delGroupAfter","Ctrl-S":"save","Ctrl-F":"find","Ctrl-G":"findNext","Shift-Ctrl-G":"findPrev","Shift-Ctrl-F":"replace","Shift-Ctrl-R":"replaceAll","Ctrl-[":"indentLess","Ctrl-]":"indentMore","Ctrl-U":"undoSelection","Shift-Ctrl-U":"redoSelection","Alt-U":"redoSelection",fallthrough:"basic"},Oo.macDefault={"Cmd-A":"selectAll","Cmd-D":"deleteLine","Cmd-Z":"undo","Shift-Cmd-Z":"redo","Cmd-Y":"redo","Cmd-Up":"goDocStart","Cmd-End":"goDocEnd","Cmd-Down":"goDocEnd","Alt-Left":"goGroupLeft","Alt-Right":"goGroupRight","Cmd-Left":"goLineStart","Cmd-Right":"goLineEnd","Alt-Backspace":"delGroupBefore","Ctrl-Alt-Backspace":"delGroupAfter","Alt-Delete":"delGroupAfter","Cmd-S":"save","Cmd-F":"find","Cmd-G":"findNext","Shift-Cmd-G":"findPrev","Cmd-Alt-F":"replace","Shift-Cmd-Alt-F":"replaceAll","Cmd-[":"indentLess","Cmd-]":"indentMore","Cmd-Backspace":"delLineLeft","Cmd-U":"undoSelection","Shift-Cmd-U":"redoSelection",fallthrough:["basic","emacsy"]},
// Very basic readline/emacs-style bindings, which are standard on Mac.
Oo.emacsy={"Ctrl-F":"goCharRight","Ctrl-B":"goCharLeft","Ctrl-P":"goLineUp","Ctrl-N":"goLineDown","Alt-F":"goWordRight","Alt-B":"goWordLeft","Ctrl-A":"goLineStart","Ctrl-E":"goLineEnd","Ctrl-V":"goPageDown","Shift-Ctrl-V":"goPageUp","Ctrl-D":"delCharAfter","Ctrl-H":"delCharBefore","Alt-D":"delWordAfter","Alt-Backspace":"delWordBefore","Ctrl-K":"killLine","Ctrl-T":"transposeChars"},Oo["default"]=Ji?Oo.macDefault:Oo.pcDefault;
// Given an array of keymaps and a key name, call handle on any
// bindings found, until that returns a truthy value, at which point
// we consider the key handled. Implements things like binding a key
// to false stopping further handling and keymap fallthrough.
var Wo=e.lookupKey=function(e,t,r){function n(t){t=gr(t);var i=t[e];if(i===!1)return"stop";if(null!=i&&r(i))return!0;if(t.nofallthrough)return"stop";var o=t.fallthrough;if(null==o)return!1;if("[object Array]"!=Object.prototype.toString.call(o))return n(o);for(var l=0;l<o.length;++l){var s=n(o[l]);if(s)return s}return!1}for(var i=0;i<t.length;++i){var o=n(t[i]);if(o)return"stop"!=o}},Do=e.isModifierKey=function(e){var t=wl[e.keyCode];return"Ctrl"==t||"Alt"==t||"Shift"==t||"Mod"==t},Eo=e.keyName=function(e,t){if(Xi&&34==e.keyCode&&e["char"])return!1;var r=wl[e.keyCode];return null==r||e.altGraphKey?!1:(e.altKey&&(r="Alt-"+r),(ro?e.metaKey:e.ctrlKey)&&(r="Ctrl-"+r),(ro?e.ctrlKey:e.metaKey)&&(r="Cmd-"+r),!t&&e.shiftKey&&(r="Shift-"+r),r)};
// FROMTEXTAREA
e.fromTextArea=function(t,r){function n(){t.value=u.getValue()}
// Set autofocus to true if this textarea is focused, or if it has
// autofocus and no other element is focused.
if(r||(r={}),r.value=t.value,!r.tabindex&&t.tabindex&&(r.tabindex=t.tabindex),!r.placeholder&&t.placeholder&&(r.placeholder=t.placeholder),null==r.autofocus){var i=di();r.autofocus=i==t||null!=t.getAttribute("autofocus")&&i==document.body}if(t.form&&(Zo(t.form,"submit",n),!r.leaveSubmitMethodAlone)){var o=t.form,l=o.submit;try{var s=o.submit=function(){n(),o.submit=l,o.submit(),o.submit=s}}catch(a){}}t.style.display="none";var u=e(function(e){t.parentNode.insertBefore(e,t.nextSibling)},r);return u.save=n,u.getTextArea=function(){return t},u.toTextArea=function(){n(),t.parentNode.removeChild(u.getWrapperElement()),t.style.display="",t.form&&(Qo(t.form,"submit",n),"function"==typeof t.form.submit&&(t.form.submit=l))},u};
// STRING STREAM
// Fed to the mode parsers, provides helper functions to make
// parsers more succinct.
var Io=e.StringStream=function(e,t){this.pos=this.start=0,this.string=e,this.tabSize=t||8,this.lastColumnPos=this.lastColumnValue=0,this.lineStart=0};Io.prototype={eol:function(){return this.pos>=this.string.length},sol:function(){return this.pos==this.lineStart},peek:function(){return this.string.charAt(this.pos)||void 0},next:function(){return this.pos<this.string.length?this.string.charAt(this.pos++):void 0},eat:function(e){var t=this.string.charAt(this.pos);if("string"==typeof e)var r=t==e;else var r=t&&(e.test?e.test(t):e(t));return r?(++this.pos,t):void 0},eatWhile:function(e){for(var t=this.pos;this.eat(e););return this.pos>t},eatSpace:function(){for(var e=this.pos;/[\s\u00a0]/.test(this.string.charAt(this.pos));)++this.pos;return this.pos>e},skipToEnd:function(){this.pos=this.string.length},skipTo:function(e){var t=this.string.indexOf(e,this.pos);return t>-1?(this.pos=t,!0):void 0},backUp:function(e){this.pos-=e},column:function(){return this.lastColumnPos<this.start&&(this.lastColumnValue=ll(this.string,this.start,this.tabSize,this.lastColumnPos,this.lastColumnValue),this.lastColumnPos=this.start),this.lastColumnValue-(this.lineStart?ll(this.string,this.lineStart,this.tabSize):0)},indentation:function(){return ll(this.string,null,this.tabSize)-(this.lineStart?ll(this.string,this.lineStart,this.tabSize):0)},match:function(e,t,r){if("string"!=typeof e){var n=this.string.slice(this.pos).match(e);return n&&n.index>0?null:(n&&t!==!1&&(this.pos+=n[0].length),n)}var i=function(e){return r?e.toLowerCase():e},o=this.string.substr(this.pos,e.length);return i(o)==i(e)?(t!==!1&&(this.pos+=e.length),!0):void 0},current:function(){return this.string.slice(this.start,this.pos)},hideFirstChars:function(e,t){this.lineStart+=e;try{return t()}finally{this.lineStart-=e}}};
// TEXTMARKERS
// Created with markText and setBookmark methods. A TextMarker is a
// handle that can be used to clear or find a marked position in the
// document. Line objects hold arrays (markedSpans) containing
// {from, to, marker} object pointing to such marker objects, and
// indicating that such a marker is present on that line. Multiple
// lines may point to the same marker when it spans across lines.
// The spans will have null for their from/to properties when the
// marker continues beyond the start/end of the line. Markers have
// links back to the lines they currently touch.
var zo=e.TextMarker=function(e,t){this.lines=[],this.type=t,this.doc=e};qn(zo),
// Clear the marker.
zo.prototype.clear=function(){if(!this.explicitlyCleared){var e=this.doc.cm,t=e&&!e.curOp;if(t&&je(e),$n(this,"clear")){var r=this.find();r&&_n(this,"clear",r.from,r.to)}for(var n=null,i=null,o=0;o<this.lines.length;++o){var l=this.lines[o],s=Cr(l.markedSpans,this);e&&!this.collapsed?nt(e,Ln(l),"text"):e&&(null!=s.to&&(i=Ln(l)),null!=s.from&&(n=Ln(l))),l.markedSpans=Lr(l.markedSpans,s),null==s.from&&this.collapsed&&!Kr(this.doc,l)&&e&&Cn(l,Xe(e.display))}if(e&&this.collapsed&&!e.options.lineWrapping)for(var o=0;o<this.lines.length;++o){var a=Br(this.lines[o]),u=h(a);u>e.display.maxLineLength&&(e.display.maxLine=a,e.display.maxLineLength=u,e.display.maxLineChanged=!0)}null!=n&&e&&this.collapsed&&rt(e,n,i+1),this.lines.length=0,this.explicitlyCleared=!0,this.atomic&&this.doc.cantEdit&&(this.doc.cantEdit=!1,e&&fe(e.doc)),e&&_n(e,"markerCleared",e,this),t&&$e(e),this.parent&&this.parent.clear()}},
// Find the position of the marker in the document. Returns a {from,
// to} object by default. Side can be passed to get a specific side
// -- 0 (both), -1 (left), or 1 (right). When lineObj is true, the
// Pos objects returned contain a line object, rather than a line
// number (used to prevent looking up the same line twice).
zo.prototype.find=function(e,t){null==e&&"bookmark"==this.type&&(e=1);for(var r,n,i=0;i<this.lines.length;++i){var o=this.lines[i],l=Cr(o.markedSpans,this);if(null!=l.from&&(r=lo(t?o:Ln(o),l.from),-1==e))return r;if(null!=l.to&&(n=lo(t?o:Ln(o),l.to),1==e))return n}return r&&{from:r,to:n}},
// Signals that the marker's widget changed, and surrounding layout
// should be recomputed.
zo.prototype.changed=function(){var e=this.find(-1,!0),t=this,r=this.doc.cm;e&&r&&qe(r,function(){var n=e.line,i=Ln(e.line),o=He(r,i);if(o&&(De(o),r.curOp.selectionChanged=r.curOp.forceUpdate=!0),r.curOp.updateMaxLine=!0,!Kr(t.doc,n)&&null!=t.height){var l=t.height;t.height=null;var s=Yr(t)-l;s&&Cn(n,n.height+s)}})},zo.prototype.attachLine=function(e){if(!this.lines.length&&this.doc.cm){var t=this.doc.cm.curOp;t.maybeHiddenMarkers&&-1!=ti(t.maybeHiddenMarkers,this)||(t.maybeUnhiddenMarkers||(t.maybeUnhiddenMarkers=[])).push(this)}this.lines.push(e)},zo.prototype.detachLine=function(e){if(this.lines.splice(ti(this.lines,e),1),!this.lines.length&&this.doc.cm){var t=this.doc.cm.curOp;(t.maybeHiddenMarkers||(t.maybeHiddenMarkers=[])).push(this)}};
// Collapsed markers have unique ids, in order to be able to order
// them, which is needed for uniquely determining an outer marker
// when they overlap (they may nest, but not partially overlap).
var Po=0,Fo=e.SharedTextMarker=function(e,t){this.markers=e,this.primary=t;for(var r=0;r<e.length;++r)e[r].parent=this};qn(Fo),Fo.prototype.clear=function(){if(!this.explicitlyCleared){this.explicitlyCleared=!0;for(var e=0;e<this.markers.length;++e)this.markers[e].clear();_n(this,"clear")}},Fo.prototype.find=function(e,t){return this.primary.find(e,t)};
// LINE WIDGETS
// Line widgets are block elements displayed above or below a line.
var Ro=e.LineWidget=function(e,t,r){if(r)for(var n in r)r.hasOwnProperty(n)&&(this[n]=r[n]);this.cm=e,this.node=t};qn(Ro),Ro.prototype.clear=function(){var e=this.cm,t=this.line.widgets,r=this.line,n=Ln(r);if(null!=n&&t){for(var i=0;i<t.length;++i)t[i]==this&&t.splice(i--,1);t.length||(r.widgets=null);var o=Yr(this);qe(e,function(){Xr(e,r,-o),nt(e,n,"widget"),Cn(r,Math.max(0,r.height-o))})}},Ro.prototype.changed=function(){var e=this.height,t=this.cm,r=this.line;this.height=null;var n=Yr(this)-e;n&&qe(t,function(){t.curOp.forceUpdate=!0,Xr(t,r,n),Cn(r,r.height+n)})};
// LINE DATA STRUCTURE
// Line objects. These hold state related to a line, including
// highlighting info (the styles array).
var Bo=e.Line=function(e,t,r){this.text=e,Wr(this,t),this.height=r?r(this):1};qn(Bo),Bo.prototype.lineNo=function(){return Ln(this)};
// Convert a style as returned by a mode (either null, or a string
// containing one or more styles) to a CSS style. This is cached,
// and also looks for line-wide styles.
var Go={},Vo={};gn.prototype={chunkSize:function(){return this.lines.length},
// Remove the n lines at offset 'at'.
removeInner:function(e,t){for(var r=e,n=e+t;n>r;++r){var i=this.lines[r];this.height-=i.height,qr(i),_n(i,"delete")}this.lines.splice(e,t)},
// Helper used to collapse a small branch into a single leaf.
collapse:function(e){e.push.apply(e,this.lines)},
// Insert the given array of lines at offset 'at', count them as
// having the given height.
insertInner:function(e,t,r){this.height+=r,this.lines=this.lines.slice(0,e).concat(t).concat(this.lines.slice(e));for(var n=0;n<t.length;++n)t[n].parent=this},
// Used to iterate over a part of the tree.
iterN:function(e,t,r){for(var n=e+t;n>e;++e)if(r(this.lines[e]))return!0}},vn.prototype={chunkSize:function(){return this.size},removeInner:function(e,t){this.size-=t;for(var r=0;r<this.children.length;++r){var n=this.children[r],i=n.chunkSize();if(i>e){var o=Math.min(t,i-e),l=n.height;if(n.removeInner(e,o),this.height-=l-n.height,i==o&&(this.children.splice(r--,1),n.parent=null),0==(t-=o))break;e=0}else e-=i}
// If the result is smaller than 25 lines, ensure that it is a
// single leaf node.
if(this.size-t<25&&(this.children.length>1||!(this.children[0]instanceof gn))){var s=[];this.collapse(s),this.children=[new gn(s)],this.children[0].parent=this}},collapse:function(e){for(var t=0;t<this.children.length;++t)this.children[t].collapse(e)},insertInner:function(e,t,r){this.size+=t.length,this.height+=r;for(var n=0;n<this.children.length;++n){var i=this.children[n],o=i.chunkSize();if(o>=e){if(i.insertInner(e,t,r),i.lines&&i.lines.length>50){for(;i.lines.length>50;){var l=i.lines.splice(i.lines.length-25,25),s=new gn(l);i.height-=s.height,this.children.splice(n+1,0,s),s.parent=this}this.maybeSpill()}break}e-=o}},
// When a node has grown, check whether it should be split.
maybeSpill:function(){if(!(this.children.length<=10)){var e=this;do{var t=e.children.splice(e.children.length-5,5),r=new vn(t);if(e.parent){e.size-=r.size,e.height-=r.height;var n=ti(e.parent.children,e);e.parent.children.splice(n+1,0,r)}else{// Become the parent node
var i=new vn(e.children);i.parent=e,e.children=[i,r],e=i}r.parent=e.parent}while(e.children.length>10);e.parent.maybeSpill()}},iterN:function(e,t,r){for(var n=0;n<this.children.length;++n){var i=this.children[n],o=i.chunkSize();if(o>e){var l=Math.min(t,o-e);if(i.iterN(e,l,r))return!0;if(0==(t-=l))break;e=0}else e-=o}}};var Uo=0,Ko=e.Doc=function(e,t,r){if(!(this instanceof Ko))return new Ko(e,t,r);null==r&&(r=0),vn.call(this,[new gn([new Bo("",null)])]),this.first=r,this.scrollTop=this.scrollLeft=0,this.cantEdit=!1,this.cleanGeneration=1,this.frontier=r;var n=lo(r,0);this.sel=$(n),this.history=new Tn(null),this.id=++Uo,this.modeOption=t,"string"==typeof e&&(e=yl(e)),pn(this,{from:n,to:n,text:e}),ae(this,$(n),nl)};Ko.prototype=ni(vn.prototype,{constructor:Ko,
// Iterate over the document. Supports two forms -- with only one
// argument, it calls that for each line in the document. With
// three, it iterates over the range given by the first two (with
// the second being non-inclusive).
iter:function(e,t,r){r?this.iterN(e-this.first,t-e,r):this.iterN(this.first,this.first+this.size,e)},
// Non-public interface for adding and removing lines.
insert:function(e,t){for(var r=0,n=0;n<t.length;++n)r+=t[n].height;this.insertInner(e-this.first,t,r)},remove:function(e,t){this.removeInner(e-this.first,t)},
// From here, the methods are part of the public interface. Most
// are also available from CodeMirror (editor) instances.
getValue:function(e){var t=wn(this,this.first,this.first+this.size);return e===!1?t:t.join(e||"\n")},setValue:Je(function(e){var t=lo(this.first,0),r=this.first+this.size-1;jt(this,{from:t,to:lo(r,bn(this,r).text.length),text:yl(e),origin:"setValue"},!0),ae(this,$(t))}),replaceRange:function(e,t,r,n){t=Z(this,t),r=r?Z(this,r):t,er(this,e,t,r,n)},getRange:function(e,t,r){var n=xn(this,Z(this,e),Z(this,t));return r===!1?n:n.join(r||"\n")},getLine:function(e){var t=this.getLineHandle(e);return t&&t.text},getLineHandle:function(e){return J(this,e)?bn(this,e):void 0},getLineNumber:function(e){return Ln(e)},getLineHandleVisualStart:function(e){return"number"==typeof e&&(e=bn(this,e)),Br(e)},lineCount:function(){return this.size},firstLine:function(){return this.first},lastLine:function(){return this.first+this.size-1},clipPos:function(e){return Z(this,e)},getCursor:function(e){var t,r=this.sel.primary();return t=null==e||"head"==e?r.head:"anchor"==e?r.anchor:"end"==e||"to"==e||e===!1?r.to():r.from()},listSelections:function(){return this.sel.ranges},somethingSelected:function(){return this.sel.somethingSelected()},setCursor:Je(function(e,t,r){oe(this,Z(this,"number"==typeof e?lo(e,t||0):e),null,r)}),setSelection:Je(function(e,t,r){oe(this,Z(this,e),Z(this,t||e),r)}),extendSelection:Je(function(e,t,r){re(this,Z(this,e),t&&Z(this,t),r)}),extendSelections:Je(function(e,t){ne(this,ee(this,e,t))}),extendSelectionsBy:Je(function(e,t){ne(this,ri(this.sel.ranges,e),t)}),setSelections:Je(function(e,t,r){if(e.length){for(var n=0,i=[];n<e.length;n++)i[n]=new Y(Z(this,e[n].anchor),Z(this,e[n].head));null==t&&(t=Math.min(e.length-1,this.sel.primIndex)),ae(this,j(i,t),r)}}),addSelection:Je(function(e,t,r){var n=this.sel.ranges.slice(0);n.push(new Y(Z(this,e),Z(this,t||e))),ae(this,j(n,n.length-1),r)}),getSelection:function(e){for(var t,r=this.sel.ranges,n=0;n<r.length;n++){var i=xn(this,r[n].from(),r[n].to());t=t?t.concat(i):i}return e===!1?t:t.join(e||"\n")},getSelections:function(e){for(var t=[],r=this.sel.ranges,n=0;n<r.length;n++){var i=xn(this,r[n].from(),r[n].to());e!==!1&&(i=i.join(e||"\n")),t[n]=i}return t},replaceSelection:function(e,t,r){for(var n=[],i=0;i<this.sel.ranges.length;i++)n[i]=e;this.replaceSelections(n,t,r||"+input")},replaceSelections:Je(function(e,t,r){for(var n=[],i=this.sel,o=0;o<i.ranges.length;o++){var l=i.ranges[o];n[o]={from:l.from(),to:l.to(),text:yl(e[o]),origin:r}}for(var s=t&&"end"!=t&&Xt(this,n,t),o=n.length-1;o>=0;o--)jt(this,n[o]);s?se(this,s):this.cm&&lr(this.cm)}),undo:Je(function(){qt(this,"undo")}),redo:Je(function(){qt(this,"redo")}),undoSelection:Je(function(){qt(this,"undo",!0)}),redoSelection:Je(function(){qt(this,"redo",!0)}),setExtending:function(e){this.extend=e},getExtending:function(){return this.extend},historySize:function(){for(var e=this.history,t=0,r=0,n=0;n<e.done.length;n++)e.done[n].ranges||++t;for(var n=0;n<e.undone.length;n++)e.undone[n].ranges||++r;return{undo:t,redo:r}},clearHistory:function(){this.history=new Tn(this.history.maxGeneration)},markClean:function(){this.cleanGeneration=this.changeGeneration(!0)},changeGeneration:function(e){return e&&(this.history.lastOp=this.history.lastOrigin=null),this.history.generation},isClean:function(e){return this.history.generation==(e||this.cleanGeneration)},getHistory:function(){return{done:Fn(this.history.done),undone:Fn(this.history.undone)}},setHistory:function(e){var t=this.history=new Tn(this.history.maxGeneration);t.done=Fn(e.done.slice(0),null,!0),t.undone=Fn(e.undone.slice(0),null,!0)},markText:function(e,t,r){return vr(this,Z(this,e),Z(this,t),r,"range")},setBookmark:function(e,t){var r={replacedWith:t&&(null==t.nodeType?t.widget:t),insertLeft:t&&t.insertLeft,clearWhenEmpty:!1,shared:t&&t.shared};return e=Z(this,e),vr(this,e,e,r,"bookmark")},findMarksAt:function(e){e=Z(this,e);var t=[],r=bn(this,e.line).markedSpans;if(r)for(var n=0;n<r.length;++n){var i=r[n];(null==i.from||i.from<=e.ch)&&(null==i.to||i.to>=e.ch)&&t.push(i.marker.parent||i.marker)}return t},findMarks:function(e,t,r){e=Z(this,e),t=Z(this,t);var n=[],i=e.line;return this.iter(e.line,t.line+1,function(o){var l=o.markedSpans;if(l)for(var s=0;s<l.length;s++){var a=l[s];i==e.line&&e.ch>a.to||null==a.from&&i!=e.line||i==t.line&&a.from>t.ch||r&&!r(a.marker)||n.push(a.marker.parent||a.marker)}++i}),n},getAllMarks:function(){var e=[];return this.iter(function(t){var r=t.markedSpans;if(r)for(var n=0;n<r.length;++n)null!=r[n].from&&e.push(r[n].marker)}),e},posFromIndex:function(e){var t,r=this.first;return this.iter(function(n){var i=n.text.length+1;return i>e?(t=e,!0):(e-=i,void++r)}),Z(this,lo(r,t))},indexFromPos:function(e){e=Z(this,e);var t=e.ch;return e.line<this.first||e.ch<0?0:(this.iter(this.first,e.line,function(e){t+=e.text.length+1}),t)},copy:function(e){var t=new Ko(wn(this,this.first,this.first+this.size),this.modeOption,this.first);return t.scrollTop=this.scrollTop,t.scrollLeft=this.scrollLeft,t.sel=this.sel,t.extend=!1,e&&(t.history.undoDepth=this.history.undoDepth,t.setHistory(this.getHistory())),t},linkedDoc:function(e){e||(e={});var t=this.first,r=this.first+this.size;null!=e.from&&e.from>t&&(t=e.from),null!=e.to&&e.to<r&&(r=e.to);var n=new Ko(wn(this,t,r),e.mode||this.modeOption,t);return e.sharedHist&&(n.history=this.history),(this.linked||(this.linked=[])).push({doc:n,sharedHist:e.sharedHist}),n.linked=[{doc:this,isParent:!0,sharedHist:e.sharedHist}],br(n,yr(this)),n},unlinkDoc:function(t){if(t instanceof e&&(t=t.doc),this.linked)for(var r=0;r<this.linked.length;++r){var n=this.linked[r];if(n.doc==t){this.linked.splice(r,1),t.unlinkDoc(this),xr(yr(this));break}}
// If the histories were shared, split them again
if(t.history==this.history){var i=[t.id];mn(t,function(e){i.push(e.id)},!0),t.history=new Tn(null),t.history.done=Fn(this.history.done,i),t.history.undone=Fn(this.history.undone,i)}},iterLinkedDocs:function(e){mn(this,e)},getMode:function(){return this.mode},getEditor:function(){return this.cm}}),
// Public alias.
Ko.prototype.eachLine=Ko.prototype.iter;
// Set up methods on CodeMirror's prototype to redirect to the editor's document.
var _o="iter insert remove copy getEditor".split(" ");for(var Xo in Ko.prototype)Ko.prototype.hasOwnProperty(Xo)&&ti(_o,Xo)<0&&(e.prototype[Xo]=function(e){return function(){return e.apply(this.doc,arguments)}}(Ko.prototype[Xo]));qn(Ko);
// EVENT UTILITIES
// Due to the fact that we still support jurassic IE versions, some
// compatibility wrappers are needed.
var Yo,jo=e.e_preventDefault=function(e){e.preventDefault?e.preventDefault():e.returnValue=!1},$o=e.e_stopPropagation=function(e){e.stopPropagation?e.stopPropagation():e.cancelBubble=!0},qo=e.e_stop=function(e){jo(e),$o(e)},Zo=e.on=function(e,t,r){if(e.addEventListener)e.addEventListener(t,r,!1);else if(e.attachEvent)e.attachEvent("on"+t,r);else{var n=e._handlers||(e._handlers={}),i=n[t]||(n[t]=[]);i.push(r)}},Qo=e.off=function(e,t,r){if(e.removeEventListener)e.removeEventListener(t,r,!1);else if(e.detachEvent)e.detachEvent("on"+t,r);else{var n=e._handlers&&e._handlers[t];if(!n)return;for(var i=0;i<n.length;++i)if(n[i]==r){n.splice(i,1);break}}},Jo=e.signal=function(e,t){var r=e._handlers&&e._handlers[t];if(r)for(var n=Array.prototype.slice.call(arguments,2),i=0;i<r.length;++i)r[i].apply(null,n)},el=0,tl=30,rl=e.Pass={toString:function(){return"CodeMirror.Pass"}},nl={scroll:!1},il={origin:"*mouse"},ol={origin:"+move"};Zn.prototype.set=function(e,t){clearTimeout(this.id),this.id=setTimeout(t,e)};
// Counts the column offset in a string, taking tabs into account.
// Used mostly to find indentation.
var ll=e.countColumn=function(e,t,r,n,i){null==t&&(t=e.search(/[^\s\u00a0]/),-1==t&&(t=e.length));for(var o=n||0,l=i||0;;){var s=e.indexOf("	",o);if(0>s||s>=t)return l+(t-o);l+=s-o,l+=r-l%r,o=s+1}},sl=[""],al=function(e){e.select()};Zi?// Mobile Safari apparently has a bug where select() is broken.
al=function(e){e.selectionStart=0,e.selectionEnd=e.value.length}:Vi&&(// Suppress mysterious IE10 errors
al=function(e){try{e.select()}catch(t){}}),[].indexOf&&(ti=function(e,t){return e.indexOf(t)}),[].map&&(ri=function(e,t){return e.map(t)});var ul,cl=/[\u00df\u3040-\u309f\u30a0-\u30ff\u3400-\u4db5\u4e00-\u9fcc\uac00-\ud7af]/,fl=e.isWordChar=function(e){return/\w/.test(e)||e>""&&(e.toUpperCase()!=e.toLowerCase()||cl.test(e))},hl=/[\u0300-\u036f\u0483-\u0489\u0591-\u05bd\u05bf\u05c1\u05c2\u05c4\u05c5\u05c7\u0610-\u061a\u064b-\u065e\u0670\u06d6-\u06dc\u06de-\u06e4\u06e7\u06e8\u06ea-\u06ed\u0711\u0730-\u074a\u07a6-\u07b0\u07eb-\u07f3\u0816-\u0819\u081b-\u0823\u0825-\u0827\u0829-\u082d\u0900-\u0902\u093c\u0941-\u0948\u094d\u0951-\u0955\u0962\u0963\u0981\u09bc\u09be\u09c1-\u09c4\u09cd\u09d7\u09e2\u09e3\u0a01\u0a02\u0a3c\u0a41\u0a42\u0a47\u0a48\u0a4b-\u0a4d\u0a51\u0a70\u0a71\u0a75\u0a81\u0a82\u0abc\u0ac1-\u0ac5\u0ac7\u0ac8\u0acd\u0ae2\u0ae3\u0b01\u0b3c\u0b3e\u0b3f\u0b41-\u0b44\u0b4d\u0b56\u0b57\u0b62\u0b63\u0b82\u0bbe\u0bc0\u0bcd\u0bd7\u0c3e-\u0c40\u0c46-\u0c48\u0c4a-\u0c4d\u0c55\u0c56\u0c62\u0c63\u0cbc\u0cbf\u0cc2\u0cc6\u0ccc\u0ccd\u0cd5\u0cd6\u0ce2\u0ce3\u0d3e\u0d41-\u0d44\u0d4d\u0d57\u0d62\u0d63\u0dca\u0dcf\u0dd2-\u0dd4\u0dd6\u0ddf\u0e31\u0e34-\u0e3a\u0e47-\u0e4e\u0eb1\u0eb4-\u0eb9\u0ebb\u0ebc\u0ec8-\u0ecd\u0f18\u0f19\u0f35\u0f37\u0f39\u0f71-\u0f7e\u0f80-\u0f84\u0f86\u0f87\u0f90-\u0f97\u0f99-\u0fbc\u0fc6\u102d-\u1030\u1032-\u1037\u1039\u103a\u103d\u103e\u1058\u1059\u105e-\u1060\u1071-\u1074\u1082\u1085\u1086\u108d\u109d\u135f\u1712-\u1714\u1732-\u1734\u1752\u1753\u1772\u1773\u17b7-\u17bd\u17c6\u17c9-\u17d3\u17dd\u180b-\u180d\u18a9\u1920-\u1922\u1927\u1928\u1932\u1939-\u193b\u1a17\u1a18\u1a56\u1a58-\u1a5e\u1a60\u1a62\u1a65-\u1a6c\u1a73-\u1a7c\u1a7f\u1b00-\u1b03\u1b34\u1b36-\u1b3a\u1b3c\u1b42\u1b6b-\u1b73\u1b80\u1b81\u1ba2-\u1ba5\u1ba8\u1ba9\u1c2c-\u1c33\u1c36\u1c37\u1cd0-\u1cd2\u1cd4-\u1ce0\u1ce2-\u1ce8\u1ced\u1dc0-\u1de6\u1dfd-\u1dff\u200c\u200d\u20d0-\u20f0\u2cef-\u2cf1\u2de0-\u2dff\u302a-\u302f\u3099\u309a\ua66f-\ua672\ua67c\ua67d\ua6f0\ua6f1\ua802\ua806\ua80b\ua825\ua826\ua8c4\ua8e0-\ua8f1\ua926-\ua92d\ua947-\ua951\ua980-\ua982\ua9b3\ua9b6-\ua9b9\ua9bc\uaa29-\uaa2e\uaa31\uaa32\uaa35\uaa36\uaa43\uaa4c\uaab0\uaab2-\uaab4\uaab7\uaab8\uaabe\uaabf\uaac1\uabe5\uabe8\uabed\udc00-\udfff\ufb1e\ufe00-\ufe0f\ufe20-\ufe26\uff9e\uff9f]/;ul=document.createRange?function(e,t,r){var n=document.createRange();return n.setEnd(e,r),n.setStart(e,t),n}:function(e,t,r){var n=document.body.createTextRange();return n.moveToElementText(e.parentNode),n.collapse(!0),n.moveEnd("character",r),n.moveStart("character",t),n},Pi&&(di=function(){try{return document.activeElement}catch(e){return document.body}});var dl,pl,gl,vl=!1,ml=function(){
// There is *some* kind of drag-and-drop support in IE6-8, but I
// couldn't get it to work yet.
if(Ri)return!1;var e=ui("div");return"draggable"in e||"dragDrop"in e}(),yl=e.splitLines=3!="\n\nb".split(/\n/).length?function(e){for(var t=0,r=[],n=e.length;n>=t;){var i=e.indexOf("\n",t);-1==i&&(i=e.length);var o=e.slice(t,"\r"==e.charAt(i-1)?i-1:i),l=o.indexOf("\r");-1!=l?(r.push(o.slice(0,l)),t+=l+1):(r.push(o),t=i+1)}return r}:function(e){return e.split(/\r\n?|\n/)},bl=window.getSelection?function(e){try{return e.selectionStart!=e.selectionEnd}catch(t){return!1}}:function(e){try{var t=e.ownerDocument.selection.createRange()}catch(r){}return t&&t.parentElement()==e?0!=t.compareEndPoints("StartToEnd",t):!1},xl=function(){var e=ui("div");return"oncopy"in e?!0:(e.setAttribute("oncopy","return;"),"function"==typeof e.oncopy)}(),wl={3:"Enter",8:"Backspace",9:"Tab",13:"Enter",16:"Shift",17:"Ctrl",18:"Alt",19:"Pause",20:"CapsLock",27:"Esc",32:"Space",33:"PageUp",34:"PageDown",35:"End",36:"Home",37:"Left",38:"Up",39:"Right",40:"Down",44:"PrintScrn",45:"Insert",46:"Delete",59:";",61:"=",91:"Mod",92:"Mod",93:"Mod",107:"=",109:"-",127:"Delete",173:"-",186:";",187:"=",188:",",189:"-",190:".",191:"/",192:"`",219:"[",220:"\\",221:"]",222:"'",63232:"Up",63233:"Down",63234:"Left",63235:"Right",63272:"Delete",63273:"Home",63275:"End",63276:"PageUp",63277:"PageDown",63302:"Insert"};e.keyNames=wl,function(){
// Number keys
for(var e=0;10>e;e++)wl[e+48]=wl[e+96]=String(e);
// Alphabetic keys
for(var e=65;90>=e;e++)wl[e]=String.fromCharCode(e);
// Function keys
for(var e=1;12>=e;e++)wl[e+111]=wl[e+63235]="F"+e}();var Cl,Ll=function(){function e(e){return 247>=e?r.charAt(e):e>=1424&&1524>=e?"R":e>=1536&&1773>=e?n.charAt(e-1536):e>=1774&&2220>=e?"r":e>=8192&&8203>=e?"w":8204==e?"b":"L"}function t(e,t,r){this.level=e,this.from=t,this.to=r}
// Character types for codepoints 0 to 0xff
var r="bbbbbbbbbtstwsbbbbbbbbbbbbbbssstwNN%%%NNNNNN,N,N1111111111NNNNNNNLLLLLLLLLLLLLLLLLLLLLLLLLLNNNNNNLLLLLLLLLLLLLLLLLLLLLLLLLLNNNNbbbbbbsbbbbbbbbbbbbbbbbbbbbbbbbbb,N%%%%NNNNLNNNNN%%11NLNNN1LNNNNNLLLLLLLLLLLLLLLLLLLLLLLNLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLN",n="rrrrrrrrrrrr,rNNmmmmmmrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrmmmmmmmmmmmmmmrrrrrrrnnnnnnnnnn%nnrrrmrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrmmmmmmmmmmmmmmmmmmmNmmmm",i=/[\u0590-\u05f4\u0600-\u06ff\u0700-\u08ac]/,o=/[stwN]/,l=/[LRr]/,s=/[Lb1n]/,a=/[1n]/,u="L";return function(r){if(!i.test(r))return!1;for(var n,c=r.length,f=[],h=0;c>h;++h)f.push(n=e(r.charCodeAt(h)));
// W1. Examine each non-spacing mark (NSM) in the level run, and
// change the type of the NSM to the type of the previous
// character. If the NSM is at the start of the level run, it will
// get the type of sor.
for(var h=0,d=u;c>h;++h){var n=f[h];"m"==n?f[h]=d:d=n}
// W2. Search backwards from each instance of a European number
// until the first strong type (R, L, AL, or sor) is found. If an
// AL is found, change the type of the European number to Arabic
// number.
// W3. Change all ALs to R.
for(var h=0,p=u;c>h;++h){var n=f[h];"1"==n&&"r"==p?f[h]="n":l.test(n)&&(p=n,"r"==n&&(f[h]="R"))}
// W4. A single European separator between two European numbers
// changes to a European number. A single common separator between
// two numbers of the same type changes to that type.
for(var h=1,d=f[0];c-1>h;++h){var n=f[h];"+"==n&&"1"==d&&"1"==f[h+1]?f[h]="1":","!=n||d!=f[h+1]||"1"!=d&&"n"!=d||(f[h]=d),d=n}
// W5. A sequence of European terminators adjacent to European
// numbers changes to all European numbers.
// W6. Otherwise, separators and terminators change to Other
// Neutral.
for(var h=0;c>h;++h){var n=f[h];if(","==n)f[h]="N";else if("%"==n){for(var g=h+1;c>g&&"%"==f[g];++g);for(var v=h&&"!"==f[h-1]||c>g&&"1"==f[g]?"1":"N",m=h;g>m;++m)f[m]=v;h=g-1}}
// W7. Search backwards from each instance of a European number
// until the first strong type (R, L, or sor) is found. If an L is
// found, then change the type of the European number to L.
for(var h=0,p=u;c>h;++h){var n=f[h];"L"==p&&"1"==n?f[h]="L":l.test(n)&&(p=n)}
// N1. A sequence of neutrals takes the direction of the
// surrounding strong text if the text on both sides has the same
// direction. European and Arabic numbers act as if they were R in
// terms of their influence on neutrals. Start-of-level-run (sor)
// and end-of-level-run (eor) are used at level run boundaries.
// N2. Any remaining neutrals take the embedding direction.
for(var h=0;c>h;++h)if(o.test(f[h])){for(var g=h+1;c>g&&o.test(f[g]);++g);for(var y="L"==(h?f[h-1]:u),b="L"==(c>g?f[g]:u),v=y||b?"L":"R",m=h;g>m;++m)f[m]=v;h=g-1}for(var x,w=[],h=0;c>h;)if(s.test(f[h])){var C=h;for(++h;c>h&&s.test(f[h]);++h);w.push(new t(0,C,h))}else{var L=h,S=w.length;for(++h;c>h&&"L"!=f[h];++h);for(var m=L;h>m;)if(a.test(f[m])){m>L&&w.splice(S,0,new t(1,L,m));var k=m;for(++m;h>m&&a.test(f[m]);++m);w.splice(S,0,new t(2,k,m)),L=m}else++m;h>L&&w.splice(S,0,new t(1,L,h))}return 1==w[0].level&&(x=r.match(/^\s+/))&&(w[0].from=x[0].length,w.unshift(new t(0,0,x[0].length))),1==ei(w).level&&(x=r.match(/\s+$/))&&(ei(w).to-=x[0].length,w.push(new t(0,c-x[0].length,c))),w[0].level!=ei(w).level&&w.push(new t(w[0].level,c,c)),w}}();
// THE END
return e.version="4.2.0",e});