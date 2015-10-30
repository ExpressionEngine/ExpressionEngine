/*  WysiHat - WYSIWYG JavaScript framework, version 0.2.1
 *  (c) 2008-2010 Joshua Peek
 *  JQ-WysiHat - jQuery port of WysiHat to run on jQuery
 *  (c) 2010 Scott Williams & Aaron Gustafson
 *  EL-WysiHat - Extensive rewrite of JQ-WysiHat for ExpressionEngine
 *  (c) 2012 EllisLab, Inc.
 *
 *  WysiHat is freely distributable under the terms of an MIT-style license.
 *--------------------------------------------------------------------------*/
!function(e,t,n){
// ---------------------------------------------------------------------
/**
 * This file is rather lengthy, so I've organized it into rough
 * sections. I suggest reading the documentation for each section
 * to get a general idea of where things happen. The list below
 * are headers (except for #1) so that you can search for them.
 *
 * Core Namespace
 * Editor Class
 * Element Manager
 * Change Events
 * Paste Handler
 * Key Helper
 * Event Class
 * Undo Class
 * Selection Utility
 * Editor Commands
 * Commands Mixin
 * Formatting Class
 * Blank Button
 * Toolbar Class
 * Defaults and jQuery Binding
 * Browser Compat Classes
 */
// ---------------------------------------------------------------------
/**
 * WysiHat Namespace
 *
 * Tracks registered buttons and provides the basic setup function.
 * Usually the latter should be called through $.fn.wysihat instead.
 */
var i=window.WysiHat={name:"WysiHat",/**
	 * Add a button
	 *
	 * This does not mean it will be displayed,
	 * it only means that it will be a valid button
	 * option in $.fn.wysihat.
	 */
addButton:function(e,t){this._buttons[e]=t},/**
	 * Attach WysiHat to a field
	 *
	 * This is what makes it all happen. Most of the
	 * time you will want to use the jQuery.fn version
	 * though:
	 * $(textarea).wysihat(options);
	 */
attach:function(e,n){return new i.Editor(t(e),n)},/**
	 * Simple Prototypal Inheritance
	 *
	 * Acts a lot like ES5 object.create with the addition
	 * of a <parent> property on the child which contains
	 * proxied versions of the parent *methods*. Giving us easy
	 * extending if we want it (we do).
	 *
	 * @todo bad place for this, it looks like you can extend wysihat
	 */
inherit:function(e,n){function i(){var n;
// Proxy the parent methods to get .parent working
this.parent={};for(n in e)e.hasOwnProperty(n)&&(this.parent[n]=t.proxy(e[n],this))}var r,o;i.prototype=e,o=new i;
// No hasOwn check here. If you pass an object with
// a prototype as props, then you're a JavascrHipster.
for(r in n)o[r]=n[r];return o},/**
	 * Available buttons.
	 * Don't touch it, use addButton above.
	 */
_buttons:[]};
// ---------------------------------------------------------------------
/**
 * WysiHat.Editor
 *
 * The parent class of the editor. Instantiating it gets the whole
 * snafu going. Holds the textarea and editor objects as well as
 * all of the utility classes.
 */
// ---------------------------------------------------------------------
i.Editor=function(e,t){this.$field=e.hide(),this.$editor=this.create(),e.before(this.$editor),this.createWrapper(),this.Element=i.Element,this.Commands=i.Commands,this.Formatting=i.Formatting,this.init(t)},i.Editor.prototype={/**
	 * Special empty entity so that we always have
	 * paragraph tags to work with.
	 */
_emptyChar:String.fromCharCode(8203),_empty:function(){return"<p>"+this._emptyChar+"</p>"},isEmpty:function(){return html=this.$editor.html(),""==html||"\x00"==html||"<br>"==html||"<br/>"==html||"<p></p>"==html||"<p><br></p>"==html||"<p>\x00</p>"==html||html==this._empty()?!0:!1},/**
	 * Create the main editor html
	 */
create:function(){return t("<div/>",{"class":i.name+"-editor",data:{wysihat:this,field:this.$field},role:"application",contentEditable:"true",
// Respect textarea's existing row count settings
height:this.$field.height(),
// Text direction
dir:this.$field.attr("dir"),html:i.Formatting.getBrowserMarkupFrom(this.$field)})},/**
	 * Wrap everything up so that we can do things
	 * like the image overlay without crazy hacks.
	 */
createWrapper:function(){var e=this;this.$field.add(this.$editor).wrapAll(t("<div/>",{"class":i.name+"-container",
// keep sizes in sync
mouseup:function(){e.$field.is(":visible")?e.$editor.height(e.$field.outerHeight()):e.$editor.is(":visible")&&e.$field.height(e.$editor.outerHeight())}}))},/**
	 * Setup all of the utility classes
	 */
init:function(e){var n=this.$editor,r=this;this.Undo=new i.Undo,this.Selection=new i.Selection(n),this.Event=new i.Event(this),this.Toolbar=new i.Toolbar(n,e.buttons),this.$field.change(t.proxy(this,"updateEditor")),
// if, on submit or autosave, the editor is active, we
// need to sync to the field before sending the data
n.closest("form").on("submit entry:autosave",function(){
// Instead of checking to see if the $editor is visible,
// we check to see if the $field is NOT visible to account
// cases where the editor may be hidden in a dynamic layout
r.$field.is(":visible")||r.updateField()})},/**
	 * Update the editor's textarea
	 *
	 * Syncs the editor and its field from the editor's content.
	 */
updateField:function(){this.$field.val(i.Formatting.getApplicationMarkupFrom(this.$editor))},/**
	 * Update the editor contents
	 *
	 * Syncs the editor and its field from the fields's content.
	 */
updateEditor:function(){this.$editor.html(i.Formatting.getBrowserMarkupFrom(this.$field)),this.selectEmptyParagraph()},/**
	 * Select Empty Paragraph
	 *
	 * Makes sure we actually have a paragraph to put our cursor in
	 * when the editor is completely empty.
	 */
selectEmptyParagraph:function(){var n,i=this.$editor,r=(i.html(),window.getSelection());this.isEmpty()&&(i.html(this._empty()),n=e.createRange(),r.removeAllRanges(),n.selectNodeContents(i.find("p").get(0)),t.browser.mozilla&&i.find("p").eq(0).html(""),r.addRange(n))}},i.Editor.constructor=i.Editor,
// ---------------------------------------------------------------------
/**
 * Element Manager
 *
 * Holds information about available elements and can be used to
 * check if an element is of a valid type.
 */
// ---------------------------------------------------------------------
i.Element=function(){function e(e){for(var t=arguments.length,n=!1;0==n&&t-->1;)n=e.is(arguments[t].join(","));return n}
// @todo add tr somewhere
var t=["blockquote","details","fieldset","figure","td"],n=["article","aside","header","footer","nav","section"],i=["blockquote","details","dl","ol","table","ul"],r=["dd","dt","li","summary","td","th"],o=["address","caption","dd","div","dt","figcaption","figure","h1","h2","h3","h4","h5","h6","hgroup","hr","p","pre","summary","small"],s=["audio","canvas","embed","iframe","img","object","param","source","track","video"],a=["a","abbr","b","br","cite","code","del","dfn","em","i","ins","kbd","mark","span","q","samp","s","strong","sub","sup","time","u","var","wbr"],l=["b","code","del","em","i","ins","kbd","span","s","strong","u","font"],d=["address","blockquote","div","dd","dt","h1","h2","h3","h4","h5","h6","p","pre"],c=["button","datalist","fieldset","form","input","keygen","label","legend","optgroup","option","output","select","textarea"];return{isRoot:function(n){return e(n,t)},isSection:function(t){return e(t,n)},isContainer:function(t){return e(t,i)},isSubContainer:function(t){return e(t,r)},isBlock:function(s){return e(s,t,n,i,r,o)},isHTML4Block:function(t){return e(t,d)},isContentElement:function(t){return e(t,r,o)},isMediaElement:function(t){return e(t,s)},isPhraseElement:function(t){return e(t,a)},isFormatter:function(t){return e(t,l)},isFormComponent:function(t){return e(t,c)},getRoots:function(){return t},getSections:function(e){return n},getContainers:function(){return i},getSubContainers:function(){return r},getBlocks:function(){return t.concat(n,i,r,o)},getHTML4Blocks:function(){return d},getContentElements:function(){return r.concat(o)},getMediaElements:function(){return s},getPhraseElements:function(){return a},getFormatters:function(){return l},getFormComponents:function(){return c}}}(),
// ---------------------------------------------------------------------
/**
 * Change Events
 *
 * Binds to various events to fire things such fieldChange and
 * editorChange. Currently also handles browser insertion for
 * empty events.
 *
 * Will probably be removed in favor of a real event system.
 */
// ---------------------------------------------------------------------
t(e).ready(function(){var n,i,r=t(e);"onselectionchange"in e&&"selection"in e?(i=function(){var n=e.selection.createRange(),i=n.parentElement();t(i).trigger("WysiHat-selection:change")},r.on("selectionchange",i)):(i=function(){var i,r,o=e.activeElement,s=o.tagName.toLowerCase();if("textarea"==s||"input"==s)n=null;else{if(i=window.getSelection(),i.rangeCount<1)return;if(r=i.getRangeAt(0),r&&r.equalRange(n))return;for(n=r,o=r.commonAncestorContainer;o.nodeType==Node.TEXT_NODE;)o=o.parentNode}t(o).trigger("WysiHat-selection:change")},r.mouseup(i),r.keyup(i))}),
// ---------------------------------------------------------------------
/**
 * Paste Handler
 *
 * A paste helper utility. How this works, is that browsers will
 * fire paste before actually inserting the text. So that we can
 * quickly create a new contentEditable object that is outside the
 * viewport. Focus it. And the text will go in there. That makes
 * it much easier for us to clean up.
 */
// ---------------------------------------------------------------------
i.Paster=function(){
// helper element to do cleanup on
var n=t('<div id="paster" contentEditable="true"/>').css({width:"100%",height:10,position:"absolute",left:-9999}),r=50,o=200;return{getHandler:function(s){return function(a,l){var d=s.Commands.getRanges(),c=d[0].startContainer,h=0;return n.html("").css("top",t(e).scrollTop()),n.appendTo(e.body),n.focus(),setTimeout(function u(){
// slow browser? wait a little longer
if(!n.html()&&(h+=r,o>h))return void setTimeout(u,r);var e=t(c).closest(i.Element.getBlocks().join(","));e.length?s.Formatting.cleanupPaste(n,e.get(0).tagName):s.Formatting.cleanupPaste(n),s.$editor.focus(),s.Commands.restoreRanges(d),
// attempt to clear out the range, this is necessary if they
// select and paste. The browsers will still report the old contents.
d[0].deleteContents?d[0].deleteContents():s.Commands.insertHTML(""),s.isEmpty()&&
// on an empty editor we want to completely replace
// otherwise the first paragraph gets munged
s.selectEmptyParagraph(),s.Commands.insertHTML(n.html());
// The final cleanup pass will inevitably lose the selection
// as it removes garbage from the markup.
var a=s.Selection.get();
// This is basically a final cleanup pass. I wanted to avoid
// running these since they touch the whole editor and not just
// the pasted bits, but these methods are great at removing
// markup cruft. So here we are.
s.updateField(),s.updateEditor(),s.Selection.set(a),n=n.remove(),l()},r),!1}}}}();
// ---------------------------------------------------------------------
/**
 * Key Helper
 *
 * Small utility that holds key values and common shortcuts.
 */
// ---------------------------------------------------------------------
var r,o;r=function(){
// numbers
for(var e={3:"enter",8:"backspace",9:"tab",13:"enter",16:"shift",17:"ctrl",18:"alt",27:"esc",32:"space",37:"left",38:"up",39:"right",40:"down",46:"delete",91:"mod",92:"mod",93:"mod",
// argh
59:";",186:";",187:"=",188:",",189:"-",190:".",191:"/",192:"`",219:"[",220:"\\",221:"]",222:"'",63232:"up",63233:"down",63234:"left",63235:"right",63272:"delete"},t=0;10>t;t++)e[t+48]=String(t);
// letters
for(var t=65;90>=t;t++)e[t]=String.fromCharCode(t);return e}(),o=function(){var e=/AppleWebKit/.test(navigator.userAgent)&&/Mobile\/\w+/.test(navigator.userAgent),t=e||/Mac/.test(navigator.platform),n=t?"cmd":"ctrl";return{cut:n+"-x",copy:n+"-c",paste:n+"-v",undo:n+"-z",redo:n+"-shift-z",bold:n+"-b",italics:n+"-i",underline:n+"-u"}}(),i.Event=function(e){this.Editor=e,this.$editor=e.$editor,this.eventHandlers=[],this.textStart=null,this.pasteStart=null,this.textDeleting=!1,this.Undo=e.Undo,this.Selection=e.Selection,this._hijack_events(),this.add("paste",i.Paster.getHandler(e))},i.Event.prototype={add:function(e,t){this.eventHandlers[e]=t},has:function(e){return e in this.eventHandlers},run:function(e,t,n){var i=this.eventHandlers[e](t,n);i!==!1&&n()},fire:function(e){var n,i,r=this;if(this._saveTextState(e),"undo"==e||"redo"==e){var o,s="undo"==e?"hasUndo":"hasRedo";return void(this.Undo[s]()&&(o=this.Undo[e](this.$editor.html()),this.$editor.html(o[0]),this.Selection.set(o[1])))}return this.has(e)?(n=this.getState(),i=function(){this.hasRun||(this.hasRun=!0,r.textChange(n),r._saveTextState(e),r.$editor.focus())},void this.run(e,n,t.proxy(i,i))):!0},textChange:function(e,t){t=t||this.getState(),this.Editor.selectEmptyParagraph(),this.Undo.push(e.html,t.html,e.selection,t.selection),this.$editor.closest("form").trigger("entry:startAutosave")},isKeyCombo:function(e,t){var n="",i="",o=e.indexOf("-")>-1;return t.altGraphKey?!1:(t.metaKey&&(n+="cmd-"),t.altKey&&(n+="alt-"),t.ctrlKey&&(n+="ctrl-"),t.shiftKey&&(n+="shift-"),!o&&e.length>1?n.replace(/-$/,"")==e:(i=r[t.keyCode],i?e.toLowerCase()==(n+i).toLowerCase():!1))},isEvent:function(e,t){var n=t.type;if(n==e)return!0;if("key"!=n.substr(0,3))return!1;var i=o[e];return i?this.isKeyCombo(i,t):!1},getState:function(){return{html:this.$editor.html(),selection:this.Selection.get()}},_saveTextState:function(e){"redo"!=e&&this.textStart&&(this.textChange(this.textStart),this.textStart=null)},_hijack_events:function(){var e={"selectionchange focusin mousedown":t.proxy(this._rangeEvent,this),"keydown keyup keypress":t.proxy(this._keyEvent,this),"cut undo redo paste input contextmenu":t.proxy(this._menuEvent,this),focus:t.proxy(this._focusEvent,this)};this.$editor.on(e)},_focusEvent:function(){this.Editor.isEmpty()&&this.Editor.selectEmptyParagraph()},_keyComboEvent:function(e){var t,n=["undo","redo","paste"];if("keydown"==e.type)for(;t=n.shift();)if(this.isEvent(t,e))return"paste"==t?(this.fire(t),!0):(e.preventDefault(),this.fire(t),!1);return!0},_keyEvent:function(e){if("keypress"==e.type)return!0;if(e.ctrlKey||e.altKey||e.metaKey)return this._keyComboEvent(e);if("keydown"==e.type)"backspace"==r[e.keyCode]?0==this.textDeleting&&(this.textDeleting=!0,this._saveTextState("backspace")):1==this.textDeleting&&(this.textDeleting=!1,this._saveTextState("keypress")),null==this.textStart&&(this.textStart=this.getState());else if("keyup"==e.type)switch(r[e.keyCode]){case"up":case"down":case"left":case"right":this._saveTextState("keyup")}},_rangeEvent:function(e){this._saveTextState(e.type)},_menuEvent:function(e){for(var t,n=["undo","redo","paste"];t=n.shift();)this.isEvent(t,e)&&("paste"!=t&&e.preventDefault(),this.fire(t))}},i.Event.constructor=i.Event,i.Undo=function(){this.max_depth=75,this.saved=[],this.index=0},i.Undo.prototype={push:function(e,n,i,r){var o=[],s=this;o=t.isArray(e)?t.map(e,function(e,t){return s._diff(e,n[t])}):this._diff(e,n),o&&(this.index<this.saved.length&&(this.saved=this.saved.slice(0,this.index),this.index=this.saved.length),this.saved.length>this.max_depth&&(this.saved=this.saved.slice(this.saved.length-this.max_depth),this.index=this.saved.length),this.index++,this.saved.push({changes:o,selection:[i,r]}))},undo:function(e){this.index--;for(var t=this.saved[this.index],n=t.changes,i=n.length,r=0;i>r;r++)change=n[r],e=e.substring(0,change[0])+change[1]+e.substring(change[0]+change[2].length);return[e,t.selection[0]]},redo:function(e){for(var t=this.saved[this.index],n=t.changes,i=n.length,r=i-1;r>=0;r--)change=n[r],e=e.substring(0,change[0])+change[2]+e.substring(change[0]+change[1].length);return this.index++,[e,t.selection[1]]},hasUndo:function(){return 0!=this.index},hasRedo:function(){return this.index!=this.saved.length},_diff:function(e,t){var n,i=e.length,r=t.length,o=0,s=0;if(e==t)return null;for(;i>o&&r>o&&e[o]==t[o];)o++;for(;i>s&&r>s&&e[i-s-1]==t[r-s-1];)s++;return o==Math.min(i,r)&&(o=0),s==Math.min(i,r)&&(s=0),(o||s)&&(e=e.substring(o,i-s+1),t=t.substring(o,r-s+1),i=e.length,r=t.length),i!==r&&(n=r>i?t.indexOf(e):e.indexOf(t),n>-1)?r>i?[[o,"",t.substr(0,n)],[o+i,"",t.substr(n+i)]]:[[o,e.substr(0,n),""],[o+n+r,e.substr(n+r),""]]:[[o,e,t]]}},i.Undo.constructor=i.Undo,i.Selection=function(e){this.$editor=e,this.top=this.$editor.get(0)},i.Selection.prototype={_replace:new RegExp("[\r\n]","g"),get:function(t){var i,r,o=window.getSelection(),s=e.createRange();if(t===n){if(!o.rangeCount)return[0,0];t=o.getRangeAt(0)}return i=t.toString().replace(this._replace,"").length,s.setStart(this.top,0),s.setEnd(t.startContainer,t.startOffset),r=s.toString().replace(this._replace,"").length,[r,r+i]},set:function(i,r){t.isArray(i)&&(r=i[1],i=i[0]);var o,s,a=window.getSelection(),l=e.createRange();o=this._getOffsetNode(this.top,i,!0),l.setStart.apply(l,o),r===n||r==i?(r=i,l.collapse(!0)):(s=this._getOffsetNode(this.top,r,!1),l.setEnd.apply(l,s)),a.removeAllRanges(),a.addRange(l)},toString:function(e){var t=window.getSelection();return e===n&&(e=t.getRangeAt(0)),e.toString()},_getOffsetNode:function(e,n,r){function o(e){if(e.nodeType==Node.TEXT_NODE||e.nodeType==Node.CDATA_SECTION_NODE)n>0&&(s=e,n-=e.nodeValue.replace(/\n/g,"").length);else for(var t=0,i=e.childNodes.length;n>0&&i>t;++t)o(e.childNodes[t])}var s=e,a=0,l=this.$editor.get(0).lastChild,d=i.Element.getBlocks();if(o(e),0==n){if(s.nodeType!=Node.TEXT_NODE){for(;null!==s.firstChild;)s=s.firstChild;return[s,0]}if(r){for(var c=0;null===s.nextSibling&&s.parentNode!==l;)c++,s=s.parentNode;for(t.inArray(s.nodeName.toLowerCase(),d)>-1&&null!==s.nextSibling&&(s=s.nextSibling);c&&s.firstChild&&"br"!=s.firstChild.nodeName.toLowerCase();)c--,s=s.firstChild}}return a=s.nodeValue?s.nodeValue.length:0,[s,a+n]}},i.Selection.constructor=i.Selection,i.Commands=function(){var n={is:{},make:{}},i={makeEasy:["bold","underline","italic","strikethrough","fontname","fontsize","forecolor","createLink","insertImage","insertOrderedList","insertUnorderedList"],isSelectors:{bold:"b, strong",italic:"i, em",link:"a[href]",underline:"u, ins",indented:"blockquote",strikethrough:"s, del",orderedList:"ol",unorderedList:"ul"},isNativeState:{bold:"bold",italic:"italic",underline:"underline",strikethrough:"strikethrough",orderedList:"insertOrderedList",unorderedList:"insertUnorderedList"}};t.each(i.makeEasy,function(e,t){n.make[t]=function(e){n.execCommand(t,!1,e)}}),t.each(i.isSelectors,function(t,r){t in i.isNativeState?n.is[t]=function(){return n.selectionIsWithin(r)||e.queryCommandState(i.isNativeState[t])}:n.is[t]=function(){return n.selectionIsWithin(r)}});var r={is:{linked:"link",underlined:"underline",struckthrough:"strikethrough",ol:"orderedList",ul:"unorderedList"},make:{italicize:"italic",font:"fontname",color:"forecolor",link:"createLink",ol:"insertOrderedList",ul:"insertUnorderedList",orderedList:"insertOrderedList",unorderedList:"insertUnorderedList",align:"alignment"}};return t.each(r.is,function(e,t){n.is[e]=function(){return n.is[t]()}}),t.each(r.make,function(e,i){n.make[e]=t.proxy(n.make,i)}),n.noSpans=function(){try{return e.execCommand("styleWithCSS",0,!1),function(){e.execCommand("styleWithCSS",0,!1)}}catch(n){try{return e.execCommand("useCSS",0,!0),function(){e.execCommand("useCSS",0,!0)}}catch(n){try{return e.execCommand("styleWithCSS",!1,!1),function(){e.execCommand("styleWithCSS",!1,!1)}}catch(n){return t.noop}}}}(),n}(),t.extend(i.Commands,{_blockElements:i.Element.getContentElements().join(",").replace(",div,",",div:not(."+i.name+"-editor),"),styleSelectors:{fontname:"fontFamily",fontsize:"fontSize",forecolor:"color",hilitecolor:"backgroundColor",backcolor:"backgroundColor"},validCommands:["backColor","bold","createLink","fontName","fontSize","foreColor","hiliteColor","italic","removeFormat","strikethrough","subscript","superscript","underline","unlink","delete","formatBlock","forwardDelete","indent","insertHorizontalRule","insertHTML","insertImage","insertLineBreak","insertOrderedList","insertParagraph","insertText","insertUnorderedList","justifyCenter","justifyFull","justifyLeft","justifyRight","outdent","copy","cut","paste","selectAll","styleWithCSS","useCSS"],execCommand:function(t,n,i){this.noSpans();try{e.execCommand(t,n,i)}catch(r){return null}},isMakeCommand:function(e){return e in this.make},isValidExecCommand:function(e){return t.inArray(e,this.validCommands)>-1},queryCommandState:function(t){if(t in this.is)return this.is[t]();try{return e.queryCommandState(t)}catch(n){return null}},selectionIsWithin:function(e){var n=i.Element.getPhraseElements(),r=!1,o=e.split(","),s=o.length,a=window.getSelection(),l=a.anchorNode,d=a.focusNode;if(l&&l.nodeType&&3==l.nodeType&&""==l.nodeValue&&(l=l.nextSibling),!l)return!1;if(t.browser.mozilla){for(;s--;)if(-1!=t.inArray(o[s],n)){r=!0;break}r&&1==l.nodeType&&-1==t.inArray(l.nodeName.toLowerCase(),n)&&(s=l.firstChild,s&&(""==s.nodeValue&&(s=s.nextSibling),1==s.nodeType&&(l=s)))}for(;l&&d&&1!=l.nodeType&&1!=d.nodeType;)1!=l.nodeType&&(l=l.parentNode),1!=d.nodeType&&(d=d.parentNode);return!(!t(l).closest(e).length&&!t(d).closest(e).length)},getSelectedStyles:function(){var e=window.getSelection(),n=t(e.getNode()),i={};for(var r in this.styleSelectors)i[r]=n.css(this.styleSelectors[r]);return i},replaceElement:function(e,n){if(!e.hasClass(i.name+"-editor")){for(var r=e.get(0),o=t("<"+n+"/>").html(r.innerHTML),s=r.attributes,a=s.length||0;a--;)o.attr(s[a].name,s[a].value);return e.replaceWith(o),o}},deleteElement:function(e){var n=t(e);n.replaceWith(n.html())},stripFormattingElements:function(){function e(n,i){var o=t(i);o.children().each(e),s(o)&&r.deleteElement(o)}for(var n,r=this,o=window.getSelection(),s=i.Element.isFormatter,a=o.rangeCount,l=[];a--;)n=o.getRangeAt(a),l.push(n),this.getRangeElements(n,this._blockElements).each(e);this.restoreRanges(l)},manipulateSelection:function(){for(var e,t=window.getSelection(),n=t.rangeCount,i=[],r=arguments,o=r[0];n--;)e=t.getRangeAt(n),i.push(e),r[0]=e,o.apply(this,r);this.restoreRanges(i)},getRangeElements:function(e,n){var i=t(e.startContainer).closest(n),r=t(e.endContainer).closest(n),o=t("nullset");return i.parents(".WysiHat-editor").length&&r.parents(".WysiHat-editor").length&&(o=i,i.filter(r).length||(o=i.nextAll().filter(r).length?i.nextUntil(r).andSelf().add(r):i.prevUntil(r).andSelf().add(r))),o},getRanges:function(){for(var e,t=window.getSelection(),n=t.rangeCount,i=[];n--;)e=t.getRangeAt(n),i.push(e);return i},restoreRanges:function(e){var t=window.getSelection(),n=e.length;for(t.removeAllRanges();n--;)t.addRange(e[n])},changeContentBlock:function(e){for(var n,i=window.getSelection(),r=this,o=t(r),s="WysiHat-replaced",a=i.rangeCount,l=[];a--;)n=i.getRangeAt(a),l.push(n),this.getRangeElements(n,this._blockElements).each(function(){r.replaceElement(t(this),e)}).data(s,!0);o.children(e).removeData(s),this.restoreRanges(l)},unformatContentBlock:function(){this.changeContentBlock("p")},unlinkSelection:function(){this.manipulateSelection(function(e){this.getRangeElements(e,"[href]").each(this.clearElement)})},wrapHTML:function(){var n,i=window.getSelection(),r=i.getRangeAt(0),o=i.getNode(),s=arguments.length;for(r.collapsed&&(r=e.createRange(),r.selectNodeContents(o),i.removeAllRanges(),i.addRange(r)),r=i.getRangeAt(0);s--;)n=t("<"+arguments[s]+"/>"),r.surroundContents(n.get(0))},toggleHTML:function(e){var t=e.$editor,n=e.$element,i=t.data("field"),r=n.siblings(),o=n.data("text");t.is(":visible")?(n.find("b").text(n.data("toggle-text")),r.hide(),t.hide(),i.show()):(n.find("b").text(o),r.show(),i.hide(),t.show())},insertHTML:function(n){if(t.browser.msie){var i=e.selection.createRange();i.pasteHTML(n),i.collapse(!1),i.select()}else this.execCommand("insertHTML",!1,n)},quoteSelection:function(){var e=t("<blockquote/>");this.manipulateSelection(function(e,n){var r=n.clone(),o=this.getRangeElements(e,this._blockElements),s=o.length-1,a=t();o.each(function(e){var n,o=t(this),l=!1;i.Element.isSubContainer(o)&&(l=!0),!e&&l&&e==s?(n=t("<p/>").html(o.html()),o.html("").append(n),a=a.add(n)):a=l?a.add(o.closest(i.Element.getContainers().join(","))):a.add(o),e==s&&a.wrapAll(r)})},e)},unquoteSelection:function(){this.manipulateSelection(function(e){this.getRangeElements(e,"blockquote > *").each(function(){var e=this,n=t(e),r=n.closest("blockquote"),o=r.clone().html(""),s=r.children(),a=s.length-1,l=t();n.unwrap("blockquote"),a>0&&s.each(function(n){this!=e&&(l=l.add(this)),(n==a||this==e)&&(l.wrapAll(o.clone()),l=t())}),r=n.parent(),i.Element.isSubContainer(r)&&1==r.children().length&&r.html(n.html())})})}}),t.extend(i.Commands.make,{blockquote:function(){i.Commands.is.indented()?i.Commands.unquoteSelection():i.Commands.quoteSelection()},alignment:function(e){i.Commands.execCommand("justify"+e)},backgroundColor:function(e){var n=t.browser.mozilla?"hilitecolor":"backcolor";i.Commands.execCommand(n,!1,e)}});
// ---------------------------------------------------------------------
/**
 * Commands Mixin
 *
 * Prettier solution to working with the basic manipulations.
 *
 * The old version of WysiHat had a boatload of fooSelection()
 * and isFoo() methods. It got a little unweidy, especially as
 * they were extended directly onto the editor jquery result.
 *
 * The above fixes most of that, but in order to smooth out the
 * bumps a bit more, I'm giving both the editor and the buttons
 * a single is() and make() api.
 *
 * They still have access to this.Commands for more advanced
 * manipulations.
 *
 * this.is('italic');
 * this.make('italic');
 * this.toggle('blockquote');
 *
 * this.Commands.advancedStuff();
 */
// ---------------------------------------------------------------------
var s={/**
	 * Better solution for what used to be a bunch
	 * of isFooBar() methods:
	 * this.is('bold')
	 */
is:function(e){return i.Commands.is[e]()},/**
	 * Nice method for doing built-in manipulations
	 * such as: this.make('bold')
	 */
make:function(e,t){return i.Commands.make[e](t)},/**
	 * Same as make, but makes more sense
	 * for some: this.toggle('blockquote')
	 */
toggle:function(e,t){return i.Commands.make[e](t)}};t.extend(i.Editor.prototype,s),
// ---------------------------------------------------------------------
/**
 * Formatting Class
 *
 * Responsible for keeping the markup clean and compliant. Also
 * deals with keeping changes between the raw text and editor in
 * sync periodically.
 */
// ---------------------------------------------------------------------
i.Formatting={_bottomUp:function(e,n,i){var r=e.find(n),o=t.makeArray(r).reverse();t.each(o,i)},cleanup:function(e){var n=i.Commands.replaceElement,r=i.Commands.deleteElement;
// kill comments
e.contents().filter(function(){return this.nodeType==Node.COMMENT_NODE}).remove(),this._bottomUp(e,"span",function(){var e=t(this),i=e.css("font-weight"),r="bold"==i||i>500,o="italic"==e.css("font-style");e.hasClass("Apple-style-span")&&e.removeClass("Apple-style-span"),e.removeAttr("style"),o&&r?(e.wrap("<b>"),n(e,"i")):r?n(e,"b"):o&&n(e,"i")}),e.children("div").each(function(){this.attributes.length||n(t(this),"p")}).end().find("strong").each(function(){n(t(this),"b")}).end().find("em").each(function(){n(t(this),"i")}).end().find("strike").each(function(){n(t(this),"del")}).end().find("u").each(function(){n(t(this),"ins")}).end().find("p:empty,script,noscript,style").remove(),
// firefox will sometimes end up nesting identical
// tags. Let's not do that, please.
e.find("b > b, i > i").each(function(){r(this)})},
// selection before tag, between tags, after tags
// between tags (x offset)
cleanupPaste:function(n,r){i.Commands.replaceElement;this.cleanup(n),
// Ok, now we want to get rid of everything except for the
// bare tags (with some exceptions, but not many). The trick
// is to run through the found elements backwards. Otherwise
// the node reference disappears when the parent is replaced.
this._bottomUp(n,"*",function(){var n=this.nodeName.toLowerCase(),i=e.createElement(n);switch(n){case"a":i.href=this.href,i.title=this.title;break;case"img":i.src=this.src,i.alt=this.alt}i.innerHTML=this.innerHTML,t(this).replaceWith(i)}),
// most of this deals with newlines, start
// out with a reasonable subset
n.find("br").replaceWith("\n"),n.html(function(e,n){// remove comments
// no newlines, no paragraphs, no nonsense
// remove comments
// no newlines, no paragraphs, no nonsense
// with the single line case out of the way, convert everything
// to paragraphs. This will make weeding out the double newlines
// easier below. I know it seems silly. By the end of this we're
// back to input for safari, but normalized for all others.
return n=t.trim(n),n=n.replace(/<\/p>\s*<p>/g,"\n\n").replace(/^(<p>)+/,"").replace(/(<\/p>)+$/,"").replace(/<!--[^>]*-->/g,""),-1==n.indexOf("\n")?n:(n=n.replace(/\n/,"<p>").replace(/\n/g,"\n</p><p>"),t.trim(n)+"</p>")}),
// remove needless spans and empty elements
n.find("span").children(i.Element.getBlocks()).unwrap(),n.find(":empty").remove(),
// on reinsertion we need to check for identically nested elements
// and clean those up. Otherwise pasting an h1 into an h1 is a clusterf***
"p"!=r.toLowerCase()&&n.find(r).replaceWith(function(e,t){return t});
// ok, now the fun bit with the paragraphs and newlines again.
// We equalize all newlines into paragraphs above, but really
// we only want them for the doubles newlines. All others are
// supposed to be <br>s. So we need to step through all the
// sibling pairs and merge when they are not separated by a blank.
var o,s=[];
// we no longer need these
for(
// if previous blank, start new one
// if previous not blank, add to previous
n.find("p ~ p").each(function(){var e=t(this),n=e.prev();o?t.trim(n.html())||(o.after("\n"),o=s.pop()):o=n,o.html(function(n,i){var r=t.trim(e.html());
// both have contents? add a newline between them
return i=t.trim(i),i&&r&&(i+="<br>"),i+r}),s.push(e)});o=s.pop();)o.remove();
// since all of the code above was newline sensitive, what
// comes out has none. So make it pretty!
n.before("\n").find("br").replaceWith("<br>\n")},reBlocks:new RegExp("(</(?:ul|ol)>|</(?:"+i.Element.getBlocks().join("|")+")>)","g"),format:function(e){var t=this;e.html(function(e,n){return n.replace(/<\/?[A-Z]+/g,function(e){return e.toLowerCase()}).replace(/(\t|\n| )+/g," ").replace(/>\s+</g,"> <").replace("/&nbsp;/g"," ").replace("/<p>[ ]+</p>/g","").replace(/<br ?\/?>\s?<\/p>/g,"</p>").replace(/<p>\n+<\/p>/g,"").replace(t.reBlocks,"$1\n\n").replace(/<br ?\/?>/g,"<br>\n").replace(/(ul|ol|li)>\s+<(\/)?(ul|ol|li)>/g,"$1>\n<$2$3>").replace(/><li>/g,">\n<li>").replace(/<\/li>\n+</g,"</li>\n<").replace(/^\s+(<li>|<\/?ul>|<\/?ol>)/gm,"$1").replace(/<li>/g,"    <li>").replace(/>\s*(<\/?tr>)/g,">$1").replace(/(<\/?tr>)\s*</g,"$1<").replace(/<(\/?(table|tbody))>/g,"<$1>\n").replace(/<\/tr>/g,"</tr>\n").replace(/<tr>/g,"    <tr>")}),
// Remove the extra white space that gets added after the
// last block in the .replace(that.reBlocks, '$1\n\n') line.
// If we don't remove it, then it sticks around and eventually
// becomes a new paragraph.  Which is just annoying.
e.html(e.html().trim())},getBrowserMarkupFrom:function(e){var n,i=t("<div>"+e.val()+"</div>");return this.cleanup(i),n=i.html(),(""==n||"<br>"==n||"<br/>"==n)&&i.html("<p>&#x200b;</p>"),i.html()},getApplicationMarkupFrom:function(e){var n,i,r=e.clone();return n=t("<div/>").html(r.html()),i=n.html(),(""==i||"<br>"==i||"<br/>"==i)&&n.html("<p>&#x200b;</p>"),this.cleanup(n),this.format(n),n.html().replace(/<\/?[A-Z]+/g,function(e){return e.toLowerCase()})}};
// ---------------------------------------------------------------------
/**
 * Blank Button
 *
 * The base prototype for all buttons. Handles the basic init and
 * provides a nice way to extend the buttons without having to re-
 * do all of the work the toolbar does.
 */
// ---------------------------------------------------------------------
var a={init:function(e,t){return this.name=e,this.$editor=t,this.$field=t.data("field"),this},setElement:function(e){return this.$element=t(e),this},getHandler:function(){if(this.handler)return t.proxy(this,"handler");var e=this;return i.Commands.isMakeCommand(this.name)?function(){return i.Commands.make[e.name]()}:i.Commands.isValidExecCommand(this.name)?function(){return i.Commands.execCommand(e.name)}:t.noop},getStateHandler:function(){if(this.query)return t.proxy(this,"query");if(i.Commands.isValidExecCommand(this.name)){var e=this;return function(t){
// @pk clean up
var n=t.data("wysihat");return n.Commands.queryCommandState(e.name)}}return t.noop},setOn:function(){return this.$element.addClass("selected").attr("aria-pressed","true").find("b").text(this["toggle-text"]?this["toggle-text"]:this.label),this},setOff:function(){return this.$element.removeClass("selected").attr("aria-pressed","false").find("b").text(this.label),this}};
// ---------------------------------------------------------------------
/**
 * Toolbar Class
 *
 * Handles the creation of the toolbar and manages the individual
 * buttons states. You can add your own by using:
 * WysiHat.addButton(name, { options });
 */
// ---------------------------------------------------------------------
i.Toolbar=function(e,n){this.suspendQueries=!1,this.$editor=e,this.$toolbar=t('<ul class="toolbar rte"></ul>'),e.before(this.$toolbar);
// add buttons
var i,r=n.length;for(i=0;r>i;i++)this.addButton(n[i]);
// Add .last to the last "normal" tool (not .rte-elements nor .rte-view)
this.$toolbar.children(".rte-elements").length?this.$toolbar.children(".rte-elements").prev().addClass("last"):this.$toolbar.children(".rte-view").length?this.$toolbar.children(".rte-view").prev().addClass("last"):this.$toolbar.children("li:last").addClass("last")},i.Toolbar.prototype={addButton:function(e){var n=this.$editor.data("wysihat"),r=i.inherit(a,i._buttons[e]).init(e,n.$editor);t.extend(r,s),
// Add utility references straight onto the button
r.Editor=n,r.Event=n.Event,r.Commands=n.Commands,r.Selection=n.Selection,r.setElement(this.createButtonElement(r)),r.Event.add(e,r.getHandler()),this.observeButtonClick(r),this.observeStateChanges(r)},createButtonElement:function(e){var n;if(e.type&&"select"==e.type){var i=e.options,r=i.length,o=0;for(n=t("<select/>");r>o;o++)n.append('<option value="'+i[o][0]+'">'+i[o][1]+"</option>");n.appendTo(this.$toolbar).wrap('<li class="rte-elements"/>')}else n=t('<li><a href=""></a></li>'),n.appendTo(this.$toolbar);return e.cssClass&&n.addClass(e.cssClass),e.title&&n.find("a").attr("title",e.title),n.data("text",e.label),e["toggle-text"]&&n.data("toggle-text",e["toggle-text"]),n},observeButtonClick:function(e){var t=e.type&&"select"==e.type?"change":"click",n=this;e.$element.on(t,function(t){
// IE had trouble doing change handlers
// as the state check would run too soon
// and reset the input element, so we suspend
// the query checks until after the event handler
// has run.
n.suspendQueries=!0;var i=e.$editor;
// Bring focus to the editor before the handler is called
// so that selection data is available to tools
return i.is(":focus")||i.focus(),e.Event.fire(e.name),n.suspendQueries=!1,!1})},observeStateChanges:function(e){var t,n=this,i=e.getStateHandler();n.$editor.on("WysiHat-selection:change",function(){if(!n.suspendQueries){var r=i(e.$editor,e.$element);r!=t&&(t=r,n.updateButtonState(e,r))}})},updateButtonState:function(e,t){return t?void e.setOn():void e.setOff()}},i.Toolbar.constructor=i.Toolbar}(document,jQuery),
// ---------------------------------------------------------------------
/**
 * Defaults and jQuery Binding
 *
 * This code sets up reasonable editor defaults and then adds
 * a convenience setup function to jQuery.fn that you can use
 * as $('textarea').wysihat(options).
 */
// ---------------------------------------------------------------------
jQuery.fn.wysihat=function(e){var t=this.data("wysihat");return t?-1!=jQuery.inArray(e,["Event","Selection","Toolbar","Undo"])?t[e]:t:this.each(function(){t=WysiHat.attach(this,e),$(this).data("wysihat",t)})},
// ---------------------------------------------------------------------
/**
 * Browser Compat Classes
 *
 * Below we normalize the Range and Selection classes to work
 * properly across all browsers. If you like IE, you'll feel
 * right at home down here.
 */
// ---------------------------------------------------------------------
function(e,t){"undefined"==typeof Node&&!function(){function e(){return{ATTRIBUTE_NODE:2,CDATA_SECTION_NODE:4,COMMENT_NODE:8,DOCUMENT_FRAGMENT_NODE:11,DOCUMENT_NODE:9,DOCUMENT_TYPE_NODE:10,ELEMENT_NODE:1,ENTITY_NODE:6,ENTITY_REFERENCE_NODE:5,NOTATION_NODE:12,PROCESSING_INSTRUCTION_NODE:7,TEXT_NODE:3}}window.Node=new e}(),e.getSelection?(
// quick fix so we can extend the native prototype
window.Selection={},window.Selection.prototype=window.getSelection().__proto__):/**
 * Selection and Range Shims
 *
 * Big hat tips to Tim Down's Rangy and Tim Cameron
 * Ryan's IERange. Neither quite worked here so I
 * reimplemented it with lots of inspiration from
 * their code.
 *
 * Rangy and IERange are MIT Licensed
 */
!function(){/**
	 * Ranges. These are fun.
	 */
function n(){this.startContainer,this.startOffset,this.endContainer,this.endOffset,this.collapsed}/**
	 * And now selections! Wahoo!
	 */
function i(){this._reset(),this._selection=e.selection}/**
	 * Dom Position Helper Object
	 *
	 * This can slowly be pulled out, but it's used in a few
	 * places and actually isn't too inconvenient.
	 */
function r(e,t){this.node=e,this.offset=t}n.prototype={/**
		 * Set the beginning of the range
		 */
setStart:function(e,t){this.startContainer=e,this.startOffset=t,e==this.endContainer&&t==this.endOffset&&(this.collapsed=!0)},/**
		 * Set the end of the range
		 */
setEnd:function(e,t){this.endContainer=e,this.endOffset=t,e==this.startContainer&&t==this.startOffset&&(this.collapsed=!0)},/**
		 * Collapse the range
		 */
collapse:function(e){e?(
// move to beginning
this.endContainer=this.startContainer,this.endOffset=this.startOffset):(
// move to end
this.startContainer=this.endContainer,this.startOffset=this.endOffset)},/**
		 * Get the containing node
		 */
getNode:function(){var t=e.selection.createRange();return s.getParentElement(t)},/**
		 * Select a specific node
		 */
selectNode:function(e){this.setStart(e.parentNode,s.getNodeIndex(e)),this.setEnd(e.parentNode,s.getNodeIndex(e)+1)},insertNode:function(e){s.insertNode(e,this.startContainer,this.startOffset)},/**
		 * Select a node's contents
		 */
selectNodeContents:function(e){var t=s.isCharacterDataNode(e)?e.length:e.childNodes.length;this.setStart(e,0),this.setEnd(e,t)},surroundContents:function(e){},/**
		 * Grab a copy of this Range
		 */
cloneRange:function(){var e=new n;return e.setStart(this.startContainer,this.startOffset),e.setEnd(this.endContainer,this.endOffset),e},/**
		 * Get the text content
		 */
toString:function(){var e=s.rangeToTextRange(this);return e?e.text:""}},/**
	 * Open the range getter up to the public.
	 */
e.createRange=function(){return new n},i.prototype={/**
		 * Sort of an init / reset.
		 *
		 * Selections are singletons so their
		 * state is very fragile.
		 */
_reset:function(){this.rangeCount=0,this.anchorNode=null,this.anchorOffset=null,this.focusNode=null,this.focusOffset=null,
// implementation
this._ranges=[]},/**
		 * Add a range to the visible selection
		 */
addRange:function(e){var t=s.rangeToTextRange(e);
// Check for intersection with old?
// Skipping it for now, I don't think we
// ever use them that way. If you decide to
// add it, I suggest riffing off webkit's
// webcore DOMSelection::addRange logic. -pk
return t?(t.select(),this.rangeCount=1,this._ranges=[e],this.isCollapsed=e.collapsed,void this._updateNodeRefs(e)):void this.removeAllRanges()},/**
		 * Deselect Everything
		 */
removeAllRanges:function(){this.rangeCount&&this._selection.empty(),this._reset()},/**
		 * Firefox supports more than one range in a selection.
		 * We do not.
		 */
getRangeAt:function(e){return 0!==e?null:this._ranges[e]},/**
		 * Get the string contents
		 */
toString:function(){
// grab range contents
// grab range contents
return this.rangeCount?this._ranges[0].toString():""},/**
		 * Refresh the selection state
		 *
		 * There is only one selection per window, so we call
		 * this every time the user asks for a selection through
		 * getSelection.
		 */
_refresh:function(){
// the TextRange parentElement implementation is bugtastic, so
// we need to do this manually ...
var e,t,i,r=this._selection.createRange(),o=s.getParentElement(r);
// is collapsed?
0==r.compareEndPoints("StartToEnd",r)?(e=s.getBoundary(r,o,!0,!0),t=e):(e=s.getBoundary(r,o,!0,!1),t=s.getBoundary(r,o,!1,!1));var i=new n;return i.setStart(e.node,e.offset),i.setEnd(t.node,t.offset),this.rangeCount=1,this._ranges=[i],this.isCollapsed=i.collapsed,this._updateNodeRefs(i),this},/**
		 * Sync the nodes and offsets
		 *
		 * For whatever reason the selection holds
		 * what amounts to duplicate data about the
		 * ranges. No magic __get in js, so we copy.
		 */
_updateNodeRefs:function(e){this.anchorNode=e.startContainer,this.anchorOffset=e.startOffset,this.focusNode=e.endContainer,this.focusOffset=e.endOffset}};/**
	 * Open the selection getter up to the public.
	 *
	 * It is generally a good idea to grab a new selection
	 * if there is any chance of it being messed with. This
	 * applies doubly in this case because of the _refresh call.
	 */
var o=new i;window.getSelection=function(){return o._refresh()};/**
	 * Some utility helper methods.
	 *
	 * Big, big hat tip to Rangy!
	 * http://code.google.com/p/rangy/
	 */
var s={/**
		 * Character data nodes have text, others
		 * have childNodes.
		 */
isCharacterDataNode:function(e){var t=e.nodeType;return 3==t||4==t||8==t},/**
		 * Find a node offset for non-chardatanode
		 * selection offsets.
		 */
getNodeIndex:function(e){for(var t=0;e=e.previousSibling;)t++;return t},/*
		 * Check for ancestors. May be able to move
		 * this to $.contains(ancestor, descendant) in the future.
		 */
isAncestorOf:function(e,t,n){for(var i=n?t:t.parentNode;i;){if(i===e)return!0;i=i.parentNode}return!1},/**
		 * Find a shared ancestor
		 */
getCommonAncestor:function(e,n){var i,r=[];for(i=e;i;i=i.parentNode)r.push(i);for(i=n;i;i=i.parentNode)if(t.inArray(i,r)>-1)return i;return null},/*
		 * Insert the node at a specific offset.
		 * Needs to split text nodes if the insertion is to happen
		 * in the middle of some text.
		 */
insertNode:function(e,n,i){var r=11==e.nodeType?e.firstChild:e;return this.isCharacterDataNode(n)?i==n.length?t(e).insertAfter(n):n.parentNode.insertBefore(e,0==i?n:this.splitDataNode(n,i)):i>=n.childNodes.length?n.appendChild(e):n.insertBefore(e,n.childNodes[i]),r},/**
		 * Split a text, comment, or cdata node
		 * to make room for a new insertion.
		 */
splitDataNode:function(e,n){var i=e.cloneNode(!1);return i.deleteData(0,n),e.deleteData(n,e.length-n),t(i).insertAfter(e),i},/**
		 * Convert a range object back to a textRange
		 */
rangeToTextRange:function(t){var n,i;return n=this.createBoundaryTextRange(new r(t.startContainer,t.startOffset),!0),t.collapsed?n:(i=this.createBoundaryTextRange(new r(t.endContainer,t.endOffset),!1),n&&i?(textRange=e.body.createTextRange(),textRange.setEndPoint("StartToStart",n),textRange.setEndPoint("EndToEnd",i),textRange):!1)},/**
		 * IE's textRange.parentElement is buggy, so
		 * this function does a bit more work to ensure
		 * consistency.
		 */
getParentElement:function(e){var t,n,i,r,o=e.parentElement();
// find starting element
// find ending element
// find common parent
return r=e.duplicate(),r.collapse(!0),n=r.parentElement(),r=e.duplicate(),r.collapse(!1),i=r.parentElement(),t=n==i?n:this.getCommonAncestor(n,i),t==o?t:this.getCommonAncestor(o,t)},/**
		 * Traverse the dom and place a textNode at the desired position.
		 */
createBoundaryTextRange:function(n,i){var r,o,s,a,l=e,d=n.offset,c=l.body.createTextRange(),h=this.isCharacterDataNode(n.node);
// Position the range immediately before the node containing the boundary
// Making the working element non-empty element persuades IE to consider the TextRange boundary to be within the
// element rather than immediately before or after it, which is what we want
// insertBefore is supposed to work like appendChild if the second parameter is null. However, a bug report
// for IERange suggests that it can crash the browser: http://code.google.com/p/ierange/issues/detail?id=12
// Clean up
// Move the working range to the text offset, if required
// Clean up and bail
return h?(r=n.node,o=r.parentNode):(a=n.node.childNodes,r=d<a.length?a[d]:null,o=n.node),s=l.createElement("span"),s.innerHTML="&#feff;",r?o.insertBefore(s,r):o.appendChild(s),t.contains(e.body,s)?(c.moveToElementText(s),c.collapse(!i),o.removeChild(s),h&&c[i?"moveStart":"moveEnd"]("character",d),c):(o.removeChild(s),null)},/**
		 * Gets the boundary of a TextRange expressed as a node and an offset within that node. This function started out as
		 * an improved version of code found in Tim Cameron Ryan's IERange (http://code.google.com/p/ierange/) but has
		 * grown, fixing problems with line breaks in preformatted text, adding workaround for IE TextRange bugs, handling
		 * for inputs and images, plus optimizations.
		 */
getBoundary:function(t,n,i,o){var s,a=t.duplicate();
// Deal with nodes that cannot "contain rich HTML markup". In practice, this means form inputs, images and
// similar. See http://msdn.microsoft.com/en-us/library/aa703950%28VS.85%29.aspx
if(a.collapse(i),s=a.parentElement(),this.isAncestorOf(n,s,!0)||(s=n),!s.canHaveHTML)return new r(s.parentNode,this.getNodeIndex(s));var l,d,c,h,u,f=e.createElement("span"),m=i?"StartToStart":"StartToEnd";
// Move the working range through the container's children, starting at the end and working backwards, until the
// working range reaches or goes past the boundary we're interested in
do s.insertBefore(f,f.previousSibling),a.moveToElementText(f);while((l=a.compareEndPoints(m,t))>0&&f.previousSibling);if(u=f.nextSibling,-1==l&&u&&this.isCharacterDataNode(u)){
// This is a character data node (text, comment, cdata). The working range is collapsed at the start of the
// node containing the text range's boundary, so we move the end of the working range to the boundary point
// and measure the length of its text to get the boundary's offset within the node.
a.setEndPoint(i?"EndToStart":"EndToEnd",t);var p;if(/[\r\n]/.test(u.data)){/*
					For the particular case of a boundary within a text node containing line breaks (within a <pre> element,
					for example), we need a slightly complicated approach to get the boundary's offset in IE. The facts:

					- Each line break is represented as \r in the text node's data/nodeValue properties
					- Each line break is represented as \r\n in the TextRange's 'text' property
					- The 'text' property of the TextRange does not contain trailing line breaks

					To get round the problem presented by the final fact above, we can use the fact that TextRange's
					moveStart() and moveEnd() methods return the actual number of characters moved, which is not necessarily
					the same as the number of characters it was instructed to move. The simplest approach is to use this to
					store the characters moved when moving both the start and end of the range to the start of the document
					body and subtracting the start offset from the end offset (the "move-negative-gazillion" method).
					However, this is extremely slow when the document is large and the range is near the end of it. Clearly
					doing the mirror image (i.e. moving the range boundaries to the end of the document) has the same
					problem.

					Another approach that works is to use moveStart() to move the start boundary of the range up to the end
					boundary one character at a time and incrementing a counter with the value returned by the moveStart()
					call. However, the check for whether the start boundary has reached the end boundary is expensive, so
					this method is slow (although unlike "move-negative-gazillion" is largely unaffected by the location of
					the range within the document).

					The method below is a hybrid of the two methods above. It uses the fact that a string containing the
					TextRange's 'text' property with each \r\n converted to a single \r character cannot be longer than the
					text of the TextRange, so the start of the range is moved that length initially and then a character at
					a time to make up for any trailing line breaks not contained in the 'text' property. This has good
					performance in most situations compared to the previous two methods.
					*/
var g=a.duplicate(),v=g.text.replace(/\r\n/g,"\r").length;for(p=g.moveStart("character",v);-1==(l=g.compareEndPoints("StartToEnd",g));)p++,g.moveStart("character",1)}else p=a.text.length;h=new r(u,p)}else d=(o||!i)&&f.previousSibling,c=(o||i)&&f.nextSibling,h=c&&this.isCharacterDataNode(c)?new r(c,0):d&&this.isCharacterDataNode(d)?new r(d,d.length):new r(s,this.getNodeIndex(f));
// Clean up
return f.parentNode.removeChild(f),h}};
// expose them for the trickery below
window.Range=n,window.Selection=i}(),
// Add a few more methods to all ranges and selections.
// Both native and our shims.
t.extend(Range.prototype,{/**
	 * Compare two ranges for equality. We want to
	 * compare the actual selection rather than just
	 * the offsets, since there is more than one way
	 * to specify a certain selection.
	 */
equalRange:function(e){
// if both ranges are collapsed we just need to compare one point
return e&&e.compareBoundaryPoints?this.collapsed&&e.collapsed?0==this.compareBoundaryPoints(this.START_TO_START,e):0==this.compareBoundaryPoints(this.START_TO_START,e)&&1==this.compareBoundaryPoints(this.START_TO_END,e)&&0==this.compareBoundaryPoints(this.END_TO_END,e)&&-1==this.compareBoundaryPoints(this.END_TO_START,e):!1}}),t.extend(window.Selection.prototype,{/**
	 * Get the node that most encompasses the
	 * entire selection.
	 */
getNode:function(){return this.rangeCount>0?this.getRangeAt(0).getNode():null}}),/**
 * Add $.browser if it doesn't yet exist. This typically
 * happens on the frontend where our common.js isn't available
 */
t.uaMatch=t.uaMatch||function(e){e=e.toLowerCase();var t=/(chrome)[ \/]([\w.]+)/.exec(e)||/(webkit)[ \/]([\w.]+)/.exec(e)||/(opera)(?:.*version|)[ \/]([\w.]+)/.exec(e)||/(msie) ([\w.]+)/.exec(e)||e.indexOf("compatible")<0&&/(mozilla)(?:.*? rv:([\w.]+)|)/.exec(e)||[];return{browser:t[1]||"",version:t[2]||"0"}},
// Don't clobber any existing $.browser in case it's different
t.browser||(matched=t.uaMatch(navigator.userAgent),browser={},matched.browser&&(browser[matched.browser]=!0,browser.version=matched.version),
// Chrome is Webkit, but Webkit is also Safari.
browser.chrome?browser.webkit=!0:browser.webkit&&(browser.safari=!0),t.browser=browser)}(document,jQuery);