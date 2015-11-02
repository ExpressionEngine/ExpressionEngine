/*!
 *
 * TableSorter 2.0 - Client-side table sorting with ease!
 * Version 2.0.3
 * @requires jQuery v1.2.3
 *
 * Copyright (c) 2007 Christian Bach
 * Examples and docs at: http://tablesorter.com
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 */
/**
 *
 * @description Create a sortable table with multi-column sorting capabilitys
 *
 * @example $('table').tablesorter();
 * @desc Create a simple tablesorter interface.
 *
 * @example $('table').tablesorter({ sortList:[[0,0],[1,0]] });
 * @desc Create a tablesorter interface and sort on the first and secound column in ascending order.
 *
 * @example $('table').tablesorter({ headers: { 0: { sorter: false}, 1: {sorter: false} } });
 * @desc Create a tablesorter interface and disableing the first and secound column headers.
 *
 * @example $('table').tablesorter({ 0: {sorter:"integer"}, 1: {sorter:"currency"} });
 * @desc Create a tablesorter interface and set a column parser for the first and secound column.
 *
 *
 * @param Object settings An object literal containing key/value pairs to provide optional settings.
 *
 * @option String cssHeader (optional) 			A string of the class name to be appended to sortable tr elements in the thead of the table.
 * 												Default value: "header"
 *
 * @option String cssAsc (optional) 			A string of the class name to be appended to sortable tr elements in the thead on a ascending sort.
 * 												Default value: "headerSortUp"
 *
 * @option String cssDesc (optional) 			A string of the class name to be appended to sortable tr elements in the thead on a descending sort.
 * 												Default value: "headerSortDown"
 *
 * @option String sortInitialOrder (optional) 	A string of the inital sorting order can be asc or desc.
 * 												Default value: "asc"
 *
 * @option String sortMultisortKey (optional) 	A string of the multi-column sort key.
 * 												Default value: "shiftKey"
 *
 * @option String textExtraction (optional) 	A string of the text-extraction method to use.
 * 												For complex html structures inside td cell set this option to "complex",
 * 												on large tables the complex option can be slow.
 * 												Default value: "simple"
 *
 * @option Object headers (optional) 			An array containing the forces sorting rules.
 * 												This option let's you specify a default sorting rule.
 * 												Default value: null
 *
 * @option Array sortList (optional) 			An array containing the forces sorting rules.
 * 												This option let's you specify a default sorting rule.
 * 												Default value: null
 *
 * @option Array sortForce (optional) 			An array containing forced sorting rules.
 * 												This option let's you specify a default sorting rule, which is prepended to user-selected rules.
 * 												Default value: null
 *
  * @option Array sortAppend (optional) 			An array containing forced sorting rules.
 * 												This option let's you specify a default sorting rule, which is appended to user-selected rules.
 * 												Default value: null
 *
 * @option Boolean widthFixed (optional) 		Boolean flag indicating if tablesorter should apply fixed widths to the table columns.
 * 												This is usefull when using the pager companion plugin.
 * 												This options requires the dimension jquery plugin.
 * 												Default value: false
 *
 * @option Boolean cancelSelection (optional) 	Boolean flag indicating if tablesorter should cancel selection of the table headers text.
 * 												Default value: true
 *
 * @option Boolean debug (optional) 			Boolean flag indicating if tablesorter should display debuging information usefull for development.
 *
 * @type jQuery
 *
 * @name tablesorter
 *
 * @cat Plugins/Tablesorter
 *
 * @author Christian Bach/christian.bach@polyester.se
 */
!function(t){t.extend({tablesorter:new function(){/* debuging utils */
function e(t,e){r(t+","+((new Date).getTime()-e.getTime())+"ms")}function r(t){"undefined"!=typeof console&&"undefined"!=typeof console.debug?console.log(t):alert(t)}/* parsers utils */
function n(e,i){if(e.config.debug)var n="";var a=e.tBodies[0].rows;if(e.tBodies[0].rows[0])for(var d=[],c=a[0].cells,u=c.length,f=0;u>f;f++){var l=!1;t.metadata&&t(i[f]).metadata()&&t(i[f]).metadata().sorter?l=s(t(i[f]).metadata().sorter):e.config.headers[f]&&e.config.headers[f].sorter&&(l=s(e.config.headers[f].sorter)),l||(l=o(e,c[f])),e.config.debug&&(n+="column:"+f+" parser:"+l.id+"\n"),d.push(l)}return e.config.debug&&r(n),d}function o(e,r){for(var i=F.length,n=1;i>n;n++)if(F[n].is(t.trim(d(e.config,r)),e,r))return F[n];
// 0 is always the generic parser (text)
return F[0]}function s(t){for(var e=F.length,r=0;e>r;r++)if(F[r].id.toLowerCase()==t.toLowerCase())return F[r];return!1}/* utils */
function a(r){if(r.config.debug)var i=new Date;for(var n=r.tBodies[0]&&r.tBodies[0].rows.length||0,o=r.tBodies[0].rows[0]&&r.tBodies[0].rows[0].cells.length||0,s=r.config.parsers,a={row:[],normalized:[]},c=0;n>c;++c){/** Add the table data to main data array */
var u=r.tBodies[0].rows[c],f=[];a.row.push(t(u));for(var l=0;o>l;++l)f.push(s[l].format(d(r.config,u.cells[l]),r,u.cells[l]));f.push(c),// add position for rowCache
a.normalized.push(f),f=null}return r.config.debug&&e("Building cache for "+n+" rows:",i),a}function d(e,r){if(!r)return"";var i="";return i="simple"==e.textExtraction?r.childNodes[0]&&r.childNodes[0].hasChildNodes()?r.childNodes[0].innerHTML:r.innerHTML:"function"==typeof e.textExtraction?e.textExtraction(r):t(r).text()}function c(r,i){if(r.config.debug)var n=new Date;for(var o=i,s=o.row,a=o.normalized,d=a.length,c=a[0].length-1,u=t(r.tBodies[0]),f=[],l=0;d>l;l++)if(f.push(s[a[l][c]]),!r.config.appender)for(var g=s[a[l][c]],m=g.length,p=0;m>p;p++)u[0].appendChild(g[p]);r.config.appender&&r.config.appender(r,f),f=null,r.config.debug&&e("Rebuilt table:",n),h(r),setTimeout(function(){t(r).trigger("sortEnd")},0)}function u(i){if(i.config.debug)var n=new Date;for(var o=(t.metadata?!0:!1,[]),s=0;s<i.tHead.rows.length;s++)o[s]=0;return $tableHeaders=t("thead th",i),$tableHeaders.each(function(e){this.count=0,this.column=e,this.order=m(i.config.sortInitialOrder),(f(this)||l(i,e))&&(this.sortDisabled=!0),this.sortDisabled||t(this).addClass(i.config.cssHeader),
// add cell to headerList
i.config.headerList[e]=this}),i.config.debug&&(e("Built headers:",n),r($tableHeaders)),$tableHeaders}function f(e){return t.metadata&&t(e).metadata().sorter===!1?!0:!1}function l(t,e){return t.config.headers[e]&&t.config.headers[e].sorter===!1?!0:!1}function h(t){for(var e=t.config.widgets,r=e.length,i=0;r>i;i++)g(e[i]).format(t)}function g(t){for(var e=B.length,r=0;e>r;r++)if(B[r].id.toLowerCase()==t.toLowerCase())return B[r]}function m(t){return"Number"!=typeof t?i="desc"==t.toLowerCase()?1:0:i=1==t?t:0,i}function p(t,e){for(var r=e.length,i=0;r>i;i++)if(e[i][0]==t)return!0;return!1}function b(e,r,i,n){
// remove all header information
r.removeClass(n[0]).removeClass(n[1]);var o=[];r.each(function(e){this.sortDisabled||(o[this.column]=t(this))});for(var s=i.length,a=0;s>a;a++)o[i[a][0]].addClass(n[i[a][1]])}function v(e,r){var i=e.config;if(i.widthFixed){var n=t("<colgroup>");t("tr:first td",e.tBodies[0]).each(function(){n.append(t("<col>").css("width",t(this).width()))}),t(e).prepend(n)}}function w(t,e){for(var r=t.config,i=e.length,n=0;i>n;n++){var o=e[n],s=r.headerList[o[0]];s.count=o[1],s.count++}}/* sorting methods */
function y(t,e,r){var i;t.config.debug&&(i=new Date);var n=function(e){for(var i,n,o,s=[],a=[],d=e.length,c=0;d>c;c++)o=e[c][0],n=e[c][1],s[c]=o,a[c]="text"==D(t.config.parsers,o)?0==n?L:x:0==n?$:C;return i=r.normalized[0].length-1,function(t,e){for(var r,n,o=0;d>o;o++)if(r=s[o],n=a[o](t[r],e[r]))return n;return t[i]-e[i]}};return r.normalized.sort(n(e)),t.config.debug,r}function L(t,e){return e>t?-1:t>e?1:0}function x(t,e){return t>e?-1:e>t?1:0}function $(t,e){return t-e}function C(t,e){return e-t}function D(t,e){return t[e].type}var F=[],B=[];this.defaults={cssHeader:"header",cssAsc:"headerSortUp",cssDesc:"headerSortDown",sortInitialOrder:"asc",sortMultiSortKey:"shiftKey",sortForce:null,sortAppend:null,textExtraction:"simple",parsers:{},widgets:[],widgetZebra:{css:["even","odd"]},headers:{},widthFixed:!1,cancelSelection:!0,sortList:[],headerList:[],dateFormat:"us",decimal:".",debug:!1},this.benchmark=e,/* public methods */
this.construct=function(e){return this.each(function(){if(this.tHead&&this.tBodies){var r,i,o,s;this.config={},s=t.extend(this.config,t.tablesorter.defaults,e),
// store common expression for speed
r=t(this),
// build headers
i=u(this),
// try to auto detect column type, and store in tables config
this.config.parsers=n(this,i),
// build the cache for the tbody cells
o=a(this);
// get the css class names, could be done else where.
var d=[s.cssDesc,s.cssAsc];
// fixate columns if the users supplies the fixedWidth option
v(this),
// apply event handling to headers
// this is to big, perhaps break it out?
i.click(function(e){r.trigger("sortStart");var n=r[0].tBodies[0]&&r[0].tBodies[0].rows.length||0;if(!this.sortDisabled&&n>0){
// store exp, for speed
var a=(t(this),this.column);
// user only whants to sort on one column
if(
// get current column sort order
this.order=this.count++%2,e[s.sortMultiSortKey])
// the user has clicked on an all ready sortet column.
if(p(a,s.sortList))
// revers the sorting direction for all tables.
for(var u=0;u<s.sortList.length;u++){var f=s.sortList[u],l=s.headerList[f[0]];f[0]==a&&(l.count=f[1],l.count++,f[1]=l.count%2)}else
// add column to sort list array
s.sortList.push([a,this.order]);else{if(
// flush the sort list
s.sortList=[],null!=s.sortForce)for(var h=s.sortForce,u=0;u<h.length;u++)h[u][0]!=a&&s.sortList.push(h[u]);
// add column to sort list
s.sortList.push([a,this.order])}
// stop normal event by returning false
return setTimeout(function(){
//set css for headers
b(r[0],i,s.sortList,d),c(r[0],y(r[0],s.sortList,o))},1),!1}}).mousedown(function(){return s.cancelSelection?(this.onselectstart=function(){return!1},!1):void 0}),
// apply easy methods that trigger binded events
r.bind("update",function(){
// rebuild parsers.
this.config.parsers=n(this,i),
// rebuild the cache map
o=a(this)}).bind("sorton",function(e,r){t(this).trigger("sortStart"),s.sortList=r;
// update and store the sortlist
var n=s.sortList;
// update header count index
w(this,n),
//set css for headers
b(this,i,n,d),
// sort the table and append it to the dom
c(this,y(this,n,o))}).bind("appendCache",function(){c(this,o)}).bind("applyWidgetId",function(t,e){g(e).format(this)}).bind("applyWidgets",function(){
// apply widgets
h(this)}),t.metadata&&t(this).metadata()&&t(this).metadata().sortlist&&(s.sortList=t(this).metadata().sortlist),
// if user has supplied a sort list to constructor.
s.sortList.length>0&&r.trigger("sorton",[s.sortList]),
// apply widgets
h(this)}})},this.addParser=function(t){for(var e=F.length,r=!0,i=0;e>i;i++)F[i].id.toLowerCase()==t.id.toLowerCase()&&(r=!1);r&&F.push(t)},this.addWidget=function(t){B.push(t)},this.formatFloat=function(t){var e=parseFloat(t);return isNaN(e)?0:e},this.formatInt=function(t){var e=parseInt(t);return isNaN(e)?0:e},this.isDigit=function(e,r){var i="\\"+r.decimal,n="/(^[+]?0("+i+"0+)?$)|(^([-+]?[1-9][0-9]*)$)|(^([-+]?((0?|[1-9][0-9]*)"+i+"(0*[1-9][0-9]*)))$)|(^[-+]?[1-9]+[0-9]*"+i+"0+$)/";return RegExp(n).test(t.trim(e))},this.clearTableBody=function(e){function r(){for(;this.firstChild;)this.removeChild(this.firstChild)}t.browser.msie?r.apply(e.tBodies[0]):e.tBodies[0].innerHTML=""}}}),
// extend plugin scope
t.fn.extend({tablesorter:t.tablesorter.construct});var e=t.tablesorter;
// add default parsers
e.addParser({id:"text",is:function(t){return!0},format:function(e){return t.trim(e.toLowerCase())},type:"text"}),e.addParser({id:"digit",is:function(e,r){var i=r.config;return t.tablesorter.isDigit(e,i)},format:function(e){return t.tablesorter.formatFloat(e)},type:"numeric"}),e.addParser({id:"currency",is:function(t){return/^[£$€?.]/.test(t)},format:function(e){return t.tablesorter.formatFloat(e.replace(new RegExp(/[^0-9.]/g),""))},type:"numeric"}),e.addParser({id:"ipAddress",is:function(t){return/^\d{2,3}[\.]\d{2,3}[\.]\d{2,3}[\.]\d{2,3}$/.test(t)},format:function(e){for(var r=e.split("."),i="",n=r.length,o=0;n>o;o++){var s=r[o];i+=2==s.length?"0"+s:s}return t.tablesorter.formatFloat(i)},type:"numeric"}),e.addParser({id:"url",is:function(t){return/^(https?|ftp|file):\/\/$/.test(t)},format:function(t){return jQuery.trim(t.replace(new RegExp(/(https?|ftp|file):\/\//),""))},type:"text"}),e.addParser({id:"isoDate",is:function(t){return/^\d{4}[\/-]\d{1,2}[\/-]\d{1,2}$/.test(t)},format:function(e){return t.tablesorter.formatFloat(""!=e?new Date(e.replace(new RegExp(/-/g),"/")).getTime():"0")},type:"numeric"}),e.addParser({id:"percent",is:function(e){return/\%$/.test(t.trim(e))},format:function(e){return t.tablesorter.formatFloat(e.replace(new RegExp(/%/g),""))},type:"numeric"}),e.addParser({id:"usLongDate",is:function(t){return t.match(new RegExp(/^[A-Za-z]{3,10}\.? [0-9]{1,2}, ([0-9]{4}|'?[0-9]{2}) (([0-2]?[0-9]:[0-5][0-9])|([0-1]?[0-9]:[0-5][0-9]\s(AM|PM)))$/))},format:function(e){return t.tablesorter.formatFloat(new Date(e).getTime())},type:"numeric"}),e.addParser({id:"shortDate",is:function(t){return/\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/.test(t)},format:function(e,r){var i=r.config;
// reformat the string in ISO format
//reformat the string in ISO format
return e=e.replace(/\-/g,"/"),"us"==i.dateFormat?e=e.replace(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/,"$3/$1/$2"):"uk"==i.dateFormat?e=e.replace(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/,"$3/$2/$1"):("dd/mm/yy"==i.dateFormat||"dd-mm-yy"==i.dateFormat)&&(e=e.replace(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2})/,"$1/$2/$3")),t.tablesorter.formatFloat(new Date(e).getTime())},type:"numeric"}),e.addParser({id:"time",is:function(t){return/^(([0-2]?[0-9]:[0-5][0-9])|([0-1]?[0-9]:[0-5][0-9]\s(am|pm)))$/.test(t)},format:function(e){return t.tablesorter.formatFloat(new Date("2000/01/01 "+e).getTime())},type:"numeric"}),e.addParser({id:"metadata",is:function(t){return!1},format:function(e,r,i){var n=r.config,o=n.parserMetadataName?n.parserMetadataName:"sortValue";return t(i).metadata()[o]},type:"numeric"}),
// add default widgets
e.addWidget({id:"zebra",format:function(e){if(e.config.debug)var r=new Date;t("tr:visible",e.tBodies[0]).filter(":even").removeClass(e.config.widgetZebra.css[1]).addClass(e.config.widgetZebra.css[0]).end().filter(":odd").removeClass(e.config.widgetZebra.css[0]).addClass(e.config.widgetZebra.css[1]),e.config.debug&&t.tablesorter.benchmark("Applying Zebra widget",r)}})}(jQuery);