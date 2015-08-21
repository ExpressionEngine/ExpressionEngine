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

ee()->load->helper('url');
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

// create fields
// create field groups
// create Channel, assigned with the above groups

// add upload locations
// add files

// add entries

// set site_404 and strict_urls, etc.