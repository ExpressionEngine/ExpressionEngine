<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * HTML Buttons
 */
class EE_Html_buttons
{
    public $allow_img = false;
    public $buttons = array();

    /**
     * Resets the class vars.
     */
    public function reset()
    {
        $this->buttons = array();
        $this->allow_img = false;
    }

    public function create_buttons()
    {
        $this->buttons[] = array("<b>",	"[strong]",	"[/strong]", "Bold Text");
        $this->buttons[] = array("<i>",	"[em]", 	"[/em]", "Italic Text");
        $this->buttons[] = array("<u>",	"[u]", 	"[/u]", "Underline Text");

        if ($this->allow_img == true) {
            $this->buttons[] = array("<img>", "[img]", "[/img]", "Image Link");
        }

        $this->buttons[] = array("quote", "[quote]", "[/quote]", "Quoted Text");
        $this->buttons[] = array("code",  "[code]",  "[/code]", "Code Example");

        ob_start(); ?>

<script type="text/javascript">
//<![CDATA[

var selField  = 'body';
var selMode	= "normal";

var url_text	  = "Enter the hyperlink URL";
var webpage_text  = "Enter the title of the link";
var title_text	  = "Optional: Enter a title attribute";
var image_text	= "Enter the image URL";
var email_text	= "Enter the email address";
var email_title	= "Enter the link title (or leave the field empty to use the email address as the title.)";
var enter_text	= "Enter the text you want to format";

<?php

    for ($i = 0; $i < count($this->buttons); $i++) {
        echo "var button_{$i} = 0;\n";
    } ?>


tagarray  = new Array();
usedarray = new Array();

function nullo()
{
	return;
}

//  State change

function styleswap(link)
{
	if (document.getElementById(link).className == 'htmlButtonOff')
	{
		document.getElementById(link).className = 'htmlButtonOn';
	}
	else
	{
		document.getElementById(link).className = 'htmlButtonOff';
	}
}

//  Set button mode

function setmode(which)
{
	if (which == 'guided')
		selMode = 'guided';
	else
		selMode = 'normal';
}

// Clear state

function clear_state()
{
	if (usedarray[0])
	{
		while (usedarray[0])
		{
			clearState = arraypop(usedarray);

			eval(clearState + " = 0");

			document.getElementById(clearState).className = 'htmlButtonOff';
		}
	}
}

// Array size

function getarraysize(thearray)
{
	for (i = 0; i < thearray.length; i++)
	{
		if ((thearray[i] == "undefined") || (thearray[i] == "") || (thearray[i] == null))
		{
			return i;
		}
	}

	return thearray.length;
}

// Array push

function arraypush(thearray, value)
{
	thearray[getarraysize(thearray)] = value;
}

// Array pop

function arraypop(thearray)
{
	thearraysize = getarraysize(thearray);
	retval = thearray[thearraysize - 1];
	delete thearray[thearraysize - 1];
	return retval;
}

// Insert single tag

function singleinsert(tagOpen)
{
	eval("document.getElementById('submit_post')." + selField + ".value += tagOpen");

	curField = eval("document.getElementById('submit_post')." + selField);
	curField.blur();
	curField.focus();
}


// Insert font color and size

function selectinsert(item, tag)
{
	var selval =  item.options[item.selectedIndex].value;

	if (selval == 0)
		return;

	var tagOpen		= '[' + tag + '=' + selval + ']';
	var tagClose	= '[/' + tag + ']';

	taginsert('other', tagOpen, tagClose, 'menu');
}

// Insert tag

var tagarray  	= new Array();
var usedarray 	= new Array();
var running		= 0;

function taginsert(item, tagOpen, tagClose, type)
{
	// Determine which tag we are dealing with

	var which = eval('item.name');

	var theSelection = false;
	var result		 = false;
	eval("var theField = document.getElementById('submit_post')." + selField + ";");

	if (selMode == 'guided')
	{
		data = prompt(enter_text, "");

		if ((data != null) && (data != ""))
		{
			result = tagOpen + data + tagClose;
		}
	}

	// one branch for Firefox/Safari/Opera, another for IE
	if (window.getSelection && (theSelection = window.getSelection()) != false)
	{
		theSelection = window.getSelection();

		var selLength = theField.textLength;
		var selStart = theField.selectionStart;
		var selEnd = theField.selectionEnd;
		if (selEnd <= 2 && typeof(selLength) != 'undefined')
			selEnd = selLength;

		var s1 = (theField.value).substring(0,selStart);
		var s2 = (theField.value).substring(selStart, selEnd)
		var s3 = (theField.value).substring(selEnd, selLength);

		s2 = (result == false) ? tagOpen + theSelection + tagClose : result;

		theSelection = '';

		theField.value = s1+s2+s3;
		theField.blur();
		theField.focus();

		return;
	}
	else if (document.selection)
	{
		theSelection = document.selection.createRange().text;

		theField.focus();

		if (theSelection)
		{
			document.selection.createRange().text = (result == false) ? tagOpen + theSelection + tagClose : result;
		}
		else
		{
			document.selection.createRange().text = (result == false) ? tagOpen + tagClose : result;
		}

		theSelection = '';

		theField.blur();
		theField.focus();

		return;
	}
	else if ( ! isNaN(theField.selectionEnd))
	{
		var scrollPos = theField.scrollTop;
		var selLength = theField.textLength;
		var selStart = theField.selectionStart;
		var selEnd = theField.selectionEnd;
		if (selEnd <= 2 && typeof(selLength) != 'undefined')
			selEnd = selLength;

		var s1 = (theField.value).substring(0,selStart);
		var s2 = (theField.value).substring(selStart, selEnd)
		var s3 = (theField.value).substring(selEnd, selLength);

		if (result == false)
		{
			var newStart = selStart + tagOpen.length + s2.length + tagClose.length;

			theField.value = (result == false) ? s1 + tagOpen + s2 + tagClose + s3 : result;
		}
		else
		{
			var newStart = selStart + result.length;

			theField.value = s1 + result + s3;
		}

		theField.focus();
		theField.selectionStart = newStart;
		theField.selectionEnd = newStart;
		theField.scrollTop = scrollPos;
		return;
	}
	else if (selMode == 'guided')
	{
		eval("document.getElementById('submit_post')." + selField + ".value += result");

		curField = eval("document.getElementById('submit_post')." + selField);
		curField.blur();
		curField.focus();
		return;
	}


	// Add single open tags

	if (item == 'other')
	{
		if (tagClose)
		{
			eval("document.getElementById('submit_post')." + selField + ".value += tagOpen + tagClose");
		}
		else
		{
			eval("document.getElementById('submit_post')." + selField + ".value += tagOpen");
		}
	}
	else if (eval(which) == 0)
	{
		var result = tagOpen;

		eval("document.getElementById('submit_post')." + selField + ".value += result");
		eval(which + " = 1");

		arraypush(tagarray, tagClose);
		arraypush(usedarray, which);

		running++;

		styleswap(which);
	}
	else
	{
		// Close tags

		n = 0;

		for (i = 0 ; i < tagarray.length; i++ )
		{
			if (tagarray[i] == tagClose)
			{
				n = i;

				running--;

				while (tagarray[n])
				{
					closeTag = arraypop(tagarray);
					eval("document.getElementById('submit_post')." + selField + ".value += closeTag");
				}

				while (usedarray[n])
				{
					clearState = arraypop(usedarray);
					eval(clearState + " = 0");
					document.getElementById(clearState).className = 'htmlButtonA';
				}
			}
		}
	}

	curField = eval("document.getElementById('submit_post')." + selField);
	curField.blur();
	curField.focus();
}

// Prompted tags

function promptTag(which)
{

	if ( ! which)
		return;

	// Is this a Windows user?

	var theSelection = "";
	eval("var theField = document.getElementById('submit_post')." + selField + ";");

	if ((navigator.appName == "Microsoft Internet Explorer") &&
		(navigator.appVersion.indexOf("Win") != -1))
	{
		theSelection = document.selection.createRange().text;
	}
	else if (theField.selectionEnd && (theField.selectionEnd - theField.selectionStart > 0))
	{
		var selLength = theField.textLength;
		var selStart = theField.selectionStart;
		var selEnd = theField.selectionEnd;
		if (selEnd <= 2 && typeof(selLength) != 'undefined')
			selEnd = selLength;

		var s1 = (theField.value).substring(0,selStart);
		var s2 = (theField.value).substring(selStart, selEnd)
		var s3 = (theField.value).substring(selEnd, selLength);
		theSelection = s2;
	}

	// Create Link
	if (which == "link")
	{
		var URL = prompt(url_text, "http://");

		if ( ! URL || URL == 'http://' || URL == null)
			return;

		var Name = prompt(webpage_text, theSelection);

		if ( ! Name || Name == null)
			return;

		var Link = '[url=' + URL + ']' + Name + '[/url]';
	}


	if (which == "email")
	{
		var Email = prompt(email_text,"");

		if ( ! Email || Email == null)
			return;

		var Title = prompt(email_title, theSelection);

		if (Title == null)
			return;

		if (Title == "")
			Title = Email;

		var Link = '[' + 'email=' + Email + ']' + Title + '[' + '/email]';
	}

	if ((navigator.appName == "Microsoft Internet Explorer") &&
		(navigator.appVersion.indexOf("Win") != -1))
	{
		if (theSelection != "")
		{
			document.selection.createRange().text = Link;
		}
		else
		{
			eval("document.getElementById('submit_post')." + selField + ".value += Link");
		}
	}
	else if (theField.selectionEnd && (theField.selectionEnd - theField.selectionStart > 0))
	{
		theField.value = s1 + Link + s3;
	}
	else
	{
		eval("document.getElementById('submit_post')." + selField + ".value += Link");
	}

	curField = eval("document.getElementById('submit_post')." + selField);
	curField.blur();
	curField.focus();
}

// Close all tags

function closeall()
{
	if (tagarray[0])
	{
		while (tagarray[0])
		{
			closeTag = arraypop(tagarray);

			eval("document.getElementById('submit_post')." + selField + ".value += closeTag");
		}
	}

	clear_state();
	curField = eval("document.getElementById('submit_post')." + selField);
	curField.focus();
}


//]]>
</script>

<table border='0'  cellspacing='0' cellpadding='0'>
<tr>
<?php

$i = 0;

        foreach ($this->buttons as $val) {
            $style = ($i == 0) ? 'htmlButtonOuterL' : 'htmlButtonOuter'; ?><td class='<?php echo $style; ?>'><div class='htmlButtonInner'><div class='htmlButtonOff' id='button_<?php echo $i; ?>'><a href='javascript:nullo();' title="<?php echo $val['3']; ?>" name='button_<?php echo $i; ?>' onclick='taginsert(this, "<?php echo htmlspecialchars($val['1']); ?>", "<?php echo htmlspecialchars($val['2']); ?>")' ><?php echo htmlspecialchars($val['0']); ?></a></div></div></td><?php echo "\n";

            $i++;
        } ?>
<td class='htmlButtonOuter'><div class='htmlButtonInner'><div class='htmlButtonOff'><a href='javascript:promptTag("email");' >&nbsp;@&nbsp;</a></div></div></td>
<td class='htmlButtonOuter'><div class='htmlButtonInner'><div class='htmlButtonOff'><a href='javascript:promptTag("link");' >&lt;a&gt;</a></div></div></td>

<?php

/* -------------------------------------------
/*	Hidden Configuration Variables
/*	- remove_close_all_button => Remove the Close All button from the Publish/Edit page (y/n)
/*	  Useful because most browsers no longer need it and Admins might want it gone
/* -------------------------------------------*/
if (ee()->config->item('remove_close_all_button') !== 'y') {
    ?>

<td class='htmlButtonOuter'><div class='htmlButtonInner'><div class='htmlButtonOff'><a href='javascript:closeall();' >{lang:close_tags}</a></div></div></td>

<?php
} ?>

</tr>
</table>

<?php
    $out = ob_get_contents();

        ob_end_clean();

        return $out;
    }
}
// END CLASS

// EOF
