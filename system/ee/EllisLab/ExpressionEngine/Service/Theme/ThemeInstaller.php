<?php

namespace EllisLab\ExpressionEngine\Service\Theme;

/**
 * Install the Default Content
 */
class ThemeInstaller {

	private $userdata;
	private $root_theme_path;
	private $theme_path;

	private $theme_base_url;
	private $theme_base_path;
	private $template_folder;
	private $asset_url;
	private $asset_path;

	private $structure_data;

	/**
	 * Constructor: sets the site_url and theme paths
	 *
	 * @param string $root_theme_path The root of the theme directory blah
	 *   (e.g. themes/, not themes/ee/ or themes/user/)
	 * @param array $userdata The userdata array
	 */
	public function __construct()
	{
		// ee()->load->library('api');
		// ee()->load->library('extensions');

		// $this->root_theme_path = $root_theme_path;
		// $this->theme_path = $theme_path;
		// $this->userdata = $userdata;

		// $this->theme_base_url = $this->userdata['site_url'].'themes/ee/site/default/';
		// $this->theme_base_path = $root_theme_path.'ee/site/default/';
		// $this->asset_url = $this->theme_base_url.'asset/';
		// $this->asset_path = $this->theme_base_path.'asset/';

		$this->template_folder = SYSPATH.'ee/templates/default/';
	}

	/**
	 * Set the site URL
	 * @param string $site_url The site URL
	 */
	public function setSiteURL($site_url)
	{
		$this->site_url = rtrim($site_url, '/').'/';
	}

	/**
	 * Set the theme path, most likely from a constant
	 * @param string $theme_path The theme path
	 */
	public function setThemePath($theme_path)
	{
		$this->theme_path = rtrim($theme_path, '/').'/';
	}

	/**
	 * Set the theme URL, most likely from a constant
	 * @param string $theme_url The theme URL
	 */
	public function setThemeURL($theme_url)
	{
		$this->theme_url = rtrim($theme_url, '/').'/';
	}

	/**
	 * Install a site theme
	 * @param string $theme_name The name of the site theme to install
	 * @return void
	 */
	public function install($theme_name = 'default')
	{
		if (empty($theme_name))
		{
			$theme_name = 'default';
		}

		$channel_set = $this->loadChannelSet($theme_name);

		$this->createStatusGroups($channel_set->status_groups);
		// $this->createCategoryGroups();
		// $this->createUploadLocations();
		// $this->createFieldGroups();
		// $this->createChannels();
		// $this->createEntries();
	}

	/**
	 * Load the Channel Set data from the theme
	 * @param string $theme_name The theme name
	 * @return Object json_decoded()'ed object
	 */
	private function loadChannelSet($theme_name)
	{
		return json_decode(file_get_contents($this->theme_path.'ee/site/'.$theme_name.'/channel_set.json'));
	}

	/**
	 * Create the status groups
	 * @param array $status_groups Array of objects representing the status
	 * 	groups supplied by loadChannelSet
	 * @return void
	 */
	private function createStatusGroups($status_groups)
	{
		foreach ($status_groups as $status_group_data)
		{
			$status_group = ee('Model')->make('StatusGroup');
			$status_group->site_id = 1;
			$status_group->group_name = $status_group_data->name;
			$status_group->save();

			foreach ($status_group_data->statuses as $status_data)
			{
				$status = ee('Model')->make('Status');
				$status->site_id = 1;
				$status->group_id = $status_group->group_id;
				$status->status = $status_data->status;

				if ( ! empty($status_data->highlight))
				{
					$status->highlight = $status_data->highlight;
				}

				$status->save();
			}
		}
	}

	private function createCategoryGroups()
	{
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

			$this->structure_data['cat_group_ids'][$cat_group->group_name] = $cat_group->group_id;

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
	}

	private function createUploadLocations()
	{
		$img_url = $this->asset_url.'img/';
		$img_path = $this->asset_path.'img/';

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
	}

	private function createFieldGroups()
	{
		$field_group_path = $this->theme_path.$this->userdata['theme'].'/custom_fields/';
		$field_groups = directory_map($field_group_path);
		ee()->load->model('grid_model');

		foreach ($field_groups as $group_name => $fields)
		{
			$field_group = ee('Model')->make('ChannelFieldGroup');
			$field_group->group_name = $group_name;
			$field_group->save();

			$this->structure_data['field_group_ids'][$field_group->group_name] = $field_group->group_id;

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
						$this->structure_data['gf_id'][$field->field_id][$column['col_name']] = $column['col_id'];
					}
				}

				$this->structure_data['cf_id'][$field->field_name] = $field->field_id;
			}
		}
	}

	private function createChannels()
	{
		$channels = array(
			'About' => array(
				'status_group' => $this->structure_data['status_group_ids']['about'],
				'field_group' => $this->structure_data['field_group_ids']['common']
			),
			'Blog' => array(
				'status_group' => 1,
				'cat_group' => $this->structure_data['cat_group_ids']['blog'],
				'field_group' => $this->structure_data['field_group_ids']['blog'],
				'channel_url' => "{path='blog/entry'}"
			),
			'Contact' => array(
				'status_group' => 1,
				'field_group' => $this->structure_data['field_group_ids']['common']
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
			$this->structure_data['channel_objs'][$channel->channel_name] = $channel;
		}
	}

	public function createEntries()
	{
		// add entries
		// ChannelEntry::populateChannels() is reaching into the legacy superobject
		// @todo - address this dependency
		// ee()->set('session', (object) array());

		// Override Functions::clear_caching()
		// ee()->set('functions', new Functions);

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
				$entry->setChannel($this->structure_data['channel_objs'][$channel_name]);
				$entry->site_id =  1;
				$entry->author_id = 1;
				$entry->ip_address = ee()->input->ip_address();
				$entry->versioning_enabled = $this->structure_data['channel_objs'][$channel_name]->enable_versioning;
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
						$field_col_name = "field_id_{$this->structure_data['cf_id'][$key]}";
						$post_mock[$field_col_name] = $val;
					}
					else
					{
						foreach($val->rows as $row_index => $grid_row)
						{
							$row_index = $row_index + 1;
							foreach ($grid_row as $col_name => $col_value)
							{
								$column_id = 'col_id_'.$this->structure_data['gf_id'][$this->structure_data['cf_id'][$key]][$col_name];
								$post_mock["field_id_{$this->structure_data['cf_id'][$key]}"]
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
	}

	private function setTemplatePreferences()
	{
		// set site_404 and strict_urls, save templates as files
	}
}

/**
 * Stub Functions class
 *
 * The ChannelEntry model calls Functions::clear_caching() onAfterSave, but we
 * don't have a cache yet.
 */
class FunctionsStub
{
	public function clear_caching($type)
	{
		return TRUE;
	}
}
