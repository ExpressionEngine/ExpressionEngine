<?php

/*********************
	Status Groups
*********************/

// "about" Status Group
$status_group = ee('Model')->make('StatusGroup');
$status_group->site_id = 1;
$status_group->group_name = 'about';
$status_group->save();

// "about" Status Group statuses
$status = ee('Model')->make('Status');
$status->site_id = 1;
$status->group_id = $status_group->group_id;
$status->status = 'Default Page';
$status->highlight = '2051B3';
$status->NoAccess = NULL;
$status->save();

/*********************
	Category Groups
*********************/

$category_list = array(
	'blog' => array(
		'News',
		'Personal',
		'Photos',
		'Videos',
		'Music'
	),
	'collection' => array(
		'Rock and Roll',
		'Rhythm and Blues',
		'Country',
		'Punk Rock',
		'Jazz',
		'Techno',
		'Classical',
		'Pop',
		'Holiday',
		'Soundtrack',
		'Funk',
		'Folk',
		'Heavy Metal',
		'New Age',
		'Blue Grass',
		'Reggae',
		'Hip Hop',
		'Christian and Gospel',
		'Dance'
	),
	'slideshow' => array(
		'Not Shown'
	)
);

foreach ($category_list as $group_name => $categories)
{
	$cat_group = ee('Model')->make('CategoryGroup');
	$cat_group->site_id = 1;
	$cat_group->sort_order = 'a';
	$cat_group->group_name = $group_name;
	$cat_group->save();

	foreach ($categories as $category_name)
	{
		$category = ee('Model')->make('Category');
		$category->group_id = $cat_group->group_id;
		$category->site_id = 1;
		$category->cat_name = $category_name;
		$category->cat_url_title = strtolower(str_replace(' ', '-', $category_name));
		$category->parent_id = 0;
		$category->save();
	}
}

/*********************
	Field Groups
*********************/

$default_settings = array(
	'text' => array(
		'field_content_type' => 'all',
		'field_show_smileys' => 'n',
		'field_show_glossary' => 'n',
		'field_show_spellcheck' => 'n',
		'field_show_formatting_btns' => 'n',
		'field_show_file_selector' => 'n',
		'field_show_writemode' => 'n'
	)
	'textarea' => array(
		'field_show_smileys' => 'n',
		'field_show_glossary' => 'n',
		'field_show_spellcheck' => 'n',
		'field_show_formatting_btns' => 'n',
		'field_show_file_selector' => 'n',
		'field_show_writemode' => 'n'
	)
);


4	1	1	blog_content	textarea	Content	Content for this blog entry.		0	0	0	10	128	n	ltr	y	n	xhtml	n	1	any	YTo2OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToibiI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJuIjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToibiI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToibiI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6Im4iO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6Im4iO30=
5	1	1	blog_video	grid	Video	Video for this blog.		0	0	0	6	128	n	ltr	y	n	xhtml	n	3	any	YTo4OntzOjEzOiJncmlkX21pbl9yb3dzIjtpOjA7czoxMzoiZ3JpZF9tYXhfcm93cyI7czoxOiIxIjtzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToibiI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJuIjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToibiI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToibiI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6Im4iO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6Im4iO30=
6	1	1	blog_seo_title	text	SEO Title	Page title that will be added to browser titlebar/tab.		0	0	0	6	128	n	ltr	n	n	none	n	6	any	YTo3OntzOjE4OiJmaWVsZF9jb250ZW50X3R5cGUiO3M6MzoiYWxsIjtzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToibiI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJuIjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToibiI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToibiI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6Im4iO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6Im4iO30=
7	1	1	blog_seo_desc	textarea	SEO Description	Page Description for use in HTML meta description tag, generally only seen by machines.		0	0	0	2	128	n	ltr	n	n	none	n	7	any	YTo2OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToibiI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJuIjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToibiI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToibiI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6Im4iO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6Im4iO30=
8	1	1	blog_audio	grid	Audio	Audio clip for this blog.		0	0	0	6	128	n	ltr	n	n	xhtml	n	4	any	YTo4OntzOjEzOiJncmlkX21pbl9yb3dzIjtpOjA7czoxMzoiZ3JpZF9tYXhfcm93cyI7czoxOiIxIjtzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToibiI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJuIjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToibiI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToibiI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6Im4iO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6Im4iO30=
26	1	1	blog_image	grid	Image	Photograph, comic, any image you like.		0	0	0	6	128	n	ltr	y	n	xhtml	n	5	any	YTo4OntzOjEzOiJncmlkX21pbl9yb3dzIjtpOjA7czoxMzoiZ3JpZF9tYXhfcm93cyI7czoxOiIxIjtzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToibiI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJuIjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToibiI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToibiI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6Im4iO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6Im4iO30=
1	1	5	p_seo_title	text	SEO Title	Page title that will be added to browser titlebar/tab.		0	0	0	6	128	n	ltr	n	n	none	n	1	any	YTo3OntzOjE4OiJmaWVsZF9jb250ZW50X3R5cGUiO3M6MzoiYWxsIjtzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToibiI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJuIjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToibiI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToibiI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6Im4iO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6Im4iO30=
2	1	5	p_seo_desc	textarea	SEO Description	Page Description for use in HTML meta description tag, generally only seen by machines.		0	0	0	2	128	n	ltr	n	n	none	n	2	any	YTo2OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToibiI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJuIjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToibiI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToibiI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6Im4iO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6Im4iO30=
3	1	5	p_content	textarea	Page Content	Content for this page.		0	0	0	10	128	n	ltr	y	n	xhtml	n	3	any	YTo2OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToibiI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJuIjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToieSI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToieSI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6InkiO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6InkiO30=
27	1	5	contact_address	grid	Contact Address	Address where someone can send mail.		0	0	0	6	128	n	ltr	n	n	xhtml	n	4	any	YTo4OntzOjEzOiJncmlkX21pbl9yb3dzIjtpOjA7czoxMzoiZ3JpZF9tYXhfcm93cyI7czoxOiIxIjtzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToibiI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJuIjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToibiI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToibiI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6Im4iO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6Im4iO30=
28	1	5	contact_phone	text	Contact Phone	phone number someone can call.		0	0	0	6	128	n	ltr	y	n	none	n	5	any	YTo3OntzOjE4OiJmaWVsZF9jb250ZW50X3R5cGUiO3M6MzoiYWxsIjtzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToibiI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJuIjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToibiI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToibiI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6Im4iO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6Im4iO30=
29	1	5	contact_email	text	Contact e-mail	e-mail address someone can send e-mail to.		0	0	0	6	128	n	ltr	y	n	none	n	6	any	YTo3OntzOjE4OiJmaWVsZF9jb250ZW50X3R5cGUiO3M6MzoiYWxsIjtzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToibiI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJuIjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToibiI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToibiI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6Im4iO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6Im4iO30=
30	1	5	about_image	grid	About Image	Image for the about page.		0	0	0	6	128	n	ltr	n	n	xhtml	n	7	any	YTo4OntzOjEzOiJncmlkX21pbl9yb3dzIjtpOjA7czoxMzoiZ3JpZF9tYXhfcm93cyI7czowOiIiO3M6MTg6ImZpZWxkX3Nob3dfc21pbGV5cyI7czoxOiJuIjtzOjE5OiJmaWVsZF9zaG93X2dsb3NzYXJ5IjtzOjE6Im4iO3M6MjE6ImZpZWxkX3Nob3dfc3BlbGxjaGVjayI7czoxOiJuIjtzOjI2OiJmaWVsZF9zaG93X2Zvcm1hdHRpbmdfYnRucyI7czoxOiJuIjtzOjI0OiJmaWVsZF9zaG93X2ZpbGVfc2VsZWN0b3IiO3M6MToibiI7czoyMDoiZmllbGRfc2hvd193cml0ZW1vZGUiO3M6MToibiI7fQ==

$field_group = ee('Model')->make('ChannelFieldGroup');

// create fields
// create field groups
// create Channel, assigned with the above groups

// add upload locations
// add files

// add entries

// set site_404 and strict_urls, etc.