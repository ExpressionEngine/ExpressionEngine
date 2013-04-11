/*
 * jQuery Cryptography Plug-in
 * version: 1.0.0 (24 Sep 2008)
 * copyright 2008 Scott Thompson http://www.itsyndicate.ca - scott@itsyndicate.ca
 * http://www.opensource.org/licenses/mit-license.php
 *
 * A set of functions to do some basic cryptography encoding/decoding
 * I compiled from some javascripts I found into a jQuery plug-in. 
 * Thanks go out to the original authors.
 *
 * Changelog: 1.1.0
 * - rewrote plugin to use only one item in the namespace 
 * 
 * --- Base64 Encoding and Decoding code was written by 
 *   
 * Base64 code from Tyler Akins -- http://rumkin.com
 * and is placed in the public domain
 *
 *
 * --- MD5 and SHA1 Functions based upon Paul Johnston's javascript libraries.
 * A JavaScript implementation of the RSA Data Security, Inc. MD5 Message
 * Digest Algorithm, as defined in RFC 1321.
 * Version 2.1 Copyright (C) Paul Johnston 1999 - 2002.
 * Other contributors: Greg Holt, Andrew Kepert, Ydnar, Lostinet
 * Distributed under the BSD License
 * See http://pajhome.org.uk/crypt/md5 for more info.
 *
 * xTea Encrypt and Decrypt 
 * copyright 2000-2005 Chris Veness
 * http://www.movable-type.co.uk
 *
 *
 * Examples:
 *
        var md5 = $().crypt({method:"md5",source:$("#phrase").val()});
        var sha1 = $().crypt({method:"sha1",source:$("#phrase").val()});
        var b64 = $().crypt({method:"b64enc",source:$("#phrase").val()});
        var b64dec = $().crypt({method:"b64dec",source:b64});
        var xtea = $().crypt({method:"xteaenc",source:$("#phrase").val(),keyPass:$("#passPhrase").val()});
        var xteadec = $().crypt({method:"xteadec",source:xtea,keyPass:$("#passPhrase").val()});
        var xteab64 = $().crypt({method:"xteab64enc",source:$("#phrase").val(),keyPass:$("#passPhrase").val()});
        var xteab64dec = $().crypt({method:"xteab64dec",source:xteab64,keyPass:$("#passPhrase").val()});

	You can also pass source this way.
	var md5 = $("#idOfSource").crypt({method:"md5"});
 * 
 */

(function(s){s.fn.crypt=function(m){function t(g){var i="",j,k,f,c,l,a,b=0;do j=g.source.charCodeAt(b++),k=g.source.charCodeAt(b++),f=g.source.charCodeAt(b++),c=j>>2,j=(j&3)<<4|k>>4,l=(k&15)<<2|f>>6,a=f&63,isNaN(k)?l=a=64:isNaN(f)&&(a=64),i+=g.b64Str.charAt(c)+g.b64Str.charAt(j)+g.b64Str.charAt(l)+g.b64Str.charAt(a);while(b<g.source.length);return i}function u(g){var i="",j,k,f,c,l,a=0;g.source=g.source.replace(/[^A-Za-z0-9!_-]/g,"");do j=g.b64Str.indexOf(g.source.charAt(a++)),k=g.b64Str.indexOf(g.source.charAt(a++)),
c=g.b64Str.indexOf(g.source.charAt(a++)),l=g.b64Str.indexOf(g.source.charAt(a++)),j=j<<2|k>>4,k=(k&15)<<4|c>>2,f=(c&3)<<6|l,i+=String.fromCharCode(j),64!=c&&(i+=String.fromCharCode(k)),64!=l&&(i+=String.fromCharCode(f));while(a<g.source.length);return i}function x(g){function i(c,f,a,b,d,e){c=o(o(f,c),o(b,e));return o(c<<d|c>>>32-d,a)}function j(c,f,a,b,d,e,h){return i(f&a|~f&b,c,f,d,e,h)}function k(c,f,a,b,d,e,h){return i(f&b|a&~b,c,f,d,e,h)}function f(c,f,a,b,d,e,h){return i(a^(f|~b),c,f,d,e,h)}
return function(c){for(var f=g.hexcase?"0123456789ABCDEF":"0123456789abcdef",a="",b=0;b<4*c.length;b++)a+=f.charAt(c[b>>2]>>8*(b%4)+4&15)+f.charAt(c[b>>2]>>8*(b%4)&15);return a}(function(c,g){c[g>>5]|=128<<g%32;c[(g+64>>>9<<4)+14]=g;for(var a=1732584193,b=-271733879,d=-1732584194,e=271733878,h=0;h<c.length;h+=16)var m=a,p=b,q=d,n=e,a=j(a,b,d,e,c[h+0],7,-680876936),e=j(e,a,b,d,c[h+1],12,-389564586),d=j(d,e,a,b,c[h+2],17,606105819),b=j(b,d,e,a,c[h+3],22,-1044525330),a=j(a,b,d,e,c[h+4],7,-176418897),
e=j(e,a,b,d,c[h+5],12,1200080426),d=j(d,e,a,b,c[h+6],17,-1473231341),b=j(b,d,e,a,c[h+7],22,-45705983),a=j(a,b,d,e,c[h+8],7,1770035416),e=j(e,a,b,d,c[h+9],12,-1958414417),d=j(d,e,a,b,c[h+10],17,-42063),b=j(b,d,e,a,c[h+11],22,-1990404162),a=j(a,b,d,e,c[h+12],7,1804603682),e=j(e,a,b,d,c[h+13],12,-40341101),d=j(d,e,a,b,c[h+14],17,-1502002290),b=j(b,d,e,a,c[h+15],22,1236535329),a=k(a,b,d,e,c[h+1],5,-165796510),e=k(e,a,b,d,c[h+6],9,-1069501632),d=k(d,e,a,b,c[h+11],14,643717713),b=k(b,d,e,a,c[h+0],20,-373897302),
a=k(a,b,d,e,c[h+5],5,-701558691),e=k(e,a,b,d,c[h+10],9,38016083),d=k(d,e,a,b,c[h+15],14,-660478335),b=k(b,d,e,a,c[h+4],20,-405537848),a=k(a,b,d,e,c[h+9],5,568446438),e=k(e,a,b,d,c[h+14],9,-1019803690),d=k(d,e,a,b,c[h+3],14,-187363961),b=k(b,d,e,a,c[h+8],20,1163531501),a=k(a,b,d,e,c[h+13],5,-1444681467),e=k(e,a,b,d,c[h+2],9,-51403784),d=k(d,e,a,b,c[h+7],14,1735328473),b=k(b,d,e,a,c[h+12],20,-1926607734),a=i(b^d^e,a,b,c[h+5],4,-378558),e=i(a^b^d,e,a,c[h+8],11,-2022574463),d=i(e^a^b,d,e,c[h+11],16,1839030562),
b=i(d^e^a,b,d,c[h+14],23,-35309556),a=i(b^d^e,a,b,c[h+1],4,-1530992060),e=i(a^b^d,e,a,c[h+4],11,1272893353),d=i(e^a^b,d,e,c[h+7],16,-155497632),b=i(d^e^a,b,d,c[h+10],23,-1094730640),a=i(b^d^e,a,b,c[h+13],4,681279174),e=i(a^b^d,e,a,c[h+0],11,-358537222),d=i(e^a^b,d,e,c[h+3],16,-722521979),b=i(d^e^a,b,d,c[h+6],23,76029189),a=i(b^d^e,a,b,c[h+9],4,-640364487),e=i(a^b^d,e,a,c[h+12],11,-421815835),d=i(e^a^b,d,e,c[h+15],16,530742520),b=i(d^e^a,b,d,c[h+2],23,-995338651),a=f(a,b,d,e,c[h+0],6,-198630844),e=
f(e,a,b,d,c[h+7],10,1126891415),d=f(d,e,a,b,c[h+14],15,-1416354905),b=f(b,d,e,a,c[h+5],21,-57434055),a=f(a,b,d,e,c[h+12],6,1700485571),e=f(e,a,b,d,c[h+3],10,-1894986606),d=f(d,e,a,b,c[h+10],15,-1051523),b=f(b,d,e,a,c[h+1],21,-2054922799),a=f(a,b,d,e,c[h+8],6,1873313359),e=f(e,a,b,d,c[h+15],10,-30611744),d=f(d,e,a,b,c[h+6],15,-1560198380),b=f(b,d,e,a,c[h+13],21,1309151649),a=f(a,b,d,e,c[h+4],6,-145523070),e=f(e,a,b,d,c[h+11],10,-1120210379),d=f(d,e,a,b,c[h+2],15,718787259),b=f(b,d,e,a,c[h+9],21,-343485551),
a=o(a,m),b=o(b,p),d=o(d,q),e=o(e,n);return[a,b,d,e]}(function(c){for(var f=[],a=(1<<g.chrsz)-1,b=0;b<c.length*g.chrsz;b+=g.chrsz)f[b>>5]|=(c.charCodeAt(b/g.chrsz)&a)<<b%32;return f}(g.source),g.source.length*g.chrsz))}function o(g,i){var j=(g&65535)+(i&65535);return(g>>16)+(i>>16)+(j>>16)<<16|j&65535}function y(g){return function(i){for(var j=g.hexcase?"0123456789ABCDEF":"0123456789abcdef",k="",f=0;f<4*i.length;f++)k+=j.charAt(i[f>>2]>>8*(3-f%4)+4&15)+j.charAt(i[f>>2]>>8*(3-f%4)&15);return k}(function(g,
j){g[j>>5]|=128<<24-j%32;g[(j+64>>9<<4)+15]=j;for(var k=Array(80),f=1732584193,c=-271733879,l=-1732584194,a=271733878,b=-1009589776,d=0;d<g.length;d+=16){for(var e=f,h=c,m=l,p=a,q=b,n=0;80>n;n++){k[n]=16>n?g[d+n]:(k[n-3]^k[n-8]^k[n-14]^k[n-16])<<1|(k[n-3]^k[n-8]^k[n-14]^k[n-16])>>>31;var r=f<<5|f>>>27,s;s=20>n?c&l|~c&a:40>n?c^l^a:60>n?c&l|c&a|l&a:c^l^a;r=o(o(r,s),o(o(b,k[n]),20>n?1518500249:40>n?1859775393:60>n?-1894007588:-899497514));b=a;a=l;l=c<<30|c>>>2;c=f;f=r}f=o(f,e);c=o(c,h);l=o(l,m);a=o(a,
p);b=o(b,q)}return[f,c,l,a,b]}(function(i){for(var j=[],k=(1<<g.chrsz)-1,f=0;f<i.length*g.chrsz;f+=g.chrsz)j[f>>5]|=(i.charCodeAt(f/g.chrsz)&k)<<32-g.chrsz-f%32;return j}(g.source),g.source.length*g.chrsz))}function v(g){var i=Array(2),j=Array(4),k="",f;g.source=escape(g.source);for(f=0;4>f;f++)j[f]=q(g.strKey.slice(4*f,4*(f+1)));for(f=0;f<g.source.length;f+=8){i[0]=q(g.source.slice(f,f+4));i[1]=q(g.source.slice(f+4,f+8));for(var c=i,l=c[0],a=c[1],b=0;84941944608!=b;)l+=(a<<4^a>>>5)+a^b+j[b&3],b+=
2654435769,a+=(l<<4^l>>>5)+l^b+j[b>>>11&3];c[0]=l;c[1]=a;k+=r(i[0])+r(i[1])}return z(k)}function w(g){var i=Array(2),j=Array(4),k="",f;for(f=0;4>f;f++)j[f]=q(g.strKey.slice(4*f,4*(f+1)));ciphertext=A(g.source);for(f=0;f<ciphertext.length;f+=8){i[0]=q(ciphertext.slice(f,f+4));i[1]=q(ciphertext.slice(f+4,f+8));for(var g=i,c=g[0],l=g[1],a=84941944608;0!=a;)l-=(c<<4^c>>>5)+c^a+j[a>>>11&3],a-=2654435769,c-=(l<<4^l>>>5)+l^a+j[a&3];g[0]=c;g[1]=l;k+=r(i[0])+r(i[1])}k=k.replace(/\0+$/,"");return unescape(k)}
function q(g){for(var i=0,j=0;4>j;j++)i|=g.charCodeAt(j)<<8*j;return isNaN(i)?0:i}function r(g){return String.fromCharCode(g&255,g>>8&255,g>>16&255,g>>24&255)}function z(g){return g.replace(/[\0\t\n\v\f\r\xa0'"!]/g,function(g){return"!"+g.charCodeAt(0)+"!"})}function A(g){return g.replace(/!\d\d?\d?!/g,function(g){return String.fromCharCode(g.slice(1,-1))})}m=s.extend({b64Str:"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!-_",strKey:"123",method:"md5",source:"",chrsz:8,hexcase:0},
m);if(!m.source){var p=s(this);if(p.html())m.source=p.html();else if(p.val())m.source=p.val();else return alert("Please provide source text"),!1}if("md5"==m.method)return x(m);if("sha1"==m.method)return y(m);if("b64enc"==m.method)return t(m);if("b64dec"==m.method)return u(m);if("xteaenc"==m.method)return v(m);if("xteadec"==m.method)return w(m);if("xteab64enc"==m.method)return p=v(m),m.method="b64enc",m.source=p,t(m);if("xteab64dec"==m.method)return p=u(m),m.method="xteadec",m.source=p,w(m)}})(jQuery);
