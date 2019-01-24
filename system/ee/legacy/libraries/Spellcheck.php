<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Core Spell Checking
 */
class EE_Spellcheck {

	var $enabled = FALSE;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		ee()->lang->loadfile('spellcheck');

		if (function_exists('pspell_new') OR function_exists('curl_init') OR extension_loaded('openssl'))
		{
			ee()->load->library('javascript', array('autoload' => FALSE));

			$this->enabled = TRUE;

			// Is this a CP or front end request?
			if (REQ == 'CP') {
				ee()->cp->add_to_foot(ee()->javascript->inline($this->javascript()));
			}

			ee()->javascript->output('eeSpell.init();');
			ee()->load->helper('spellcheck');
		}

		ee()->load->vars(array('spell_enabled' => $this->enabled));
	}

	/**
	 * Spellcheck Javascript
	 *
	 * put this before the EESpell Object is called
	 *
	 * @param 	string
	 * @param 	boolean
	 * @return 	string
	 */
	function Javascript($check_url = '', $wrap = FALSE)
	{
		if ($this->enabled === FALSE)
		{
			return '';
		}

		$spell_save_edit		= lang('spell_save_edit');
		$spell_edit_word		= lang('spell_edit_word');
		$unsupported_browser	= lang('unsupported_browser');
		$no_spelling_errors		= lang('no_spelling_errors');
		$spellcheck_in_progress	= lang('spellcheck_in_progress');
		$XID_SECURE_HASH		= (defined('XID_SECURE_HASH')) ? XID_SECURE_HASH : '{XID_SECURE_HASH}';
		$is_frontend			= 'true';

		if (REQ == 'CP')
		{
			$is_frontend = 'false';
			$check_url = ($check_url == "") ? str_replace('&amp;', '&', BASE).'&C=content_publish&M=spellcheck_actions&action=check' : str_replace('&amp;', '&', $check_url);
		}

		$check_url = str_replace('&amp;', '&', $check_url);


		$r  = ($wrap === TRUE) ? '<script type="text/javascript">'.NL.'//<![CDATA['.NL : '';

		$r .= <<<EOT

		/** --------------------------------------------------
		/**  Spelling Check
		/** --------------------------------------------------*/

		var SP_XMLHttp,
			SP_originalText,
			SP_checkedText,
			SP_frameBase,
			SP_frameObj,
			SP_win = false,
			SP_popupDiv,
			SP_additionalLinks,
			SP_hiddenField,
			SP_spellField,
			SP_contentField,

			SP_clicked			= false,
			SP_unSupported		= false,
			isIE				= false,
			SP_PinTA			= false,
			SP_recentlyClicked	= false,
			SP_temp				= false,
			SP_timeOut			= 1000,
			SP_timeOutMax		= 5000,
			SP_suggested		= new Array(),
			SP_repWords			= new Array(),

			spellClass			= "spellchecked_word",
			spellClassSelected	= "spellchecked_word_selected",
			isFrontend			= $is_frontend,

			langNoSuggestions	= "{$no_spelling_errors}",
			langUnsupported		= "{$unsupported_browser}",
			langInProgress		= "{$spellcheck_in_progress}",
			langEditWord		= "{$spell_edit_word}",
			langSaveEdit		= "{$spell_save_edit}";

		function spellingCheck()
		{
		}

		spellingCheck.prototype.init = function()
		{
			// create some needed elements
			$("<div id=\"spellcheck_popup\" class=\"wordSuggestion\"></div>").appendTo("body"); // suggested words

			// some needed behaviours
			$("a.revert_spellcheck").click(function(e){SP_revertSpellCheck(this);e.preventDefault();});
			$("a.save_spellcheck").click(function(e){SP_saveSpellCheck(this);e.preventDefault();});

			$(".spellcheck_link").click(function(){
				el_id = $(this).attr("id").substring(13);
				$("#spellcheck_holder_"+el_id).slideToggle("fast");
				$(this).parent().siblings(".glossary_content").hide(); // glossary is not always present
				eeSpell.getResults(el_id);
				return false;
			});
		}

		spellingCheck.prototype.searchXML = function(xmlURL, data)
		{
			var XMLurl = xmlURL;

			if (window.XMLHttpRequest)
			{
				SP_frameBase.body.innerHTML = "";
				SP_inProgress();

				SP_XMLHttp = new XMLHttpRequest();
				SP_XMLHttp.onreadystatechange = this.processReqChange;
				SP_XMLHttp.open("POST", XMLurl, true);
				SP_XMLHttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded;");
				SP_XMLHttp.send(data);
			// branch for IE/Windows ActiveX version
			}
			else if (window.ActiveXObject)
			{
				isIE = true;

				try
				{
					SP_XMLHttp = new ActiveXObject("Microsoft.XMLHTTP");
				}
				catch(g){ return SP_unsupportedBrowser();}

				if (SP_XMLHttp)
				{
					SP_frameBase.body.innerHTML = "";
					SP_inProgress();

					SP_XMLHttp.onreadystatechange = this.processReqChange;
					SP_XMLHttp.open("POST", XMLurl, true);
					SP_XMLHttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded;");
					SP_XMLHttp.send(data);
				}
			}
			else
			{
				SP_unsupportedBrowser();
			}
		}

		spellingCheck.prototype.processReqChange = function()
		{
			if ( ! isFrontend) {
				$(".spellcheck_frame").hide();
			}
			// only if SP_XMLHttp shows "loaded"
			if (SP_XMLHttp.readyState == 4)
			{
				// only if "OK"
				if (SP_XMLHttp.status == 200)
				{
					if (SP_XMLHttp.responseText == "")
					{
						SP_clicked = false;

						if (isFrontend) {
							alert("There was a problem retrieving the XML data");
						}
						else {
							SP_hiddenField.innerHTML = "<p class=\"notice\">There was a problem retrieving the XML data.</p>";
						}
					}
					else
					{
						SP_parseXML();

						if (SP_suggested.length == 0)
						{
							SP_frameBase.body.innerHTML = "";
							if (isFrontend)
								SP_frameObj.style.display = 'none';
							return SP_noSuggestions();
						}
						SP_prepareText();
						SP_tagSpans();
						// SP_tagLinks();

						if (isFrontend) {
							SP_frameObj.style.display = 'block';
							SP_hiddenField.innerHTML = SP_additionalLinks;
							SP_hiddenField.style.visibility = 'visible';
						}
						else {
							SP_hiddenField.innerHTML = SP_additionalLinks;
							$(".spellcheck_frame").show();
						}

						// In some cases, Safari was not displaying the contents
						// until there was some action with the screen or frame contents
						// after the mouse click.  This seems to fix it.  Not sure why.
						var temp = SP_frameBase.body.innerHTML;
					}
				}
				else
				{
					SP_clicked = false;
					if (isFrontend) {
						alert("There was a problem retrieving the XML data:\\n" + SP_XMLHttp.status + ' - ' + SP_XMLHttp.responseText);
					}
					else {
						SP_hiddenField.innerHTML = "<p class=\"notice\">There was a problem retrieving the XML data:\\n" + SP_XMLHttp.status + " - " + SP_XMLHttp.responseText + "</p>";
					}
				}
			}
		}

		spellingCheck.prototype.getResults = function(field)
		{
			if (SP_clicked)
			{
				return SP_closeSpellCheck();
			}

			if (SP_win) SP_win.hidePopup();

			if ( ! field)
			{
				return;
			}

			SP_spellField = field;
			SP_hiddenField = document.getElementById("spellcheck_hidden_" + field);
			SP_contentField = document.getElementsByName(field)[0];

			if ( ! isFrontend)
				SP_hiddenField.innerHTML = "";

			SP_originalText = SP_contentField.value;
			SP_checkedText  = SP_contentField.value;
			SP_suggested = new Array();

			if ( ! SP_originalText || SP_originalText == "")
			{
				return;
			}

			SP_clicked = true;

			var searchString = SP_originalText;

			xmlURL = "{$check_url}";
			data = "q=" + escape(searchString) + "&XID={$XID_SECURE_HASH}";

			if (!SP_additionalLinks)
			{
				// console.log(SP_hiddenField.innerHTML);

				SP_additionalLinks = SP_hiddenField.innerHTML;
				// console.log(SP_additionalLinks);
			}

			SP_popupDiv = document.getElementById("spellcheck_popup");
			SP_frameObj = document.getElementById("spellcheck_frame_"+SP_spellField);

			if (SP_frameObj.contentDocument)
			{
				SP_frameBase = SP_frameObj.contentDocument;
			}
			else if (SP_frameObj.contentWindow)
			{
				SP_frameBase = SP_frameObj.contentWindow.document;
			}
			else if (SP_frameObj.document)
			{
				SP_frameBase = SP_frameObj.document;
			}
			else
			{
				if ( ! SP_PinTA)
				{
					SP_clicked = false;

					if (isFrontend)
						SP_frameObj.style.display = 'block';

					SP_inProgress();
					SP_PinTA = new Date();
					setTimeout("eeSpell.getResults(SP_spellField)", SP_timeOut);
					return;
				}
				else
				{
					var current = new Date();

					if (current - SP_PinTA > SP_timeOutMax) // Final Chance
					{
						if (SP_frameObj.contentDocument)
						{
							SP_frameBase = SP_frameObj.contentDocument;
						}
						else
						{
							SP_PinTA = false;
							SP_clicked = false;
							return SP_unsupportedBrowser();
						}
					}
					else if (current - SP_PinTA > SP_timeOut) // First Chance
					{
						if (SP_frameObj.contentDocument)
						{
							SP_frameBase = SP_frameObj.contentDocument;
						}
						else
						{
							SP_clicked = false;
							setTimeout("eeSpell.getResults(SP_spellField)", SP_timeOut);
							return;
						}
					}
					else
					{
						SP_clicked = false;
						return; // Double-click
					}
				}
			}
			if (SP_win) SP_frameBase.onmouseup = SP_PopupWindow_hidePopupWindows;

			SP_PinTA = false;

			if (isFrontend)
				SP_hiddenField.style.visibility = 'hidden';

			this.searchXML(xmlURL, data);
		}


		function SP_parseXML()
		{
			//alert(SP_XMLHttp.responseText);
			var SP_suggestedItems = SP_XMLHttp.responseXML.getElementsByTagName("item");

			for (var i = 0; i < SP_suggestedItems.length; i++)
			{
				if (SP_suggestedItems[i].childNodes.length > 1)
				{
					elementText = SP_suggestedItems[i].childNodes[1].nodeValue;
				}
				else
				{
					elementText = SP_suggestedItems[i].firstChild.nodeValue;
				}

				elementParts = elementText.split(":");
				SP_suggested[i] = elementParts[0];
				SP_repWords[i]  = elementParts[1];
			}
		}

		function SP_prepareText()
		{
			SP_checkedText = SP_checkedText.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");

			for (i=0; i < SP_suggested.length; i++)
			{
				compareString = eval("/(\\W*)" + SP_suggested[i] + "(\\W*)/g");

				SP_checkedText = SP_checkedText.replace(compareString,"$1<span class=\""+spellClass+"\">" + SP_suggested[i] + "</span>$2");
			}

			SP_checkedText = SP_checkedText.replace(/(\\r\\n|\\n|\\r)/g, "<br />");
		}

		function SP_tagSpans()
		{
			SP_frameBase.body.innerHTML = SP_checkedText;

			var spans = SP_frameBase.getElementsByTagName("span");

			for (var i = 0, l = spans.length; i < l; ++i)
			{
				if (spans[i].className.indexOf(spellClass) > -1)
				{
					spans[i].id = "spellcheckedword_" + i;
					spans[i].onclick = SP_clickWord;
				}
			}
		}

		function SP_tagLinks()
		{
			var links = SP_frameBase.getElementsByTagName("a");

			for (var i = 0, l = links.length; i < l; ++i)
			{
				links[i].onclick = function() {return false;};
			}
		}



		function SP_resetSpanStyles()
		{
			var spans = SP_frameBase.getElementsByTagName("span");

			for (var i = 0, l = spans.length; i < l; ++i)
			{
				if (spans[i].className.indexOf(spellClassSelected) > -1)
				{
					spans[i].className = spellClass;
				}
			}
		}


		function SP_clickWord()
		{
			SP_recentlyClicked = this;

			var spans = SP_frameBase.getElementsByTagName("span");

			for (var i = 0, l = spans.length; i < l; ++i)
			{
				spans[i].className = spellClass;
			}

			this.className = spellClassSelected;

			for(i=0, l = SP_suggested.length; i < l; ++i)
			{
				if (SP_suggested[i] == this.innerHTML)
				{
					break;
				}
			}

			//alert(this.innerHTML + " :" + SP_repWords[i] + " ID:" + this.id);

			SP_popupDiv.innerHTML = SP_suggestionMenu(SP_repWords[i], this.id);

			frameCoordinates = SP_absolutePosition(SP_frameObj);
			scrollCoordinates = SP_scrollPosition(SP_frameBase);

			SP_win = new SP_PopupWindow("spellcheck_popup");
			SP_win.offsetX = frameCoordinates.x;
			SP_win.offsetY = frameCoordinates.y+17 - scrollCoordinates.y;
			SP_win.showPopup(this);
			SP_win.autoHide();
			SP_win.editInProgress = false;
		}

		function SP_suggestionMenu(suggestions, id)
		{
			words = suggestions.split(",");

			for (i=0, l = words.length, str=""; i < l;  ++i)
			{
				str += "<a href=\"javascript:void(0);\" onclick=\"SP_replaceWord(this, \'" + id + "\');return false;\">";
				str += words[i] + "</a><br />";
			}

			str += "----<br /><a href=\"javascript:void(0);\" onmousedown=\"SP_editWordPause();\" onclick=\"SP_editWord(this, \'" + id + "\');return false;\">" + langEditWord + "</a>";

			return str;
		}

		function SP_replaceWord(el, id)
		{
			var spans = SP_frameBase.getElementsByTagName("span");

			var newText = SP_frameBase.createTextNode(el.innerHTML);

			if (spans[id])
			{
				spans[id].parentNode.insertBefore(newText, spans[id]);
				spans[id].parentNode.removeChild(spans[id]);
				SP_resetSpanStyles();
				// spans[id].innerHTML = el.innerHTML;
			}

			if (SP_win) SP_win.hidePopup();
		}

		function SP_editWordPause()
		{
			// Safari has issues
			SP_temp = document.onmouseup;
			document.onmouseup = function() {}
		}


		function SP_editWord(el, id)
		{
			var spans = SP_frameBase.getElementsByTagName("span");

			if (spans[id])
			{
				var newObj = document.createElement("input");
				newObj.setAttribute("type", "text");
				//newObj.setAttribute("name", "input_"+id);
				newObj.setAttribute("id", "input_"+id);
				newObj.setAttribute("value", spans[id].innerHTML);
				newObj.setAttribute("size", spans[id].innerHTML.length+2);
				newObj.setAttribute("class", "input");
				newObj.onkeypress = SP_saveEditReturn;

				el.parentNode.insertBefore(newObj, el);

				var newObj2 = document.createElement("a");
				newObj2.setAttribute("href", "javascript:void(0);");
				newObj2.setAttribute("id", "link_"+id);
				newObj2.onclick = SP_saveEdit;
				newObj2.innerHTML = langSaveEdit;
				el.parentNode.insertBefore(document.createElement("br"), el);

				el.parentNode.insertBefore(newObj2, el);

				el.parentNode.removeChild(el);
				newObj.focus();
				newObj.select();
			}

			if (SP_temp)
			{
				document.onmouseup = SP_temp;
				SP_temp = false;
			}
		}

		function SP_saveEdit()
		{
			var id = this.id.replace("link_", "");

			var spans = SP_frameBase.getElementsByTagName("span");

			var inputs = document.getElementsByTagName("input");

			if (spans[id] && inputs["input_"+id])
			{
				var newText = SP_frameBase.createTextNode(inputs["input_"+id].value);
				spans[id].parentNode.insertBefore(newText, spans[id]);
				spans[id].parentNode.removeChild(spans[id]);
				SP_resetSpanStyles();
			}

			if (SP_win) SP_win.hidePopup();
		}

		function SP_saveEditReturn(e)
		{
			if (window.event)
			{
				e = window.event;
			}

			var charCode = ( ! e.which || e.which == 0) ? e.keyCode : e.which;

			if (charCode == 13)
			{
				var id = this.id.replace("input_", "");

				var spans = SP_frameBase.getElementsByTagName("span");

				if (spans[id])
				{
					var newText = SP_frameBase.createTextNode(this.value);
					spans[id].parentNode.insertBefore(newText, spans[id]);
					spans[id].parentNode.removeChild(spans[id]);
					SP_resetSpanStyles();
				}

				SP_win.hidePopup();

				return false;
			}
		}


		function SP_absolutePosition(el)
		{
			var coordinates = { x: el.offsetLeft, y: el.offsetTop };

			if (el.offsetParent)
			{
				var tmp = SP_absolutePosition(el.offsetParent);
				coordinates.x += tmp.x;
				coordinates.y += tmp.y;
			}

			return coordinates;
		};

		function SP_scrollPosition(el)
		{
			if ( ! el.documentElement.scrollTop)
			{
				var coordinates = { x: SP_frameBase.body.scrollLeft, y: SP_frameBase.body.scrollTop };
			}
			else
			{
				var coordinates = { x:  SP_frameBase.documentElement.scrollLeft, y:  SP_frameBase.documentElement.scrollTop };
			}

			return coordinates;
		};


		function SP_closeSpellCheck()
		{
			if (SP_win) SP_win.hidePopup();

			SP_clicked = false;

			if (SP_frameObj && ! SP_unSupported)
			{
				if (isFrontend) {
					SP_frameObj.style.display = 'none';
					SP_hiddenField.style.visibility = 'hidden';
				}
				SP_frameBase.body.innerHTML = "";
			}
		}

		function SP_inProgress()
		{
			SP_unSupported = false;
			SP_hiddenField.innerHTML = "<p class=\"go_notice\">"+langInProgress+"</p>";
		}

		function SP_saveSpellCheck(el_id)
		{
			if (SP_win) SP_win.hidePopup();

			SP_clicked = false;

			var spans = SP_frameBase.getElementsByTagName("span");

			for (var i = spans.length; --i >= 0;) // Need to go backwards through nodes
			{
				if (spans[i].className.indexOf(spellClass) > -1)
				{
					spans[i].parentNode.insertBefore(spans[i].firstChild, spans[i]);
					spans[i].parentNode.removeChild(spans[i]);
				}
			}

			var content = SP_frameBase.body.innerHTML;

			content = content.replace(/(\\r\\n|\\r|\\n)/g, " ").replace(/<br *\/?>/gi, "\\n");

			SP_frameBase.body.innerHTML = "";

			content = content.replace(/&gt;/g, ">").replace(/&lt;/g, "<").replace(/&amp;/g, "&");

			SP_contentField.value = content;

			if ( ! isFrontend)
				$(el_id).closest(".spellcheck_content").show().slideToggle("fast");

			return false;
		}

		function SP_revertSpellCheck(el_id)
		{
			if ( ! isFrontend)
				$(el_id).closest(".spellcheck_content").show().slideToggle("fast");
			return false;
		}

		function SP_unsupportedBrowser()
		{
			SP_clicked = false;
			SP_unSupported = true;

			if (isFrontend) {
				SP_hiddenField.innerHTML = ' | ' +  langUnsupported;
				SP_hiddenField.style.visibility = 'visible';
			}
			else {
				SP_hiddenField.innerHTML = "<p class=\"go_notice\">"+langUnsupported+"</p>";
			}
		}

		function SP_noSuggestions()
		{
			SP_clicked = false;

			if (isFrontend) {
				SP_hiddenField.innerHTML = ' | ' + langNoSuggestions;
				SP_hiddenField.style.visibility = 'visible';
			}
			else {
				$("#spellcheck_holder_"+this.SP_spellField).slideToggle("fast");
				SP_hiddenField.innerHTML = "<p class=\"go_notice\">"+langNoSuggestions+"</p>";
				$.ee_notice(langNoSuggestions);
			}
		}

		// Used on the frontend
		function SP_revertToOriginal()
		{
			if (SP_win) SP_win.hidePopup();

			SP_clicked = false;

			SP_frameObj.style.display = 'none';
			SP_hiddenField.style.visibility = 'hidden';
			SP_contentField.value = SP_originalText;
			SP_frameBase.body.innerHTML = '';

			if (document.getElementById('entryform')) hide_open_panes();  // Publish area panes
		}

		var eeSpell = new spellingCheck();

		/** ----------------------------------------
		/**  Spell Check Popup Code
		/** ----------------------------------------*/

		// ===================================================================
		//  - CREATOR -
		// Author: Matt Kruse <matt@mattkruse.com>
		// WWW: http://www.mattkruse.com/
		// ===================================================================


		// 	 SP_getAnchorPosition(anchorname)
		//	This function returns an object having .x and .y properties which are the coordinates
		//	of the named anchor, relative to the page.

		function SP_getAnchorPosition(object)
		{
			// This function will return an Object with x and y properties
			var useWindow=false;
			var coordinates=new Object();
			var x=0,y=0;
			// Browser capability sniffing
			var use_gebi=false, use_css=false, use_layers=false;
			if (document.getElementById) { use_gebi=true; }
			else if (document.all) { use_css=true; }
			else if (document.layers) { use_layers=true; }
			// Logic to find position
		 	if (use_gebi && document.all) {
				x=SP_AnchorPosition_getPageOffsetLeft(object);
				y=SP_AnchorPosition_getPageOffsetTop(object);
				}
			else if (use_gebi) {
				x=SP_AnchorPosition_getPageOffsetLeft(object);
				y=SP_AnchorPosition_getPageOffsetTop(object);
				}
		 	else if (use_css) {
				x=SP_AnchorPosition_getPageOffsetLeft(object);
				y=SP_AnchorPosition_getPageOffsetTop(object);
				}
			else if (use_layers) {
				var found=0;
				for (var i=0; i<document.anchors.length; i++) {
					if (document.anchors[i].name==object.id) { found=1; break; }
					}
				if (found==0) {
					coordinates.x=0; coordinates.y=0; return coordinates;
					}
				x=document.anchors[i].x;
				y=document.anchors[i].y;
				}
			else {
				coordinates.x=0; coordinates.y=0; return coordinates;
				}
			coordinates.x=x;
			coordinates.y=y;
			return coordinates;
			}

		// Functions for IE to get position of an object
		function SP_AnchorPosition_getPageOffsetLeft (el)
		{
			var ol=el.offsetLeft;
			while ((el=el.offsetParent) != null) { ol += el.offsetLeft; }
			return ol;
		}

		function SP_AnchorPosition_getPageOffsetTop (el)
		{
			var ot=el.offsetTop;
			while((el=el.offsetParent) != null) { ot += el.offsetTop; }
			return ot;
		}
		/* SOURCE FILE: PopupWindow.js */

		/*
		PopupWindow.js
		Author: Matt Kruse
		Last modified: 02/16/04
		*/

		// Set the position of the popup window based on the anchor
		function SP_PopupWindow_getXYPosition(object) {
			var coordinates = SP_getAnchorPosition(object);
			this.x = coordinates.x;
			this.y = coordinates.y;
			}
		// Set width/height of DIV/popup window
		function SP_PopupWindow_setSize(width,height) {
			this.width = width;
			this.height = height;
			}
		// Fill the window with contents
		function SP_PopupWindow_populate(contents) {
			this.contents = contents;
			this.populated = false;
			}
		// Set the URL to go to
		function SP_PopupWindow_setUrl(url) {
			this.url = url;
			}
		// Set the window popup properties
		function SP_PopupWindow_setWindowProperties(props) {
			this.windowProperties = props;
			}
		// Refresh the displayed contents of the popup
		function SP_PopupWindow_refresh() {
			if (this.divName != null) {
				// refresh the DIV object
				if (this.use_gebi) {
					document.getElementById(this.divName).innerHTML = this.contents;
					}
				else if (this.use_css) {
					document.all[this.divName].innerHTML = this.contents;
					}
				else if (this.use_layers) {
					var d = document.layers[this.divName];
					d.document.open();
					d.document.writeln(this.contents);
					d.document.close();
					}
				}
			else {
				if (this.popupWindow != null && ! this.popupWindow.closed) {
					if (this.url !="") {
						this.popupWindow.location.href=this.url;
						}
					else {
						this.popupWindow.document.open();
						this.popupWindow.document.writeln(this.contents);
						this.popupWindow.document.close();
					}
					this.popupWindow.focus();
					}
				}
			}
		// Position and show the popup, relative to an anchor object
		function SP_PopupWindow_showPopup(object) {
			this.getXYPosition(object);
			this.x += this.offsetX;
			this.y += this.offsetY;
			if ( ! this.populated && (this.contents != "")) {
				this.populated = true;
				this.refresh();
				}
			if (this.divName != null) {
				// Show the DIV object
				if (this.use_gebi) {
					document.getElementById(this.divName).style.left = this.x + "px";
					document.getElementById(this.divName).style.top = this.y + "px";
					document.getElementById(this.divName).style.visibility = "visible";
					}
				else if (this.use_css) {
					document.all[this.divName].style.left = this.x;
					document.all[this.divName].style.top = this.y;
					document.all[this.divName].style.visibility = "visible";
					}
				else if (this.use_layers) {
					document.layers[this.divName].left = this.x;
					document.layers[this.divName].top = this.y;
					document.layers[this.divName].visibility = "visible";
					}
				}
			else {
				if (this.popupWindow == null || this.popupWindow.closed) {
					// If the popup window will go off-screen, move it so it does not
					if (this.x<0) { this.x=0; }
					if (this.y<0) { this.y=0; }
					if (screen && screen.availHeight) {
						if ((this.y + this.height) > screen.availHeight) {
							this.y = screen.availHeight - this.height;
							}
						}
					if (screen && screen.availWidth) {
						if ((this.x + this.width) > screen.availWidth) {
							this.x = screen.availWidth - this.width;
							}
						}
					var avoidAboutBlank = window.opera || ( document.layers && ! navigator.mimeTypes["*"] ) || navigator.vendor == "KDE" || ( document.childNodes && ! document.all && ! navigator.taintEnabled );
					this.popupWindow = window.open(avoidAboutBlank?"":"about:blank","window_"+object.id,this.windowProperties+",width="+this.width+",height="+this.height+",screenX="+this.x+",left="+this.x+",screenY="+this.y+",top="+this.y+"");
					}
				this.refresh();
				}
			}
		// Hide the popup
		function SP_PopupWindow_hidePopup()
		{
			if (this.divName != null)
			{
				if (this.use_gebi)
				{
					document.getElementById(this.divName).style.visibility = "hidden";
				}
				else if (this.use_css)
				{
					document.all[this.divName].style.visibility = "hidden";
				}
				else if (this.use_layers)
				{
					document.layers[this.divName].visibility = "hidden";
				}
			}
			else
			{
				if (this.popupWindow && ! this.popupWindow.closed)
				{
					this.popupWindow.close();
					this.popupWindow = null;
				}
			}
		}
		// Pass an event and return whether or not it was the popup DIV that was clicked
		function SP_PopupWindow_isClicked(e) {
			if (this.divName != null) {
				if (this.use_layers) {
					var clickX = e.pageX;
					var clickY = e.pageY;
					var t = document.layers[this.divName];
					if ((clickX > t.left) && (clickX < t.left+t.clip.width) && (clickY > t.top) && (clickY < t.top+t.clip.height)) {
						return true;
						}
					else { return false; }
					}
				else if (document.all) { // Need to hard-code this to trap IE for error-handling
					var t = e;
					try {
						while (t.parentElement != null) {
							if (t.id==this.divName) {
								return true;
								}
							t = t.parentElement;
							}
						} catch(g) {}
					return false;
					}
				else if (this.use_gebi && e) {
					var t = e.originalTarget;
					try {while (t.parentNode != null) {
						if (t.id==this.divName) {
							return true;
							}
						t = t.parentNode;
						} } catch(g) {}
					return false;
					}
				return false;
				}
			return false;
			}

		// Check an onmouseDown event to see if we should hide
		function SP_PopupWindow_hideIfNotClicked(e) {
			if (this.autoHideEnabled && ! this.isClicked(e)) {
				this.hidePopup();
				}
			}
		// Call this to make the DIV disable automatically when mouse is clicked outside it
		function SP_PopupWindow_autoHide() {
			this.autoHideEnabled = true;
			}
		// This global function checks all PopupWindow objects onmouseup to see if they should be hidden
		function SP_PopupWindow_hidePopupWindows(e) {
			for (var i=0; i<popupWindowObjects.length; i++) {
				if (popupWindowObjects[i] != null) {
					var p = popupWindowObjects[i];
					p.hideIfNotClicked(e);
					}
				}

				SP_recentlyClicked.className = spellClass;
			}
		// Run this immediately to attach the event listener
		function SP_PopupWindow_attachListener()
		{
			if (document.layers)
			{
				document.captureEvents(Event.MOUSEUP);
			}

			window.popupWindowOldEventListener = document.onmouseup;

			if (window.popupWindowOldEventListener != null)
			{
				document.onmouseup = new Function("window.popupWindowOldEventListener(); SP_PopupWindow_hidePopupWindows();");

				if (SP_frameBase)
				{
					SP_frameBase.onmouseup = new Function("window.popupWindowOldEventListener(); SP_PopupWindow_hidePopupWindows();");
				}
			}
			else
			{
				// Turned this off because Safari is a pain with the Edit Word ability
				document.onmouseup = SP_PopupWindow_hidePopupWindows;

				if (SP_frameBase)
				{
					SP_frameBase.onmouseup = SP_PopupWindow_hidePopupWindows;
				}
			}
		}
		// CONSTRUCTOR for the PopupWindow object
		// Pass it a DIV name to use a DHTML popup, otherwise will default to window popup
		function SP_PopupWindow() {
			if ( ! window.popupWindowIndex) { window.popupWindowIndex = 0; }
			if ( ! window.popupWindowObjects) { window.popupWindowObjects = new Array(); }
			if ( ! window.SP_listenerAttached) {
				window.SP_listenerAttached = true;
				SP_PopupWindow_attachListener();
				}
			this.index = popupWindowIndex++;
			popupWindowObjects[this.index] = this;
			this.divName = null;
			this.popupWindow = null;
			this.width=0;
			this.height=0;
			this.populated = false;
			this.visible = false;
			this.autoHideEnabled = false;

			this.contents = "";
			this.url="";
			this.windowProperties="toolbar=no,location=no,status=no,menubar=no,scrollbars=auto,resizable,alwaysRaised,dependent,titlebar=no";
			if (arguments.length>0) {
				this.type="DIV";
				this.divName = arguments[0];
				}
			else {
				this.type="WINDOW";
				}
			this.use_gebi = false;
			this.use_css = false;
			this.use_layers = false;
			if (document.getElementById) { this.use_gebi = true; }
			else if (document.all) { this.use_css = true; }
			else if (document.layers) { this.use_layers = true; }
			else { this.type = "WINDOW"; }
			this.offsetX = 0;
			this.offsetY = 0;
			// Method mappings
			this.getXYPosition = SP_PopupWindow_getXYPosition;
			this.populate = SP_PopupWindow_populate;
			this.setUrl = SP_PopupWindow_setUrl;
			this.setWindowProperties = SP_PopupWindow_setWindowProperties;
			this.refresh = SP_PopupWindow_refresh;
			this.showPopup = SP_PopupWindow_showPopup;
			this.hidePopup = SP_PopupWindow_hidePopup;
			this.setSize = SP_PopupWindow_setSize;
			this.isClicked = SP_PopupWindow_isClicked;
			this.autoHide = SP_PopupWindow_autoHide;
			this.hideIfNotClicked = SP_PopupWindow_hideIfNotClicked;
			this.object = false;
			}
EOT;

		$r .= ($wrap === TRUE) ? NL.'//]]>'.NL.'</script>' : '';

		if (REQ != 'CP')
		{
			$r = str_replace('{XID_SECURE_HASH}', 'ignore', $r);
		}

		return $r;

	}

	/**
	 * iFrame
	 */
	public function iframe()
	{
		ee()->session->tracker = array_shift(ee()->session->tracker);

		ee()->session->set_tracker_cookie();

  		if ( ! defined('AMP')) define('AMP', '&amp;');
		if ( ! defined('BR'))  define('BR',  '<br />');
		if ( ! defined('NL'))  define('NL',  "\n");
		if ( ! defined('NBS')) define('NBS', "&nbsp;");


		$header =

		"<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"\n\n".
		"\"http://www.w3.org/TR/html4/loose.dtd\">\n\n".
		"<head>\n".
		"<title>".APP_NAME." | ".lang('spell_check')."</title>\n\n".
		"<meta http-equiv='content-type' content='text/html; charset=".ee()->config->item('output_charset')."'>\n".
		"<meta name='MSSmartTagsPreventParsing' content='TRUE'>\n".
		"<meta http-equiv='expires' content='-1'>\n".
		"<meta http-equiv='expires' content='Mon, 01 Jan 1970 23:59:59 GMT'>\n".
		"<meta http-equiv='pragma' content='no-cache'>\n";

		if (REQ == 'CP')
		{
			$header .= ee()->view->head_link('css/spellcheck_frame.css');
		}
		else
		{
			$header .= <<<EOH
				<style type="text/css">
				<!--
				body
				{
				margin:			0;
				padding:			5px;
				font-family:		Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
				font-size:		 11px;
				color:			 #333;
				background-color:  #fff;
				line-height: 18px;
				}

				.spellchecked_word
				{
					cursor: pointer;
					background-color: #fff;
					border-bottom: 1px dashed #ff0000;
				}

				.spellchecked_word_selected
				{
					cursor: pointer;
					background-color: #F72A58;
					color: #FFF;
				}
				-->
				</style>
EOH;
		}


		$header .= "</head>\n\n".
		"<body></body>\n</html>";

		@header('Content-Type: text/html');
		exit($header);
	}



	/** -----------------------------------------
	/**  Spell Check for Textareas
	/** -----------------------------------------*/
	function check($lang='en')
	{
		/* -------------------------------------------
		/*	Hidden Configuration Variable
		/*
		/*	- spellcheck_language_code => What is the two letter ISO 639 language
		/*	  code for the spellcheck (ex: en, es, de)
		/* -------------------------------------------*/

		if (ee()->config->item('spellcheck_language_code') !== FALSE && strlen(ee()->config->item('spellcheck_language_code')) == 2)
		{
			$lang = ee()->config->item('spellcheck_language_code');
		}

		// ----------------------------------
		//  These 100 words make up 1/2 of all written material
		//  and by not checking them we should be able to greatly
		//  speed up the spellchecker
		// ----------------------------------

		$common = array('the', 'of', 'and', 'a', 'to', 'in', 'is', 'you', 'that',
						'it', 'he', 'was', 'for', 'on', 'are', 'as', 'with', 'his',
						'they', 'I', 'at', 'be', 'this', 'have', 'from', 'or', 'one',
						'had', 'by', 'word', 'but', 'not', 'what', 'all', 'were', 'we',
						'when', 'your', 'can', 'said', 'there', 'use', 'an', 'each',
						'which', 'she', 'do', 'how', 'their', 'if', 'will', 'up',
						'other', 'about', 'out', 'many', 'then', 'them', 'these', 'so',
						'some', 'her', 'would', 'make', 'like', 'him', 'into', 'time',
						'has', 'look', 'two', 'more', 'write', 'go', 'see', 'number',
						'no', 'way', 'could', 'people', 'my', 'than', 'first', 'water',
						'been', 'call', 'who', 'oil', 'its', 'now', 'find', 'long',
						'down', 'day', 'did', 'get', 'come', 'made', 'may', 'part');

		// The contents of the field are encoded by javascript before
		// they are sent to us so we have to decode them before processing.
		// We are also removing any HTML code and HTML code entities so that we
		// do not process them as misspelled words.

		$content = preg_replace("|<.*?".">|", '', rawurldecode(ee('Security/XSS')->clean(ee()->input->get_post('q'))));
		$content = str_replace(array('&amp;', '&lt;', '&gt;'), '', $content);

		$str = '<?xml version="1.0" encoding="UTF-8"?'.">\n<items>\n";
		$items = array();
		$prechecked  = array();

		if ( ! function_exists('pspell_new'))
		{
			$content = str_replace('&', ' ', stripslashes($content));

			// Google has silently changed the service internally, setting ignoredups="1" now causes results to
			// always return as spelled correctly.  -- changed 8/20/08 d.j.
			$payload = 	'<spellrequest textalreadyclipped="0" ignoredups="0" ignoredigits="1" ignoreallcaps="0"><text>'
						.	$content
						.'</text></spellrequest>';

			$url = 'https://www.google.com/tbproxy/spell?lang='.$lang.'&hl='.$lang;

			if (function_exists('curl_init'))
			{
				$data = EE_Spellcheck::curl_process($url, $payload);
			}
			else
			{
				$data = EE_Spellcheck::fsockopen_process($url, $payload);
			}

			if ($data == '')
			{
				ee()->output->set_status_header(404);
				@header("Date: ".gmdate("D, d M Y H:i:s")." GMT");
				exit('Unable to connect to spellcheck');
			}

			// suckz => <c o="10" l="5" s="0">sucks	sicks	suck	sacks	socks</c>

			if ($data != '' && preg_match_all("|<c\s+(.*?)>(.*?)</c>|is", $data, $matches))
			{
				for($i = 0, $s = count($matches['0']); $i < $s; ++$i)
				{
					$x = explode('"', $matches['1'][$i]);
					$word = substr($content, $x['1'], $x['3']);

					if ( ! in_array($word, $prechecked))
					{
						$sug = preg_split("|\s+|s", $matches['2'][$i]);
						natcasesort($sug);

						$items[] = $word.':'.implode(',',$sug).'';
						$prechecked[] = $word;
					}
				}
			}
		}
		else
		{
			// Split it up by non-words
			preg_match_all("|[\w\']{2,20}|", stripslashes($content), $parts);

			$pspell = pspell_new($lang);

			for($i=0, $s = count($parts['0']); $i < $s; $i++)
			{
				if ( ! is_numeric($parts['0'][$i]) &&
					! in_array(strtolower($parts['0'][$i]), $common) &&
					! in_array($parts['0'][$i], $prechecked) &&
					! pspell_check($pspell, $parts['0'][$i]))
				{
					$sug = array();

					if ($suggestions = pspell_suggest($pspell, $parts['0'][$i]))
					{
						foreach ($suggestions as $suggest)
						{
							$sug[] = $suggest;

							if (count($sug) > 8) break;
						}
					}

					natcasesort($sug);

					$items[] = $parts['0'][$i].':'.implode(',',$sug).'';
					$prechecked[] = $parts['0'][$i];
				}
			}
		}

		$str .= (count($items) == 0) ? '' : "<item>".implode("</item>\n<item>",$items)."</item>";

		$str .= "\n</items>";

		@header("Content-Type: text/xml");
		exit($str);
	}




	/** ----------------------------------------
	/**  Sing a Song, Have a Dance
	/** ----------------------------------------*/

	function curl_process($url, $payload)
	{
		$ch=curl_init();
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_POST,1);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$payload);

		// Start ob to prevent curl_exec from displaying stuff.
		ob_start();
		curl_exec($ch);

		//Get contents of output buffer
		$info=ob_get_contents();
		curl_close($ch);

		//End ob and erase contents.
		ob_end_clean();

		return $info;
	}



	/** ----------------------------------------
	/**  Drinking with Friends is Fun!
	/** ----------------------------------------*/

	function fsockopen_process($url, $payload)
	{
		$parts	= parse_url($url);
		$host	= $parts['host'];
		$path	= ( ! isset($parts['path'])) ? '/' : $parts['path'];
		$port	= ($parts['scheme'] == "https") ? '443' : '80';
		$ssl	= ($parts['scheme'] == "https") ? 'ssl://' : '';

		if (isset($parts['query']) && $parts['query'] != '')
		{
			$path .= '?'.$parts['query'];
		}

		$info = '';

		$fp = @fsockopen($ssl.$host, $port, $error_num, $error_str, 8);

		if (is_resource($fp))
		{
			fputs($fp, "POST {$path} HTTP/1.0\r\n");
			fputs($fp, "Host: {$host}\r\n");
			fputs($fp, "Content-Type: application/x-www-form-urlencoded\r\n");
			fputs($fp, "Content-Length: ".strlen($payload)."\r\n");
			fputs($fp, "Connection: close\r\n\r\n");
			fputs($fp, $payload . "\r\n\r\n");

			/* ------------------------------
			/*  This error suppression has to do with a PHP bug involving
			/*  SSL connections: http://bugs.php.net/bug.php?id=23220
			/* ------------------------------*/

			$old_level = error_reporting(0);

			while($datum = fread($fp, 4096))
			{
				$info .= $datum;
			}

			error_reporting($old_level);

			@fclose($fp);
		}

		return $info;
	}



}
// END CLASS

// EOF
