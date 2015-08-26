<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/*********************
	Legacy Libs
*********************/

ee()->load->library('api');
ee()->load->library('extensions');

$theme_base_url = $this->userdata['site_url'].'themes/user/site/default/';
$theme_base_path = $this->root_theme_path.'user/site/default/';
$template_folder = SYSPATH.'user/templates/default/';
$asset_url = $theme_base_url.'asset/';
$asset_path = $theme_base_path.'asset/';

/*********************
	Status Groups
*********************/

// "about" Status Group
$status_group = ee('Model')->make('StatusGroup');
$status_group->site_id = 1;
$status_group->group_name = 'about';
$status_group->save();

$status_group_ids[$status_group->group_name] = $status_group->group_id;

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

	$cat_group_ids[$cat_group->group_name] = $cat_group->group_id;

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
	Upload Locations
*********************/

$img_url = $asset_url.'img/';
$img_path = $asset_path.'img/';

foreach (array('blog', 'common', 'home') as $upload_name)
{
	$upload_destination = ee('Model')->make('UploadDestination');
	$upload_destination->site_id = 1;
	$upload_destination->name = $upload_name;
	$upload_destination->url = $img_url.$upload_name.'/';
	$upload_destination->server_path = $img_path.$upload_name.'/';
	$upload_destination->save();

	$dir = $img_path.$upload_name;

	foreach (directory_map($dir) as $filename)
	{
		if (! is_array($filename) && is_file($dir.'/'.$filename))
		{
			$filepath = $dir.'/'.$filename;
			$time = time();
			$file = ee('Model')->make('File');
			$file->site_id = 1;
			$file->upload_location_id = $upload_destination->id;
			$file->uploaded_by_member_id = 1;
			$file->modified_by_member_id = 1;
			$file->title = $filename;
			$file->file_name = $filename;
			$file->upload_date = $time;
			$file->modified_date = $time;
			$file->mime_type = mime_content_type($filepath);
			$file->file_size = filesize($filepath);
			$file->save();
		}
	}
}

/*********************
	Field Groups
*********************/

$field_group_path = $this->theme_path.$this->userdata['theme'].'/custom_fields/';
$field_groups = directory_map($field_group_path);
ee()->load->model('grid_model');

foreach ($field_groups as $group_name => $fields)
{
	$field_group = ee('Model')->make('ChannelFieldGroup');
	$field_group->group_name = $group_name;
	$field_group->save();

	$field_group_ids[$field_group->group_name] = $field_group->group_id;

	foreach ($fields as $file_name)
	{
		// reset for Grid fields
		unset($_POST['grid']);

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
						// Grid is expecting a POSTed checkbox, so if it's in POST at all
						// this value will be set to 'y'
						// @todo Fieldtypes should receive data, not reach into POST
						if ($col_label == 'required' && $col_value == 'n')
						{
							continue;
						}

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

		// cache our grid field column names and id's
		if (isset($_POST['grid']))
		{
			$columns = ee()->grid_model->get_columns_for_field($field->field_id, 'channel');

			foreach ($columns as $column)
			{
				$gf_id[$field->field_id][$column['col_name']] = $column['col_id'];
			}
		}

		$cf_id[$field->field_name] = $field->field_id;
	}
}

/*********************
	  Channels
*********************/

$channels = array(
	'About' => array(
		'status_group' => $status_group_ids['about'],
		'field_group' => $field_group_ids['common']
	),
	'Blog' => array(
		'status_group' => 1,
		'cat_group' => $cat_group_ids['blog'],
		'field_group' => $field_group_ids['blog'],
		'channel_url' => "{path='blog/entry'}"
	),
	'Contact' => array(
		'status_group' => 1,
		'field_group' => $field_group_ids['common']
	)
);

foreach ($channels as $channel_label => $channel_prefs)
{
	$channel = ee('Model')->make('Channel');
	$channel->title_field_label = lang('title');
	$channel->channel_name = strtolower($channel_label);
	$channel->channel_title = $channel_label;
	$channel->channel_lang = 'en';

	foreach ($channel_prefs as $pref_key => $pref_value)
	{
		$channel->$pref_key = $pref_value;
	}

	$channel->save();
	$channel_objs[$channel->channel_name] = $channel;
}

// add entries
// ChannelEntry::populateChannels() is reaching into the legacy superobject
// @todo - address this dependency
//ee()->set('session', (object) array());
ee()->load->library('session');
ee()->session->userdata['group_id'] = 1;

$entry_data_path = $this->theme_path.$this->userdata['theme'].'/channel_entries/';

foreach (array('about', 'blog', 'contact') as $channel_name)
{
	$dir = $entry_data_path.$channel_name.'/';

	foreach (directory_map($dir) as $filename)
	{
		$entry_data = json_decode(file_get_contents($dir.$filename));

		$entry = ee('Model')->make('ChannelEntry');
		$entry->setChannel($channel_objs[$channel_name]);
		$entry->site_id =  1;
		$entry->author_id = 1;
		$entry->ip_address = ee()->input->ip_address();
		$entry->versioning_enabled = $channel_objs[$channel_name]->enable_versioning;
		$entry->sticky = FALSE;
		$entry->allow_comments = TRUE;

		// can't use localize here because it's expecting session class methods
		// to be available on the legacy superobject
		$entry->year = date('Y');
		$entry->month = date('m');
		$entry->day = date('d');

		$post_mock = array(
			'title' => $entry_data->title,
			'url_title' => $entry_data->url_title,
			'status' => $entry_data->status
		);

		foreach ($entry_data->custom_fields as $key => $val)
		{
			if (is_string($val))
			{
				$field_col_name = "field_id_{$cf_id[$key]}";
				$post_mock[$field_col_name] = $val;
			}
			else
			{
				foreach($val->rows as $row_index => $grid_row)
				{
					$row_index = $row_index + 1;
					foreach ($grid_row as $col_name => $col_value)
					{
						$column_id = 'col_id_'.$gf_id[$cf_id[$key]][$col_name];
						$post_mock["field_id_{$cf_id[$key]}"]
								["rows"]
								["new_row_{$row_index}"]
								[$column_id]
							= $col_value;
					}
				}
			}
		}

		$entry->set($post_mock);
		$entry->save();
	}
}

// set site_404 and strict_urls, etc.



