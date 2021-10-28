<?php

//---------------------------------------------------
//	Admin Notification of New Entry
//--------------------------------------------------

if (! function_exists('admin_notify_entry_title')) {
    function admin_notify_entry_title()
    {
        return <<<EOF
A new channel entry has been posted
EOF;
    }
}

if (! function_exists('admin_notify_entry')) {
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

if (! function_exists('admin_notify_reg_title')) {
    function admin_notify_reg_title()
    {
        return <<<EOF
Notification of new member registration
EOF;
    }
}

if (! function_exists('admin_notify_reg')) {
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

if (! function_exists('admin_notify_comment_title')) {
    function admin_notify_comment_title()
    {
        return <<<EOF
You have just received a comment
EOF;
    }
}

if (! function_exists('admin_notify_comment')) {
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

if (! function_exists('mbr_activation_instructions_title')) {
    function mbr_activation_instructions_title()
    {
        return <<<EOF
Enclosed is your activation code
EOF;
    }
}

if (! function_exists('mbr_activation_instructions')) {
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

if (! function_exists('forgot_password_instructions_title')) {
    function forgot_password_instructions_title()
    {
        return <<<EOF
Login information
EOF;
    }
}

if (! function_exists('forgot_password_instructions')) {
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

if (! function_exists('password_changed_notification_title')) {
    function password_changed_notification_title()
    {
        return <<<EOF
Password changed
EOF;
    }
}

if (! function_exists('password_changed_notification')) {
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

if (! function_exists('email_changed_notification_title')) {
    function email_changed_notification_title()
    {
        return <<<EOF
Email address changed
EOF;
    }
}

if (! function_exists('email_changed_notification')) {
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

if (! function_exists('forgot_username_instructions_title')) {
    function forgot_username_instructions_title()
    {
        return <<<EOF
Username information
EOF;
    }
}

if (! function_exists('forgot_username_instructions')) {
    function forgot_username_instructions()
    {
        return <<<EOF
{name},

Your username is: {username}

If you didn't request your username yourself, please contact an administrator right away.

{site_name}
{site_url}
EOF;
    }
}

//---------------------------------------------------
//	Validated Member Notification
//--------------------------------------------------

if (! function_exists('validated_member_notify_title')) {
    function validated_member_notify_title()
    {
        return <<<EOF
Your membership account has been activated
EOF;
    }
}

if (! function_exists('validated_member_notify')) {
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

if (! function_exists('decline_member_validation_title')) {
    function decline_member_validation_title()
    {
        return <<<EOF
Your membership account has been declined
EOF;
    }
}

if (! function_exists('decline_member_validation')) {
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

if (! function_exists('comment_notification_title')) {
    function comment_notification_title()
    {
        return <<<EOF
Someone just responded to your comment
EOF;
    }
}

if (! function_exists('comment_notification')) {
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

if (! function_exists('comments_opened_notification_title')) {
    function comments_opened_notification_title()
    {
        return <<<EOF
New comments have been added
EOF;
    }
}

if (! function_exists('comments_opened_notification')) {
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

if (! function_exists('admin_notify_forum_post_title')) {
    function admin_notify_forum_post_title()
    {
        return <<<EOF
Someone just posted in {forum_name}
EOF;
    }
}

if (! function_exists('admin_notify_forum_post')) {
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

if (! function_exists('forum_post_notification_title')) {
    function forum_post_notification_title()
    {
        return <<<EOF
Someone just posted in {forum_name}
EOF;
    }
}

if (! function_exists('forum_post_notification')) {
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

if (! function_exists('private_message_notification_title')) {
    function private_message_notification_title()
    {
        return <<<EOF
Someone has sent you a Private Message
EOF;
    }
}

if (! function_exists('private_message_notification')) {
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
if (! function_exists('pm_inbox_full_title')) {
    function pm_inbox_full_title()
    {
        return <<<EOF
Your private message mailbox is full
EOF;
    }
}

if (! function_exists('pm_inbox_full')) {
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
if (! function_exists('forum_moderation_notification_title')) {
    function forum_moderation_notification_title()
    {
        return <<<EOF
Moderation notification in {forum_name}
EOF;
    }
}

if (! function_exists('forum_moderation_notification')) {
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
if (! function_exists('forum_report_notification_title')) {
    function forum_report_notification_title()
    {
        return <<<EOF
Post reported in {forum_name}
EOF;
    }
}

if (! function_exists('forum_report_notification')) {
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
if (! function_exists('offline_template')) {
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
if (! function_exists('message_template')) {
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

/* -------------------------------------
//  User Messages Template
/* -------------------------------------*/
if (! function_exists('post_install_message_template')) {
    function post_install_message_template()
    {
        return <<<EOF
<!doctype html>
<html>
	<head>
		<title>Welcome to ExpressionEngine!</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" lang="en-us" dir="ltr">
		<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"  name="viewport">
		<link href="{theme_folder_url}cp/css/common.min.css" rel="stylesheet">
			</head>
	<body class="installer-page">
		<section class="flex-wrap">
			<section class="wrap">
				<div class="login__logo">
  <svg width="281px" height="36px" viewBox="0 0 281 36" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
  <title>ExpressionEngine</title>
  <defs>
      <polygon id="path-1" points="0.3862 0.1747 18.6557 0.1747 18.6557 21.5 0.3862 21.5"></polygon>
      <polygon id="path-3" points="0.3926 0.17455 13.9915 0.17455 13.9915 15.43755 0.3926 15.43755"></polygon>
      <polygon id="path-5" points="0 0.06905 25.8202 0.06905 25.8202 31.6178513 0 31.6178513"></polygon>
      <polygon id="path-7" points="0.10635 0.204 25.9268587 0.204 25.9268587 31.7517 0.10635 31.7517"></polygon>
  </defs>
  <g id="logo" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
      <g id="expressionengine">
          <path d="M92.88015,27.0665 L89.28865,20.955 L94.66665,14.6405 L94.77265,13.9 L91.11315,13.9 L87.86765,17.95 C87.76015,18.0845 87.57265,18.353 87.30415,19.2645 C87.33065,18.353 87.14315,18.0845 87.08965,17.9775 L84.80915,13.9 L80.59815,13.9 L84.62115,20.8475 L78.21065,28.3045 L82.42165,28.3045 L86.04315,23.905 C86.23065,23.664 86.52565,23.154 86.66065,22.5915 C86.66065,23.154 86.79465,23.6905 86.92865,23.905 L89.42265,28.3045 L92.70265,28.3045 L92.88015,27.0665 Z" id="Fill-1"></path>
          <path d="M80.2395,11.9686 L70.9585,11.9686 L70.288,16.6091 L78.7645,16.6091 L77.4495,19.6141 L69.751,19.6141 C69.805,19.8011 69.805,20.0156 69.778,20.1231 L69.027,25.3011 L78.3345,25.3011 L77.9055,28.3046 L65.003,28.3046 L67.7925,8.9651 L80.6685,8.9651 L80.2395,11.9686 Z" id="Fill-3"></path>
          <path d="M102.3328,16.20735 C101.5283,16.20735 100.5628,16.34085 99.3558,17.11935 L98.1493,25.46185 C98.8998,25.83735 99.9723,25.99835 100.8848,25.99835 C103.0573,25.99835 104.2378,24.60235 104.7478,20.98085 C104.8548,20.28385 104.9083,19.69385 104.9083,19.18485 C104.9083,17.03835 104.0508,16.20735 102.3328,16.20735 M108.3418,20.98085 C107.6718,25.70235 105.5783,28.73385 100.5093,28.73385 C99.5708,28.73385 98.4978,28.54635 97.5313,28.08985 C97.6128,28.38435 97.6933,28.73385 97.6393,29.02935 L96.8073,34.79585 L93.2133,34.79585 L96.2178,13.89985 L98.7928,13.89985 L99.0878,15.08085 C100.3213,14.00685 101.7703,13.47035 103.1113,13.47035 C106.9473,13.47035 108.5023,15.69685 108.5023,19.05035 C108.5023,19.66735 108.4483,20.31135 108.3418,20.98085" id="Fill-5"></path>
          <path d="M119.33865,16.69 C118.74815,16.609 118.13215,16.555 117.48715,16.555 C116.46815,16.555 115.39515,16.716 114.45615,17.28 L112.87415,28.3045 L109.27965,28.3045 L111.34515,13.8995 L114.29515,13.8995 L114.51115,15.0535 C115.71715,13.8995 116.92465,13.4705 118.21215,13.4705 C118.72265,13.4705 119.25865,13.5515 119.79515,13.659 L119.33865,16.69 Z" id="Fill-7"></path>
          <path d="M127.43385,16.31455 C125.39585,16.31455 124.40285,17.09155 123.81285,19.61405 L129.71435,19.61405 C129.76785,19.29205 129.79435,18.99655 129.79435,18.72855 C129.79435,17.14555 129.01685,16.31455 127.43385,16.31455 M133.03985,22.13505 L123.35635,22.13505 C123.30235,22.56405 123.27685,22.93955 123.27685,23.28855 C123.27685,25.05905 124.08085,25.89105 126.06585,25.89105 C127.91685,25.89105 128.96335,25.08605 129.74035,23.90505 L132.44985,25.00505 C131.18885,27.41855 128.82885,28.73355 125.66385,28.73355 C121.58635,28.73355 119.73535,26.56055 119.73535,22.93955 C119.73535,22.34955 119.78885,21.73305 119.86985,21.08855 C120.64685,15.80405 122.95485,13.47055 127.86285,13.47055 C132.31635,13.47055 133.33585,16.60905 133.33585,19.29205 C133.33585,20.09655 133.17435,21.16955 133.03985,22.13505" id="Fill-9"></path>
          <path d="M144.11795,17.70905 C143.60895,16.79705 142.66995,16.28705 141.19395,16.28705 C140.04145,16.28705 138.64595,16.52905 138.64595,17.97755 C138.64595,18.48755 138.88745,18.91655 139.53145,19.02405 L142.64245,19.58655 C144.60095,19.93605 146.20995,21.03455 146.20995,23.12755 C146.20995,27.23155 142.80295,28.73355 139.23545,28.73355 C136.71445,28.73355 134.73045,27.87555 133.76445,25.62255 L136.76845,24.52255 C137.33245,25.54155 138.24395,25.99805 139.61245,25.99805 C140.95345,25.99805 142.61595,25.59505 142.61595,23.93255 C142.61595,23.34255 142.34795,22.91355 141.56945,22.77855 L138.21645,22.16255 C136.66095,21.86655 135.13145,20.68655 135.13145,18.46005 C135.13145,14.65055 138.27045,13.47055 141.59695,13.47055 C144.57445,13.47055 146.20995,14.67805 146.93445,16.39455 L144.11795,17.70905 Z" id="Fill-11"></path>
          <path d="M157.28835,17.70905 C156.77935,16.79705 155.84135,16.28705 154.36435,16.28705 C153.21235,16.28705 151.81785,16.52905 151.81785,17.97755 C151.81785,18.48755 152.05935,18.91655 152.70335,19.02405 L155.81435,19.58655 C157.77285,19.93605 159.38185,21.03455 159.38185,23.12755 C159.38185,27.23155 155.97385,28.73355 152.40635,28.73355 C149.88635,28.73355 147.90085,27.87555 146.93585,25.62255 L149.93885,24.52255 C150.50285,25.54155 151.41585,25.99805 152.78285,25.99805 C154.12535,25.99805 155.78685,25.59505 155.78685,23.93255 C155.78685,23.34255 155.51985,22.91355 154.74135,22.77855 L151.38835,22.16255 C149.83185,21.86655 148.30235,20.68655 148.30235,18.46005 C148.30235,14.65055 151.44085,13.47055 154.76885,13.47055 C157.74485,13.47055 159.38185,14.67805 160.10535,16.39455 L157.28835,17.70905 Z" id="Fill-13"></path>
          <path d="M168.0188,11.0294 C167.9908,11.2714 167.9908,11.2714 167.7768,11.2714 L164.2888,11.2714 C164.0743,11.2714 164.0743,11.2714 164.1018,11.0294 L164.5858,7.7039 C164.6108,7.4359 164.6108,7.4084 164.8253,7.4084 L168.3133,7.4084 C168.5278,7.4084 168.5278,7.4359 168.5003,7.7039 L168.0188,11.0294 Z M167.2953,28.5464 L165.4688,28.5464 C163.3783,28.5464 162.3583,27.6334 162.3583,25.7564 C162.3583,25.4619 162.3853,25.1659 162.4378,24.8169 L163.5128,17.3874 C163.5378,17.1729 163.6728,16.8509 163.8873,16.6089 L161.2853,16.6089 L161.6618,13.8999 L167.5898,13.8999 L166.0328,24.8169 C166.0083,24.9514 166.0083,25.0864 166.0083,25.1934 C166.0083,25.5154 166.1398,25.6229 166.5443,25.6229 L167.6968,25.6229 L167.2953,28.5464 Z" id="Fill-15"></path>
          <path d="M176.8977,16.31455 C174.6972,16.31455 173.6242,17.44105 173.0882,21.08855 C172.9807,21.81305 172.9262,22.45705 172.9262,22.99305 C172.9262,25.16605 173.7837,25.89105 175.5282,25.89105 C177.7007,25.89105 178.8562,24.76305 179.3922,21.08855 C179.4997,20.39105 179.5522,19.77455 179.5522,19.23855 C179.5522,17.03805 178.6662,16.31455 176.8977,16.31455 M182.9852,21.08855 C182.2617,26.07805 180.0887,28.73355 175.1262,28.73355 C170.8872,28.73355 169.3582,26.13155 169.3582,22.85955 C169.3582,22.29555 169.4132,21.67955 169.4927,21.08855 C170.2167,16.01905 172.3647,13.47055 177.3267,13.47055 C181.5377,13.47055 183.1197,15.93905 183.1197,19.26455 C183.1197,19.85455 183.0672,20.44455 182.9852,21.08855" id="Fill-17"></path>
          <path d="M197.21265,19.23835 L195.89815,28.30435 L192.33015,28.30435 L193.64515,19.23835 C193.70015,18.91635 193.72465,18.59485 193.72465,18.29935 C193.72465,17.06535 193.24365,16.26085 191.90115,16.26085 C190.80115,16.26085 189.51415,16.87685 188.46865,17.52085 L186.91165,28.30435 L183.34415,28.30435 L185.41015,13.89985 L188.36115,13.89985 L188.60315,15.21435 C190.26465,13.89985 191.60665,13.47035 193.10865,13.47035 C196.11265,13.47035 197.32015,15.37535 197.32015,17.92385 C197.32015,18.35285 197.26715,18.78185 197.21265,19.23835" id="Fill-19"></path>
          <path d="M214.45925,11.9686 L205.17825,11.9686 L204.51025,16.6091 L212.98475,16.6091 L211.67025,19.6141 L203.97075,19.6141 C204.02625,19.8011 204.02625,20.0156 203.99875,20.1231 L203.24775,25.3011 L212.55525,25.3011 L212.12675,28.3046 L199.22325,28.3046 L202.01525,8.9651 L214.89075,8.9651 L214.45925,11.9686 Z" id="Fill-21"></path>
          <path d="M227.8411,19.23835 L226.5266,28.30435 L222.9586,28.30435 L224.2736,19.23835 C224.3261,18.91635 224.3531,18.59485 224.3531,18.29935 C224.3531,17.06535 223.8696,16.26085 222.5301,16.26085 C221.4296,16.26085 220.1426,16.87685 219.0946,17.52085 L217.5401,28.30435 L213.9726,28.30435 L216.0386,13.89985 L218.9871,13.89985 L219.2291,15.21435 C220.8931,13.89985 222.2331,13.47035 223.7371,13.47035 C226.7411,13.47035 227.9486,15.37535 227.9486,17.92385 C227.9486,18.35285 227.8936,18.78185 227.8411,19.23835" id="Fill-23"></path>
          <g id="Group-27" transform="translate(227.500000, 13.296000)">
              <mask id="mask-2" fill="white">
                  <use xlink:href="#path-1"></use>
              </mask>
              <g id="Clip-26"></g>
              <path d="M9.7742,2.9912 C7.7607,2.9912 6.6082,4.1452 6.6082,6.1297 C6.6082,7.4702 7.4667,8.0342 9.0232,8.0342 C11.0342,8.0342 12.1612,6.9617 12.1612,4.9772 C12.1612,3.6622 11.3832,2.9912 9.7742,2.9912 L9.7742,2.9912 Z M10.1207,15.0622 L5.0787,14.1227 C4.2757,14.9812 3.9262,15.5447 3.9262,16.7522 C3.9262,18.1197 4.8917,18.7372 7.4667,18.7372 C9.1557,18.7372 11.4907,18.4687 11.4907,16.2957 C11.4907,15.6262 11.1412,15.2507 10.1207,15.0622 L10.1207,15.0622 Z M18.3312,3.3132 L16.5872,3.3132 C16.3457,3.3132 15.7542,3.2867 15.3002,3.0722 C15.5672,3.7157 15.6742,4.4392 15.6742,5.0307 C15.6742,9.2142 12.3482,10.8237 8.6187,10.8237 C7.7882,10.8237 6.9852,10.7437 6.2862,10.5827 C6.1792,10.5552 6.0717,10.5292 5.9372,10.5292 C5.5352,10.5292 5.2932,10.7437 5.2932,11.1452 C5.2932,11.4137 5.4282,11.6017 6.0167,11.7092 L11.1962,12.6747 C14.0652,13.2112 15.0577,14.4447 15.0577,16.0277 C15.0577,20.6682 10.7122,21.5002 7.0647,21.5002 C4.1682,21.5002 0.3862,20.7217 0.3862,17.1002 C0.3862,15.2232 1.3767,13.6142 2.9857,12.6482 C2.6637,12.2457 2.5042,11.7902 2.5042,11.3597 C2.5042,10.3947 3.2007,9.6437 4.0062,9.2142 C3.4972,8.5707 3.0682,7.5517 3.0682,6.3717 C3.0682,2.1602 6.3387,0.1747 10.1757,0.1747 C11.5177,0.1747 12.9372,0.4167 13.9852,1.0862 L16.0537,0.6212 L18.6557,0.6212 L18.3312,3.3132 Z" id="Fill-25" mask="url(#mask-2)"></path>
          </g>
          <path d="M251.54175,11.0294 C251.51675,11.2714 251.51675,11.2714 251.30225,11.2714 L247.81475,11.2714 C247.59975,11.2714 247.59975,11.2714 247.62725,11.0294 L248.10925,7.7039 C248.13625,7.4359 248.13625,7.4084 248.35075,7.4084 L251.83875,7.4084 C252.05275,7.4084 252.05275,7.4359 252.02575,7.7039 L251.54175,11.0294 Z M250.81825,28.5464 L248.99425,28.5464 C246.90175,28.5464 245.88375,27.6334 245.88375,25.7564 C245.88375,25.4619 245.91075,25.1659 245.96375,24.8169 L247.03575,17.3874 C247.06375,17.1729 247.19825,16.8509 247.41275,16.6089 L244.81075,16.6089 L245.18475,13.8999 L251.11275,13.8999 L249.55825,24.8169 C249.53125,24.9514 249.53125,25.0864 249.53125,25.1934 C249.53125,25.5154 249.66575,25.6229 250.06725,25.6229 L251.21975,25.6229 L250.81825,28.5464 Z" id="Fill-28"></path>
          <path d="M266.32595,19.23835 L265.01095,28.30435 L261.44345,28.30435 L262.75845,19.23835 C262.81345,18.91635 262.83795,18.59485 262.83795,18.29935 C262.83795,17.06535 262.35695,16.26085 261.01445,16.26085 C259.91445,16.26085 258.62695,16.87685 257.58195,17.52085 L256.02445,28.30435 L252.45745,28.30435 L254.52345,13.89985 L257.47445,13.89985 L257.71645,15.21435 C259.37795,13.89985 260.71995,13.47035 262.22195,13.47035 C265.22595,13.47035 266.43345,15.37535 266.43345,17.92385 C266.43345,18.35285 266.38045,18.78185 266.32595,19.23835" id="Fill-30"></path>
          <g id="Group-34" transform="translate(267.000000, 13.296000)">
              <mask id="mask-4" fill="white">
                  <use xlink:href="#path-3"></use>
              </mask>
              <g id="Clip-33"></g>
              <path d="M8.0916,3.01855 C6.0531,3.01855 5.0606,3.79555 4.4691,6.31805 L10.3716,6.31805 C10.4241,5.99605 10.4516,5.70055 10.4516,5.43255 C10.4516,3.84955 9.6731,3.01855 8.0916,3.01855 M13.6971,8.83905 L4.0126,8.83905 C3.9596,9.26805 3.9326,9.64355 3.9326,9.99255 C3.9326,11.76305 4.7381,12.59505 6.7216,12.59505 C8.5731,12.59505 9.6211,11.79005 10.3961,10.60905 L13.1056,11.70905 C11.8461,14.12255 9.4861,15.43755 6.3201,15.43755 C2.2436,15.43755 0.3926,13.26455 0.3926,9.64355 C0.3926,9.05355 0.4446,8.43705 0.5271,7.79255 C1.3031,2.50805 3.6106,0.17455 8.5201,0.17455 C12.9736,0.17455 13.9916,3.31305 13.9916,5.99605 C13.9916,6.80055 13.8316,7.87355 13.6971,8.83905" id="Fill-32" mask="url(#mask-4)"></path>
          </g>
          <path d="M20.60205,17.64605 C21.11355,14.75605 22.01655,12.45255 23.28405,10.79305 C24.18105,9.60555 25.17405,9.00405 26.23755,9.00405 C26.80055,9.00405 27.27705,9.22055 27.65305,9.64955 C28.01805,10.06905 28.20405,10.64605 28.20405,11.36305 C28.20405,13.02405 27.45705,14.53555 25.98455,15.86155 C24.91705,16.81355 23.20305,17.51055 20.89205,17.93305 L20.53855,17.99805 L20.60205,17.64605 Z M30.67305,21.68355 C29.37505,22.92855 28.23905,23.80705 27.31805,24.24655 C26.34905,24.70655 25.34805,24.93855 24.34355,24.93855 C23.11755,24.93855 22.12155,24.54805 21.38655,23.77655 C20.65105,23.00705 20.27805,21.90355 20.27805,20.49455 L20.37305,19.08355 L20.56855,19.05005 C24.00755,18.47005 26.60155,17.80655 28.27555,17.07555 C29.93155,16.35405 31.14005,15.49505 31.86855,14.52405 C32.59155,13.56105 32.95655,12.59155 32.95655,11.65055 C32.95655,10.50805 32.52355,9.59355 31.63105,8.84705 C30.73555,8.10155 29.44355,7.72455 27.79455,7.72455 C25.50305,7.72455 23.33455,8.25905 21.34955,9.31405 C19.36805,10.36805 17.78305,11.82905 16.64005,13.65605 C15.50005,15.48105 14.92155,17.41555 14.92155,19.40105 C14.92155,21.61755 15.60505,23.39505 16.95205,24.68005 C18.30455,25.96905 20.19355,26.62005 22.56705,26.62005 C24.25255,26.62005 25.84755,26.28155 27.30805,25.61355 C28.70455,24.97455 30.14905,23.86705 31.60805,22.37255 C31.33005,22.16805 30.87005,21.82855 30.67305,21.68355 L30.67305,21.68355 Z" id="Fill-35"></path>
          <g id="Group-39" transform="translate(0.000000, 2.796000)">
              <mask id="mask-6" fill="white">
                  <use xlink:href="#path-5"></use>
              </mask>
              <g id="Clip-38"></g>
              <path d="M7.2737,19.35005 C5.3202,11.70605 9.9462,3.71505 17.8897,0.06905 C17.6907,0.14055 17.5042,0.22255 17.3077,0.29605 C17.5087,0.20005 17.6882,0.11955 17.8272,0.07205 L2.9432,3.91255 L6.9112,6.26005 C1.7147,10.66105 -0.9663,16.11555 0.3187,21.14505 C2.3302,29.02005 13.3457,33.12605 25.8202,31.10805 C17.1117,31.75655 9.2257,26.99355 7.2737,19.35005" id="Fill-37" mask="url(#mask-6)"></path>
          </g>
          <g id="Group-42" transform="translate(23.500000, 0.296000)">
              <mask id="mask-8" fill="white">
                  <use xlink:href="#path-7"></use>
              </mask>
              <g id="Clip-41"></g>
              <path d="M18.65285,12.4697 C20.60635,20.1147 15.98135,28.1052 8.03735,31.7517 C8.23585,31.6797 8.42235,31.5977 8.61885,31.5232 C8.41785,31.6212 8.23835,31.7002 8.09935,31.7482 L22.98335,27.9087 L19.01585,25.5612 C24.21185,21.1597 26.89285,15.7042 25.60835,10.6747 C23.59635,2.8027 12.58085,-1.3053 0.10635,0.7142 C8.81435,0.0637 16.70135,4.8267 18.65285,12.4697" id="Fill-40" mask="url(#mask-8)"></path>
          </g>
      </g>
    </g>
  </svg>
</div>
				<div class="panel warn">
  <div class="panel-heading" style="text-align: center;">
    <h3>ExpressionEngine has been installed!</h3>
  </div>
  <div class="panel-body">
    <div class="updater-msg">
  		<p style="margin-bottom: 20px;">If you see this message, then everything went well.</p>

  		<div class="alert alert--attention">
            <div class="alert__icon">
              <i class="fas fa-info-circle fa-fw"></i>
            </div>
            <div class="alert__content">
    			<p>If you are site owner, please login into your Control Panel and create your first template.</p>
    		</div>
  		</div>
  		<div class="alert alert--attention">
            <div class="alert__icon">
              <i class="fas fa-info-circle fa-fw"></i>
            </div>
            <div class="alert__content">
    			<p>If this is your first time using ExpressionEngine CMS, make sure to <a href="https://docs.expressionengine.com/latest/getting-started/the-big-picture.html">check out the documentation</a> to get started.</p>
    		</div>
  		</div>
  	</div>
  </div>
  <div class="panel-footer">

  </div>
</div>
			</div>
			<section class="bar">
				<p style="float: left;"><a href="https://expressionengine.com/" rel="external"><b>ExpressionEngine</b></a></p>
				<p style="float: right;">&copy;2020 <a href="https://packettide.com/" rel="external">Packet Tide</a>, LLC</p>
			</section>
		</section>

	</body>
</html>
EOF;
    }
}

// EOF
