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

(function(r){r.fn.crypt=function(g){function v(a){var b="",d,c,e,f,g,h,i=0;do d=a.source.charCodeAt(i++),c=a.source.charCodeAt(i++),e=a.source.charCodeAt(i++),f=d>>2,d=(d&3)<<4|c>>4,g=(c&15)<<2|e>>6,h=e&63,isNaN(c)?g=h=64:isNaN(e)&&(h=64),b+=a.b64Str.charAt(f)+a.b64Str.charAt(d)+a.b64Str.charAt(g)+a.b64Str.charAt(h);while(i<a.source.length);return b}function w(a){var b="",d,c,e,f,g,h=0;a.source=a.source.replace(/[^A-Za-z0-9!_-]/g,"");do d=a.b64Str.indexOf(a.source.charAt(h++)),c=a.b64Str.indexOf(a.source.charAt(h++)),
f=a.b64Str.indexOf(a.source.charAt(h++)),g=a.b64Str.indexOf(a.source.charAt(h++)),d=d<<2|c>>4,c=(c&15)<<4|f>>2,e=(f&3)<<6|g,b+=String.fromCharCode(d),64!=f&&(b+=String.fromCharCode(c)),64!=g&&(b+=String.fromCharCode(e));while(h<a.source.length);return b}function l(a,b){var d=(a&65535)+(b&65535);return(a>>16)+(b>>16)+(d>>16)<<16|d&65535}function x(a){var b=Array(2),d=Array(4),c="",e;a.source=escape(a.source);for(e=0;4>e;e++)d[e]=n(a.strKey.slice(4*e,4*(e+1)));for(e=0;e<a.source.length;e+=8){b[0]=n(a.source.slice(e,
e+4));b[1]=n(a.source.slice(e+4,e+8));for(var f=b,g=f[0],h=f[1],i=0;84941944608!=i;)g+=(h<<4^h>>>5)+h^i+d[i&3],i+=2654435769,h+=(g<<4^g>>>5)+g^i+d[i>>>11&3];f[0]=g;f[1]=h;c+=p(b[0])+p(b[1])}return c.replace(/[\0\t\n\v\f\r\xa0'"!]/g,function(a){return"!"+a.charCodeAt(0)+"!"})}function y(a){var b=Array(2),d=Array(4),e="",c;for(c=0;4>c;c++)d[c]=n(a.strKey.slice(4*c,4*(c+1)));ciphertext=a.source.replace(/!\d\d?\d?!/g,function(a){return String.fromCharCode(a.slice(1,-1))});for(c=0;c<ciphertext.length;c+=
8){b[0]=n(ciphertext.slice(c,c+4));b[1]=n(ciphertext.slice(c+4,c+8));for(var a=b,f=a[0],g=a[1],h=84941944608;0!=h;)g-=(f<<4^f>>>5)+f^h+d[h>>>11&3],h-=2654435769,f-=(g<<4^g>>>5)+g^h+d[h&3];a[0]=f;a[1]=g;e+=p(b[0])+p(b[1])}e=e.replace(/\0+$/,"");return unescape(e)}function n(a){for(var b=0,c=0;4>c;c++)b|=a.charCodeAt(c)<<8*c;return isNaN(b)?0:b}function p(a){return String.fromCharCode(a&255,a>>8&255,a>>16&255,a>>24&255)}g=r.extend({b64Str:"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!-_",
strKey:"123",method:"md5",source:"",chrsz:8,hexcase:0},g);if(!g.source){var i=r(this);if(i.html())g.source=i.html();else if(i.val())g.source=i.val();else return alert("Please provide source text"),!1}if("md5"==g.method){for(var q=function(a,b){return a<<b|a>>>32-b},i=function(a,b,c,d,e,f,g){return l(q(l(l(a,b&c|~b&d),l(e,g)),f),b)},h=function(a,b,c,d,e,f,g){return l(q(l(l(a,b&d|c&~d),l(e,g)),f),b)},j=function(a,b,c,d,e,f,g){return l(q(l(l(a,b^c^d),l(e,g)),f),b)},k=function(a,b,c,d,e,f,g){return l(q(l(l(a,
c^(b|~d)),l(e,g)),f),b)},a=g.source,e=[],d=(1<<g.chrsz)-1,b=0;b<a.length*g.chrsz;b+=g.chrsz)e[b>>5]|=(a.charCodeAt(b/g.chrsz)&d)<<b%32;a=g.source.length*g.chrsz;e[a>>5]|=128<<a%32;e[(a+64>>>9<<4)+14]=a;for(var a=1732584193,d=-271733879,b=-1732584194,c=271733878,f=0;f<e.length;f+=16)var s=a,t=d,u=b,m=c,a=i(a,d,b,c,e[f+0],7,-680876936),c=i(c,a,d,b,e[f+1],12,-389564586),b=i(b,c,a,d,e[f+2],17,606105819),d=i(d,b,c,a,e[f+3],22,-1044525330),a=i(a,d,b,c,e[f+4],7,-176418897),c=i(c,a,d,b,e[f+5],12,1200080426),
b=i(b,c,a,d,e[f+6],17,-1473231341),d=i(d,b,c,a,e[f+7],22,-45705983),a=i(a,d,b,c,e[f+8],7,1770035416),c=i(c,a,d,b,e[f+9],12,-1958414417),b=i(b,c,a,d,e[f+10],17,-42063),d=i(d,b,c,a,e[f+11],22,-1990404162),a=i(a,d,b,c,e[f+12],7,1804603682),c=i(c,a,d,b,e[f+13],12,-40341101),b=i(b,c,a,d,e[f+14],17,-1502002290),d=i(d,b,c,a,e[f+15],22,1236535329),a=h(a,d,b,c,e[f+1],5,-165796510),c=h(c,a,d,b,e[f+6],9,-1069501632),b=h(b,c,a,d,e[f+11],14,643717713),d=h(d,b,c,a,e[f+0],20,-373897302),a=h(a,d,b,c,e[f+5],5,-701558691),
c=h(c,a,d,b,e[f+10],9,38016083),b=h(b,c,a,d,e[f+15],14,-660478335),d=h(d,b,c,a,e[f+4],20,-405537848),a=h(a,d,b,c,e[f+9],5,568446438),c=h(c,a,d,b,e[f+14],9,-1019803690),b=h(b,c,a,d,e[f+3],14,-187363961),d=h(d,b,c,a,e[f+8],20,1163531501),a=h(a,d,b,c,e[f+13],5,-1444681467),c=h(c,a,d,b,e[f+2],9,-51403784),b=h(b,c,a,d,e[f+7],14,1735328473),d=h(d,b,c,a,e[f+12],20,-1926607734),a=j(a,d,b,c,e[f+5],4,-378558),c=j(c,a,d,b,e[f+8],11,-2022574463),b=j(b,c,a,d,e[f+11],16,1839030562),d=j(d,b,c,a,e[f+14],23,-35309556),
a=j(a,d,b,c,e[f+1],4,-1530992060),c=j(c,a,d,b,e[f+4],11,1272893353),b=j(b,c,a,d,e[f+7],16,-155497632),d=j(d,b,c,a,e[f+10],23,-1094730640),a=j(a,d,b,c,e[f+13],4,681279174),c=j(c,a,d,b,e[f+0],11,-358537222),b=j(b,c,a,d,e[f+3],16,-722521979),d=j(d,b,c,a,e[f+6],23,76029189),a=j(a,d,b,c,e[f+9],4,-640364487),c=j(c,a,d,b,e[f+12],11,-421815835),b=j(b,c,a,d,e[f+15],16,530742520),d=j(d,b,c,a,e[f+2],23,-995338651),a=k(a,d,b,c,e[f+0],6,-198630844),c=k(c,a,d,b,e[f+7],10,1126891415),b=k(b,c,a,d,e[f+14],15,-1416354905),
d=k(d,b,c,a,e[f+5],21,-57434055),a=k(a,d,b,c,e[f+12],6,1700485571),c=k(c,a,d,b,e[f+3],10,-1894986606),b=k(b,c,a,d,e[f+10],15,-1051523),d=k(d,b,c,a,e[f+1],21,-2054922799),a=k(a,d,b,c,e[f+8],6,1873313359),c=k(c,a,d,b,e[f+15],10,-30611744),b=k(b,c,a,d,e[f+6],15,-1560198380),d=k(d,b,c,a,e[f+13],21,1309151649),a=k(a,d,b,c,e[f+4],6,-145523070),c=k(c,a,d,b,e[f+11],10,-1120210379),b=k(b,c,a,d,e[f+2],15,718787259),d=k(d,b,c,a,e[f+9],21,-343485551),a=l(a,s),d=l(d,t),b=l(b,u),c=l(c,m);i=[a,d,b,c];g=g.hexcase?
"0123456789ABCDEF":"0123456789abcdef";h="";for(j=0;j<4*i.length;j++)h+=g.charAt(i[j>>2]>>8*(j%4)+4&15)+g.charAt(i[j>>2]>>8*(j%4)&15);return h}if("sha1"==g.method){h=g.source;i=[];j=(1<<g.chrsz)-1;for(k=0;k<h.length*g.chrsz;k+=g.chrsz)i[k>>5]|=(h.charCodeAt(k/g.chrsz)&j)<<32-g.chrsz-k%32;h=g.source.length*g.chrsz;i[h>>5]|=128<<24-h%32;i[(h+64>>9<<4)+15]=h;h=Array(80);j=1732584193;k=-271733879;e=-1732584194;a=271733878;d=-1009589776;for(b=0;b<i.length;b+=16){c=j;f=k;s=e;t=a;u=d;for(m=0;80>m;m++){h[m]=
16>m?i[b+m]:(h[m-3]^h[m-8]^h[m-14]^h[m-16])<<1|(h[m-3]^h[m-8]^h[m-14]^h[m-16])>>>31;var z=l(l(j<<5|j>>>27,20>m?k&e|~k&a:40>m?k^e^a:60>m?k&e|k&a|e&a:k^e^a),l(l(d,h[m]),20>m?1518500249:40>m?1859775393:60>m?-1894007588:-899497514)),d=a,a=e,e=k<<30|k>>>2,k=j,j=z}j=l(j,c);k=l(k,f);e=l(e,s);a=l(a,t);d=l(d,u)}i=[j,k,e,a,d];g=g.hexcase?"0123456789ABCDEF":"0123456789abcdef";h="";for(j=0;j<4*i.length;j++)h+=g.charAt(i[j>>2]>>8*(3-j%4)+4&15)+g.charAt(i[j>>2]>>8*(3-j%4)&15);return h}if("b64enc"==g.method)return v(g);
if("b64dec"==g.method)return w(g);if("xteaenc"==g.method)return x(g);if("xteadec"==g.method)return y(g);if("xteab64enc"==g.method)return i=x(g),g.method="b64enc",g.source=i,v(g);if("xteab64dec"==g.method)return i=w(g),g.method="xteadec",g.source=i,y(g)}})(jQuery);
