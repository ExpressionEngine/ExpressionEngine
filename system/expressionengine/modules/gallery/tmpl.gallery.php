<?php

/*
=====================================================
 ExpressionEngine - by EllisLab
-----------------------------------------------------
 http://expressionengine.com/
-----------------------------------------------------
 Copyright (c) 2003 - 2010, EllisLab, Inc.
=====================================================
 THIS IS COPYRIGHTED SOFTWARE
 PLEASE READ THE LICENSE AGREEMENT
 http://expressionengine.com/docs/license.html
=====================================================
 File: Gallery Theme 1
-----------------------------------------------------
 Purpose: Photo Gallery Module Theme
=====================================================
*/

if ( ! defined('EXT'))
{
	exit('Invalid file request');
}


ob_start();
?>
{preload_replace:gallery_name="{TMPL_gallery_name}"}

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title> {exp:gallery:category_name}{category}{/exp:gallery:category_name}</title>
<meta http-equiv="Content-Type" content="text/html; charset={charset}" />

<link rel='stylesheet' type='text/css' media='all' href='{stylesheet={TMPL_template_group_name}/gallery_css}' /> 
<style type='text/css' media='screen'>@import "{stylesheet={TMPL_template_group_name}/gallery_css}";</style>
</head>

<body>

<div id="content">


<table cellpadding='0' cellspacing='0' border='0' width='98%'>
<tr>
<td>

<div class="breadcrumb">
<a href="{path={TMPL_template_group_name}/index}">Gallery Home</a> &nbsp;<b>&#8250;</b>&nbsp; {exp:gallery:category_name}{category}{/exp:gallery:category_name}
</div>

</td>
<td align="right">
	
<form>
<select name="URL" onchange="window.location=this.options[this.selectedIndex].value">
<option value=" ">Category Jump Navigation</option>	

{exp:gallery:category_list gallery="{gallery_name}"}
<option value="{category_path={TMPL_template_group_name}/category} ">{category_name}</option>
{/exp:gallery:category_list}

</select>		
</form>

</td>
</tr>
</table>


{exp:gallery:entries gallery="{gallery_name}"  orderby="date" sort="desc" columns="4" rows="3"}

<table class="tableBorder" cellpadding="6" cellspacing="1" border="0" width="100%">
<tr>
<th colspan="4">{category}</th>
</tr>

{entries}

{row_start}<tr>{/row_start}

{row}
<td class="thumbs">
<a href="{id_path={TMPL_template_group_name}/image_full}"><img src="{thumb_url}"  class="border" width="{thumb_width}" height="{thumb_height}" border="0" title="{title}" /></a>
<div class="title">{title}</div>
</td>
{/row}

{row_blank}<td class="thumbs">&nbsp;</td>{/row_blank}

{row_end}</tr>{/row_end}

{/entries}

</table>

{paginate}
<div class="paginate">
<span class="pagecount">Page {current_page} of {total_pages} pages</span>  {pagination_links}
</div>
{/paginate}

{/exp:gallery:entries}

</div>

<div class="powered"><a href="http://expressionengine.com/">Powered by ExpressionEngine</a></div>

</body>
</html>
<?php
$template['category'] = ob_get_contents();
ob_end_clean(); 



ob_start();
?>
{preload_replace:gallery_name="{TMPL_gallery_name}"}

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>Comments</title>
<meta http-equiv="Content-Type" content="text/html; charset={charset}" />

<link rel='stylesheet' type='text/css' media='all' href='{stylesheet={TMPL_template_group_name}/gallery_css}' /> 
<style type='text/css' media='screen'>@import "{stylesheet={TMPL_template_group_name}/gallery_css}";</style>
</head>

<body>

<div id="narrowcontent">


<ol>

{exp:gallery:comments}

<li>{comment}
<div class="posted">Posted by {url_or_email_as_author}  &nbsp;on&nbsp; {comment_date format='%m/%d'} &nbsp;at&nbsp; {comment_date format='%h:%i %A'}</div>
</li>

{/exp:gallery:comments}

</ol>


<div class="spacer">&nbsp;</div>


{exp:gallery:comment_form preview="{TMPL_template_group_name}/comment_preview"}

{if logged_out}

<p>
Name:<br />
<input type="text" name="name" value="{name}" size="50" style="width:100%;" />
</p>

<p>
Email:<br />
<input type="text" name="email" value="{email}" size="50" style="width:100%;" />
</p>

<p>
Location:<br />
<input type="text" name="location" value="{location}" size="50" style="width:100%;" />
</p>

<p>
URL:<br />
<input type="text" name="url" value="{url}" size="50" style="width:100%;" />
</p>

{/if}

<p>
<a href="{path={TMPL_template_group_name}/smileys}" onclick="window.open(this.href, '_blank', 'width=400,height=440');return false;" onkeypress="this.onclick()">Smileys</a>
</p>

<p>
<textarea name="comment" cols="50" rows="12" style="width:100%;" >{comment}</textarea>
</p>

{if logged_out}
<p><input type="checkbox" name="save_info" value="yes" {save_info} /> Remember my personal information</p>
{/if}

<p><input type="checkbox" name="notify_me" value="yes" {notify_me} /> Notify me of follow-up comments?</p>

{if captcha}
<p>Submit the word you see below:</p>
<p>
{captcha}
<br />
<input type="text" name="captcha" value="{captcha_word}" size="20" maxlength="20" style="width:140px;" />
</p>
{/if}

<input type="submit" name="submit" value="Submit" />
<input type="submit" name="preview" value="Preview" />

{/exp:gallery:comment_form}


<div class="spacer">&nbsp;</div>

<div class="windowclose"><a href="#" onclick="window.close();">Close Window</a></div>


<div class="powered"><a href="http://expressionengine.com/">Powered by ExpressionEngine</a></div>

</div>

</body>
</html>
<?php
$template['comments'] = ob_get_contents();
ob_end_clean(); 



ob_start();
?>
{preload_replace:gallery_name="{TMPL_gallery_name}"}

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>Comments</title>
<meta http-equiv="Content-Type" content="text/html; charset={charset}" />

<link rel='stylesheet' type='text/css' media='all' href='{stylesheet={TMPL_template_group_name}/gallery_css}' /> 
<style type='text/css' media='screen'>@import "{stylesheet={TMPL_template_group_name}/gallery_css}";</style>
</head>

<body>

<div id="narrowcontent">



{exp:gallery:comment_preview}
{comment}
{/exp:gallery:comment_preview}


<div class="spacer">&nbsp;</div>

{exp:gallery:comment_form preview="{TMPL_template_group_name}/comment_preview"}

{if logged_out}

<p>
Name:<br />
<input type="text" name="name" value="{name}" size="50" style="width:100%;" />
</p>

<p>
Email:<br />
<input type="text" name="email" value="{email}" size="50" style="width:100%;" />
</p>

<p>
Location:<br />
<input type="text" name="location" value="{location}" size="50" style="width:100%;" />
</p>

<p>
URL:<br />
<input type="text" name="url" value="{url}" size="50" style="width:100%;" />
</p>

{/if}

<p>
<a href="{path={TMPL_template_group_name}/smileys}" onclick="window.open(this.href, '_blank', 'width=400,height=440');return false;" onkeypress="this.onclick()">Smileys</a>
</p>


<p>
<textarea name="comment" cols="50" rows="12" style="width:100%;" >{comment}</textarea>
</p>

{if logged_out}
<p><input type="checkbox" name="save_info" value="yes" {save_info} /> Remember my personal information</p>
{/if}

<p><input type="checkbox" name="notify_me" value="yes" {notify_me} /> Notify me of follow-up comments?</p>

{if captcha}
<p>Submit the word you see below:</p>
<p>
{captcha}
<br />
<input type="text" name="captcha" value="{captcha_word}" size="20" maxlength="20" style="width:140px;" />
</p>
{/if}

<input type="submit" name="submit" value="Submit" />
<input type="submit" name="preview" value="Preview" />

{/exp:gallery:comment_form}




<div class="spacer">&nbsp;</div>


<div class="powered"><a href="http://expressionengine.com/">Powered by ExpressionEngine</a></div>

</div>

</body>
</html>
<?php
$template['comment_preview'] = ob_get_contents();
ob_end_clean(); 



ob_start();
?>
body {
 margin:			0;
 padding:			30px;
 font-family:		Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:		 11px;
 color:			 #000;
 background-color:  #CECED1;
}

#content {
 left:			  0px;
 right:			 10px;
 background-color: 	#fff;
border: 1px solid 	#333;
 margin:			0 15px 0 15px;
 padding:			12px 15px 30px 15px;
 width:			 auto;
}
* html #content {
 width:			 100%;
 width:				auto;
}

#narrowcontent {
 left:			  0px;
 right:			 10px;
 background-color: 	#fff;
 border: 1px solid 	#333;
 margin:			0 10px 0 10px;
 padding:			10px 12px 12px 12px;
 width:			 auto;
}
* html #narrowcontent {
 width:			 100%;
 width:				auto;
}

a {
 text-decoration:	none;
 color:			 #330099;
 background-color:  transparent;
}
  
a:visited {
 color:			 #330099;
 background-color:  transparent;
}

a:hover {
 color:			 #000;
 text-decoration:	underline;
 background-color:  transparent;
}

.breadcrumb {
 margin:			15px 0 15px 6px;
 font-family:		Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:		 11px;
 background-color:  transparent;
}

.paginate {
 margin:			10px 0 10px 6px;
 font-family:		Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:		 11px;
 background-color:  transparent;
}

.windowclose {
 margin:			15px 0 10px 0;
 font-family:		Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:		 10px;
 background-color:  transparent;
 text-align: 		center;
}

.spacer {
 font-size:	10px;
 margin:		5px 0 5px 0;
}

img {
 margin:		0;
 padding:	0;
 border:		0;
}

.border {
 border:	1px solid #000;
}

.paddedborder {
 padding: 	20px;
 border: 	1px solid #000;
}

.thumbs {
 text-align:	center;
 padding:	15px 3px 8px 3px;
 background-color: #EAEBEE;
}

.title {
 margin:			3px 0 0 0;
 font-family:		Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:		 11px;
 font-weight: 		bold;
 color:			 #000;
}

.caption {
 margin:  			12px 0 4px 0;
 background-color:	transparent;
 font-family:		Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:		 11px;
 color: #000;
}

.commentlink {
 margin:			4px 0 8px 0;
}


li {
 margin:			0 0 15px 0;
 font-family:		Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:		 11px;
 color: 				#333;
 background-color:  transparent;
 text-align: 		left;
 padding-bottom: 	5px;
 border-bottom: 		1px dashed #ccc;
}

.stats {
 margin:			4px 0 4px 0;
 font-family:		Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:		 10px;
 font-weight: 		normal;
 color:			 #fff;
}

.imageBG {
 text-align: center;
 background-color:  #8185A9;  
}

.categories {
 padding:			5px 10px 5px 12px;
 background-color:  #E9E9F2;  
}

.tableBorder {
 border: 1px solid #73737E;
}

th {
 font-family:		Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:		 12px;
 color:			 #fff;
 font-weight:		bold;
 text-align:			left;
 padding:			5px 4px 5px 12px;
 background-color:  #7378A7;  
}

td {
 font-family:		Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:		 11px;
 color:			 #000;
}

.powered {
 margin:			15px;
 font-family:		Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:		 10px;
 background-color:  transparent;
 text-align:			center;
}
<?php
$template['gallery_css'] = ob_get_contents();
ob_end_clean(); 




ob_start();
?>
{preload_replace:gallery_name="{TMPL_gallery_name}"}

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>{exp:gallery:entries gallery="{gallery_name}" limit="1" log_views="off"}{title}{/exp:gallery:entries}</title>
<meta http-equiv="Content-Type" content="text/html; charset={charset}" />

<link rel='stylesheet' type='text/css' media='all' href='{stylesheet={TMPL_template_group_name}/gallery_css}' /> 
<style type='text/css' media='screen'>@import "{stylesheet={TMPL_template_group_name}/gallery_css}";</style>
</head>

<body>

<div id="content">


<table cellpadding='0' cellspacing='0' border='0' width='98%'>
<tr>
<td>

<div class="breadcrumb">
<a href="{path={TMPL_template_group_name}/index}">Gallery Home</a> &nbsp;<b>&#8250;</b>&nbsp;
{exp:gallery:entries gallery="{gallery_name}" limit="1" log_views="off"} <a href="{category_path={TMPL_template_group_name}/category}">{category}</a>  &nbsp;<b>&#8250;</b>&nbsp; {title}{/exp:gallery:entries}
</div>

</td>
<td align="right">

{exp:gallery:prev_entry gallery="{gallery_name}"}<a href="{path={TMPL_template_group_name}/image_full}"><b>&#8249;</b> Previous Image</a>&nbsp;&nbsp;{/exp:gallery:prev_entry}
{exp:gallery:next_entry gallery="{gallery_name}"}&nbsp;&nbsp;<a href="{path={TMPL_template_group_name}/image_full}">Next Image</a> <b>&#8250;</b>{/exp:gallery:next_entry}

</td>
</tr>
</table>


{exp:gallery:entries gallery="{gallery_name}" }

<div class="imageBG">
<div class="paddedborder">

<img src="{image_url}"  class="border" width="{width}" height="{height}" border="0" title="{title}" />
	
<div class="title">
{title}
</div>

{if allow_comments}
<div class="commentlink">
<a href="{id_path={TMPL_template_group_name}/comments}" onclick="window.open(this.href, '_blank', 'width=600,height=540,location=no,status=yes,menubar=no,resizable=yes,scrollbars=yes');return false;" >{if total_comments == 0}No comments have been submitted yet{/if}{if total_comments == 1}1 person has commented{/if}{if total_comments	> 1}{total_comments} people have commented{/if}</a>
</div>
{/if}

<div class="stats">
This image has been viewed {views} {if views == 1}time{/if}{if views != 1}times{/if}
</div>
		
</div>
</div>

<div class="caption">
{caption}
</div>


{/exp:gallery:entries}


<div class="spacer">&nbsp;</div>

<div class="powered"><a href="http://expressionengine.com/">Powered by ExpressionEngine</a></div>

</div>

</body>
</html>
<?php
$template['image_full'] = ob_get_contents();
ob_end_clean(); 




ob_start();
?>
{preload_replace:gallery_name="{TMPL_gallery_name}"}

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>{exp:gallery:entries gallery="{gallery_name}" limit="1" log_views="off"}{title}{/exp:gallery:entries}</title>
<meta http-equiv="Content-Type" content="text/html; charset={charset}" />

<link rel='stylesheet' type='text/css' media='all' href='{stylesheet={TMPL_template_group_name}/gallery_css}' /> 
<style type='text/css' media='screen'>@import "{stylesheet={TMPL_template_group_name}/gallery_css}";</style>
</head>

<body>


{exp:gallery:entries gallery="{gallery_name}"}

<table class="tableBorder" cellpadding="6" cellspacing="1" border="0" width="100%">
<tr>
<td class='imageBG'>

<img src="{medium_url}"  class="border" width="{medium_width}" height="{medium_height}" border="0" title="{title}" />

<div class="title">
{title}
</div>

{if allow_comments}
<div class="commentlink">
<a href="{id_path={TMPL_template_group_name}/comments}">{if total_comments == 0}No comments have been submitted yet{/if}{if total_comments == 1}1 person has commented{/if}{if total_comments	> 1}{total_comments} people have commented{/if}</a>
</div>
{/if}

<div class="stats">
This image has been viewed {views} {if views == 1}time{/if}{if views != 1}times{/if}
</div>

</td>
</tr>
</table>

{/exp:gallery:entries}


<div class="windowclose"><a href="#" onclick="window.close();">Close Window</a></div>

</body>
</html>
<?php
$template['image_med'] = ob_get_contents();
ob_end_clean(); 



ob_start();
?>
{preload_replace:gallery_name="{TMPL_gallery_name}"}

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>Photo Gallery</title>
<meta http-equiv="Content-Type" content="text/html; charset={charset}" />

<link rel='stylesheet' type='text/css' media='all' href='{stylesheet={TMPL_template_group_name}/gallery_css}' /> 
<style type='text/css' media='screen'>@import "{stylesheet={TMPL_template_group_name}/gallery_css}";</style>
</head>

<body>

<div id="content">

<div class="spacer">&nbsp;</div>


{exp:gallery:categories gallery="{gallery_name}"}

<table class="tableBorder" cellpadding="6" cellspacing="1" border="0" width="100%">
<th>Category</th>
<th>Description</th>
<th>Files</th>
<th>Views</th>
<th>Comments</th>
<th>Most Recent</th>

{category_row}

{row_start}<tr>{/row_start}
	
{row}
<td class="categories">{subcat_marker}<img src='{cp_image_dir}cat_marker.gif' border='0' title='' />&nbsp;{/subcat_marker}<strong><a href="{category_path={TMPL_template_group_name}/category}">{category}</a></strong></td>
<td class="categories">{category_description}</td>
<td class="categories">{total_files}</td>
<td class="categories">{total_views}</td>
<td class="categories">{total_comments}</td>
<td class="categories">{recent_entry_date format='%M %d, %Y %g:%i %A'}</td>
{/row}
	
{row_end}</tr>{/row_end}

{/category_row}

</table>

{/exp:gallery:categories}


<div class="spacer">&nbsp;</div>


{exp:gallery:entries gallery="{gallery_name}" orderby="entry_date" columns="4" rows="1"}

<table class="tableBorder" cellpadding="6" cellspacing="1" border="0" width="100%">
<tr>
<th colspan='4'>Most Recent Images</th>
</tr>

{entries}

{row_start}<tr>{/row_start}

{row}
<td class="thumbs">
<a href="{id_path={TMPL_template_group_name}/image_med}" onclick="window.open(this.href, '_blank', 'width=700,height=500,location=no,menubar=no,resizable=yes,scrollbars=yes');return false;" ><img src="{thumb_url}"  class="border" width="{thumb_width}" height="{thumb_height}" border="0" title="{title}" /></a>
<div class="title">{title}</div>
</td>
{/row}

{row_blank}<td class="thumbs">&nbsp;</td>{/row_blank}

{row_end}</tr>{/row_end}

{/entries}

</table>

{/exp:gallery:entries}


<div class="spacer">&nbsp;</div>


{exp:gallery:entries gallery="{gallery_name}"  orderby="random" columns="4" rows="1"}

<table class="tableBorder" cellpadding="6" cellspacing="1" border="0" width="100%">
<tr>
<th colspan="4">Random Images</th>
</tr>

{entries}
	
{row_start}<tr>{/row_start}
		
{row}
<td class="thumbs">
<a href="{id_path={TMPL_template_group_name}/image_med}" onclick="window.open(this.href, '_blank', 'width=700,height=500,location=no,menubar=no,resizable=yes,scrollbars=yes');return false;" ><img src="{thumb_url}"  class="border" width="{thumb_width}" height="{thumb_height}" border="0" title="{title}" /></a>
<div class="title">{title}</div>
</td>
{/row}

{row_blank}<td  class="thumbs">&nbsp;</td>{/row_blank}

{row_end}</tr>{/row_end}

{/entries}

</table>

{/exp:gallery:entries}


</div>

<div class="powered"><a href="http://expressionengine.com/">Powered by ExpressionEngine</a></div>

</body>
</html>
<?php
$template['index'] = ob_get_contents();
ob_end_clean(); 



ob_start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" 
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{lang}" lang="{lang}"> 
<head>
<meta http-equiv="content-type" content="text/html; charset={charset}" />
<title>Smileys</title>

<style type="text/css">

body { 
 background-color: #ffffff; 
 margin-left: 40px; 
 margin-right: 40px; 
 margin-top: 30px; 
 font-size: 11px; 
 font-family: verdana,trebuchet,sans-serif; 
}
a:link { 
 color: #990000; 
 font-size: 11px; 
 font-weight: normal; 
 text-decoration: underline; 
}
a:visited { 
 color: #990000; 
 font-size: 11px; 
 font-weight: normal; 
 text-decoration: underline; 
}
a:active { 
 color: #990000; 
 font-size: 11px; 
 font-weight: normal; 
 text-decoration: underline; 
}
a:hover { 
 color: #990000; 
 font-size: 11px; 
 font-weight: normal; 
 text-decoration: none; 
}

</style>

<script type="text/javascript">
<!--
function add_smiley(smiley)
{
	var el = opener.document.getElementById('comment_form').comment;
	
	if ('selectionStart' in el) {
		newStart = el.selectionStart + smiley.length;

		el.value = el.value.substr(0, el.selectionStart) +
						smiley +
						el.value.substr(el.selectionEnd, el.value.length);
		el.setSelectionRange(newStart, newStart);
	}
	else if (opener.document.selection) {
		opener.document.selection.createRange().text = text;
	}
	else {
		el.value += " " + smiley + " ";
	}
	
	el.focus();
	window.close();
}

//-->
</script>

</head>
<body>

<p>Click on an image to add it to your comment</p>

<table border="0" width="100%" cellpadding="6" cellspacing="1">

{exp:emoticon columns="4"}
<tr>
<td><div>{smiley}</div></td>
</tr>
{/exp:emoticon}

</table>

</body>
</html>
<?php
$template['smileys'] = ob_get_contents();
ob_end_clean(); 



/* End of file tmpl.gallery.php */
/* Location: ./system/expressionengine/modules/gallery/tmpl.gallery.php */