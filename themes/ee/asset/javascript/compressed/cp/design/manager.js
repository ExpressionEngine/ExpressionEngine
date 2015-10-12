/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
/* This file exposes three callback functions:
 *
 * EE.manager.showPrefsRow and EE.manager.hidePrefsRow and
 * EE.manager.refreshPrefs
 */
/*jslint browser: true, onevar: true, undef: true, nomen: true, eqeqeq: true, plusplus: false, bitwise: true, regexp: false, strict: true, newcap: true, immed: true */
/*global $, jQuery, EE, window, document, console, alert */
"use strict";!function(t){t(document).ready(function(){function e(a,s){t("div.box",a).html(s),
// Bind validation
EE.cp.formValidation.init(a),t("form",a).on("submit",function(){return t.ajax({type:"POST",url:this.action,data:t(this).serialize()+"&save_modal=yes",dataType:"json",success:function(t){"success"==t.messageType?a.trigger("modal:close"):e(a,t.body)}}),!1})}t("table .toolbar .settings a").click(function(a){var s=t("."+t(this).attr("rel"));t.ajax({type:"GET",url:EE.template_settings_url.replace("###",t(this).data("template-id")),dataType:"html",success:function(t){e(s,t)}})})})}(jQuery);