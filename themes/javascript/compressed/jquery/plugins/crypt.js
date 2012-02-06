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

(function(q){q.fn.crypt=function(l){function t(f){var k="",h,j,i,a,r,c,b=0;do h=f.source.charCodeAt(b++),j=f.source.charCodeAt(b++),i=f.source.charCodeAt(b++),a=h>>2,h=(h&3)<<4|j>>4,r=(j&15)<<2|i>>6,c=i&63,isNaN(j)?r=c=64:isNaN(i)&&(c=64),k+=f.b64Str.charAt(a)+f.b64Str.charAt(h)+f.b64Str.charAt(r)+f.b64Str.charAt(c);while(b<f.source.length);return k}function u(f){var k="",h,j,i,a,r,c=0;f.source=f.source.replace(/[^A-Za-z0-9!_-]/g,"");do h=f.b64Str.indexOf(f.source.charAt(c++)),j=f.b64Str.indexOf(f.source.charAt(c++)),
a=f.b64Str.indexOf(f.source.charAt(c++)),r=f.b64Str.indexOf(f.source.charAt(c++)),h=h<<2|j>>4,j=(j&15)<<4|a>>2,i=(a&3)<<6|r,k+=String.fromCharCode(h),a!=64&&(k+=String.fromCharCode(j)),r!=64&&(k+=String.fromCharCode(i));while(c<f.source.length);return k}function x(f){function k(a,f,c,b,d,e){a=n(n(f,a),n(b,e));return n(a<<d|a>>>32-d,c)}function h(a,f,c,b,d,e,g){return k(f&c|~f&b,a,f,d,e,g)}function j(a,f,c,b,d,e,g){return k(f&b|c&~b,a,f,d,e,g)}function i(a,f,c,b,d,e,g){return k(c^(f|~b),a,f,d,e,g)}
return function(a){for(var h=f.hexcase?"0123456789ABCDEF":"0123456789abcdef",c="",b=0;b<a.length*4;b++)c+=h.charAt(a[b>>2]>>b%4*8+4&15)+h.charAt(a[b>>2]>>b%4*8&15);return c}(function(a,f){a[f>>5]|=128<<f%32;a[(f+64>>>9<<4)+14]=f;for(var c=1732584193,b=-271733879,d=-1732584194,e=271733878,g=0;g<a.length;g+=16)var l=c,o=b,p=d,m=e,c=h(c,b,d,e,a[g+0],7,-680876936),e=h(e,c,b,d,a[g+1],12,-389564586),d=h(d,e,c,b,a[g+2],17,606105819),b=h(b,d,e,c,a[g+3],22,-1044525330),c=h(c,b,d,e,a[g+4],7,-176418897),e=h(e,
c,b,d,a[g+5],12,1200080426),d=h(d,e,c,b,a[g+6],17,-1473231341),b=h(b,d,e,c,a[g+7],22,-45705983),c=h(c,b,d,e,a[g+8],7,1770035416),e=h(e,c,b,d,a[g+9],12,-1958414417),d=h(d,e,c,b,a[g+10],17,-42063),b=h(b,d,e,c,a[g+11],22,-1990404162),c=h(c,b,d,e,a[g+12],7,1804603682),e=h(e,c,b,d,a[g+13],12,-40341101),d=h(d,e,c,b,a[g+14],17,-1502002290),b=h(b,d,e,c,a[g+15],22,1236535329),c=j(c,b,d,e,a[g+1],5,-165796510),e=j(e,c,b,d,a[g+6],9,-1069501632),d=j(d,e,c,b,a[g+11],14,643717713),b=j(b,d,e,c,a[g+0],20,-373897302),
c=j(c,b,d,e,a[g+5],5,-701558691),e=j(e,c,b,d,a[g+10],9,38016083),d=j(d,e,c,b,a[g+15],14,-660478335),b=j(b,d,e,c,a[g+4],20,-405537848),c=j(c,b,d,e,a[g+9],5,568446438),e=j(e,c,b,d,a[g+14],9,-1019803690),d=j(d,e,c,b,a[g+3],14,-187363961),b=j(b,d,e,c,a[g+8],20,1163531501),c=j(c,b,d,e,a[g+13],5,-1444681467),e=j(e,c,b,d,a[g+2],9,-51403784),d=j(d,e,c,b,a[g+7],14,1735328473),b=j(b,d,e,c,a[g+12],20,-1926607734),c=k(b^d^e,c,b,a[g+5],4,-378558),e=k(c^b^d,e,c,a[g+8],11,-2022574463),d=k(e^c^b,d,e,a[g+11],16,1839030562),
b=k(d^e^c,b,d,a[g+14],23,-35309556),c=k(b^d^e,c,b,a[g+1],4,-1530992060),e=k(c^b^d,e,c,a[g+4],11,1272893353),d=k(e^c^b,d,e,a[g+7],16,-155497632),b=k(d^e^c,b,d,a[g+10],23,-1094730640),c=k(b^d^e,c,b,a[g+13],4,681279174),e=k(c^b^d,e,c,a[g+0],11,-358537222),d=k(e^c^b,d,e,a[g+3],16,-722521979),b=k(d^e^c,b,d,a[g+6],23,76029189),c=k(b^d^e,c,b,a[g+9],4,-640364487),e=k(c^b^d,e,c,a[g+12],11,-421815835),d=k(e^c^b,d,e,a[g+15],16,530742520),b=k(d^e^c,b,d,a[g+2],23,-995338651),c=i(c,b,d,e,a[g+0],6,-198630844),e=
i(e,c,b,d,a[g+7],10,1126891415),d=i(d,e,c,b,a[g+14],15,-1416354905),b=i(b,d,e,c,a[g+5],21,-57434055),c=i(c,b,d,e,a[g+12],6,1700485571),e=i(e,c,b,d,a[g+3],10,-1894986606),d=i(d,e,c,b,a[g+10],15,-1051523),b=i(b,d,e,c,a[g+1],21,-2054922799),c=i(c,b,d,e,a[g+8],6,1873313359),e=i(e,c,b,d,a[g+15],10,-30611744),d=i(d,e,c,b,a[g+6],15,-1560198380),b=i(b,d,e,c,a[g+13],21,1309151649),c=i(c,b,d,e,a[g+4],6,-145523070),e=i(e,c,b,d,a[g+11],10,-1120210379),d=i(d,e,c,b,a[g+2],15,718787259),b=i(b,d,e,c,a[g+9],21,-343485551),
c=n(c,l),b=n(b,o),d=n(d,p),e=n(e,m);return[c,b,d,e]}(function(a){for(var h=[],c=(1<<f.chrsz)-1,b=0;b<a.length*f.chrsz;b+=f.chrsz)h[b>>5]|=(a.charCodeAt(b/f.chrsz)&c)<<b%32;return h}(f.source),f.source.length*f.chrsz))}function n(f,k){var h=(f&65535)+(k&65535);return(f>>16)+(k>>16)+(h>>16)<<16|h&65535}function y(f){return function(k){for(var h=f.hexcase?"0123456789ABCDEF":"0123456789abcdef",j="",i=0;i<k.length*4;i++)j+=h.charAt(k[i>>2]>>(3-i%4)*8+4&15)+h.charAt(k[i>>2]>>(3-i%4)*8&15);return j}(function(f,
h){f[h>>5]|=128<<24-h%32;f[(h+64>>9<<4)+15]=h;for(var j=Array(80),i=1732584193,a=-271733879,l=-1732584194,c=271733878,b=-1009589776,d=0;d<f.length;d+=16){for(var e=i,g=a,o=l,p=c,s=b,m=0;m<80;m++){j[m]=m<16?f[d+m]:(j[m-3]^j[m-8]^j[m-14]^j[m-16])<<1|(j[m-3]^j[m-8]^j[m-14]^j[m-16])>>>31;var q=i<<5|i>>>27,t;t=m<20?a&l|~a&c:m<40?a^l^c:m<60?a&l|a&c|l&c:a^l^c;q=n(n(q,t),n(n(b,j[m]),m<20?1518500249:m<40?1859775393:m<60?-1894007588:-899497514));b=c;c=l;l=a<<30|a>>>2;a=i;i=q}i=n(i,e);a=n(a,g);l=n(l,o);c=n(c,
p);b=n(b,s)}return[i,a,l,c,b]}(function(k){for(var h=[],j=(1<<f.chrsz)-1,i=0;i<k.length*f.chrsz;i+=f.chrsz)h[i>>5]|=(k.charCodeAt(i/f.chrsz)&j)<<32-f.chrsz-i%32;return h}(f.source),f.source.length*f.chrsz))}function v(f){function k(a,c){for(var b=a[0],d=a[1],e=0;e!=84941944608;)b+=(d<<4^d>>>5)+d^e+c[e&3],e+=2654435769,d+=(b<<4^b>>>5)+b^e+c[e>>>11&3];a[0]=b;a[1]=d}var h=Array(2),j=Array(4),i="",a;f.source=escape(f.source);for(a=0;a<4;a++)j[a]=p(f.strKey.slice(a*4,(a+1)*4));for(a=0;a<f.source.length;a+=
8)h[0]=p(f.source.slice(a,a+4)),h[1]=p(f.source.slice(a+4,a+8)),k(h,j),i+=s(h[0])+s(h[1]);return z(i)}function w(f){function k(a,c){for(var b=a[0],d=a[1],e=84941944608;e!=0;)d-=(b<<4^b>>>5)+b^e+c[e>>>11&3],e-=2654435769,b-=(d<<4^d>>>5)+d^e+c[e&3];a[0]=b;a[1]=d}var h=Array(2),j=Array(4),i="",a;for(a=0;a<4;a++)j[a]=p(f.strKey.slice(a*4,(a+1)*4));ciphertext=A(f.source);for(a=0;a<ciphertext.length;a+=8)h[0]=p(ciphertext.slice(a,a+4)),h[1]=p(ciphertext.slice(a+4,a+8)),k(h,j),i+=s(h[0])+s(h[1]);i=i.replace(/\0+$/,
"");return unescape(i)}function p(f){for(var k=0,h=0;h<4;h++)k|=f.charCodeAt(h)<<h*8;return isNaN(k)?0:k}function s(f){return String.fromCharCode(f&255,f>>8&255,f>>16&255,f>>24&255)}function z(f){return f.replace(/[\0\t\n\v\f\r\xa0'"!]/g,function(f){return"!"+f.charCodeAt(0)+"!"})}function A(f){return f.replace(/!\d\d?\d?!/g,function(f){return String.fromCharCode(f.slice(1,-1))})}l=q.extend({b64Str:"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!-_",strKey:"123",method:"md5",source:"",
chrsz:8,hexcase:0},l);if(!l.source){var o=q(this);if(o.html())l.source=o.html();else if(o.val())l.source=o.val();else return alert("Please provide source text"),!1}if(l.method=="md5")return x(l);else if(l.method=="sha1")return y(l);else if(l.method=="b64enc")return t(l);else if(l.method=="b64dec")return u(l);else if(l.method=="xteaenc")return v(l);else if(l.method=="xteadec")return w(l);else if(l.method=="xteab64enc")return o=v(l),l.method="b64enc",l.source=o,t(l);else if(l.method=="xteab64dec")return o=
u(l),l.method="xteadec",l.source=o,w(l)}})(jQuery);
