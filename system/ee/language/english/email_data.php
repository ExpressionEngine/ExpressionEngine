<?php

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

if ( ! function_exists('password_changed_notification_title'))
{
	function password_changed_notification_title()
	{
return <<<EOF
Password changed
EOF;
	}
}

if ( ! function_exists('password_changed_notification'))
{
	function password_changed_notification()
	{
return <<<EOF
{name},

Your password was just changed.

If you didn't make this change yourself, please contact an administrator right away.

{site_name}
{site_url}
EOF;
	}
}

if ( ! function_exists('email_changed_notification_title'))
{
	function email_changed_notification_title()
	{
return <<<EOF
Email address changed
EOF;
	}
}

if ( ! function_exists('email_changed_notification'))
{
	function email_changed_notification()
	{
return <<<EOF
{name},

Your email address has been changed, and this email address is no longer associated with your account.

If you didn't make this change yourself, please contact an administrator right away.

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
<!doctype html>
<html dir="ltr">
    <head>
        <title>System Offline</title>
        <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"  name="viewport">

        <style type="text/css">
:root, body {
    --ee-panel-bg: #fff;
    --ee-panel-border: #dfe0ef;
    --ee-text-normal: #0d0d19;
    --ee-main-bg: #f7f7fb;
    --ee-link: #5D63F1;
    --ee-link-hover: #171feb;
}

*, :after, :before {
    box-sizing: inherit;
}

html {
    box-sizing: border-box;
    font-size: 15px;
    height: 100%;
    line-height: 1.15;
}

body {
    font-family: Roboto,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Ubuntu,"Helvetica Neue",Oxygen,Cantarell,sans-serif;
    height: 100%;
    font-size: 1rem;
    line-height: 1.6;
    color: var(--ee-text-normal);
    background: var(--ee-main-bg);
    -webkit-font-smoothing: antialiased;
    margin: 0;
}

.panel {
    margin-bottom: 20px;
    background-color: var(--ee-panel-bg);
    border: 1px solid var(--ee-panel-border);
    border-radius: 6px;
}
.redirect {
	max-width: 700px;
	min-width: 350px;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%,-50%);
}

.panel-heading {
    padding: 20px 25px;
    position: relative;
}

.panel-body {
    padding: 20px 25px;
}

.panel-body:after, .panel-body:before {
    content: " ";
    display: table;
}

.redirect p {
    margin-bottom: 20px;
}
p {
    line-height: 1.6;
}
a, blockquote, code, h1, h2, h3, h4, h5, h6, ol, p, pre, ul {
    color: inherit;
    margin: 0;
    padding: 0;
    font-weight: inherit;
}

a {
    color: var(--ee-link);
    text-decoration: none;
    -webkit-transition: color .15s ease-in-out;
    -moz-transition: color .15s ease-in-out;
    -o-transition: color .15s ease-in-out;
}

a:hover {
    color: var(--ee-link-hover);
}

h3 {
    font-size: 1.35em;
    font-weight: 500;
}

ol, ul {
    padding-left: 0;
}

ol li, ul li {
    list-style-position: inside;
}

.panel-footer {
    padding: 20px 25px;
    position: relative;
}


        </style>
    </head>
    <body>
        <section class="flex-wrap">
            <section class="wrap">
                <div class="panel redirect">
                    <div class="panel-heading">
                        <h3>System Offline</h3>
                    </div>
					<div class="panel-body">
					This site is currently offline
                    </div>
                </div>
            </section>
        </section>
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
<!doctype html>
<html dir="ltr">
    <head>
        <title>{title}</title>
        <meta http-equiv="content-type" content="text/html; charset={charset}">
        <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"  name="viewport">
        <meta name="referrer" content="no-referrer">
        {meta_refresh}
        <style type="text/css">
:root, body {
    --ee-panel-bg: #fff;
    --ee-panel-border: #dfe0ef;
    --ee-text-normal: #0d0d19;
    --ee-main-bg: #f7f7fb;
    --ee-link: #5D63F1;
    --ee-link-hover: #171feb;
}

*, :after, :before {
    box-sizing: inherit;
}

html {
    box-sizing: border-box;
    font-size: 15px;
    height: 100%;
    line-height: 1.15;
}

body {
    font-family: Roboto,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Ubuntu,"Helvetica Neue",Oxygen,Cantarell,sans-serif;
    height: 100%;
    font-size: 1rem;
    line-height: 1.6;
    color: var(--ee-text-normal);
    background: var(--ee-main-bg);
    -webkit-font-smoothing: antialiased;
    margin: 0;
}

.panel {
    margin-bottom: 20px;
    background-color: var(--ee-panel-bg);
    border: 1px solid var(--ee-panel-border);
    border-radius: 6px;
}
.redirect {
	max-width: 700px;
	min-width: 350px;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%,-50%);
}

.panel-heading {
    padding: 20px 25px;
    position: relative;
}

.panel-body {
    padding: 20px 25px;
}

.panel-body:after, .panel-body:before {
    content: " ";
    display: table;
}

.redirect p {
    margin-bottom: 20px;
}
p {
    line-height: 1.6;
}
a, blockquote, code, h1, h2, h3, h4, h5, h6, ol, p, pre, ul {
    color: inherit;
    margin: 0;
    padding: 0;
    font-weight: inherit;
}

a {
    color: var(--ee-link);
    text-decoration: none;
    -webkit-transition: color .15s ease-in-out;
    -moz-transition: color .15s ease-in-out;
    -o-transition: color .15s ease-in-out;
}

a:hover {
    color: var(--ee-link-hover);
}

h3 {
    font-size: 1.35em;
    font-weight: 500;
}

ol, ul {
    padding-left: 0;
}

ol li, ul li {
    list-style-position: inside;
}

.panel-footer {
    padding: 20px 25px;
    position: relative;
}


        </style>
    </head>
    <body>
        <section class="flex-wrap">
            <section class="wrap">
                <div class="panel redirect">
                    <div class="panel-heading">
                        <h3>{heading}</h3>
                    </div>
                    <div class="panel-body">
                        {content}


                    </div>
                    <div class="panel-footer">
                        {link}
                    </div>
                </div>
            </section>
        </section>
    </body>
</html>
EOF;
	}
}

// EOF
