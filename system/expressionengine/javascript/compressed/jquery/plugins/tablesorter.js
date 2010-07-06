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
/* debuging utils */
/* parsers utils */
/* utils */
/** Add the table data to main data array */
/* sorting methods */
/* public methods */

(function(e){e.extend({tablesorter:new (function(){function b(a,c){n(a+","+((new Date).getTime()-c.getTime())+"ms")}function n(a){typeof console!="undefined"&&typeof console.debug!="undefined"?console.log(a):alert(a)}function p(a,c){if(a.config.debug)var f="";var h=a.tBodies[0].rows;if(a.tBodies[0].rows[0]){var d=[];h=h[0].cells;for(var m=h.length,g=0;g<m;g++){var j=false;if(e.metadata&&e(c[g]).metadata()&&e(c[g]).metadata().sorter)j=s(e(c[g]).metadata().sorter);else if(a.config.headers[g]&&a.config.headers[g].sorter)j=
s(a.config.headers[g].sorter);if(!j)a:{j=a;for(var k=h[g],l=q.length,r=1;r<l;r++)if(q[r].is(e.trim(z(j.config,k)),j,k)){j=q[r];break a}j=q[0]}if(a.config.debug)f+="column:"+g+" parser:"+j.id+"\n";d.push(j)}}a.config.debug&&n(f);return d}function s(a){for(var c=q.length,f=0;f<c;f++)if(q[f].id.toLowerCase()==a.toLowerCase())return q[f];return false}function t(a){if(a.config.debug)var c=new Date;for(var f=a.tBodies[0]&&a.tBodies[0].rows.length||0,h=a.tBodies[0].rows[0]&&a.tBodies[0].rows[0].cells.length||
0,d=a.config.parsers,m={row:[],normalized:[]},g=0;g<f;++g){var j=a.tBodies[0].rows[g],k=[];m.row.push(e(j));for(var l=0;l<h;++l)k.push(d[l].format(z(a.config,j.cells[l]),a,j.cells[l]));k.push(g);m.normalized.push(k)}a.config.debug&&b("Building cache for "+f+" rows:",c);return m}function z(a,c){if(!c)return"";var f="";return f=a.textExtraction=="simple"?c.childNodes[0]&&c.childNodes[0].hasChildNodes()?c.childNodes[0].innerHTML:c.innerHTML:typeof a.textExtraction=="function"?a.textExtraction(c):e(c).text()}
function x(a,c){if(a.config.debug)var f=new Date;for(var h=c.row,d=c.normalized,m=d.length,g=d[0].length-1,j=e(a.tBodies[0]),k=[],l=0;l<m;l++){k.push(h[d[l][g]]);if(!a.config.appender)for(var r=h[d[l][g]],v=r.length,u=0;u<v;u++)j[0].appendChild(r[u])}a.config.appender&&a.config.appender(a,k);k=null;a.config.debug&&b("Rebuilt table:",f);y(a);setTimeout(function(){e(a).trigger("sortEnd")},0)}function E(a){if(a.config.debug)var c=new Date;for(var f=[],h=0;h<a.tHead.rows.length;h++)f[h]=0;$tableHeaders=
e("thead th",a);$tableHeaders.each(function(d){this.count=0;this.column=d;var m=a.config.sortInitialOrder;this.order=i=typeof m!="Number"?m.toLowerCase()=="desc"?1:0:m==1?m:0;if(!(m=e.metadata&&e(this).metadata().sorter===false?true:false))m=a.config.headers[d]&&a.config.headers[d].sorter===false?true:false;if(m)this.sortDisabled=true;this.sortDisabled||e(this).addClass(a.config.cssHeader);a.config.headerList[d]=this});if(a.config.debug){b("Built headers:",c);n($tableHeaders)}return $tableHeaders}
function y(a){for(var c=a.config.widgets,f=c.length,h=0;h<f;h++)A(c[h]).format(a)}function A(a){for(var c=w.length,f=0;f<c;f++)if(w[f].id.toLowerCase()==a.toLowerCase())return w[f]}function F(a,c){for(var f=c.length,h=0;h<f;h++)if(c[h][0]==a)return true;return false}function B(a,c,f,h){c.removeClass(h[0]).removeClass(h[1]);var d=[];c.each(function(){this.sortDisabled||(d[this.column]=e(this))});a=f.length;for(c=0;c<a;c++)d[f[c][0]].addClass(h[f[c][1]])}function G(a){if(a.config.widthFixed){var c=
e("<colgroup>");e("tr:first td",a.tBodies[0]).each(function(){c.append(e("<col>").css("width",e(this).width()))});e(a).prepend(c)}}function C(a,c,f){if(a.config.debug)var h=new Date;for(var d="var sortWrapper = function(a,b) {",m=c.length,g=0;g<m;g++){var j=c[g][0],k=c[g][1],l="e"+g;d+="var "+l+" = "+(a.config.parsers[j].type=="text"?k==0?"sortText":"sortTextDesc":k==0?"sortNumeric":"sortNumericDesc")+"(a["+j+"],b["+j+"]); ";d+="if("+l+") { return "+l+"; } ";d+="else { "}g=f.normalized[0].length-
1;d+="return a["+g+"]-b["+g+"];";for(g=0;g<m;g++)d+="}; ";d+="return 0; ";d+="}; ";eval(d);f.normalized.sort(sortWrapper);a.config.debug&&b("Sorting on "+c.toString()+" and dir "+k+" time:",h);return f}var q=[],w=[];this.defaults={cssHeader:"header",cssAsc:"headerSortUp",cssDesc:"headerSortDown",sortInitialOrder:"asc",sortMultiSortKey:"shiftKey",sortForce:null,sortAppend:null,textExtraction:"simple",parsers:{},widgets:[],widgetZebra:{css:["even","odd"]},headers:{},widthFixed:false,cancelSelection:true,
sortList:[],headerList:[],dateFormat:"us",decimal:".",debug:false};this.benchmark=b;this.construct=function(a){return this.each(function(){if(this.tHead&&this.tBodies){var c,f,h,d;this.config={};d=e.extend(this.config,e.tablesorter.defaults,a);c=e(this);f=E(this);this.config.parsers=p(this,f);h=t(this);var m=[d.cssDesc,d.cssAsc];G(this);f.click(function(g){c.trigger("sortStart");var j=c[0].tBodies[0]&&c[0].tBodies[0].rows.length||0;if(!this.sortDisabled&&j>0){e(this);j=this.column;this.order=this.count++%
2;if(g[d.sortMultiSortKey])if(F(j,d.sortList))for(g=0;g<d.sortList.length;g++){var k=d.sortList[g],l=d.headerList[k[0]];if(k[0]==j){l.count=k[1];l.count++;k[1]=l.count%2}}else d.sortList.push([j,this.order]);else{d.sortList=[];if(d.sortForce!=null){k=d.sortForce;for(g=0;g<k.length;g++)k[g][0]!=j&&d.sortList.push(k[g])}d.sortList.push([j,this.order])}setTimeout(function(){B(c[0],f,d.sortList,m);x(c[0],C(c[0],d.sortList,h))},1);return false}}).mousedown(function(){if(d.cancelSelection){this.onselectstart=
function(){return false};return false}});c.bind("update",function(){this.config.parsers=p(this,f);h=t(this)}).bind("sorton",function(g,j){e(this).trigger("sortStart");d.sortList=j;for(var k=d.sortList,l=this.config,r=k.length,v=0;v<r;v++){var u=k[v],D=l.headerList[u[0]];D.count=u[1];D.count++}B(this,f,k,m);x(this,C(this,k,h))}).bind("appendCache",function(){x(this,h)}).bind("applyWidgetId",function(g,j){A(j).format(this)}).bind("applyWidgets",function(){y(this)});if(e.metadata&&e(this).metadata()&&
e(this).metadata().sortlist)d.sortList=e(this).metadata().sortlist;d.sortList.length>0&&c.trigger("sorton",[d.sortList]);y(this)}})};this.addParser=function(a){for(var c=q.length,f=true,h=0;h<c;h++)if(q[h].id.toLowerCase()==a.id.toLowerCase())f=false;f&&q.push(a)};this.addWidget=function(a){w.push(a)};this.formatFloat=function(a){a=parseFloat(a);return isNaN(a)?0:a};this.formatInt=function(a){a=parseInt(a);return isNaN(a)?0:a};this.isDigit=function(a,c){var f="\\"+c.decimal;return RegExp("/(^[+]?0("+
f+"0+)?$)|(^([-+]?[1-9][0-9]*)$)|(^([-+]?((0?|[1-9][0-9]*)"+f+"(0*[1-9][0-9]*)))$)|(^[-+]?[1-9]+[0-9]*"+f+"0+$)/").test(e.trim(a))};this.clearTableBody=function(a){if(e.browser.msie)(function(){for(;this.firstChild;)this.removeChild(this.firstChild)}).apply(a.tBodies[0]);else a.tBodies[0].innerHTML=""}})});e.fn.extend({tablesorter:e.tablesorter.construct});var o=e.tablesorter;o.addParser({id:"text",is:function(){return true},format:function(b){return e.trim(b.toLowerCase())},type:"text"});o.addParser({id:"digit",
is:function(b,n){return e.tablesorter.isDigit(b,n.config)},format:function(b){return e.tablesorter.formatFloat(b)},type:"numeric"});o.addParser({id:"currency",is:function(b){return/^[\u00a3$\u20ac?.]/.test(b)},format:function(b){return e.tablesorter.formatFloat(b.replace(RegExp(/[^0-9.]/g),""))},type:"numeric"});o.addParser({id:"ipAddress",is:function(b){return/^\d{2,3}[\.]\d{2,3}[\.]\d{2,3}[\.]\d{2,3}$/.test(b)},format:function(b){b=b.split(".");for(var n="",p=b.length,s=0;s<p;s++){var t=b[s];n+=
t.length==2?"0"+t:t}return e.tablesorter.formatFloat(n)},type:"numeric"});o.addParser({id:"url",is:function(b){return/^(https?|ftp|file):\/\/$/.test(b)},format:function(b){return jQuery.trim(b.replace(RegExp(/(https?|ftp|file):\/\//),""))},type:"text"});o.addParser({id:"isoDate",is:function(b){return/^\d{4}[\/-]\d{1,2}[\/-]\d{1,2}$/.test(b)},format:function(b){return e.tablesorter.formatFloat(b!=""?(new Date(b.replace(RegExp(/-/g),"/"))).getTime():"0")},type:"numeric"});o.addParser({id:"percent",
is:function(b){return/\%$/.test(e.trim(b))},format:function(b){return e.tablesorter.formatFloat(b.replace(RegExp(/%/g),""))},type:"numeric"});o.addParser({id:"usLongDate",is:function(b){return b.match(RegExp(/^[A-Za-z]{3,10}\.? [0-9]{1,2}, ([0-9]{4}|'?[0-9]{2}) (([0-2]?[0-9]:[0-5][0-9])|([0-1]?[0-9]:[0-5][0-9]\s(AM|PM)))$/))},format:function(b){return e.tablesorter.formatFloat((new Date(b)).getTime())},type:"numeric"});o.addParser({id:"shortDate",is:function(b){return/\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/.test(b)},
format:function(b,n){var p=n.config;b=b.replace(/\-/g,"/");if(p.dateFormat=="us")b=b.replace(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/,"$3/$1/$2");else if(p.dateFormat=="uk")b=b.replace(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/,"$3/$2/$1");else if(p.dateFormat=="dd/mm/yy"||p.dateFormat=="dd-mm-yy")b=b.replace(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2})/,"$1/$2/$3");return e.tablesorter.formatFloat((new Date(b)).getTime())},type:"numeric"});o.addParser({id:"time",is:function(b){return/^(([0-2]?[0-9]:[0-5][0-9])|([0-1]?[0-9]:[0-5][0-9]\s(am|pm)))$/.test(b)},
format:function(b){return e.tablesorter.formatFloat((new Date("2000/01/01 "+b)).getTime())},type:"numeric"});o.addParser({id:"metadata",is:function(){return false},format:function(b,n,p){b=n.config;b=!b.parserMetadataName?"sortValue":b.parserMetadataName;return e(p).metadata()[b]},type:"numeric"});o.addWidget({id:"zebra",format:function(b){if(b.config.debug)var n=new Date;e("tr:visible",b.tBodies[0]).filter(":even").removeClass(b.config.widgetZebra.css[1]).addClass(b.config.widgetZebra.css[0]).end().filter(":odd").removeClass(b.config.widgetZebra.css[0]).addClass(b.config.widgetZebra.css[1]);
b.config.debug&&e.tablesorter.benchmark("Applying Zebra widget",n)}})})(jQuery);
