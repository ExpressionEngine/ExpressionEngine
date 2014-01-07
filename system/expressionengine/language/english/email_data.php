<?php

//---------------------------------------------------
//	Admin Notification of Mailinglist subscription
//--------------------------------------------------

if ( ! function_exists('admin_notify_mailinglist_title'))
{
	function admin_notify_mailinglist_title()
	{
return <<<EOF
Someone has subscribed to your mailing list
EOF;
	}
}

if ( ! function_exists('admin_notify_mailinglist'))
{
	function admin_notify_mailinglist()
	{
return <<<EOF
A new mailing list subscription has been accepted.

Email Address: {email}
Mailing List: {mailing_list}
EOF;
	}
}



//---------------------------------------------------
//	Admin Notification of New Entry
//--------------------------------------------------

if ( ! function_exists('admin_notify_entry_title'))
{
	function admin_notify_entry_title()
	{
return <<<EOF
A new channel entry has been posted
EOF;
	}
}

if ( ! function_exists('admin_notify_entry'))
{
	function admin_notify_entry()
	{
return <<<EOF
A new entry has been posted in the following channel:
{channel_name}

The title of the entry is:
{entry_title}

Posted by: {name}
Email: {email}

To read the entry please visit:
{entry_url}

EOF;
	}
}



//---------------------------------------------------
//	Admin Notification of New Member Registrations
//--------------------------------------------------

if ( ! function_exists('admin_notify_reg_title'))
{
	function admin_notify_reg_title()
	{
return <<<EOF
Notification of new member registration
EOF;
	}
}

if ( ! function_exists('admin_notify_reg'))
{
	function admin_notify_reg()
	{
return <<<EOF
New member registration site: {site_name}

Screen name: {name}
User name: {username}
Email: {email}

Your control panel URL: {control_panel_url}
EOF;
	}
}



//---------------------------------------------------
//	Admin Notification of New Comment
//--------------------------------------------------

if ( ! function_exists('admin_notify_comment_title'))
{
	function admin_notify_comment_title()
	{
return <<<EOF
You have just received a comment
EOF;
	}
}

if ( ! function_exists('admin_notify_comment'))
{
	function admin_notify_comment()
	{
return <<<EOF
You have just received a comment for the following channel:
{channel_name}

The title of the entry is:
{entry_title}

Located at:
{comment_url}

Posted by: {name}
Email: {email}
URL: {url}
Location: {location}

{comment}
EOF;
	}
}



//---------------------------------------------------
//	Membership Activation Instructions
//--------------------------------------------------

if ( ! function_exists('mbr_activation_instructions_title'))
{
	function mbr_activation_instructions_title()
	{
return <<<EOF
Enclosed is your activation code
EOF;
	}
}

if ( ! function_exists('mbr_activation_instructions'))
{
	function mbr_activation_instructions()
	{
return <<<EOF
Thank you for your new member registration.

To activate your new account, please visit the following URL:

{unwrap}{activation_url}{/unwrap}

Thank You!

{site_name}

{site_url}
EOF;
	}
}



//---------------------------------------------------
//	Member Forgotten Password Instructions
//--------------------------------------------------

if ( ! function_exists('forgot_password_instructions_title'))
{
	function forgot_password_instructions_title()
	{
return <<<EOF
Login information
EOF;
	}
}

if ( ! function_exists('forgot_password_instructions'))
{
	function forgot_password_instructions()
	{
return <<<EOF
{name},

To reset your password, please go to the following page:

{reset_url}

Then log in with your username: {username}

If you do not wish to reset your password, ignore this message. It will expire in 24 hours.

{site_name}
{site_url}
EOF;
	}
}


//---------------------------------------------------
//	Validated Member Notification
//--------------------------------------------------

if ( ! function_exists('validated_member_notify_title'))
{
	function validated_member_notify_title()
	{
return <<<EOF
Your membership account has been activated
EOF;
	}
}

if ( ! function_exists('validated_member_notify'))
{
	function validated_member_notify()
	{
return <<<EOF
{name},

Your membership account has been activated and is ready for use.

Thank You!

{site_name}
{site_url}
EOF;
	}
}



//---------------------------------------------------
//	Decline Member Validation
//--------------------------------------------------

if ( ! function_exists('decline_member_validation_title'))
{
	function decline_member_validation_title()
	{
return <<<EOF
Your membership account has been declined
EOF;
	}
}

if ( ! function_exists('decline_member_validation'))
{
	function decline_member_validation()
	{
return <<<EOF
{name},

We're sorry but our staff has decided not to validate your membership.

{site_name}
{site_url}
EOF;
	}
}



//---------------------------------------------------
//	Mailinglist Activation Instructions
//--------------------------------------------------

if ( ! function_exists('mailinglist_activation_instructions_title'))
{
	function mailinglist_activation_instructions_title()
	{
return <<<EOF
Email Confirmation
EOF;
	}
}

if ( ! function_exists('mailinglist_activation_instructions'))
{
	function mailinglist_activation_instructions()
	{
return <<<EOF
Thank you for joining the "{mailing_list}" mailing list!

Please click the link below to confirm your email.

If you do not want to be added to our list, ignore this email.

{unwrap}{activation_url}{/unwrap}

Thank You!

{site_name}
EOF;
	}
}




//---------------------------------------------------
//	Comment Notification
//--------------------------------------------------

if ( ! function_exists('comment_notification_title'))
{
	function comment_notification_title()
	{
return <<<EOF
Someone just responded to your comment
EOF;
	}
}

if ( ! function_exists('comment_notification'))
{
	function comment_notification()
	{
return <<<EOF
{name_of_commenter} just responded to the entry you subscribed to at:
{channel_name}

The title of the entry is:
{entry_title}

You can see the comment at the following URL:
{comment_url}

{comment}

To stop receiving notifications for this comment, click here:
{notification_removal_url}
EOF;
	}
}

//---------------------------------------------------
//	Comments Opened Notification
//--------------------------------------------------

if ( ! function_exists('comments_opened_notification_title'))
{
	function comments_opened_notification_title()
	{
return <<<EOF
New comments have been added
EOF;
	}
}

if ( ! function_exists('comments_opened_notification'))
{
	function comments_opened_notification()
	{
return <<<EOF
Responses have been added to the entry you subscribed to at:
{channel_name}

The title of the entry is:
{entry_title}

You can see the comments at the following URL:
{comment_url}

{comments}
{comment}
{/comments}

To stop receiving notifications for this entry, click here:
{notification_removal_url}
EOF;
	}
}

//---------------------------------------------------
//	Admin Notification of New Forum Post
//--------------------------------------------------

if ( ! function_exists('admin_notify_forum_post_title'))
{
	function admin_notify_forum_post_title()
	{
return <<<EOF
Someone just posted in {forum_name}
EOF;
	}
}

if ( ! function_exists('admin_notify_forum_post'))
{
	function admin_notify_forum_post()
	{
return <<<EOF
{name_of_poster} just submitted a new post in {forum_name}

The title of the thread is:
{title}

The post can be found at:
{post_url}

{body}
EOF;
	}
}



//---------------------------------------------------
//	Forum Post User Notification
//--------------------------------------------------

if ( ! function_exists('forum_post_notification_title'))
{
	function forum_post_notification_title()
	{
return <<<EOF
Someone just posted in {forum_name}
EOF;
	}
}

if ( ! function_exists('forum_post_notification'))
{
	function forum_post_notification()
	{
return <<<EOF
Someone just posted in a thread you subscribed to at:
{forum_name}

The title of the thread is:
{title}

The post can be found at:
{post_url}

{body}

To stop receiving notifications for this comment, click here:
{notification_removal_url}
EOF;
	}
}



//---------------------------------------------------
//	Private Message Notification
//--------------------------------------------------

if ( ! function_exists('private_message_notification_title'))
{
	function private_message_notification_title()
	{
return <<<EOF
Someone has sent you a Private Message
EOF;
	}
}

if ( ! function_exists('private_message_notification'))
{
	function private_message_notification()
	{
return <<<EOF

{recipient_name},

{sender_name} has just sent you a Private Message titled ‘{message_subject}’.

You can see the Private Message by logging in and viewing your inbox at:
{site_url}

Content:

{message_content}

To stop receiving notifications of Private Messages, turn the option off in your Email Settings.

{site_name}
{site_url}
EOF;
	}
}



/* -------------------------------------
/*  Notification of Full PM Inbox
/* -------------------------------------*/
if ( ! function_exists('pm_inbox_full_title'))
{
	function pm_inbox_full_title()
	{
return <<<EOF
Your private message mailbox is full
EOF;
	}
}

if ( ! function_exists('pm_inbox_full'))
{
	function pm_inbox_full()
	{
return <<<EOF
{recipient_name},

{sender_name} has just attempted to send you a Private Message,
but your inbox is full, exceeding the maximum of {pm_storage_limit}.

Please log in and remove unwanted messages from your inbox at:
{site_url}
EOF;
	}
}



/* -------------------------------------
/*  Notification of Forum Topic Moderation
/* -------------------------------------*/
if ( ! function_exists('forum_moderation_notification_title'))
{
	function forum_moderation_notification_title()
	{
return <<<EOF
Moderation notification in {forum_name}
EOF;
	}
}

if ( ! function_exists('forum_moderation_notification'))
{
	function forum_moderation_notification()
	{
return <<<EOF
{name_of_recipient}, a moderator has {moderation_action} your thread.

The title of the thread is:
{title}

The thread can be found at:
{thread_url}
EOF;
	}
}



/* -------------------------------------
/*  Notification of Forum Post Report
/* -------------------------------------*/
if ( ! function_exists('forum_report_notification_title'))
{
	function forum_report_notification_title()
	{
return <<<EOF
Post reported in {forum_name}
EOF;
	}
}

if ( ! function_exists('forum_report_notification'))
{
	function forum_report_notification()
	{
return <<<EOF
{reporter_name} just reported a post written by {author} in:
{forum_name}

The reason(s) for the report:
{reasons}

Additional notes from {reporter_name}:
{notes}

The post can be found at:
{post_url}

Contents of reported post:
{body}
EOF;
	}
}



/* -------------------------------------
//  OFFLINE SYSTEM PAGE
/* -------------------------------------*/
if ( ! function_exists('offline_template'))
{
	function offline_template()
	{
return <<<EOF
<html>
<head>

<title>System Offline</title>

<style type="text/css">

body {
background-color:	#ffffff;
margin:				50px;
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-size:			11px;
color:				#000;
background-color:	#fff;
}

a {
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-weight:		bold;
letter-spacing:		.09em;
text-decoration:	none;
color:			  #330099;
background-color:	transparent;
}

a:visited {
color:				#330099;
background-color:	transparent;
}

a:hover {
color:				#000;
text-decoration:	underline;
background-color:	transparent;
}

#content  {
border:				#999999 1px solid;
padding:			22px 25px 14px 25px;
}

h1 {
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-weight:		bold;
font-size:			14px;
color:				#000;
margin-top: 		0;
margin-bottom:		14px;
}

p {
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-size: 			12px;
font-weight: 		normal;
margin-top: 		12px;
margin-bottom: 		14px;
color: 				#000;
}
</style>

</head>

<body>

<div id="content">

<h1>System Offline</h1>

<p>This site is currently offline</p>

</div>

</body>

</html>
EOF;
	}
}



/* -------------------------------------
//  User Messages Template
/* -------------------------------------*/
if ( ! function_exists('message_template'))
{
	function message_template()
	{
return <<<EOF
<html>
<head>

<title>{title}</title>

<meta http-equiv='content-type' content='text/html; charset={charset}' />

{meta_refresh}

<style type="text/css">

body {
background-color:	#ffffff;
margin:				50px;
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-size:			11px;
color:				#000;
background-color:	#fff;
}

a {
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
letter-spacing:		.09em;
text-decoration:	none;
color:			  #330099;
background-color:	transparent;
}

a:visited {
color:				#330099;
background-color:	transparent;
}

a:active {
color:				#ccc;
background-color:	transparent;
}

a:hover {
color:				#000;
text-decoration:	underline;
background-color:	transparent;
}

#content  {
border:				#000 1px solid;
background-color: 	#DEDFE3;
padding:			22px 25px 14px 25px;
}

h1 {
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-weight:		bold;
font-size:			14px;
color:				#000;
margin-top: 		0;
margin-bottom:		14px;
}

p {
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-size: 			12px;
font-weight: 		normal;
margin-top: 		12px;
margin-bottom: 		14px;
color: 				#000;
}

ul {
margin-bottom: 		16px;
}

li {
list-style:			square;
font-family:		Verdana, Arial, Tahoma, Trebuchet MS, Sans-serif;
font-size: 			12px;
font-weight: 		normal;
margin-top: 		8px;
margin-bottom: 		8px;
color: 				#000;
}

</style>

</head>

<body>

<div id="content">

<h1>{heading}</h1>

{content}

<p>{link}</p>

</div>

</body>

</html>
EOF;
	}
}



/* -------------------------------------
//  Mailing List Template
/* -------------------------------------*/
if ( ! function_exists('mailinglist_template'))
{
	function mailinglist_template()
	{
return <<<EOF
{message_text}

To remove your email from this mailing list, click here:
{if html_email}<a href="{unsubscribe_url}">{unsubscribe_url}</a>{/if}
{if plain_email}{unsubscribe_url}{/if}
EOF;
	}
}




/* End of file email_data.php */
/* Location: ./system/expressionengine/language/english/email_data.php */