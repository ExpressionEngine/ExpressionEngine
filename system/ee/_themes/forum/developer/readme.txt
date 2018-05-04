=====================================================
 ExpressionEngine - by EllisLab
-----------------------------------------------------
 https://ellislab.com/
-----------------------------------------------------
 Copyright (c) 2003 - 2016, EllisLab, Inc.
=====================================================
 THIS IS COPYRIGHTED SOFTWARE
 PLEASE READ THE LICENSE AGREEMENT
 https://expressionengine.com/license
=====================================================
 File: readme.txt
-----------------------------------------------------
 Purpose: Developer Forum Theme Readme
=====================================================

This theme is designed to provide a relatively clean slate to
start from when designing / branding your own Discussion Forum.
Please remember that if left in the forum_theme folder on your
server, members will still be able to select this theme in the
theme selector.

-----------------------------------------------------

If you're brand new to the discussion forum templates we recommend
you start by exploring the themes via the Control Panel. That is the
easiest way to see the HTML and CSS that the theme uses. From your
ExpressionEngine Control Panel go to:

Modules › Discussion Forum › Templates › Developer (this theme's files).

The majority of your visual design can be accomplished by altering
just three Template Groups. First is the CSS Templates (the physical
file is theme_css.php). The second are the Global Templates (the
physical file is theme_global.php) and the third are the Index Page
Templates (the physical file is theme_index.php).

As you gain experience with the templates, you will see how they
interact and connect to create the seamless dynamic experience of
the Discussion Forums.  This will allow you to make radical changes
to the structure and design of your forums.  However, the goal of
this bare-bones theme is to make it quick and simple for you to
re-color, style, and brand the Discussion Forum module to match your
existing site.

-----------------------------------------------------

Styling has been kept to a minimum.  The markup uses the same
classes and basic layout of the default theme, and all of the
necessary style definitions are included in theme_css.php,
although many are empty or carry only one or two basic values.

The stylesheet is devoid of any background images, and uses
consistent coloring to make find & replace a simpler task.
Inheritance is used where possible, requiring fewer modifications
to make broad changes.

The topic marker images have been replaced with generic GIFs
indicating what they are for; you will want to replace these with
your own images.  They are each 24px x 18px in dimension with the
exception of marker_announcements.gif which is 24px x 22px.

icon_aim.gif, icon_email.gif, etc. will also need to be replaced
with your own images.  These images are used on the Public
Profile page and are 56px x 14px in dimension.

