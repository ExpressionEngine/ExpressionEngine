<?php

/*********************
	Legacy Libs
*********************/

ee()->load->library('api');
ee()->load->library('extensions');

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

$field_group_path = $this->theme_path.$this->userdata['theme'].'/custom_fields/';
$field_groups = directory_map($field_group_path);

foreach ($field_groups as $group_name => $fields)
{
	$field_group = ee('Model')->make('ChannelFieldGroup');
	$field_group->group_name = $group_name;
	$field_group->save();

	foreach ($fields as $file_name)
	{
		$file_path = $field_group_path.$group_name.'/'.$file_name;

		$parts = explode('.', $file_name);
		$field_type = array_pop($parts);
		$field_name = implode('.', $parts);

		if (file_exists($file_path))
		{
			$field_details = json_decode(file_get_contents($file_path), TRUE);
		}
		else
		{
			// shouldn't ever happen, but just in case...
			continue;
		}

		$field = ee('Model')->make('ChannelField');

		$field->site_id = 1;
		$field->field_name = $field_name;
		$field->field_type = $field_type;

		$data = array('group_id' => $field_group->group_id);
		$i = 0;

		foreach ($field_details as $key => $val)
		{
			if ($key == 'columns')
			{
				// grid[cols][new_0][col_label]
				foreach ($val as $column)
				{
					foreach ($column as $col_label => $col_value)
					{
						$_POST['grid']['cols']["new_{$i}"]['col_'.$col_label] = $col_value;
					}

					$i++;
				}
			}
			else
			{
				$data['field_'.$key] = $val;
			}
		}

		// unusual item that has no defaults
		if ( ! isset($data['field_list_items']))
		{
			$data['field_list_items'] = '';
		}

		$field->set($data);
		$field->save();
	}
}

// create fields
// create field groups
// create Channel, assigned with the above groups

// add upload locations
// add files

// add entries

// set site_404 and strict_urls, etc.