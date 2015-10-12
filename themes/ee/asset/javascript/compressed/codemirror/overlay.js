// CodeMirror, copyright (c) by Marijn Haverbeke and others
// Distributed under an MIT license: http://codemirror.net/LICENSE
// Utility function that allows modes to be combined. The mode given
// as the base argument takes care of most of the normal mode
// functionality, but a second (typically simple) mode is used, which
// can override the style of text. Both modes get to parse all of the
// text, but when both assign a non-null style to a piece of code, the
// overlay wins, unless the combine argument was true and not overridden,
// or state.overlay.combineTokens was true, in which case the styles are
// combined.
!function(e){"object"==typeof exports&&"object"==typeof module?// CommonJS
e(require("../../lib/codemirror")):"function"==typeof define&&define.amd?// AMD
define(["../../lib/codemirror"],e):// Plain browser env
e(CodeMirror)}(function(e){"use strict";e.overlayMode=function(o,r,n){return{startState:function(){return{base:e.startState(o),overlay:e.startState(r),basePos:0,baseCur:null,overlayPos:0,overlayCur:null,lineSeen:null}},copyState:function(n){return{base:e.copyState(o,n.base),overlay:e.copyState(r,n.overlay),basePos:n.basePos,baseCur:null,overlayPos:n.overlayPos,overlayCur:null}},token:function(e,a){
// state.overlay.combineTokens always takes precedence over combine,
// unless set to null
// state.overlay.combineTokens always takes precedence over combine,
// unless set to null
return(e.sol()||e.string!=a.lineSeen||Math.min(a.basePos,a.overlayPos)<e.start)&&(a.lineSeen=e.string,a.basePos=a.overlayPos=e.start),e.start==a.basePos&&(a.baseCur=o.token(e,a.base),a.basePos=e.pos),e.start==a.overlayPos&&(e.pos=e.start,a.overlayCur=r.token(e,a.overlay),a.overlayPos=e.pos),e.pos=Math.min(a.basePos,a.overlayPos),null==a.overlayCur?a.baseCur:null!=a.baseCur&&a.overlay.combineTokens||n&&null==a.overlay.combineTokens?a.baseCur+" "+a.overlayCur:a.overlayCur},indent:o.indent&&function(e,r){return o.indent(e.base,r)},electricChars:o.electricChars,innerMode:function(e){return{state:e.base,mode:o}},blankLine:function(e){o.blankLine&&o.blankLine(e.base),r.blankLine&&r.blankLine(e.overlay)}}}});