<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Template Model
 */
class Template_model extends CI_Model {

	// --------------------------------------------------
	// These methods utilized the Template_Entity object.
	// --------------------------------------------------

	// Private on purpose, no one should know or override,
	// this hopefully won't be there long.
	// Temporary stand in cache of site preferences, to be
	// used with multi-site manager.  Just here until we
	// can rewrite EE_Config to do this properly.
	private $site_prefs_cache = array();

	/**
	 * Fetch Templates and return Template_Entities
	 *
	 * Fetches templates from the database (loading the data from
	 * a saved file if appropriate) and returns an array of
	 * populated Template_Entity objects.
	 *
	 * @param	mixed[]	$fields	Optional. An array of fields and values that
	 * 		can be used to filter the entities returned. Any field from
	 * 		Template_Entity may be used.  The key of the array is the field
	 * 		name and the values of the array are the values to check.  Values
	 * 		are only checked for exact equality and will be connected with
	 * 		'AND'.
	 * @param	boolean	$load_groups	Optional. If set, then associated
	 * 		Template_Group_Entity objects Will be loaded and set on the
	 * 		returned Template_Entity objects.
	 *
	 * @return	Template_Entity[]	An array of Template Entities matching
	 * 		the fields requested.
	 */
	public function fetch(array $fields=array(), $load_groups=FALSE)
	{
		$templates = $this->fetch_from_db($fields, $load_groups);
		if ($this->config->item('save_tmpl_files') == 'y')
		{
			foreach($templates as $template)
			{
				$this->_load_template_file($template);
			}
		}
		return $templates;
	}

	/**
	 * Load the Template from the Appropriate File
	 *
	 * Takes a populated Template_Entity and finds the file in which the
	 * template has been saved.  It then loads the file's content into
	 * Template_Entity::template_data.  If the $only_load_last_edit parameter
 	 * is passed as TRUE, then it will only load the file if the file was
	 * edited more recently than the template in the database. Otherwise
	 * it will bail out.
	 *
	 * @param	Template_Entity	$template	The populated template object you
	 * 		wish to load from a file.
	 * @param	boolean	$only_load_last_edit	When passed as TRUE, will only
	 * 		load the file if the file was edited more recently than the database.
	 *
	 * @return	void
	 */
	protected function _load_template_file(Template_Entity $template, $only_load_last_edit=FALSE)
	{
		// Fetch the site config if we need it
		$site_switch = FALSE;
		if ($this->config->item('site_id') != $template->site_id)
		{
			$site_switch = $this->config->config;

			if (isset($this->site_prefs_cache[$template->site_id]))
			{
				$this->config->config = $this->site_prefs_cache[$template->site_id];
			}
			else
			{
				$this->config->site_prefs('', $template->site_id);
				$this->site_prefs_cache[$template->site_id] = $this->config->config;
			}
		}

		// Get the filepath to the template's saved file.
		$this->load->library('api');
		$this->legacy_api->instantiate('template_structure');
		$filepath = PATH_TMPL . $this->config->item('site_short_name') . DIRECTORY_SEPARATOR
			. $template->get_group()->group_name . '.group' . DIRECTORY_SEPARATOR . $template->template_name
			. $this->api_template_structure->file_extensions($template->template_type);


		// We don't need the other site's configuration values anymore, so
		// reset them.  If we do this here we only have to do it once,
		// otherwise we have to do it everywhere we bail out.
		if ($site_switch !== FALSE)
		{
			$this->config->config = $site_switch;
		}

		if (file_exists($filepath))
		{
			if ($only_load_last_edit)
			{
				$this->load->helper('file');
				$file_date = get_file_info($filepath, 'date');
				if ($file_date !== FALSE && $template->edit_date >= $file_date['date'])
				{
					return;
				}
			}

			$template->template_data = file_get_contents($filepath);
			$template->loaded_from_file = TRUE;
		}

	}

	/**
	 * Fetch Template Entities from the Database
	 *
	 * Queries the database and returns an array of populated Template_Entity
	 * objects.  Does not load the files associated with those entities (if
	 * there are any), only loads the template stored in the database.
	 *
	 * @param	mixed[]	$fields	Optional. An array of fields and values that
	 * 		can be used to filter the entities returned. Any field from
	 * 		Template_Entity may be used.  The key of the array is the field
	 * 		name and the values of the array are the values to check.  Values
	 * 		are only checked for exact equality and will be connected with
	 * 		'AND'. So array('template_name' => 'home', 'site_id' => 1) becomes
	 * 		"WHERE template_name = 'home' AND site_id = 1".
	 * @param	boolean	$load_groups	Optional. If set, then associated
	 * 		Template_Group_Entity objects Will be loaded and set on the
	 * 		returned Template_Entity objects.
	 *
	 * @return	Template_Entity[]
	 *
	 */
	public function fetch_from_db(array $fields=array(), $load_groups=FALSE)
	{
		$this->db->select();
		$this->db->from('templates');

		if ($load_groups)
		{
			$this->db->join('template_groups', 'templates.group_id = template_groups.group_id');
		}

		foreach ($fields as $field=>$value)
		{
			$this->db->where($field, $value);
		}

		return $this->entities_from_db_result($this->db->get(), $load_groups);
	}

	/**
	 * Convert Database Result Into Entity Objects
	 *
	 * Takes a database result object and returns an array of Template_Entity
	 * objects. Database result must be a query against the templates table. If
	 * no rows are found in the result, an empty array will be returned.
	 *
	 * @param	DB_result	The result object from a query against the templates
	 *						table.
	 * @param	boolean	Optional. If set, then associated Template_Group_Entity
	 *					objects Will be loaded and set on the returned
	 *					Template_Entity objects.
	 *
	 * @return	Template_Entity[]
	 */
	public function entities_from_db_result($result, $load_groups=FALSE)
	{
		$entities = array();
		foreach ($result->result_array() as $row)
		{
			$entity = new Template_Entity($row);
			if ($load_groups)
			{
				$entity->set_group(new Template_Group_Entity($row));
			}
			$entities[] = $entity;
		}
		return $entities;
	}

	/**
	 * Fetch the Most Recently Edited Version of the Template (File or DB)
	 *
	 * Load the template entities from the database and, in the case that the
	 * saved file was editted more recently than the version in the database,
	 * override the entity's content with the version in the file.
	 *
	 * @param	mixed[] $fields	Optional. An array of fields and values that
	 * 		can be used to filter the entities returned. Any field from
	 * 		Template_Entity may be used.  The key of the array is the field
	 * 		name and the values of the array are the values to check.  Values
	 * 		are only checked for exact equality and will be connected with
	 * 		'AND'.
	 * @param	boolean	$load_groups	Optional. If set, then associated
	 * 		Template_Group_Entity objects Will be loaded and set on the
	 * 		returned Template_Entity objects.
	 *
	 * @return	Template_Entity[] The fetched array of Template Entities, from
	 * 		the most recently editted source.
	 */
	public function fetch_last_edit(array $fields=array(), $load_groups=FALSE)
	{
		$templates = $this->fetch_from_db($fields, $load_groups);
		if ($this->config->item('save_tmpl_files') == 'y')
		{
			foreach($templates as $template)
			{
				$this->_load_template_file($template, TRUE);
			}
		}
		return $templates;
	}

	/**
	 * Saves an Entity to the Database/File
	 *
	 * Saves an entity to the database and, if file saving is enabled for that
	 * template, saves it to the appropriate file.
	 *
	 * @param Template_Entity The template you want to save.
	 */
	public function save_entity(Template_Entity $entity)
	{
		$this->save_to_database($entity);

		if ($this->config->item('save_tmpl_files') == 'y')
		{
			$this->save_to_file($entity);
		}
	}

	/**
	 * Save a Template_Entity to a File
	 *
	 * Saves a Template to a file.  Requires the Template to have the name
	 * and group set.  Group will need to have its Template name set.
	 *
	 * @param	Template_Entity	$template	A populated Template_Entity that
	 * 		also has its associated Template_Group_Entity populated and linked.
	 * 		At a minimum Template_Entity::$template_name,
	 * 		Template_Entity::template_type, Template_Entity::$template_data, and
	 * 		Template_Group_Entity::$group_name will need to be set.
	 *
	 * 	@return	boolean	TRUE on success, FALSE on failure.
	 */
	public function save_to_file(Template_Entity $template)
	{
		$site_switch = FALSE;
		if ($this->config->item('site_id') != $template->site_id)
		{
			$site_switch = $this->config->config;

			if (isset($this->site_prefs_cache[$template->site_id]))
			{
				$this->config->config = $this->site_prefs_cache[$template->site_id];
			}
			else
			{
				$this->config->site_prefs('', $template->site_id);
				$this->site_prefs_cache[$template->site_id] = $this->config->config;
			}
		}

		// check the main template path
		$basepath = PATH_TMPL;

		if ( ! is_dir($basepath) OR ! is_really_writable($basepath))
		{
			return FALSE;
		}

		$this->load->library('extensions');
		$this->load->library('api');
		$this->legacy_api->instantiate('template_structure');

		// add a site short name folder, in case MSM uses the same template path, and repeat
		$basepath .= $this->config->item('site_short_name');

		// At this point we don't need config anymore, so reset it.
		if ($site_switch !== FALSE)
		{
			$this->config->config = $site_switch;
		}


		if ( ! is_dir($basepath))
		{
			if ( ! mkdir($basepath, DIR_WRITE_MODE))
			{
				return FALSE;
			}
			chmod($basepath, DIR_WRITE_MODE);
		}

		// and finally with our template group
		$basepath .= DIRECTORY_SEPARATOR . $template->get_group()->group_name . '.group';

		if ( ! is_dir($basepath))
		{
			if ( ! mkdir($basepath, DIR_WRITE_MODE))
			{
				return FALSE;
			}
			chmod($basepath, DIR_WRITE_MODE);
		}

		$filename = $template->template_name . $this->api_template_structure->file_extensions($template->template_type);

		if ( ! $fp = fopen($basepath . DIRECTORY_SEPARATOR . $filename, FOPEN_WRITE_CREATE_DESTRUCTIVE))
		{
			return FALSE;
		}
		else
		{
			flock($fp, LOCK_EX);
			fwrite($fp, $template->template_data);
			flock($fp, LOCK_UN);
			fclose($fp);

			chmod($basepath . DIRECTORY_SEPARATOR . $filename, FILE_WRITE_MODE);
		}

		return TRUE;
	}

	/**
 	 * Save a Template_Entity to the Database
 	 *
 	 * Save a Template Entity to the database.  Converts the entity to an
 	 * array.  If it has an ID it updates it, otherwise it inserts it. A
 	 *
 	 * @param	Template_Entity	$entity	The Template_Entity to save to the
 	 * 		database.  If an ID is present, Template will be updated. If
 	 * 		not it will be inserted.
 	 *
 	 * @return	boolean	TRUE on success, FALSE on failure.
	 */
	public function save_to_database(Template_Entity $entity)
	{
		$entity->edit_date = ee()->localize->now;

		$data = $this->_entity_to_db_array($entity);
		if ($entity->template_id)
		{
			$this->db->where('site_id', $entity->site_id);
			$this->db->where('template_id', $entity->template_id);

			$this->db->update('templates', $data);
			return TRUE;
		}
		else
		{
			$this->db->insert('templates', $data);
			$entity->template_id = $this->db->insert_id();
			return TRUE;
		}
		throw new RuntimeException('Attempt to save a template to the database apparently failed.');
	}

	/**
	 * Convert a Template_Entity to an Array
	 *
	 * Converts a Template_Entity to an array for saving to the database.
	 *
	 * @param	Template_Entity	$entity	The Entity you wish to save to the database.
	 *
	 * @return	mixed[]	The associative array to send to CI_DB.
	 */
	protected function _entity_to_db_array(Template_Entity $entity)
	{
		$data = array(
			'template_id' => $entity->template_id,
			'site_id' => $entity->site_id,
			'group_id' => $entity->group_id,
			'template_name' => $entity->template_name,
			'template_type' => $entity->template_type,
			'template_data' => $entity->template_data,
			'template_notes' => $entity->template_notes,
			'edit_date' => $entity->edit_date,
			'last_author_id' => $entity->last_author_id,
			'cache' => $entity->cache,
			'refresh' => $entity->refresh,
			'no_auth_bounce' => $entity->no_auth_bounce,
			'enable_http_auth' => $entity->enable_http_auth,
			'allow_php' => $entity->allow_php,
			'php_parse_location' => $entity->php_parse_location,
			'hits' => $entity->hits,
			'protect_javascript' => $entity->protect_javascript
		);
		return $data;
	}

	/**
	 * Get Template Group Metadata
	 *
	 * @access	public
	 * @param	int
	 * @return	object
	 */
	function get_group_info($group_id)
	{
		return $this->db->get_where('template_groups', array('group_id' => $group_id));
	}

	/**
	 * Create Group
	 *
	 * Inserts a new template group into the db
	 *
	 * @access	public
	 * @param	array
	 * @return	int
	 */
	function create_group($data)
	{
		if ($data['is_site_default'] == 'y')
		{
			$this->db->where('site_id', $data['site_id']);
			$this->db->update('template_groups', array('is_site_default' => 'n'));
		}

		if ( ! isset($data['group_order']))
		{
			$data['group_order'] = $this->db->count_all('template_groups') + 1;
		}

		$this->db->insert('template_groups', $data);

		$template_group_id = $this->db->insert_id();

		// If a user other than Super Admin is creating a template group, give them
		// access to the group they just created
		if ($this->session->userdata('group_id') != 1)
		{
			$data = array();
			$data['group_id'] = $this->session->userdata('group_id');
			$data['template_group_id'] = $template_group_id;

			$this->db->insert('template_member_groups', $data);
		}

		return $template_group_id;
	}

	/**
	 * Create Template
	 *
	 * Inserts a new template into the db
	 *
	 * @access	public
	 * @param	array
	 * @return	int
	 */
	function create_template($data)
	{
		$this->db->insert('templates', $data);
		return $this->db->insert_id();
	}

	/**
	 * Get Template Groups
	 *
	 * @access	public
	 * @return	object
	 */
	function get_template_groups($site_id = 0)
	{
		if ($site_id !== 'all' OR $site_id === 0)
		{
			// If we're not looking for all sites, and there's no ID, use the
			// current site ID
			$this->db->where('site_id', $this->config->item('site_id'));
		}
		elseif (is_numeric($site_id))
		{
			// If it's numeric, use that in the where clause
			$this->db->where('site_id', (int) $site_id);
		}
		else
		{
			$this->db->order_by('site_id');
		}

		$this->db->order_by('group_order, group_name ASC');
		return $this->db->get('template_groups');
	}

	/**
	 * Update Template Group
	 *
	 * @access	public
	 * @param	int
	 * @param	array
	 * @return	void
	 */
	function update_template_group($group_id, $fields = array())
	{
		if (isset($fields['is_site_default']) && $fields['is_site_default'] == 'y')
		{
			$this->db->where('site_id', $fields['site_id']);
			$this->db->update('template_groups', array('is_site_default' => 'n'));
		}

		$this->db->where('group_id', $group_id);
		$this->db->set($fields);
		$this->db->update('template_groups');
	}

	/**
	 * Update Template Route
	 *
	 * @access	public
	 * @param	int
	 * @param	array
	 * @return	void
	 */
	function update_template_route($template_id, $fields = array())
	{
		$query = $this->db->get_where('template_routes', array('template_id' => $template_id), 1, 0);

		if($query->num_rows() == 0)
		{
			$fields['template_id'] = $template_id;
			$this->db->insert('template_routes', $fields);
		}
		else
		{
			$this->db->update('template_routes', $fields, array('template_id' => $template_id));
		}

		if ($this->db->affected_rows() != 1)
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Get Template Info
	 *
	 * @access	public
	 * @param	int
	 * @param	array
	 * @return	object
	 */
	function get_template_info($template_id, $fields = array())
	{
		if (count($fields) > 0)
		{
			$this->db->select(implode(",", $fields));
		}

		$this->db->join('template_routes AS tr', 'tr.template_id = templates.template_id', 'left');
		$this->db->where('templates.template_id', $template_id);
		$this->db->where('site_id', $this->config->item('site_id'));
		return $this->db->get('templates');
	}

	/**
	 * Rename Template File
	 *
	 * @access	public
	 * @return	bool
	 */
	function rename_template_file($template_group, $template_type, $old_name, $new_name)
	{
		$this->load->library('api');
		$this->legacy_api->instantiate('template_structure');
		$ext = $this->api_template_structure->file_extensions($template_type);

		$basepath  = PATH_TMPL;
		$basepath .= $this->config->item('site_short_name');
		$basepath .= '/'.$template_group.'.group';

		$existing_path = $basepath.'/'.$old_name.$ext;

		if ( ! file_exists($existing_path))
		{
			return FALSE;
		}

		return rename($existing_path, $basepath.'/'.$new_name.$ext);
	}

	/**
	 * Update Template Ajax
	 *
	 * Used when editing template prefs inline from the manager
	 *
	 * @access	public
	 * @return	array
	 */
	function update_template_ajax($template_id, $data)
	{
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('template_id', $template_id);

		$this->db->update('templates', $data);

		if ($this->db->affected_rows() != 1)
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Update Access Ajax
	 *
	 * Used when editing template access prefs inline from the manager
	 *
	 * @access	public
	 * @return	array
	 */
	function update_access_ajax($template_id, $m_group_id, $new_status)
	{
		// Check if it is in there
		$this->db->where('template_id', $template_id);
		$this->db->where('member_group', $m_group_id);
		$count = $this->db->count_all_results('template_no_access');

		// if they are allowed to access it - remove from no_access
		if ($new_status == 'y')
		{
			if ($count > 0)
			{
				$this->db->where('template_id', $template_id);
				$this->db->where('member_group', $m_group_id);
				$this->db->delete('template_no_access');
			}
		}
		else
		{
			if ($count == 0)
			{
				$this->db->insert('template_no_access', array(
					'template_id'	=> $template_id,
					'member_group'	=> $m_group_id
				));
			}
		}

		return TRUE;
	}

	/**
	 * Update Access Ajax Details
	 *
	 * Used when editing template access prefs inline from the manager
	 * @access	public
	 * @return	bool
	 */
	function update_access_details_ajax($template_id, $data)
	{
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('template_id', $template_id);

		$this->db->update('templates', $data);

		return TRUE;
	}

	/**
	 * Delete Template
	 *
	 * @access	public
	 * @param	int
	 * @return	bool
	 */
	function delete_template($template_id, $path = FALSE)
	{
		if ($path !== FALSE)
		{
			if ( ! @unlink($path))
			{
				return FALSE;
			}
		}

		$this->db->where('item_id', $template_id);
		$this->db->where('item_table', 'templates');
		$this->db->where('item_field', 'template_data');
		$this->db->delete('revision_tracker');

		$this->db->where('template_id', $template_id);
		$this->db->delete('template_no_access');

		$this->db->where('template_id', $template_id);
		$this->db->delete('templates');

		if ($this->db->affected_rows() == 1)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Get Templates
	 *
	 * @access	public
	 * @param	string
	 * @return	array
	 */
	function get_templates($site_id = NULL, $additional_fields = array(), $additional_where = array())
	{
		if ($site_id === NULL OR ! is_numeric($site_id))
		{
			$site_id = $this->config->item('site_id');
		}

		if ( ! is_array($additional_fields))
		{
			$additional_fields = array($additional_fields);
		}

		if (count($additional_fields) > 0)
		{
			$this->db->select(implode(',', $additional_fields));
		}

		$this->db->select("template_id, template_name, group_name");
		$this->db->from("templates");
		$this->db->join("template_groups", "templates.group_id = template_groups.group_id");
		$this->db->where('templates.site_id', $site_id);

		// add additional WHERE clauses
		foreach ($additional_where as $field => $value)
		{
			if (is_array($value))
			{
				$this->db->where_in($field, $value);
			}
			else
			{
				$this->db->where($field, $value);
			}
		}

		$this->db->order_by('group_name, template_name');
		$results = $this->db->get();

		return $results;
	}

	/**
	 * Get Snippets
	 *
	 * Return all Snippets
	 *
	 * @access	public
	 * @return	object
	 */
	function get_snippets()
	{
		$this->db->where('(site_id = '.$this->db->escape_str($this->config->item('site_id')).' OR site_id = 0)');
		$this->db->order_by('snippet_name');
		return $this->db->get('snippets');
	}

	/**
	 * Get Snippet
	 *
	 * Gets the details of a specific Snippet
	 *
	 * @access	public
	 * @param	string
	 * @return	array
	 */
	function get_snippet($snippet, $by_name = FALSE)
	{
		if (ctype_digit($snippet) && $by_name === FALSE)
		{
			$this->db->where('snippet_id', $snippet);
		}
		else
		{
			$this->db->where('snippet_name', $snippet);
		}

		$this->db->where('(site_id = '.$this->db->escape_str($this->config->item('site_id')).' OR site_id = 0)');

		$result = $this->db->get('snippets');

		if ($result->num_rows() != 1)
		{
			return FALSE;
		}

		// return an associative array for convenience
		return $result->row_array();
	}

	/**
	 * Check Snippet for Uniqueness
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function unique_snippet_name($snippet_name)
	{
		$this->db->where('snippet_name', $snippet_name);
		$results = $this->db->get('snippets');

		if ($results->num_rows() == 0)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Delete Snippet
	 *
	 * @access	public
	 * @param	int
	 * @return	int
	 */
	function delete_snippet($snippet_id)
	{
		$this->db->where('snippet_id', $snippet_id);
		$this->db->delete('snippets');
		return $this->db->affected_rows();
	}

	/**
	 * Get Global Variables
	 *
	 * Return all global variables
	 *
	 * @access	public
	 * @return	object
	 */
	function get_global_variables()
	{
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->order_by('variable_name');
		return $this->db->get('global_variables');
	}

	/**
	 * Get Global Variable
	 *
	 * Get the values of one global variable
	 *
	 * @access	public
	 * @param	integer
	 * @return	array
	 */
	function get_global_variable($variable_id = '')
	{
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('variable_id', $variable_id);
		$this->db->order_by('variable_name');
		$results = $this->db->get('global_variables');

		return $results;
	}

	/**
	 * Check Duplicate Global Variable Name
	 *
	 * Used to check for already existing global variables with the same name
	 *
	 * @access	public
	 * @param	string
	 * @return	boolean
	 */
	function check_duplicate_global_variable_name($variable_name = '')
	{
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('variable_name', $variable_name);
		$this->db->order_by('variable_name');
		$results = $this->db->get('global_variables');

		if ($results->num_rows() == 0)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Update Global Variable
	 *
	 * @access	public
	 * @param	integer
	 * @param	string
	 * @param	string
	 * @return	integer
	 */
	function update_global_variable($variable_id, $variable_name, $variable_data)
	{
		$this->db->set('variable_name', $variable_name);
		$this->db->set('variable_data', $variable_data);
		$this->db->set('site_id', $this->config->item('site_id'));
		$this->db->where('variable_id', $variable_id);

		$this->db->update('global_variables');

		return $this->db->affected_rows();
	}

	/**
	 * Create Global Variable
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	integer
	 */
	function create_global_variable($variable_name, $variable_data)
	{
		$this->db->set('variable_name', $variable_name);
		$this->db->set('variable_data', $variable_data);
		$this->db->set('site_id', $this->config->item('site_id'));

		$this->db->insert('global_variables');

		return $this->db->insert_id();
	}

	/**
	 * Delete Global Variable
	 *
	 * @access	public
	 * @param	integer
	 * @return	integer
	 */
	function delete_global_variable($variable_id)
	{
		$this->db->where('variable_id', $variable_id);
		$this->db->delete('global_variables');

		return $this->db->affected_rows();
	}

	/**
	 * Get Specialty Email Templates Summary
	 *
	 * Gets the ids and names of all specialty email templates
	 *
	 * @access	public
	 * @return	array
	 */
	function get_specialty_email_templates_summary()
	{
		$this->db->select('template_id, template_name');
		$this->db->from("specialty_templates");
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('template_name !=', "message_template");
		$this->db->where('template_name !=', "offline_template");
		$this->db->order_by('template_name');
		$results = $this->db->get();

		return $results;
	}

	/**
	 * Get Specialty Template Data
	 *
	 * Returns a specialty template
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function get_specialty_template($template_name)
	{
		$this->db->select('data_title, template_id, template_data, enable_template');
		$this->db->from("specialty_templates");
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('template_name', $template_name);
		$results = $this->db->get();

		return $results;
	}

	/**
	 * Get Specialty Template Variables
	 *
	 * Returns available variables to a given specialty template
	 *
	 * @access	public
	 * @param	string
	 * @return	array
	 */
	function get_specialty_template_vars($template_name)
	{
		$vars = array(
						'admin_notify_reg'						=> array('name', 'username', 'email', 'site_name', 'control_panel_url'),
						'admin_notify_entry'					=> array('channel_name', 'entry_title', 'entry_url', 'comment_url', 'cp_edit_entry_url', 'name', 'email'),
						'admin_notify_comment'					=> array('channel_name', 'entry_title', 'entry_id', 'url_title', 'channel_id', 'comment_url_title_auto_path',  'comment_url', 'comment', 'comment_id', 'name', 'url', 'email', 'location', 'unwrap}{delete_link}{/unwrap', 'unwrap}{close_link}{/unwrap', 'unwrap}{approve_link}{/unwrap'),
						'admin_notify_forum_post'				=> array('name_of_poster', 'forum_name', 'title', 'body', 'thread_url', 'post_url'),
						'mbr_activation_instructions'			=> array('name',  'username', 'email', 'activation_url', 'site_name', 'site_url'),
						'forgot_password_instructions'			=> array('name', 'username', 'reset_url', 'site_name', 'site_url'),
						'decline_member_validation'			=> array('name', 'username', 'site_name', 'site_url'),
						'validated_member_notify'				=> array('name', 'username', 'email', 'site_name', 'site_url'),
						'comment_notification'					=> array('name_of_commenter', 'name_of_recipient', 'channel_name', 'entry_title', 'entry_id', 'url_title', 'channel_id', 'comment_url_title_auto_path', 'comment_url', 'comment', 'notification_removal_url', 'site_name', 'site_url', 'comment_id'),

						'comments_opened_notification'					=> array('name_of_recipient', 'channel_name', 'entry_title', 'entry_id', 'url_title', 'channel_id', 'comment_url_title_auto_path', 'comment_url', 'notification_removal_url', 'site_name', 'site_url', 'total_comments_added', 'comments', 'name_of_commenter', 'comment_id', 'comment', '/comments'),

						'forum_post_notification'				=> array('name_of_recipient', 'name_of_poster', 'forum_name', 'title', 'thread_url', 'body', 'post_url'),
						'private_message_notification'			=> array('sender_name', 'recipient_name','message_subject', 'message_content', 'site_url', 'site_name'),
						'pm_inbox_full'							=> array('sender_name', 'recipient_name', 'pm_storage_limit','site_url', 'site_name'),
						'forum_moderation_notification'			=> array('name_of_recipient', 'forum_name', 'moderation_action', 'title', 'thread_url'),
						'forum_report_notification'				=> array('forum_name', 'reporter_name', 'author', 'body', 'reasons', 'notes', 'post_url')
					);

			return (isset($vars[$template_name])) ? $vars[$template_name] : array();
	}

	/**
	 * Update Specialty Template
	 *
	 * @access	public
	 * @param	integer
	 * @param	string
	 * @return	string
	 */
	function update_specialty_template($template_id, $template_data, $enable_template = 'y', $template_title = NULL)
	{
		$this->db->set('template_data', $template_data);
		$this->db->set('enable_template', $enable_template);

		if ($template_title)
		{
			$this->db->set('data_title', $template_title);
		}

		$this->db->where('template_id', $template_id);
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->update('specialty_templates');

		return $this->db->affected_rows();
	}

}

/**
 * A Prototype Database Entity
 *
 * A prototype database entity for use with the templates table. Properties
 * have a 1 to 1 correspondence with db table properties.  In addition, the
 * template knows whether or not it has been loaded from a file.
 */
class Template_Entity {
	/**
	 *
	 */
	protected $template_id;

	/**
	 *
	 */
	protected $site_id;

	/**
	 *
	 */
	protected $group_id;

	/**
	 *
	 */
	protected $template_name;

	/**
	 *
	 */
	protected $template_type;

	/**
	 *
	 */
	protected $template_data;

	/**
	 *
	 */
	protected $template_notes;

	/**
	 *
	 */
	protected $edit_date;

	/**
	 *
	 */
	protected $last_author_id;

	/**
	 *
	 */
	protected $cache;

	/**
	 *
	 */
	protected $refresh;

	/**
	 *
	 */
	protected $no_auth_bounce;

	/**
	 *
	 */
	protected $enable_http_auth;

	/**
	 *
	 */
	protected $allow_php;

	/**
	 *
	 */
	protected $php_parse_location;

	/**
	 *
	 */
	protected $hits;

	protected $protect_javascript;


	// ----------------------------------------------------
	//		Non-Database Properties
	// ----------------------------------------------------

	/**
	 * An entity only property that indicates whether this
	 * entity was loaded from a file or just the database. If
	 * TRUE then this template was loaded from a file, otherwise
	 * it was loaded from the database.
	 */
	protected $loaded_from_file = FALSE;

	// ----------------------------------------------------
	// 		Associated Entities
	// ----------------------------------------------------

	/**
	 * An instance of Template_Group_Entity representing the associated
	 * Template Group.
	 */
	protected $template_group;

	/**
	 *
	 */
	public function __construct(array $templates_row = array())
	{
		foreach ($templates_row as $property=>$value)
		{
			if ( property_exists($this, $property))
			{
				$this->{$property} = $value;
			}
		}
	}

	/**
	 *
	 */
	public function __get($name)
	{
		if ( strpos('_', $name) === 0  OR ! property_exists($this, $name))
		{
			throw new RuntimeException('Attempt to access non-existent property "' . $name . '"');
		}

		return $this->{$name};
	}

	/**
	 *
	 */
	public function __set($name, $value)
	{
		if ( strpos('_', $name) === 0 OR ! property_exists($this, $name))
		{
			throw new RuntimeException('Attempt to access non-existent property "' . $name . '"');
		}

		$this->{$name} = $value;
	}


	/**
 	 * Get Associated Template Group
 	 *
 	 * Gets an Entity representing this Template's Template Group.
 	 *
	 * @returns	Template_Group_Entity	The associated Template Group.
	 */
	public function get_group()
	{
		return $this->template_group;
	}

	/**
	 * Set this Template's Template Group
	 *
	 * Used to set the link to this Template's Template Group.
	 *
	 * @param	Template_Group_Entity	$group	The group Entity to link to
	 * 		this Template.
	 *
	 * @return $this
	 */
	public function set_group(Template_Group_Entity $group)
	{
		$this->template_group = $group;
		$this->group_id = $group->group_id;
		return $this;
	}
}
/**
 *
 */
class Template_Group_Entity
{
	/**
	 *
	 */
	private $group_id;

	/**
	 *
	 */
	private $site_id;

	/**
	 *
	 */
	private $group_name;

	/**
	 *
	 */
	private $group_order;

	/**
	 *
	 */
	private $is_site_default;

	/**
	 *
	 */
	public function __construct(array $groups_row = array())
	{
		foreach ($groups_row as $property=>$value)
		{
			if ( property_exists($this, $property))
			{
				$this->{$property} = $value;
			}
		}
	}

	/**
	 *
	 */
	public function __get($name)
	{
		if ( strpos('_', $name) === 0  OR ! property_exists($this, $name))
		{
			throw new RuntimeException('Attempt to access non-existent property "' . $name . '"');
		}

		return $this->{$name};
	}

	/**
	 *
	 */
	public function __set($name, $value)
	{
		if ( strpos('_', $name) === 0 OR ! property_exists($this, $name))
		{
			throw new RuntimeException('Attempt to access non-existent property "' . $name . '"');
		}

		$this->{$name} = $value;
	}

}

// EOF
