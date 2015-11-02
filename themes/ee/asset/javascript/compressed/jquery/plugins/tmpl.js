/*!
 * jQuery Templates Plugin 1.0.0pre
 * http://github.com/jquery/jquery-tmpl
 * Requires jQuery 1.4.2
 *
 * Copyright Software Freedom Conservancy, Inc.
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 */
!function(t,e){function n(e,n,l,a){
// Returns a template item data structure for a new rendered instance of a template (a 'template item').
// The content field is a hierarchical array of strings and nested items (to be
// removed and replaced by nodes field of dom elements, once inserted in DOM).
var r={data:a||0===a||a===!1?a:n?n.data:{},_wrap:n?n._wrap:null,tmpl:null,parent:n||null,nodes:[],calls:c,nest:m,wrap:f,html:s,update:d};
// Build the hierarchical content to be used during insertion into DOM
// Keep track of new template item, until it is stored as jQuery Data on DOM element
return e&&t.extend(r,e,{nodes:[],parent:n}),l&&(r.tmpl=l,r._ctnt=r._ctnt||r.tmpl(t,r),r.key=++k,(j.length?v:g)[k]=r),r}
//========================== Private helper functions, used by code above ==========================
function l(e,n,r){
// Convert hierarchical content into flat string array
// and finally return array of fragments ready for DOM insertion
var p,i=r?t.map(r,function(t){
// Insert template item annotations, to be converted to jQuery.data( "tmplItem" ) when elems are inserted into DOM.
// This is a child template item. Build nested template.
return"string"==typeof t?e.key?t.replace(/(<\w+)(?=[\s>])(?![^>]*_tmplitem)([^>]*)/g,"$1 "+h+'="'+e.key+'" $2'):t:l(t,e,t._ctnt)}):
// If content is not defined, insert tmplItem directly. Not a template item. May be a string, or a string array, e.g. from {{html $item.html()}}.
e;
// top-level template
// Support templates which have initial or final text nodes, or consist only of text
// Also support HTML entities within the HTML markup.
return n?i:(i=i.join(""),i.replace(/^\s*([^<\s][^<]*)?(<[\w\W]+>)([^>]*[^>\s])?\s*$/,function(e,n,l,r){p=t(l).get(),u(p),n&&(p=a(n).concat(p)),r&&(p=p.concat(a(r)))}),p?p:a(i))}function a(e){
// Use createElement, since createTextNode will not render HTML entities correctly
var n=document.createElement("div");return n.innerHTML=e,t.makeArray(n.childNodes)}
// Generate a reusable function that will serve to render a template against data
function r(e){
// Use the variable __ to hold a string array while building the compiled template. (See https://github.com/jquery/jquery-tmpl/issues#issue/10).
// Convert the template into pure JavaScript
return new Function("jQuery","$item","var $=jQuery,call,__=[],$data=$item.data;with($data){__.push('"+t.trim(e).replace(/([\\'])/g,"\\$1").replace(/[\r\t\n]/g," ").replace(/\$\{([^\}]*)\}/g,"{{= $1}}").replace(/\{\{(\/?)(\w+|.)(?:\(((?:[^\}]|\}(?!\}))*?)?\))?(?:\s+(.*?)?)?(\(((?:[^\}]|\}(?!\}))*?)\))?\s*\}\}/g,function(e,n,l,a,r,p,o){var u,c,m,f=t.tmpl.tag[l];if(!f)throw"Unknown template tag: "+l;
// Support for target being things like a.toLowerCase();
// In that case don't call with template item as 'this' pointer. Just evaluate...
return u=f._default||[],p&&!/\w$/.test(r)&&(r+=p,p=""),r?(r=i(r),o=o?","+i(o)+")":p?")":"",c=p?r.indexOf(".")>-1?r+i(p):"("+r+").call($item"+o:r,m=p?c:"(typeof("+r+")==='function'?("+r+").call($item):("+r+"))"):m=c=u.$1||"null",a=i(a),"');"+f[n?"close":"open"].split("$notnull_1").join(r?"typeof("+r+")!=='undefined' && ("+r+")!=null":"true").split("$1a").join(m).split("$1").join(c).split("$2").join(a||u.$2||"")+"__.push('"})+"');}return __;")}function p(e,n){
// Build the wrapped content.
e._wrap=l(e,!0,t.isArray(n)?n:[y.test(n)?n:t(n).html()]).join("")}function i(t){return t?t.replace(/\\'/g,"'").replace(/\\\\/g,"\\"):null}function o(t){var e=document.createElement("div");return e.appendChild(t.cloneNode(!0)),e.innerHTML}
// Store template items in jQuery.data(), ensuring a unique tmplItem data data structure for each rendered template instance.
function u(e){function l(e){function l(t){t+=u,p=c[t]=c[t]||n(p,g[p.parent.key+u]||p.parent)}var a,r,p,i,o=e;
// Ensure that each rendered template inserted into the DOM has its own template item,
if(i=e.getAttribute(h)){for(;o.parentNode&&1===(o=o.parentNode).nodeType&&!(a=o.getAttribute(h)););a!==i&&(o=o.parentNode?11===o.nodeType?0:o.getAttribute(h)||0:0,(p=g[i])||(p=v[i],p=n(p,g[o]||v[o]),p.key=++k,g[k]=p),T&&l(i)),e.removeAttribute(h)}else T&&(p=t.data(e,"tmplItem"))&&(
// This was a rendered element, cloned during append or appendTo etc.
// TmplItem stored in jQuery data has already been cloned in cloneCopyEvent. We must replace it with a fresh cloned tmplItem.
l(p.key),g[p.key]=p,o=t.data(e.parentNode,"tmplItem"),o=o?o.key:0);if(p){
// Find the template item of the parent element.
// (Using !=, not !==, since pntItem.key is number, and pntNode may be a string)
for(r=p;r&&r.key!=o;)
// Add this element as a top-level node for this rendered template item, as well as for any
// ancestor items between this item and the item of its parent element
r.nodes.push(e),r=r.parent;
// Delete content built during rendering - reduce API surface area and memory use, and avoid exposing of stale data after rendering...
delete p._ctnt,delete p._wrap,
// Store template item as jQuery data on the element
t.data(e,"tmplItem",p)}}var a,r,p,i,o,u="_"+T,c={};for(p=0,i=e.length;i>p;p++)if(1===(a=e[p]).nodeType){for(r=a.getElementsByTagName("*"),o=r.length-1;o>=0;o--)l(r[o]);l(a)}}
//---- Helper functions for template item ----
function c(t,e,n,l){return t?void j.push({_:t,tmpl:e,item:this,data:n,options:l}):j.pop()}function m(e,n,l){
// nested template, using {{tmpl}} tag
return t.tmpl(t.template(e),n,l,this)}function f(e,n){
// nested template, using {{wrap}} tag
var l=e.options||{};
// Apply the template, which may incorporate wrapped content,
return l.wrapped=n,t.tmpl(t.template(e.tmpl),e.data,l,e.item)}function s(e,n){var l=this._wrap;return t.map(t(t.isArray(l)?l.join(""):l).filter(e||"*"),function(t){return n?t.innerText||t.textContent:t.outerHTML||o(t)})}function d(){var e=this.nodes;t.tmpl(null,null,null,this).insertBefore(e[0]),t(e).remove()}var $,_=t.fn.domManip,h="_tmplitem",y=/^[^<]*(<[\w\W]+>)[^>]*$|\{\{\! /,g={},v={},w={key:0,data:{}},k=0,T=0,j=[];
// Override appendTo etc., in order to provide support for targeting multiple elements. (This code would disappear if integrated in jquery core).
t.each({appendTo:"append",prependTo:"prepend",insertBefore:"before",insertAfter:"after",replaceAll:"replaceWith"},function(e,n){t.fn[e]=function(l){var a,r,p,i,o=[],u=t(l),c=1===this.length&&this[0].parentNode;if($=g||{},c&&11===c.nodeType&&1===c.childNodes.length&&1===u.length)u[n](this[0]),o=this;else{for(r=0,p=u.length;p>r;r++)T=r,a=(r>0?this.clone(!0):this).get(),t(u[r])[n](a),o=o.concat(a);T=0,o=this.pushStack(o,e,u.selector)}return i=$,$=null,t.tmpl.complete(i),o}}),t.fn.extend({
// Use first wrapped element as template markup.
// Return wrapped set of template items, obtained by rendering template against data.
tmpl:function(e,n,l){return t.tmpl(this[0],e,n,l)},
// Find which rendered template item the first wrapped DOM element belongs to
tmplItem:function(){return t.tmplItem(this[0])},
// Consider the first wrapped element as a template declaration, and get the compiled template or store it as a named template.
template:function(e){return t.template(e,this[0])},domManip:function(e,n,l,a){if(e[0]&&t.isArray(e[0])){for(var r,p=t.makeArray(arguments),i=e[0],o=i.length,u=0;o>u&&!(r=t.data(i[u++],"tmplItem")););r&&T&&(p[2]=function(e){
// Handler called by oldManip when rendered template has been inserted into DOM.
t.tmpl.afterManip(this,e,l)}),_.apply(this,p)}else _.apply(this,arguments);return T=0,$||t.tmpl.complete(g),this}}),t.extend({
// Return wrapped set of template items, obtained by rendering template against data.
tmpl:function(e,a,r,i){var o,u=!i;if(u)i=w,e=t.template[e]||t.template(null,e),v={};else if(!e)
// Rebuild, without creating a new template item
// The template item is already associated with DOM - this is a refresh.
// Re-evaluate rendered template for the parentItem
return e=i.tmpl,g[i.key]=i,i.nodes=[],i.wrapped&&p(i,i.wrapped),t(l(i,null,i.tmpl(t,i)));return e?("function"==typeof a&&(a=a.call(i||{})),r&&r.wrapped&&p(r,r.wrapped),o=t.isArray(a)?t.map(a,function(t){return t?n(r,i,e,t):null}):[n(r,i,e,a)],u?t(l(i,null,o)):o):[]},
// Return rendered template item for an element.
tmplItem:function(e){var n;for(e instanceof t&&(e=e[0]);e&&1===e.nodeType&&!(n=t.data(e,"tmplItem"))&&(e=e.parentNode););return n||w},
// Set:
// Use $.template( name, tmpl ) to cache a named template,
// where tmpl is a template string, a script element or a jQuery instance wrapping a script element, etc.
// Use $( "selector" ).template( name ) to provide access by name to a script block template declaration.
// Get:
// Use $.template( name ) to access a cached template.
// Also $( selectorToScriptBlock ).template(), or $.template( null, templateString )
// will return the compiled template, without adding a name reference.
// If templateString includes at least one HTML tag, $.template( templateString ) is equivalent
// to $.template( null, templateString )
template:function(e,n){
// Compile template and associate with name
// This is an HTML string being passed directly in.
// If this is a template block, use cached copy, or generate tmpl function and cache.
// If not in map, and not containing at least on HTML tag, treat as a selector.
// (If integrated with core, use quickExpr.exec)
return n?("string"==typeof n?n=r(n):n instanceof t&&(n=n[0]||{}),n.nodeType&&(n=t.data(n,"tmpl")||t.data(n,"tmpl",r(n.innerHTML))),"string"==typeof e?t.template[e]=n:n):e?"string"!=typeof e?t.template(null,e):t.template[e]||t.template(null,y.test(e)?e:t(e)):null},encode:function(t){
// Do HTML encoding replacing < > & and ' and " by corresponding entities.
return(""+t).split("<").join("&lt;").split(">").join("&gt;").split('"').join("&#34;").split("'").join("&#39;")}}),t.extend(t.tmpl,{tag:{tmpl:{_default:{$2:"null"},open:"if($notnull_1){__=__.concat($item.nest($1,$2));}"},wrap:{_default:{$2:"null"},open:"$item.calls(__,$1,$2);__=[];",close:"call=$item.calls();__=call._.concat($item.wrap(call,__));"},each:{_default:{$2:"$index, $value"},open:"if($notnull_1){$.each($1a,function($2){with(this){",close:"}});}"},"if":{open:"if(($notnull_1) && $1a){",close:"}"},"else":{_default:{$1:"true"},open:"}else if(($notnull_1) && $1a){"},html:{
// Unecoded expression evaluation.
open:"if($notnull_1){__.push($1a);}"},"=":{
// Encoded expression evaluation. Abbreviated form is ${}.
_default:{$1:"$data"},open:"if($notnull_1){__.push($.encode($1a));}"},"!":{
// Comment tag. Skipped by parser
open:""}},
// This stub can be overridden, e.g. in jquery.tmplPlus for providing rendered events
complete:function(t){g={}},
// Call this from code which overrides domManip, or equivalent
// Manage cloning/storing template items etc.
afterManip:function(e,n,l){
// Provides cloned fragment ready for fixup prior to and after insertion into DOM
var a=11===n.nodeType?t.makeArray(n.childNodes):1===n.nodeType?[n]:[];
// Return fragment to original caller (e.g. append) for DOM insertion
l.call(e,n),
// Fragment has been inserted:- Add inserted nodes to tmplItem data structure. Replace inserted element annotations by jQuery.data.
u(a),T++}})}(jQuery);