<?php

namespace EllisLab\ExpressionEngine\Service\Theme;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine ThemeInstaller Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 * @internal    This Class is not ready for third-party usage and will mostly
 * 				likely change quickly
 */
class ThemeInstaller {

	/**
	 * @var string The site URL
	 */
	private $site_url;

	/**
	 * @var string The absolute base path
	 */
	private $base_path;

	/**
	 * @var string The absolute theme path
	 */
	private $theme_path;

	/**
	 * @var string The theme URL
	 */
	private $theme_url;

	/**
	 * @var array Multidimensional associative array containing model data for
	 * 	- status_group
	 * 	- cat_group
	 * 	- upload_destination
	 * 	- field_group
	 * 	- channel
	 * 	Also contains field and column ids for:
	 * 	- custom_field
	 * 	- grid_field
	 */
	private $model_data;

	/**
	 * Constructor: sets the site_url and theme paths
	 *
	 * @param string $root_theme_path The root of the theme directory blah
	 *   (e.g. themes/, not themes/ee/ or themes/user/)
	 * @param array $userdata The userdata array
	 */
	public function __construct()
	{
		ee()->load->library('api');
		ee()->load->library('extensions');

		ee()->remove('functions');
		ee()->set('functions', new FunctionsStub());
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
	 * Set the theme path, most likely from a constant
	 * @param string $theme_path The theme path
	 */
	public function setBasePath($base_path)
	{
		$this->base_path = rtrim($base_path, '/').'/';
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

		$this->createTemplates($theme_name, $channel_set->template_preferences);
		$this->createStatusGroups($channel_set->status_groups);
		$this->createCategoryGroups($channel_set->category_groups);
		$this->createUploadDestinations($theme_name, $channel_set->upload_destinations);
		$this->createFieldGroups($theme_name);
		$this->createChannels($channel_set->channels);
		$this->createEntries($theme_name);
		$this->setConfigItems($channel_set->config);
		$this->setMemberTheme($theme_name);
		$this->installModules($channel_set->modules);
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
	 * Create the templates from the site theme
	 * @param string $theme_name The theme name
	 * @param array $template_preferences Array of objects representing the
	 * 	template preferences supplied by loadChannelSet
	 * @return void
	 */
	private function createTemplates($theme_name, $template_preferences)
	{
		$path_tmpl = SYSPATH.'user/templates/';

		// Create the default_site directory if it doesn't exist
		if ( ! is_dir($path_tmpl.'default_site'))
		{
			$old_umask = umask(0);
			mkdir($path_tmpl.'default_site', DIR_WRITE_MODE);
			umask($old_umask);
		}

		chmod($path_tmpl.'default_site', DIR_WRITE_MODE);

		$theme_template_dir = $this->theme_path."ee/site/{$theme_name}/templates/";
		foreach (directory_map($theme_template_dir) as $directory => $contents)
		{
			$from_dir = $theme_template_dir.$directory.'/';
			$to_dir = $path_tmpl."default_site/{$directory}/";

			if ( ! is_dir($to_dir))
			{
				$old_umask = umask(0);
				mkdir($to_dir, DIR_WRITE_MODE);
				umask($old_umask);
			}

			// Copy partials over and force saving them to the database
			$partials = array('_partials' => 'Snippet', '_variables' => 'GlobalVariable');
			if (in_array($directory, array_keys($partials)))
			{
				foreach ($contents as $filename)
				{
					copy($from_dir.$filename, $to_dir.$filename);
				}

				// Load all of the partials to save them to the db
				ee('Model')->make($partials[$directory])->loadAll();
			}
			// Copy over templates and create them as well
			else
			{
				$group = ee('Model')->make('TemplateGroup');
				$group_name = str_replace('.group', '', $directory);
				$group->site_id = 1;
				$group->group_name = $group_name;

				if (isset($template_preferences->$group_name->preferences->is_site_default)
					&& $template_preferences->$group_name->preferences->is_site_default == TRUE)
				{
					$group->is_site_default = 'y';
				}

				$group->save();

				foreach ($contents as $filename)
				{
					copy($from_dir.$filename, $to_dir.$filename);

					$file = new \SplFileInfo($to_dir.$filename);
					$template_name = $file->getBasename('.'.$file->getExtension());

					$template = ee('Model')->make('Template');
					$template->group_id = $group->group_id;
					$template->template_name = $template_name;
					$template->template_data = file_get_contents($file->getRealPath());
					$template->template_type = ($file->getExtension() != 'html') ? $file->getExtension() : 'webpage';
					$template->last_author_id = 0;
					$template->edit_date = time();
					$template->site_id = 1;
					$template = $this->setTemplatePreferences(
						$template,
						(isset($template_preferences->$group_name->$template_name))
							? $template_preferences->$group_name->$template_name
							: new \stdClass()
					);
					$template->save();
				}
			}
		}
	}

	/**
	 * Set a template's preferences
	 * @param Template $template The template object representing the
	 * 	not-yet-saved template
	 * @param Object $template_preferences Just this template's preferences,
	 * 	pulled from the loadChannelSet object
	 * @return Template The modified template object with preferences in place
	 */
	private function setTemplatePreferences($template, $template_preferences)
	{
		// Set caching
		if (isset($template_preferences->preferences->cache)
			&& isset($template_preferences->preferences->refresh))
		{
			$template->cache = ($template_preferences->preferences->cache == 'y')
				? 'y'
				: 'n';

			$refresh = $template_preferences->preferences->refresh;
			$template->refresh = (is_int($refresh) || ctype_digit($refresh))
				? $refresh
				: 60;
		}

		// Set PHP
		if (isset($template_preferences->preferences->allow_php)
			&& $template_preferences->preferences->allow_php == 'y'
			&& isset($template_preferences->preferences->php_parse_location)
			&& in_array($template_preferences->preferences->php_parse_location, array('input', 'output')))
		{
			$template->allow_php = 'y';
			$template->php_parse_location = $template_preferences->preferences->php_parse_location;
		}

		// Set template access
		if (isset($template_preferences->access))
		{
			$member_groups = ee('Model')->get('MemberGroup')
				->filter('site_id', 1)
				->filter('group_id', '!=', 1)
				->all();

			$access = $template_preferences->access;
			$template->NoAccess = $member_groups->filter(function($group) use ($access) {
				return ($access->{$group->group_title} == 'n');
			});
		}

		return $template;
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

			$this->model_data['status_group'][$status_group->group_name] = $status_group;

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

	/**
	 * Create the category groups
	 * @param array $category_groups Array of objects representing the category
	 * 	groups supplied by loadChannelSet
	 * @return void
	 */
	private function createCategoryGroups($category_groups)
	{
		foreach ($category_groups as $category_group_data)
		{
			$cat_group = ee('Model')->make('CategoryGroup');
			$cat_group->site_id = 1;
			$cat_group->sort_order = (isset($category_group_data->sort_order))
				? $category_group_data->sort_order
				: 'a';
			$cat_group->group_name = $category_group_data->name;
			$cat_group->save();

			$this->model_data['cat_group'][$cat_group->group_name] = $cat_group;

			foreach ($category_group_data->categories as $index => $category_name)
			{
				$category = ee('Model')->make('Category');
				$category->group_id = $cat_group->group_id;
				$category->site_id = 1;
				$category->cat_name = $category_name;
				$category->cat_url_title = strtolower(str_replace(' ', '-', $category_name));
				$category->parent_id = 0;

				if ($cat_group->sort_order == 'c')
				{
					$category->cat_order = $index + 1;
				}

				$category->save();
			}
		}
	}

	/**
	 * Create the upload locations
	 * @param string $theme_name The name of the theme, used for pulling in
	 * 	images and files
	 * @param array $upload_locations Array of objects representing the upload
	 * 	locations supplied by loadChannelSet
	 * @return void
	 */
	private function createUploadDestinations($theme_name, $upload_locations)
	{
		$img_url = "{base_url}themes/ee/site/{$theme_name}/";
		$img_path = $this->theme_path."ee/site/{$theme_name}/";

		foreach ($upload_locations as $upload_location_data)
		{
			$path = $img_path.$upload_location_data->path;

			$upload_destination = ee('Model')->make('UploadDestination');
			$upload_destination->site_id = 1;
			$upload_destination->name = $upload_location_data->name;
			$upload_destination->url = $img_url.$upload_location_data->path;
			$upload_destination->server_path = str_replace($this->base_path, '{base_path}', $path);
			$upload_destination->save();

			$this->model_data['upload_destination'][strtolower($upload_destination->name)] = $upload_destination;

			foreach (directory_map($path) as $filename)
			{
				if ( ! is_array($filename) && is_file($path.'/'.$filename))
				{
					$filepath = $path.'/'.$filename;
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

	/**
	 * Create the field groups and fields
	 * @param string $theme_name The name of the theme, used for pulling in
	 * 	custom fields
	 * @return void
	 */
	private function createFieldGroups($theme_name)
	{
		$field_group_path = $this->theme_path."ee/site/{$theme_name}/custom_fields/";
		$field_groups = directory_map($field_group_path);

		// Sorting file listing as a temporary workaround where the order of
		// files may cause an exception
		ksort($field_groups);

		foreach ($field_groups as $group_name => $fields)
		{
			$field_group = ee('Model')->make('ChannelFieldGroup');
			$field_group->group_name = $group_name;
			$field_group->save();

			$this->model_data['field_group'][$field_group->group_name] = $field_group;

			sort($fields);
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
					ee()->load->model('grid_model');
					$columns = ee()->grid_model->get_columns_for_field($field->field_id, 'channel');

					foreach ($columns as $column)
					{
						$this->model_data['grid_field'][$field->field_id][$column['col_name']] = $column['col_id'];
					}
				}

				$this->model_data['custom_field'][$field->field_name] = $field->field_id;
			}
		}
	}

	/**
	 * Create the channels
	 * @param array $channels Array of objects representing the channels
	 * 	supplied by loadChannelSet
	 * @return void
	 */
	private function createChannels($channels)
	{
		foreach ($channels as $channel_data)
		{
			$channel = ee('Model')->make('Channel');
			$channel->title_field_label = lang('title');
			$channel->channel_name = strtolower($channel_data->channel_title);
			$channel->channel_title = $channel_data->channel_title;
			$channel->channel_lang = 'en';
			unset($channel_data->channel_title);

			foreach (array('field_group', 'status_group', 'cat_group') as $group_type)
			{
				if (isset($channel_data->$group_type))
				{
					$channel->$group_type = $this->model_data[$group_type][$channel_data->$group_type]->group_id;
					unset($channel_data->$group_type);
				}
			}

			foreach ($channel_data as $pref_key => $pref_value)
			{
				$channel->$pref_key = $pref_value;
			}

			$channel->save();

			$this->model_data['channel'][$channel->channel_name] = $channel;
		}
	}

	/**
	 * Create the channel entries
	 * @param string $theme_name The name of the theme, used for pulling in
	 * 	channel entries
	 * @return void
	 */
	public function createEntries($theme_name)
	{
		ee()->load->library('session');
		ee()->session->userdata['group_id'] = 1;

		$entry_data_path = $this->theme_path."ee/site/{$theme_name}/channel_entries/";

		foreach (directory_map($entry_data_path) as $channel_name => $channel_entries)
		{
			foreach ($channel_entries as $filename)
			{
				$entry_data = json_decode(file_get_contents($entry_data_path.$channel_name.'/'.$filename));

				$entry = ee('Model')->make('ChannelEntry');
				$entry->setChannel($this->model_data['channel'][$channel_name]);
				$entry->site_id =  1;
				$entry->author_id = 1;
				$entry->ip_address = ee()->input->ip_address();
				$entry->versioning_enabled = $this->model_data['channel'][$channel_name]->enable_versioning;
				$entry->sticky = FALSE;
				$entry->allow_comments = (isset($entry_data->allow_comments))
					? $entry_data->allow_comments
					: 'y';

				$entry->title = $entry_data->title;
				$entry->url_title = $entry_data->url_title;
				$entry->status = $entry_data->status;

				$entry->year = date('Y');
				$entry->month = date('m');
				$entry->day = date('d');
				$entry->entry_date = ee()->localize->now;

				$post_mock = array();

				foreach ($entry_data->custom_fields as $key => $val)
				{
					if (is_string($val))
					{
						$field_col_name = "field_id_{$this->model_data['custom_field'][$key]}";
						$post_mock[$field_col_name] = $this->replaceUploadDestination($val);
					}
					else
					{
						foreach($val->rows as $row_index => $grid_row)
						{
							$row_index = $row_index + 1;
							foreach ($grid_row as $col_name => $col_value)
							{
								$column_id = 'col_id_'.$this->model_data['grid_field'][$this->model_data['custom_field'][$key]][$col_name];
								$post_mock["field_id_{$this->model_data['custom_field'][$key]}"]
										["rows"]
										["new_row_{$row_index}"]
										[$column_id]
									= $this->replaceUploadDestination($col_value);
							}
						}
					}
				}

				if (isset($entry_data->categories))
				{
					$categories = array();
					$cat_groups = $this->model_data['channel'][$channel_name]
						->CategoryGroups
						->pluck('group_id');

					$categories = ee('Model')->get('Category')
						->filter('cat_name', 'IN', $entry_data->categories)
						->filter('group_id', 'IN', $cat_groups)
						->all();

					$entry->Categories = $categories;
				}

				$entry->set($post_mock);
				$entry->save();

				if (isset($entry_data->comments))
				{
					$this->createComments($entry, $entry_data->comments);
				}
			}
		}
	}

	/**
	 * Create comments for a given entry
	 * @param ChannelEntry $entry The ChannelEntry model representing the
	 * channel entry
	 * @param array $channels Array of objects representing the comments
	 * 	supplied by loadChannelSet
	 * @return ChannelEntry The modified $entry object
	 */
	private function createComments($entry, $comments)
	{
		$author = ee('Model')->get('Member', 1)->first();

		foreach ($comments as $index => $comment_data)
		{
			$comment_data = array_merge(
				array(
					'site_id'      => 1,
					'entry_id'     => $entry->entry_id,
					'channel_id'   => $entry->channel_id,
					'author_id'    => 0,
					'status'       => 'o',
					'url'          => '',
					'location'     => '',
					'ip_address'   => '127.0.0.1',
					'comment_date' => time() + $index
				),
				(array) $comment_data
			);

			if ($comment_data['author_id'] == 1)
			{
				$comment_data['name']     = $author->screen_name;
				$comment_data['email']    = $author->email;
				$comment_data['url']      = $this->site_url;
				$comment_data['location'] = $author->location;
			}

			$comment = ee('Model')->make('Comment', $comment_data);

			if ($comment_data['author_id'] == 1)
			{
				$comment->Author = $author;
			}

			$comment->save();
		}

		return $entry;
	}

	/**
	 * Replace occurences of {filedir_<destination name>} with the correct ID
	 * @param string $str The string to look through for {filedir_<dest>}
	 * @return string The string with the correct filedir ID
	 */
	private function replaceUploadDestination($str)
	{
		if (stristr($str, '{filedir_') !== FALSE)
		{
			$model_data = $this->model_data;
			$str = preg_replace_callback(
				'/\{filedir_(.*?)\}/',
				function($matches) use ($model_data) {
					$id = $model_data['upload_destination'][$matches[1]]->id;
					return "{filedir_{$id}}";
				},
				$str
			);
		}

		return $str;
	}

	/**
	 * Set the config items
	 * @param array $config Associative array of config items
	 * @return void
	 */
	private function setConfigItems($config)
	{
		ee()->config->update_site_prefs((array) $config, array(1));
	}

	/**
	 * Copy over the member theme and set it up
	 * @param string $theme_name The name of the theme, used for pulling in
	 * 	channel entries
	 * @return void
	 */
	private function setMemberTheme($theme_name)
	{
		$to_dir = $this->theme_path."ee/member/{$theme_name}/";
		$from_dir = $this->theme_path."ee/site/{$theme_name}/members/";

		if ( ! is_dir($from_dir))
		{
			return;
		}

		if (is_dir($to_dir))
		{
			foreach (directory_map($to_dir) as $directory => $filename)
			{
				if (is_string($directory))
				{
					foreach ($filename as $filename)
					{
						unlink($to_dir.$directory.'/'.$filename);
					}

					@rmdir($to_dir.$directory);
				}
				else
				{
					unlink($to_dir.$filename);
				}
			}
		}
		else
		{
			mkdir($to_dir, DIR_WRITE_MODE);
		}

		foreach (directory_map($from_dir) as $filename)
		{
			copy($from_dir.$filename, $to_dir.$filename);
		}

		ee()->config->update_site_prefs(array('member_theme' => $theme_name), array(1));
	}

	/**
	 * Install required modules
	 * @param array $modules Array of required modules
	 * @return void
	 */
	private function installModules($modules)
	{
		ee()->load->library('addons');
		ee()->addons->install_modules($modules);
	}
}

class FunctionsStub
{
	public function clear_caching($type)
	{
		return TRUE;
	}
}


// EOF
