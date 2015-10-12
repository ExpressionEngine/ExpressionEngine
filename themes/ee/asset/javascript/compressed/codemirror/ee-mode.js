/**
 * EE codemirror linter.
 */
!function(){"use strict";function t(t){var e=jQuery.inArray(t,EE.editor.lint.available)>=0,n=jQuery.inArray(t,EE.editor.lint.not_installed)>=0;return!e||n?e?'Module "'+t+'" exists, but is not installed.':'Addon "'+t+'" does not exist.':""}EE.codemirror_linter={getAnnotations:function(e){for(var n,r=[],i=/(\{\/?exp:)([\w]+)/i,o=0,u=0;n=i.exec(e);){
// find the line and character of the match
var a=e.substr(0,n.index),c=a.split("\n"),f=c.length-1,s=c[f].length+n[1].length;
// adjust line to absolute position in the textarea
f+=o,o=f,
// adjust character for same-line tags
1==c.length&&(s+=u);
// check tag for validity
var d=t(n[2]);d&&r.push({from:CodeMirror.Pos(f,s),to:CodeMirror.Pos(f,s+n[2].length),message:d}),
// store ch for next search
u=s+n[2].length,
// trim text for next search
e=e.substr(n.index+n[0].length)}return r}}}(),/**
 * An EE textmirror "mode". Basically a lexer.
 */
function(t){"use strict";t.defineMode("ee:inner",function(){function t(t,c){return t.eatWhile(/[^\{]/),(o=t.match(/^\{!--/,!1))?(c.tokenize=i(),"tag"):(u=t.match(/^\{(if|if:elseif)\s/,!1))?(c.tokenize=r(),"tag"):(u=t.match(/\{(if:else|\/if)\}/,!1))?(c.tokenize=e(),"punctuation"):(a=t.match(/\{\/?([\w:]+)/,!1))?(c.tokenize=n(a[1]),"punctuation"):void t.next()}function e(){return function(e,n){if(e.match(/(if:else|\/if)/))return"keyword";var r=e.next();return"{"==r?"punctuation":"}"==r?(e.next(),n.tokenize=t,"punctuation"):"punctuation"}}function n(e){return function(n,r){if(n.eatWhile(/\s+/),n.match(/^"(\\|\"|[^"])*?"/))return"string";if(n.match(/^'(\\|\'|[^'])*?'/))return"string";var i;if(i=n.match(/\w*([a-zA-Z]([\w:-]+\w)?|(\w[\w:-]+)?[a-zA-Z])\w*/))return i[0]==e?"variable":"variable-2";var o=n.next();return"="==o||"{"==o?"punctuation":"}"==o?(n.next(),r.tokenize=t,"punctuation"):"punctuation"}}function r(){return function(e,n){if(e.eatWhile(/\s+/),e.match(/(if|if:elseif)/))return"keyword";if(e.match(/\b(true|false)\b/i))return"keyword";if(e.match(/\b(and|or|xor)\b/i))return"operator";if(e.match(/"(\\|\"|[^"])*?"/))return"string";if(e.match(/'(\\|\'|[^'])*?'/))return"string";if(e.match(/\b(\d+\.\d*|\d*\.\d+|\d+)\b/))return"number";if(e.match(/\w*([a-zA-Z]([\w:-]+\w)?|(\w[\w:-]+)?[a-zA-Z])\w*/))return"variable";if(e.match(/[=!|<>!&%~\(\)\$\^\*\+\-\.]+/))return"operator";var r=e.next();return"{"==r||"/"==r?"punctuation":"}"==r?(e.next(),n.tokenize=t,"punctuation"):"punctuation"}}function i(){return function(e,n){return e.eat(/\{!--/),e.next(),e.match(/^--}/,!0)&&(n.tokenize=t),"comment"}}var o,u,a;return{startState:function(){return{tokenize:t}},token:function(t,e){return e.tokenize(t,e)}}}),
// lay ee on top of the html mode
t.defineMode("ee",function(e){var n=t.getMode(e,"text/html"),r=t.getMode(e,"ee:inner");return t.overlayMode(n,r)}),t.defineMIME("text/x-ee","ee")}(CodeMirror);