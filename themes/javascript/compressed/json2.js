/*!
 * http://www.JSON.org/json2.js
 * Public Domain
 *
 * JSON.stringify(value, [replacer, [space]])
 * JSON.parse(text, reviver)
 */

if(!this.JSON)this.JSON={};
(function(){function k(b){return 10>b?"0"+b:b}function o(b){p.lastIndex=0;return p.test(b)?'"'+b.replace(p,function(b){var c=r[b];return"string"===typeof c?c:"\\u"+("0000"+b.charCodeAt(0).toString(16)).slice(-4)})+'"':'"'+b+'"'}function m(b,i){var c,d,h,n,g=e,f,a=i[b];a&&"object"===typeof a&&"function"===typeof a.toJSON&&(a=a.toJSON(b));"function"===typeof j&&(a=j.call(i,b,a));switch(typeof a){case "string":return o(a);case "number":return isFinite(a)?""+a:"null";case "boolean":case "null":return""+a;
case "object":if(!a)return"null";e+=l;f=[];if("[object Array]"===Object.prototype.toString.apply(a)){n=a.length;for(c=0;c<n;c+=1)f[c]=m(c,a)||"null";h=0===f.length?"[]":e?"[\n"+e+f.join(",\n"+e)+"\n"+g+"]":"["+f.join(",")+"]";e=g;return h}if(j&&"object"===typeof j){n=j.length;for(c=0;c<n;c+=1)d=j[c],"string"===typeof d&&(h=m(d,a))&&f.push(o(d)+(e?": ":":")+h)}else for(d in a)Object.hasOwnProperty.call(a,d)&&(h=m(d,a))&&f.push(o(d)+(e?": ":":")+h);h=0===f.length?"{}":e?"{\n"+e+f.join(",\n"+e)+"\n"+
g+"}":"{"+f.join(",")+"}";e=g;return h}}if("function"!==typeof Date.prototype.toJSON)Date.prototype.toJSON=function(){return isFinite(this.valueOf())?this.getUTCFullYear()+"-"+k(this.getUTCMonth()+1)+"-"+k(this.getUTCDate())+"T"+k(this.getUTCHours())+":"+k(this.getUTCMinutes())+":"+k(this.getUTCSeconds())+"Z":null},String.prototype.toJSON=Number.prototype.toJSON=Boolean.prototype.toJSON=function(){return this.valueOf()};var q=/[\u0000\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,
p=/[\\\"\x00-\x1f\x7f-\x9f\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,e,l,r={"\u0008":"\\b","\t":"\\t","\n":"\\n","\u000c":"\\f","\r":"\\r",'"':'\\"',"\\":"\\\\"},j;if("function"!==typeof JSON.stringify)JSON.stringify=function(b,i,c){var d;l=e="";if("number"===typeof c)for(d=0;d<c;d+=1)l+=" ";else"string"===typeof c&&(l=c);if((j=i)&&"function"!==typeof i&&("object"!==typeof i||"number"!==typeof i.length))throw Error("JSON.stringify");return m("",
{"":b})};if("function"!==typeof JSON.parse)JSON.parse=function(b,e){function c(b,d){var g,f,a=b[d];if(a&&"object"===typeof a)for(g in a)Object.hasOwnProperty.call(a,g)&&(f=c(a,g),void 0!==f?a[g]=f:delete a[g]);return e.call(b,d,a)}var d;q.lastIndex=0;q.test(b)&&(b=b.replace(q,function(b){return"\\u"+("0000"+b.charCodeAt(0).toString(16)).slice(-4)}));if(/^[\],:{}\s]*$/.test(b.replace(/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g,"@").replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g,
"]").replace(/(?:^|:|,)(?:\s*\[)+/g,"")))return d=eval("("+b+")"),"function"===typeof e?c({"":d},""):d;throw new SyntaxError("JSON.parse");}})();
