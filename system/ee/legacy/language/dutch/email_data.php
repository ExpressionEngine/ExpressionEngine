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
Een nieuw entry is gepost in het volgende kanaal:
{channel_name}

De titel van het entry is:
{entry_title}

Geplaatst door: {name}
Email: {email}

Ga naar om het bericht te lezen:
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
Melding van nieuwe member registration
EOF;
	}
}

if ( ! function_exists('admin_notify_reg'))
{
	function admin_notify_reg()
	{
return <<<EOF
Nieuwe member registration site: {site_name}

Schermnaam: {name}
Username: {username}
E-mail: {email}

Uw control panel URL: {control_panel_url}
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
U hebt zojuist een reactie ontvangen
EOF;
	}
}

if ( ! function_exists('admin_notify_comment'))
{
	function admin_notify_comment()
	{
return <<<EOF
Je hebt zojuist een reactie ontvangen voor het volgende channel:
{channel_name}

De titel van het entry is:
{entry_title}

Gevestigd in:
{comment_url}

Geplaatst door: {name}
E-mail: {email}
URL: {url}
Locatie: {location}

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
Ingesloten is je activatiecode
EOF;
	}
}

if ( ! function_exists('mbr_activation_instructions'))
{
	function mbr_activation_instructions()
	{
return <<<EOF
Bedankt voor je nieuwe member registration.

Ga naar de volgende URL om uw nieuwe account te activeren:

{unwrap}{activation_url}{/unwrap}

Dank je!

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
Login informatie
EOF;
	}
}

if ( ! function_exists('forgot_password_instructions'))
{
	function forgot_password_instructions()
	{
return <<<EOF
{name},

Ga naar de volgende pagina om uw wachtwoord opnieuw in te stellen:

{reset_url}

Log dan in met uw gebruikersnaam: {username}

Als u uw wachtwoord niet opnieuw wilt instellen, negeert u dit bericht. Het verloopt over 24 uur.

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
Wachtwoord veranderd
EOF;
	}
}

if ( ! function_exists('password_changed_notification'))
{
	function password_changed_notification()
	{
return <<<EOF
{name},

Uw wachtwoord is zojuist gewijzigd.

Als u deze wijziging niet zelf hebt aangebracht, neemt u onmiddellijk contact op met een beheerder.

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
E-mailadres gewijzigd
EOF;
	}
}

if ( ! function_exists('email_changed_notification'))
{
	function email_changed_notification()
	{
return <<<EOF
{name},

Uw e-mailadres is gewijzigd en dit e-mailadres is niet langer gekoppeld aan uw account.

Als u deze wijziging niet zelf hebt aangebracht, neemt u onmiddellijk contact op met een beheerder.

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
Uw membership account is geactiveerd
EOF;
	}
}

if ( ! function_exists('validated_member_notify'))
{
	function validated_member_notify()
	{
return <<<EOF
{name},

Uw membership account is geactiveerd en klaar voor gebruik.

Dank je!

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
Uw membership account is geweigerd
EOF;
	}
}

if ( ! function_exists('decline_member_validation'))
{
	function decline_member_validation()
	{
return <<<EOF
{name},

Het spijt ons, maar onze medewerkers hebben besloten uw lidmaatschap niet te valideren.

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
Iemand reageerde zojuist op uw reactie
EOF;
	}
}

if ( ! function_exists('comment_notification'))
{
	function comment_notification()
	{
return <<<EOF
{name_of_commenter} reageerde op het entry waarop u zich heeft geabonneerd:
{channel_name}

De titel van het entry is:
{entry_title}

U kunt de reactie zien op de volgende URL:
{comment_url}

{comment}

Klik hier om de meldingen voor deze reactie niet meer te ontvangen:
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
Nieuwe opmerkingen zijn toegevoegd
EOF;
	}
}

if ( ! function_exists('comments_opened_notification'))
{
	function comments_opened_notification()
	{
return <<<EOF
Er zijn reacties toegevoegd aan het entry waarop u bent geabonneerd:
{channel_name}

De titel van het entry is:
{entry_title}

U kunt de reacties zien op de volgende URL:
{comment_url}

{comments}
{comment}
{/comments}

Klik hier om de meldingen voor dit bericht niet meer te ontvangen:
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
Iemand heeft net gepost in {forum_name}
EOF;
	}
}

if ( ! function_exists('admin_notify_forum_post'))
{
	function admin_notify_forum_post()
	{
return <<<EOF
{name_of_poster} heeft zojuist een nieuw bericht geplaatst in {forum_name}

De titel van de thread is:
{title}

De post is te vinden op:
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
Iemand heeft net gepost in {forum_name}
EOF;
	}
}

if ( ! function_exists('forum_post_notification'))
{
	function forum_post_notification()
	{
return <<<EOF
Iemand heeft zojuist gepost in een thread waarop u zich hebt geabonneerd:
{forum_name}

De titel van de thread is:
{title}

De post is te vinden op:
{post_url}

{body}

Klik hier om de meldingen voor deze reactie niet meer te ontvangen:
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
Iemand heeft u een privébericht gestuurd
EOF;
	}
}

if ( ! function_exists('private_message_notification'))
{
	function private_message_notification()
	{
return <<<EOF

{recipient_name},

{sender_name} heeft u zojuist een privébericht gestuurd met de titel ‘{message_subject}’.

U kunt het privébericht zien door in te loggen en uw inbox te bekijken op:
{site_url}

Content:

{message_content}

Om de ontvangst van meldingen van privéberichten te stoppen, zet u de optie uit in uw e-mailinstellingen.

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
Uw privé-berichtenpostbus is vol
EOF;
	}
}

if ( ! function_exists('pm_inbox_full'))
{
	function pm_inbox_full()
	{
return <<<EOF
{recipient_name},

{sender_name} heeft zojuist geprobeerd je een privébericht te sturen,
maar je inbox is vol en overschrijdt het maximum van {pm_storage_limit}.

Meld u aan en verwijder ongewenste berichten uit uw Postvak IN op:
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
{name_of_recipient}, een moderator heeft {moderation_action} jouw thread.

De titel van de thread is:
{title}

De thread is te vinden op:
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
Post gemeld in {forum_name}
EOF;
	}
}

if ( ! function_exists('forum_report_notification'))
{
	function forum_report_notification()
	{
return <<<EOF
{reporter_name} heeft net een bericht gerapporteerd dat is geschreven door {auteur} in:
{forum_name}

De reden (en) voor het rapport:
{reasons}

Aanvullende opmerkingen van {reporter_name}:
{notes}

De post is te vinden op:
{post_url}

Inhoud van gemelde post:
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

// EOF
