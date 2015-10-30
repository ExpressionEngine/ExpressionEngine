// CodeMirror, copyright (c) by Marijn Haverbeke and others
// Distributed under an MIT license: http://codemirror.net/LICENSE
// Open simple dialogs on top of an editor. Relies on dialog.css.
!function(e){"object"==typeof exports&&"object"==typeof module?// CommonJS
e(require("../../lib/codemirror")):"function"==typeof define&&define.amd?// AMD
define(["../../lib/codemirror"],e):// Plain browser env
e(CodeMirror)}(function(e){function o(e,o,n){var t,i=e.getWrapperElement();// Assuming it's a detached DOM element.
return t=i.appendChild(document.createElement("div")),n?t.className="CodeMirror-dialog CodeMirror-dialog-bottom":t.className="CodeMirror-dialog CodeMirror-dialog-top","string"==typeof o?t.innerHTML=o:t.appendChild(o),t}function n(e,o){e.state.currentNotificationClose&&e.state.currentNotificationClose(),e.state.currentNotificationClose=o}e.defineExtension("openDialog",function(t,i,r){function u(){l||(l=!0,c.parentNode.removeChild(c))}n(this,null);var a,c=o(this,t,r&&r.bottom),l=!1,f=this,d=c.getElementsByTagName("input")[0];return d?(r&&r.value&&(d.value=r.value),e.on(d,"keydown",function(o){r&&r.onKeyDown&&r.onKeyDown(o,d.value,u)||(13==o.keyCode||27==o.keyCode)&&(d.blur(),e.e_stop(o),u(),f.focus(),13==o.keyCode&&i(d.value))}),r&&r.onKeyUp&&e.on(d,"keyup",function(e){r.onKeyUp(e,d.value,u)}),r&&r.value&&(d.value=r.value),d.focus(),e.on(d,"blur",u)):(a=c.getElementsByTagName("button")[0])&&(e.on(a,"click",function(){u(),f.focus()}),a.focus(),e.on(a,"blur",u)),u}),e.defineExtension("openConfirm",function(t,i,r){function u(){l||(l=!0,a.parentNode.removeChild(a),f.focus())}n(this,null);var a=o(this,t,r&&r.bottom),c=a.getElementsByTagName("button"),l=!1,f=this,d=1;c[0].focus();for(var s=0;s<c.length;++s){var m=c[s];!function(o){e.on(m,"click",function(n){e.e_preventDefault(n),u(),o&&o(f)})}(i[s]),e.on(m,"blur",function(){--d,setTimeout(function(){0>=d&&u()},200)}),e.on(m,"focus",function(){++d})}}),/*
   * openNotification
   * Opens a notification, that can be closed with an optional timer
   * (default 5000ms timer) and always closes on click.
   *
   * If a notification is opened while another is opened, it will close the
   * currently opened one and open the new one immediately.
   */
e.defineExtension("openNotification",function(t,i){function r(){l||(l=!0,clearTimeout(u),a.parentNode.removeChild(a))}n(this,r);var u,a=o(this,t,i&&i.bottom),c=i&&(void 0===i.duration?5e3:i.duration),l=!1;e.on(a,"click",function(o){e.e_preventDefault(o),r()}),c&&(u=setTimeout(r,i.duration))})});