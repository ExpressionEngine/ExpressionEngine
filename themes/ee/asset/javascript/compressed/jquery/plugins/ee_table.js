/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.3
 * @filesource
 */
!function(t){"use strict";
// --------------------------------------------------------------------------
/**
 * Implements a LRU (least-recently-used) cache.
 */
function e(t){this.size=0,this.limit=t,this.cache=[],// [[page, data], [page2, data2]]
this.cache_map=[]}
// --------------------------------------------------------------------------
/**
 * Table pagination class
 */
function i(e,i){var s=this;this.els=t("p."+e.uniqid),this.template_id=e.uniqid+"_pag_template",
// compile the template
t.template(this.template_id,e.pagination),
// _request will grab the new page, and then call update
this.els.delegate("a","click",function(){var t=s._extract_qs(this.href,i.options.base_url);return i.add_filter(t),!1})}
// --------------------------------------------------------------------------
/**
 * Table sorting class
 */
function s(e,i){var s=this;
// @todo pass css and sort prefs configs (initial + allowed columns)
// @todo make our own based on the php example sort?
if(this.sort=[],this.plugin=i,this.headers=i.element.find("th"),this.css={asc:e.cssAsc,desc:e.cssDesc},
// helpers
this.header_map={},this._initial_sort=e.sort,!e.pagination)return void t(i.element).tablesorter();
// cache all headers and check if we want
// them to be sortable
this.headers.each(function(){var i=t(this),r=i.data("table_column");s.header_map[r]=i,i.data("sortable",e.columns[r].sort)}),
// setup events
i.element.find("thead").delegate("th","selectstart",function(){return!1}).delegate("th","click",function(i){var r=t(this);
// allow things like checkboxes inside table headers
if(r.has("input").length)return!0;
// if holding shift key: add
if(!r.data("sortable"))return!1;var n=i.shiftKey?"add":"set";return s[n](r.data("table_column"),r.hasClass(e.cssAsc)?"desc":"asc"),!1});for(
// setup initial sort without making a request
// @todo, this could be better
var r=this._initial_sort.length;r--;){var n=this.header_map[this._initial_sort[r][0]];void 0!==n&&(this.sort.push(this._initial_sort[r]),n.toggleClass(this.css.asc,"asc"===this._initial_sort[r][1]).toggleClass(this.css.desc,"desc"===this._initial_sort[r][1]))}}
// @todo @pk ideas -------------
// ensure copyright notice on all files (I always forget ...)
// edit specific:
// add base64 thing to "add" tab link to "save" the search
//		- need global method to manipulate it first
// clear search link (redirect to base path)
// add sort to "return to filtered entries"? (feature)
// TODO:
// sorting on single page, should just need to set up with add_data
// make sure that all of this works with multiple tables on the page (it should)
// make keyup timeout configurable
// flip headerSortUp and down in the css, is silly
// /@todo @pk todo/ideas -------------
t.widget("ee.table",{_listening:t(),// form elements the filter is listening to
_template_string:"",// raw template
_current_data:{},// current_data
options:{uniqid:null,// uniqid of related elements
base_url:null,// url for requests
pagination:null,// element(s)
template:null,// table template
pag_template:null,// pagination template
rows:[],// initial row data
sort:[],// [[column, desc], [column2, asc]]
columns:[],// column names to match_data / search, filter on the spot
cache_limit:600,// number of items, not pages!
filters:{},// map of active filters {"field": "bar"}, usually updated right before request is made
cssAsc:"headerSortDown",// matches tablesorter.js
cssDesc:"headerSortUp"},
// jQuery ui widget constructor
_create:function(){var r=this,n=r.options;
// cache content parent and no results
r.tbody=r.element.find("tbody"),r.tbody.size()||(r.tbody=t("<tbody/>"),r.element.append(r.tbody)),r.no_results=t("<div />").html(n.no_results),
// check if we need no results to begin with
r.tbody.children().length||(r.element.hide(),r.element.after(r.no_results)),
// set defaults
r.filters=n.filters,
// fix ampersands
n.base_url=n.base_url.replace(new RegExp("&amp;","g"),"&"),
// setup dependencies
r.sort=new s(n,r),r.cache=new e(n.cache_limit),r.pagination=new i(n,r);
// cache initial data
var a=r._prep_for_cache(),h={html_rows:r.tbody.find("tr"),pagination:r.pagination.html(),rows:n.rows};r.cache.set(a,h),r._current_data=h,
// create unique template name and compile
r.template_id=n.uniqid+"_row_template",r.set_template(n.template),
// bind create event (@todo consider ditching, pretty much impossible to bind on)
r._trigger("create",null,r._ui({data:h}))},/**
	 * Get container (tbody)
	 */
get_container:function(){return this.tbody},/**
	 * Set container
	 */
set_container:function(e){this.tbody=t(e)},/**
	 * Get header element by short name
	 */
get_header:function(e){self.element.find("th").filter(function(){return t(this).data("table_column")==e})},/**
	 * Get raw template string
	 */
get_template:function(){return this._template_string},/**
	 * Use a different template
	 */
set_template:function(e){this._template_string=e,t.template(this.template_id,e)},/**
	 * Get current cache
	 */
get_current_data:function(){return this._current_data},/**
	 * Clear all caches
	 */
clear_cache:function(){return this.cache.clear(),this},/**
	 * Unbind all filters
	 */
clear_filters:function(){
// @todo reset form content?
return this.filters={},this._listening.each(function(){t(this).unbind("interact.ee_table")}),this},/**
	 * Reset sort to initial conditions
	 */
clear_sort:function(){
// @todo fire sort events
return this.sort.reset(),this},/**
	 * Add a filter
	 *
	 * Can be a form or a regular object
	 */
add_filter:function(e){var i=this,s=EE.BASE+"&"+i.options.base_url;
// add to filters and update right away
// @todo do not hardcode url!
if(t.isPlainObject(e))return i._set_filter(i._listening),i.filters=t.extend(i.filters,e),i._request(s),this;var r,n=e.closest("form"),a="interact.ee_table";
// bind to submit only if it's a form
// A filter outside of a form? We most likely don't want enter to
// do anything. This was happening in the file modal search box
return n&&e.is(n)?a+=" submit.ee_table":e.bind("keydown",function(t){13==t.keyCode&&t.preventDefault()}),t(e).bind(a,function(t){
// @todo only timeout on some inputs? (textareas)
return clearTimeout(r),r=setTimeout(function(){i._set_filter(i._listening),i._request(s)},200),!1}),i._listening=i._listening.add(e),i._set_filter(i._listening),this},/**
	 * Set sort (see sort::set for info)
	 */
set_sort:function(t,e){return this.sort.set(t,e),this},/**
	 * Add sort (see sort::add for info)
	 */
add_sort:function(t,e){return this.sort.add(t,e),this},/**
	 * Refresh with current filters and sort intact
	 */
refresh:function(){var t=EE.BASE+"&"+this.options.base_url;return this._request(t),this},/**
	 * Make a request with the current filters and sort
	 *
	 * Updates the main table, pagination, caches, and triggers
	 * the load and update events.
	 */
_request:function(e){var i,s,r=this;r._trigger("load",null,r._ui()),
// A cache hit and an ajax result below are both
// considered successes and will call this with
// the correct data =)
s=function(t){r._current_data=t,
// @todo only remove those that are not in the result set?
t.rows.length?(r.element.show(),r.tbody.html(t.html_rows),r.no_results.remove()):r.tbody.is("tbody")?(r.tbody.empty(),r.element.hide(),r.element.after(r.no_results)):r.tbody.html(r.no_results),r.pagination.update(t.pagination),r._trigger("update",null,r._ui({data:t}))};var n=r._prep_for_cache();
// Do we have this page cached?
// The pagination library reads from get, so we need
// to move tbl_offset. Doing it down here allows it
// to be in the cache key without dark magic.
// Always send an XID
// fire request start event (show progress indicator)
return i=r.cache.get(n),null!==i?s(i):(r.filters.tbl_offset&&(e+="&tbl_offset="+r.filters.tbl_offset,delete r.filters.tbl_offset),r.filters.XID=EE.XID,void t.ajax(e,{type:"post",data:r.filters,success:function(e){
// parse data
e.html_rows=t.tmpl(r.template_id,e.rows),e.pagination=r.pagination.parse(e.pagination),
// add to cache
r.cache.set(n,e,e.rows.length),s(e)},dataType:"json"}))},/**
	 * Weed out the stuff we don't want in there, like XIDs,
	 * session ids, and blank values

	 * Also take this opportunity to create a stable cache key, as
	 * some browsers sort objects and some do not =( . To get consistency
	 * for those that don't sort, we push keys and values into an array,
	 * sort the array, and concat to get a string. -pk
	*/
_prep_for_cache:function(){this.filters.tbl_sort=this.sort.get();var t,e=/^(XID|S|D|C|M)$/,i=[];for(t in this.filters)""==this.filters[t]||null!==e.exec(t)?delete this.filters[t]:i.push(t,this.filters[t]);return i.sort(),i.join("")},/**
	 * Helper method to set the filter object
	 * from form elements.
	 */
_set_filter:function(e){var i=e.serializeArray(),s=this;t.each(i,function(){s.filters[this.name]=this.value})},/**
	 * Event data helper
	 *
	 * Should reflect the state most hooks might care about
	 */
_ui:function(e){return e=e||{},t.extend({sort:this.sort.get(),filters:this.filters},e)}}),e.prototype={/*
	 * Get the cache limit
	 */
limit:function(){return this.limit},/*
	 * Get current cache size
	 */
size:function(){return this.cache.length},/**
	 * Add a cache item
	 *
	 * @param string	unique identifier
	 * @param mixed		data to cache
	 * @param int		penalty against cache limit [default = 1]
	 *
	 * We cache per page, but since our page length is variable, we want
	 * to control cache size per row. Cache_weight exists so that this
	 * plugin remains decoupled.
	 */
set:function(t,e,i){
// evict data until this item fits
for(var s=i||1;this.size+s>this.limit;){var r=this.cache.shift();this.cache_map.shift(),this.size-=r[2]}return this.cache.push([t,e,s]),this.cache_map.push(t),this.size+=s,this},/**
	 * Get a cached item
	 *
	 * If the cache key exists, it is moved to the top
	 * of a stack to avoid eviction (LRU behavior).
	 *
	 * @param	string	cache id
	 * @return	mixed	cached item or null
	 */
get:function(t){var e,i=this._find(t);
// detach and push on top of the queue (newest element)
// fix up our map
return i>-1?(e=this.cache.splice(i,1)[0],this.cache.push(e),this.cache_map.splice(i,1),this.cache_map.push(e[0]),e[1]):null},/**
	 * Delete a cached item
	 */
"delete":function(t){var e,i=this._find(t);return i>-1&&(e=this.cache.splice(i,1),this.cache_map.splice(i,1),this.size-=e[2]),this},/**
	 * Clear cache
	 */
clear:function(){return this.size=0,this.cache=[],this.cache_map=[],this},/**
	 * Find item in cache
	 *
	 * Helper method as IE does not support indexOf
	 * on arrays. This is also the reason why cache_map
	 * exists: we can search it with a native function
	 * and it's faster to iterate if we fall back.
	 */
_find:function(t){
// oh hello there IE
if(!Array.prototype.indexOf){for(var e=this.cache_map,i=e.length,s=0;i>s;s++)if(e[s]==t)return s;return-1}
// native functions!
return this.cache_map.indexOf(t)}},i.prototype={/**
	 * Parse the pagination data
	 *
	 * Only parsed once and then stuck into the
	 * page cache along with its data
	 */
parse:function(e){return e?t.tmpl(this.template_id,e).html():""},/**
	 * Update the pagination html
	 *
	 * @param mixed results from parse [cached]
	 */
update:function(t){return t?void this.els.html(t).show():void this.els.html("")},/**
	 * Get the pagination html
	 *
	 * Used to fill the initial cache
	 */
html:function(){return this.els.html()},
// Private methods //
/**
	 * Extract Query String from link
	 *
	 * Needed to allow pagination on "saved" searches,
	 * where the keywords might be in the url and we need
	 * to manually apply them to the next page.
	 */
_qs_splitter:new RegExp("([^&=]+)=?([^&]*)","g"),_extract_qs:function(t,e){t=t.replace(e,"");var i,s=t.indexOf("?"),r={};for(
// only work through the qs
s>0&&(t=t.slice(s+1));i=this._qs_splitter.exec(t);)r[decodeURIComponent(i[1])]=decodeURIComponent(i[2]);return r}},s.prototype={/**
	 * Get current sort
	 *
	 * @param	string	column name for sort to return [optional]
	 * @return	mixed	full sort array | sort direction of column | null
	 */
get:function(t){if(t){for(var e=this.sort.length;e--;)if(this.sort[e][0]==t)return this.sort[e][1];return null}return this.sort},/**
	 * Add sort to column
	 *
	 * @param	string	column name (or full sort array, see set)
	 * @param	string	sort direction [asc|desc]
	 */
add:function(t,e){var i,s=t;for(e&&(s=[[t,e]]),
// @todo fire addSort events
i=s.length;i--;)this.sort.push(s[i]),this.header_map[s[i][0]].toggleClass(this.css.asc,"asc"===s[i][1]).toggleClass(this.css.desc,"desc"===s[i][1]);return this.plugin.refresh(),this},/**
	 * Set sort
	 *
	 * @param	mixed	sort array ([[field, dir], [field2, dir]])
	 */
set:function(t,e){
// clear and add
return this.clear(),this.add(t,e),this},/**
	 * Reset sort to initial conditions
	 */
reset:function(){return this.clear(),this.set(this._initial_sort),this.plugin.refresh(),this},/**
	 * Clear sort entirely, does not reset
	 */
clear:function(){for(var t=this.sort.length;t--;)this.header_map[this.sort[t][0]].removeClass(this.css.asc+" "+this.css.desc);return this.sort=[],this}}}(jQuery);