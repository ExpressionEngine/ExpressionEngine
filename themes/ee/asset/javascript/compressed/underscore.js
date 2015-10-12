//     Underscore.js 1.4.4
//     http://underscorejs.org
//     (c) 2009-2013 Jeremy Ashkenas, DocumentCloud Inc.
//     Underscore may be freely distributed under the MIT license.
(function(){
// Baseline setup
// --------------
// Establish the root object, `window` in the browser, or `global` on the server.
var n=this,t=n._,r={},e=Array.prototype,u=Object.prototype,i=Function.prototype,a=e.push,o=e.slice,c=e.concat,l=u.toString,f=u.hasOwnProperty,s=e.forEach,p=e.map,h=e.reduce,v=e.reduceRight,d=e.filter,g=e.every,m=e.some,y=e.indexOf,b=e.lastIndexOf,x=Array.isArray,_=Object.keys,w=i.bind,j=function(n){return n instanceof j?n:this instanceof j?void(this._wrapped=n):new j(n)};
// Export the Underscore object for **Node.js**, with
// backwards-compatibility for the old `require()` API. If we're in
// the browser, add `_` as a global object via a string identifier,
// for Closure Compiler "advanced" mode.
"undefined"!=typeof exports?("undefined"!=typeof module&&module.exports&&(exports=module.exports=j),exports._=j):n._=j,
// Current version.
j.VERSION="1.4.4";
// Collection Functions
// --------------------
// The cornerstone, an `each` implementation, aka `forEach`.
// Handles objects with the built-in `forEach`, arrays, and raw objects.
// Delegates to **ECMAScript 5**'s native `forEach` if available.
var A=j.each=j.forEach=function(n,t,e){if(null!=n)if(s&&n.forEach===s)n.forEach(t,e);else if(n.length===+n.length){for(var u=0,i=n.length;i>u;u++)if(t.call(e,n[u],u,n)===r)return}else for(var a in n)if(j.has(n,a)&&t.call(e,n[a],a,n)===r)return};
// Return the results of applying the iterator to each element.
// Delegates to **ECMAScript 5**'s native `map` if available.
j.map=j.collect=function(n,t,r){var e=[];return null==n?e:p&&n.map===p?n.map(t,r):(A(n,function(n,u,i){e[e.length]=t.call(r,n,u,i)}),e)};var E="Reduce of empty array with no initial value";
// **Reduce** builds up a single result from a list of values, aka `inject`,
// or `foldl`. Delegates to **ECMAScript 5**'s native `reduce` if available.
j.reduce=j.foldl=j.inject=function(n,t,r,e){var u=arguments.length>2;if(null==n&&(n=[]),h&&n.reduce===h)return e&&(t=j.bind(t,e)),u?n.reduce(t,r):n.reduce(t);if(A(n,function(n,i,a){u?r=t.call(e,r,n,i,a):(r=n,u=!0)}),!u)throw new TypeError(E);return r},
// The right-associative version of reduce, also known as `foldr`.
// Delegates to **ECMAScript 5**'s native `reduceRight` if available.
j.reduceRight=j.foldr=function(n,t,r,e){var u=arguments.length>2;if(null==n&&(n=[]),v&&n.reduceRight===v)return e&&(t=j.bind(t,e)),u?n.reduceRight(t,r):n.reduceRight(t);var i=n.length;if(i!==+i){var a=j.keys(n);i=a.length}if(A(n,function(o,c,l){c=a?a[--i]:--i,u?r=t.call(e,r,n[c],c,l):(r=n[c],u=!0)}),!u)throw new TypeError(E);return r},
// Return the first value which passes a truth test. Aliased as `detect`.
j.find=j.detect=function(n,t,r){var e;return O(n,function(n,u,i){return t.call(r,n,u,i)?(e=n,!0):void 0}),e},
// Return all the elements that pass a truth test.
// Delegates to **ECMAScript 5**'s native `filter` if available.
// Aliased as `select`.
j.filter=j.select=function(n,t,r){var e=[];return null==n?e:d&&n.filter===d?n.filter(t,r):(A(n,function(n,u,i){t.call(r,n,u,i)&&(e[e.length]=n)}),e)},
// Return all the elements for which a truth test fails.
j.reject=function(n,t,r){return j.filter(n,function(n,e,u){return!t.call(r,n,e,u)},r)},
// Determine whether all of the elements match a truth test.
// Delegates to **ECMAScript 5**'s native `every` if available.
// Aliased as `all`.
j.every=j.all=function(n,t,e){t||(t=j.identity);var u=!0;return null==n?u:g&&n.every===g?n.every(t,e):(A(n,function(n,i,a){return(u=u&&t.call(e,n,i,a))?void 0:r}),!!u)};
// Determine if at least one element in the object matches a truth test.
// Delegates to **ECMAScript 5**'s native `some` if available.
// Aliased as `any`.
var O=j.some=j.any=function(n,t,e){t||(t=j.identity);var u=!1;return null==n?u:m&&n.some===m?n.some(t,e):(A(n,function(n,i,a){return u||(u=t.call(e,n,i,a))?r:void 0}),!!u)};
// Determine if the array or object contains a given value (using `===`).
// Aliased as `include`.
j.contains=j.include=function(n,t){return null==n?!1:y&&n.indexOf===y?-1!=n.indexOf(t):O(n,function(n){return n===t})},
// Invoke a method (with arguments) on every item in a collection.
j.invoke=function(n,t){var r=o.call(arguments,2),e=j.isFunction(t);return j.map(n,function(n){return(e?t:n[t]).apply(n,r)})},
// Convenience version of a common use case of `map`: fetching a property.
j.pluck=function(n,t){return j.map(n,function(n){return n[t]})},
// Convenience version of a common use case of `filter`: selecting only objects
// containing specific `key:value` pairs.
j.where=function(n,t,r){return j.isEmpty(t)?r?void 0:[]:j[r?"find":"filter"](n,function(n){for(var r in t)if(t[r]!==n[r])return!1;return!0})},
// Convenience version of a common use case of `find`: getting the first object
// containing specific `key:value` pairs.
j.findWhere=function(n,t){return j.where(n,t,!0)},
// Return the maximum element or (element-based computation).
// Can't optimize arrays of integers longer than 65,535 elements.
// See: https://bugs.webkit.org/show_bug.cgi?id=80797
j.max=function(n,t,r){if(!t&&j.isArray(n)&&n[0]===+n[0]&&n.length<65535)return Math.max.apply(Math,n);if(!t&&j.isEmpty(n))return-(1/0);var e={computed:-(1/0),value:-(1/0)};return A(n,function(n,u,i){var a=t?t.call(r,n,u,i):n;a>=e.computed&&(e={value:n,computed:a})}),e.value},
// Return the minimum element (or element-based computation).
j.min=function(n,t,r){if(!t&&j.isArray(n)&&n[0]===+n[0]&&n.length<65535)return Math.min.apply(Math,n);if(!t&&j.isEmpty(n))return 1/0;var e={computed:1/0,value:1/0};return A(n,function(n,u,i){var a=t?t.call(r,n,u,i):n;a<e.computed&&(e={value:n,computed:a})}),e.value},
// Shuffle an array.
j.shuffle=function(n){var t,r=0,e=[];return A(n,function(n){t=j.random(r++),e[r-1]=e[t],e[t]=n}),e};
// An internal function to generate lookup iterators.
var k=function(n){return j.isFunction(n)?n:function(t){return t[n]}};
// Sort the object's values by a criterion produced by an iterator.
j.sortBy=function(n,t,r){var e=k(t);return j.pluck(j.map(n,function(n,t,u){return{value:n,index:t,criteria:e.call(r,n,t,u)}}).sort(function(n,t){var r=n.criteria,e=t.criteria;if(r!==e){if(r>e||void 0===r)return 1;if(e>r||void 0===e)return-1}return n.index<t.index?-1:1}),"value")};
// An internal function used for aggregate "group by" operations.
var F=function(n,t,r,e){var u={},i=k(t||j.identity);return A(n,function(t,a){var o=i.call(r,t,a,n);e(u,o,t)}),u};
// Groups the object's values by a criterion. Pass either a string attribute
// to group by, or a function that returns the criterion.
j.groupBy=function(n,t,r){return F(n,t,r,function(n,t,r){(j.has(n,t)?n[t]:n[t]=[]).push(r)})},
// Counts instances of an object that group by a certain criterion. Pass
// either a string attribute to count by, or a function that returns the
// criterion.
j.countBy=function(n,t,r){return F(n,t,r,function(n,t){j.has(n,t)||(n[t]=0),n[t]++})},
// Use a comparator function to figure out the smallest index at which
// an object should be inserted so as to maintain order. Uses binary search.
j.sortedIndex=function(n,t,r,e){r=null==r?j.identity:k(r);for(var u=r.call(e,t),i=0,a=n.length;a>i;){var o=i+a>>>1;r.call(e,n[o])<u?i=o+1:a=o}return i},
// Safely convert anything iterable into a real, live array.
j.toArray=function(n){return n?j.isArray(n)?o.call(n):n.length===+n.length?j.map(n,j.identity):j.values(n):[]},
// Return the number of elements in an object.
j.size=function(n){return null==n?0:n.length===+n.length?n.length:j.keys(n).length},
// Array Functions
// ---------------
// Get the first element of an array. Passing **n** will return the first N
// values in the array. Aliased as `head` and `take`. The **guard** check
// allows it to work with `_.map`.
j.first=j.head=j.take=function(n,t,r){return null==n?void 0:null==t||r?n[0]:o.call(n,0,t)},
// Returns everything but the last entry of the array. Especially useful on
// the arguments object. Passing **n** will return all the values in
// the array, excluding the last N. The **guard** check allows it to work with
// `_.map`.
j.initial=function(n,t,r){return o.call(n,0,n.length-(null==t||r?1:t))},
// Get the last element of an array. Passing **n** will return the last N
// values in the array. The **guard** check allows it to work with `_.map`.
j.last=function(n,t,r){return null==n?void 0:null==t||r?n[n.length-1]:o.call(n,Math.max(n.length-t,0))},
// Returns everything but the first entry of the array. Aliased as `tail` and `drop`.
// Especially useful on the arguments object. Passing an **n** will return
// the rest N values in the array. The **guard**
// check allows it to work with `_.map`.
j.rest=j.tail=j.drop=function(n,t,r){return o.call(n,null==t||r?1:t)},
// Trim out all falsy values from an array.
j.compact=function(n){return j.filter(n,j.identity)};
// Internal implementation of a recursive `flatten` function.
var R=function(n,t,r){return A(n,function(n){j.isArray(n)?t?a.apply(r,n):R(n,t,r):r.push(n)}),r};
// Return a completely flattened version of an array.
j.flatten=function(n,t){return R(n,t,[])},
// Return a version of the array that does not contain the specified value(s).
j.without=function(n){return j.difference(n,o.call(arguments,1))},
// Produce a duplicate-free version of the array. If the array has already
// been sorted, you have the option of using a faster algorithm.
// Aliased as `unique`.
j.uniq=j.unique=function(n,t,r,e){j.isFunction(t)&&(e=r,r=t,t=!1);var u=r?j.map(n,r,e):n,i=[],a=[];return A(u,function(r,e){(t?e&&a[a.length-1]===r:j.contains(a,r))||(a.push(r),i.push(n[e]))}),i},
// Produce an array that contains the union: each distinct element from all of
// the passed-in arrays.
j.union=function(){return j.uniq(c.apply(e,arguments))},
// Produce an array that contains every item shared between all the
// passed-in arrays.
j.intersection=function(n){var t=o.call(arguments,1);return j.filter(j.uniq(n),function(n){return j.every(t,function(t){return j.indexOf(t,n)>=0})})},
// Take the difference between one array and a number of other arrays.
// Only the elements present in just the first array will remain.
j.difference=function(n){var t=c.apply(e,o.call(arguments,1));return j.filter(n,function(n){return!j.contains(t,n)})},
// Zip together multiple lists into a single array -- elements that share
// an index go together.
j.zip=function(){for(var n=o.call(arguments),t=j.max(j.pluck(n,"length")),r=new Array(t),e=0;t>e;e++)r[e]=j.pluck(n,""+e);return r},
// Converts lists into objects. Pass either a single array of `[key, value]`
// pairs, or two parallel arrays of the same length -- one of keys, and one of
// the corresponding values.
j.object=function(n,t){if(null==n)return{};for(var r={},e=0,u=n.length;u>e;e++)t?r[n[e]]=t[e]:r[n[e][0]]=n[e][1];return r},
// If the browser doesn't supply us with indexOf (I'm looking at you, **MSIE**),
// we need this function. Return the position of the first occurrence of an
// item in an array, or -1 if the item is not included in the array.
// Delegates to **ECMAScript 5**'s native `indexOf` if available.
// If the array is large and already in sort order, pass `true`
// for **isSorted** to use binary search.
j.indexOf=function(n,t,r){if(null==n)return-1;var e=0,u=n.length;if(r){if("number"!=typeof r)return e=j.sortedIndex(n,t),n[e]===t?e:-1;e=0>r?Math.max(0,u+r):r}if(y&&n.indexOf===y)return n.indexOf(t,r);for(;u>e;e++)if(n[e]===t)return e;return-1},
// Delegates to **ECMAScript 5**'s native `lastIndexOf` if available.
j.lastIndexOf=function(n,t,r){if(null==n)return-1;var e=null!=r;if(b&&n.lastIndexOf===b)return e?n.lastIndexOf(t,r):n.lastIndexOf(t);for(var u=e?r:n.length;u--;)if(n[u]===t)return u;return-1},
// Generate an integer Array containing an arithmetic progression. A port of
// the native Python `range()` function. See
// [the Python documentation](http://docs.python.org/library/functions.html#range).
j.range=function(n,t,r){arguments.length<=1&&(t=n||0,n=0),r=arguments[2]||1;for(var e=Math.max(Math.ceil((t-n)/r),0),u=0,i=new Array(e);e>u;)i[u++]=n,n+=r;return i},
// Function (ahem) Functions
// ------------------
// Create a function bound to a given object (assigning `this`, and arguments,
// optionally). Delegates to **ECMAScript 5**'s native `Function.bind` if
// available.
j.bind=function(n,t){if(n.bind===w&&w)return w.apply(n,o.call(arguments,1));var r=o.call(arguments,2);return function(){return n.apply(t,r.concat(o.call(arguments)))}},
// Partially apply a function by creating a version that has had some of its
// arguments pre-filled, without changing its dynamic `this` context.
j.partial=function(n){var t=o.call(arguments,1);return function(){return n.apply(this,t.concat(o.call(arguments)))}},
// Bind all of an object's methods to that object. Useful for ensuring that
// all callbacks defined on an object belong to it.
j.bindAll=function(n){var t=o.call(arguments,1);if(0===t.length)throw new Error("bindAll must be passed function names");return A(t,function(t){n[t]=j.bind(n[t],n)}),n},
// Memoize an expensive function by storing its results.
j.memoize=function(n,t){var r={};return t||(t=j.identity),function(){var e=t.apply(this,arguments);return j.has(r,e)?r[e]:r[e]=n.apply(this,arguments)}},
// Delays a function for the given number of milliseconds, and then calls
// it with the arguments supplied.
j.delay=function(n,t){var r=o.call(arguments,2);return setTimeout(function(){return n.apply(null,r)},t)},
// Defers a function, scheduling it to run after the current call stack has
// cleared.
j.defer=function(n){return j.delay.apply(j,[n,1].concat(o.call(arguments,1)))},
// Returns a function, that, when invoked, will only be triggered at most once
// during a given window of time.
j.throttle=function(n,t){var r,e,u,i,a=0,o=function(){a=new Date,u=null,i=n.apply(r,e)};return function(){var c=new Date,l=t-(c-a);return r=this,e=arguments,0>=l?(clearTimeout(u),u=null,a=c,i=n.apply(r,e)):u||(u=setTimeout(o,l)),i}},
// Returns a function, that, as long as it continues to be invoked, will not
// be triggered. The function will be called after it stops being called for
// N milliseconds. If `immediate` is passed, trigger the function on the
// leading edge, instead of the trailing.
j.debounce=function(n,t,r){var e,u;return function(){var i=this,a=arguments,o=function(){e=null,r||(u=n.apply(i,a))},c=r&&!e;return clearTimeout(e),e=setTimeout(o,t),c&&(u=n.apply(i,a)),u}},
// Returns a function that will be executed at most one time, no matter how
// often you call it. Useful for lazy initialization.
j.once=function(n){var t,r=!1;return function(){return r?t:(r=!0,t=n.apply(this,arguments),n=null,t)}},
// Returns the first function passed as an argument to the second,
// allowing you to adjust arguments, run code before and after, and
// conditionally execute the original function.
j.wrap=function(n,t){return function(){var r=[n];return a.apply(r,arguments),t.apply(this,r)}},
// Returns a function that is the composition of a list of functions, each
// consuming the return value of the function that follows.
j.compose=function(){var n=arguments;return function(){for(var t=arguments,r=n.length-1;r>=0;r--)t=[n[r].apply(this,t)];return t[0]}},
// Returns a function that will only be executed after being called N times.
j.after=function(n,t){return 0>=n?t():function(){return--n<1?t.apply(this,arguments):void 0}},
// Object Functions
// ----------------
// Retrieve the names of an object's properties.
// Delegates to **ECMAScript 5**'s native `Object.keys`
j.keys=_||function(n){if(n!==Object(n))throw new TypeError("Invalid object");var t=[];for(var r in n)j.has(n,r)&&(t[t.length]=r);return t},
// Retrieve the values of an object's properties.
j.values=function(n){var t=[];for(var r in n)j.has(n,r)&&t.push(n[r]);return t},
// Convert an object into a list of `[key, value]` pairs.
j.pairs=function(n){var t=[];for(var r in n)j.has(n,r)&&t.push([r,n[r]]);return t},
// Invert the keys and values of an object. The values must be serializable.
j.invert=function(n){var t={};for(var r in n)j.has(n,r)&&(t[n[r]]=r);return t},
// Return a sorted list of the function names available on the object.
// Aliased as `methods`
j.functions=j.methods=function(n){var t=[];for(var r in n)j.isFunction(n[r])&&t.push(r);return t.sort()},
// Extend a given object with all the properties in passed-in object(s).
j.extend=function(n){return A(o.call(arguments,1),function(t){if(t)for(var r in t)n[r]=t[r]}),n},
// Return a copy of the object only containing the whitelisted properties.
j.pick=function(n){var t={},r=c.apply(e,o.call(arguments,1));return A(r,function(r){r in n&&(t[r]=n[r])}),t},
// Return a copy of the object without the blacklisted properties.
j.omit=function(n){var t={},r=c.apply(e,o.call(arguments,1));for(var u in n)j.contains(r,u)||(t[u]=n[u]);return t},
// Fill in a given object with default properties.
j.defaults=function(n){return A(o.call(arguments,1),function(t){if(t)for(var r in t)null==n[r]&&(n[r]=t[r])}),n},
// Create a (shallow-cloned) duplicate of an object.
j.clone=function(n){return j.isObject(n)?j.isArray(n)?n.slice():j.extend({},n):n},
// Invokes interceptor with the obj, and then returns obj.
// The primary purpose of this method is to "tap into" a method chain, in
// order to perform operations on intermediate results within the chain.
j.tap=function(n,t){return t(n),n};
// Internal recursive comparison function for `isEqual`.
var S=function(n,t,r,e){
// Identical objects are equal. `0 === -0`, but they aren't identical.
// See the Harmony `egal` proposal: http://wiki.ecmascript.org/doku.php?id=harmony:egal.
if(n===t)return 0!==n||1/n==1/t;
// A strict comparison is necessary because `null == undefined`.
if(null==n||null==t)return n===t;
// Unwrap any wrapped objects.
n instanceof j&&(n=n._wrapped),t instanceof j&&(t=t._wrapped);
// Compare `[[Class]]` names.
var u=l.call(n);if(u!=l.call(t))return!1;switch(u){
// Strings, numbers, dates, and booleans are compared by value.
case"[object String]":
// Primitives and their corresponding object wrappers are equivalent; thus, `"5"` is
// equivalent to `new String("5")`.
return n==String(t);case"[object Number]":
// `NaN`s are equivalent, but non-reflexive. An `egal` comparison is performed for
// other numeric values.
return n!=+n?t!=+t:0==n?1/n==1/t:n==+t;case"[object Date]":case"[object Boolean]":
// Coerce dates and booleans to numeric primitive values. Dates are compared by their
// millisecond representations. Note that invalid dates with millisecond representations
// of `NaN` are not equivalent.
return+n==+t;
// RegExps are compared by their source patterns and flags.
case"[object RegExp]":return n.source==t.source&&n.global==t.global&&n.multiline==t.multiline&&n.ignoreCase==t.ignoreCase}if("object"!=typeof n||"object"!=typeof t)return!1;for(
// Assume equality for cyclic structures. The algorithm for detecting cyclic
// structures is adapted from ES 5.1 section 15.12.3, abstract operation `JO`.
var i=r.length;i--;)
// Linear search. Performance is inversely proportional to the number of
// unique nested structures.
if(r[i]==n)return e[i]==t;
// Add the first object to the stack of traversed objects.
r.push(n),e.push(t);var a=0,o=!0;
// Recursively compare objects and arrays.
if("[object Array]"==u){if(
// Compare array lengths to determine if a deep comparison is necessary.
a=n.length,o=a==t.length)
// Deep compare the contents, ignoring non-numeric properties.
for(;a--&&(o=S(n[a],t[a],r,e)););}else{
// Objects with different constructors are not equivalent, but `Object`s
// from different frames are.
var c=n.constructor,f=t.constructor;if(c!==f&&!(j.isFunction(c)&&c instanceof c&&j.isFunction(f)&&f instanceof f))return!1;
// Deep compare objects.
for(var s in n)if(j.has(n,s)&&(
// Count the expected number of properties.
a++,!(o=j.has(t,s)&&S(n[s],t[s],r,e))))break;
// Ensure that both objects contain the same number of properties.
if(o){for(s in t)if(j.has(t,s)&&!a--)break;o=!a}}
// Remove the first object from the stack of traversed objects.
return r.pop(),e.pop(),o};
// Perform a deep comparison to check if two objects are equal.
j.isEqual=function(n,t){return S(n,t,[],[])},
// Is a given array, string, or object empty?
// An "empty" object has no enumerable own-properties.
j.isEmpty=function(n){if(null==n)return!0;if(j.isArray(n)||j.isString(n))return 0===n.length;for(var t in n)if(j.has(n,t))return!1;return!0},
// Is a given value a DOM element?
j.isElement=function(n){return!(!n||1!==n.nodeType)},
// Is a given value an array?
// Delegates to ECMA5's native Array.isArray
j.isArray=x||function(n){return"[object Array]"==l.call(n)},
// Is a given variable an object?
j.isObject=function(n){return n===Object(n)},
// Add some isType methods: isArguments, isFunction, isString, isNumber, isDate, isRegExp.
A(["Arguments","Function","String","Number","Date","RegExp"],function(n){j["is"+n]=function(t){return l.call(t)=="[object "+n+"]"}}),
// Define a fallback version of the method in browsers (ahem, IE), where
// there isn't any inspectable "Arguments" type.
j.isArguments(arguments)||(j.isArguments=function(n){return!(!n||!j.has(n,"callee"))}),
// Optimize `isFunction` if appropriate.
"function"!=typeof/./&&(j.isFunction=function(n){return"function"==typeof n}),
// Is a given object a finite number?
j.isFinite=function(n){return isFinite(n)&&!isNaN(parseFloat(n))},
// Is the given value `NaN`? (NaN is the only number which does not equal itself).
j.isNaN=function(n){return j.isNumber(n)&&n!=+n},
// Is a given value a boolean?
j.isBoolean=function(n){return n===!0||n===!1||"[object Boolean]"==l.call(n)},
// Is a given value equal to null?
j.isNull=function(n){return null===n},
// Is a given variable undefined?
j.isUndefined=function(n){return void 0===n},
// Shortcut function for checking if an object has a given property directly
// on itself (in other words, not on a prototype).
j.has=function(n,t){return f.call(n,t)},
// Utility Functions
// -----------------
// Run Underscore.js in *noConflict* mode, returning the `_` variable to its
// previous owner. Returns a reference to the Underscore object.
j.noConflict=function(){return n._=t,this},
// Keep the identity function around for default iterators.
j.identity=function(n){return n},
// Run a function **n** times.
j.times=function(n,t,r){for(var e=Array(n),u=0;n>u;u++)e[u]=t.call(r,u);return e},
// Return a random integer between min and max (inclusive).
j.random=function(n,t){return null==t&&(t=n,n=0),n+Math.floor(Math.random()*(t-n+1))};
// List of HTML entities for escaping.
var I={escape:{"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#x27;","/":"&#x2F;"}};I.unescape=j.invert(I.escape);
// Regexes containing the keys and values listed immediately above.
var M={escape:new RegExp("["+j.keys(I.escape).join("")+"]","g"),unescape:new RegExp("("+j.keys(I.unescape).join("|")+")","g")};
// Functions for escaping and unescaping strings to/from HTML interpolation.
j.each(["escape","unescape"],function(n){j[n]=function(t){return null==t?"":(""+t).replace(M[n],function(t){return I[n][t]})}}),
// If the value of the named property is a function then invoke it;
// otherwise, return it.
j.result=function(n,t){if(null==n)return void 0;var r=n[t];return j.isFunction(r)?r.call(n):r},
// Add your own custom functions to the Underscore object.
j.mixin=function(n){A(j.functions(n),function(t){var r=j[t]=n[t];j.prototype[t]=function(){var n=[this._wrapped];return a.apply(n,arguments),D.call(this,r.apply(j,n))}})};
// Generate a unique integer id (unique within the entire client session).
// Useful for temporary DOM ids.
var N=0;j.uniqueId=function(n){var t=++N+"";return n?n+t:t},
// By default, Underscore uses ERB-style template delimiters, change the
// following template settings to use alternative delimiters.
j.templateSettings={evaluate:/<%([\s\S]+?)%>/g,interpolate:/<%=([\s\S]+?)%>/g,escape:/<%-([\s\S]+?)%>/g};
// When customizing `templateSettings`, if you don't want to define an
// interpolation, evaluation or escaping regex, we need one that is
// guaranteed not to match.
var T=/(.)^/,q={"'":"'","\\":"\\","\r":"r","\n":"n","	":"t","\u2028":"u2028","\u2029":"u2029"},B=/\\|'|\r|\n|\t|\u2028|\u2029/g;
// JavaScript micro-templating, similar to John Resig's implementation.
// Underscore templating handles arbitrary delimiters, preserves whitespace,
// and correctly escapes quotes within interpolated code.
j.template=function(n,t,r){var e;r=j.defaults({},r,j.templateSettings);
// Combine delimiters into one regular expression via alternation.
var u=new RegExp([(r.escape||T).source,(r.interpolate||T).source,(r.evaluate||T).source].join("|")+"|$","g"),i=0,a="__p+='";n.replace(u,function(t,r,e,u,o){return a+=n.slice(i,o).replace(B,function(n){return"\\"+q[n]}),r&&(a+="'+\n((__t=("+r+"))==null?'':_.escape(__t))+\n'"),e&&(a+="'+\n((__t=("+e+"))==null?'':__t)+\n'"),u&&(a+="';\n"+u+"\n__p+='"),i=o+t.length,t}),a+="';\n",
// If a variable is not specified, place data values in local scope.
r.variable||(a="with(obj||{}){\n"+a+"}\n"),a="var __t,__p='',__j=Array.prototype.join,print=function(){__p+=__j.call(arguments,'');};\n"+a+"return __p;\n";try{e=new Function(r.variable||"obj","_",a)}catch(o){throw o.source=a,o}if(t)return e(t,j);var c=function(n){return e.call(this,n,j)};
// Provide the compiled function source as a convenience for precompilation.
return c.source="function("+(r.variable||"obj")+"){\n"+a+"}",c},
// Add a "chain" function, which will delegate to the wrapper.
j.chain=function(n){return j(n).chain()};
// OOP
// ---------------
// If Underscore is called as a function, it returns a wrapped object that
// can be used OO-style. This wrapper holds altered versions of all the
// underscore functions. Wrapped objects may be chained.
// Helper function to continue chaining intermediate results.
var D=function(n){return this._chain?j(n).chain():n};
// Add all of the Underscore functions to the wrapper object.
j.mixin(j),
// Add all mutator Array functions to the wrapper.
A(["pop","push","reverse","shift","sort","splice","unshift"],function(n){var t=e[n];j.prototype[n]=function(){var r=this._wrapped;return t.apply(r,arguments),"shift"!=n&&"splice"!=n||0!==r.length||delete r[0],D.call(this,r)}}),
// Add all accessor Array functions to the wrapper.
A(["concat","join","slice"],function(n){var t=e[n];j.prototype[n]=function(){return D.call(this,t.apply(this._wrapped,arguments))}}),j.extend(j.prototype,{
// Start chaining a wrapped Underscore object.
chain:function(){return this._chain=!0,this},
// Extracts the result from a wrapped and chained object.
value:function(){return this._wrapped}})}).call(this);