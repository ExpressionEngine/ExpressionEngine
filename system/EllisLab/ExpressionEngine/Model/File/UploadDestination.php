<?php

namespace EllisLab\ExpressionEngine\Model\File;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine File Upload Location Model
 *
 * A model representing one of many possible upload destintations to which
 * files may be uploaded through the file manager or from the publish page.
 * Contains settings for this upload destination which describe what type of
 * files may be uploaded to it, as well as essential information, such as the
 * server paths where those files actually end up.
 *
 * @package		ExpressionEngine
 * @subpackage	File
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class UploadDestination extends Model {

	protected static $_primary_key = 'id';
	protected static $_table_name = 'upload_prefs';

	protected static $_relationships = array(
		'Site' => array(
			'type' => 'belongsTo'
		),
		'NoAccess' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'MemberGroup',
			'pivot' => array(
				'table' => 'upload_no_access',
				'left' => 'upload_id',
				'right' => 'member_group'
			)
		),
		'Files' => array(
			'type' => 'hasMany',
			'model' => 'File',
			'to_key' => 'upload_location_id'
		),
		'FileDimensions' => array(
			'type' => 'hasMany',
			'model' => 'FileDimension',
			'to_key' => 'upload_location_id'
		)
	);

	protected $_property_overrides = array();

	protected $id;
	protected $site_id;
	protected $name;
	protected $server_path;
	protected $url;
	protected $allowed_types;
	protected $max_size;
	protected $max_height;
	protected $max_width;
	protected $properties;
	protected $pre_format;
	protected $post_format;
	protected $file_properties;
	protected $file_pre_format;
	protected $file_post_format;
	protected $cat_group;
	protected $batch_location;

	/**
	 * Because of the 'upload_preferences' Config value, the data in the DB
	 * is not always authoritative. So we will need to get any override data
	 * from the Config object
	 *
	 * @see Entity::_construct()
	 * @param array $data An associative array of property data
	 * @return void
	 */
	public function __construct(array $data = array())
	{
		parent::__construct($data);

		// @TODO THOU SHALT INJECT ALL THY DEPENDENCIES
		if (ee()->config->item('upload_preferences') !== FALSE)
		{
			$this->_property_overrides = ee()->config->item('upload_preferences');
		}
	}

	/**
	 * Returns the propety value using the overrides if present
	 *
	 * @param str $name The name of the property to fetch
	 * @throws InvalidArgumentException if the property does not exist
	 * @return mixed The value of the property
	 */
	public function __get($name)
	{
		$value = parent::__get($name);

		// Check if have an override for this directory and that it's an
		// array (as it should be)
		if (isset($this->_property_overrides[$this->id])
			&& is_array($this->_property_overrides[$this->id])
			&& array_key_exists($name, $this->_property_overrides[$this->id]))
		{
			$value = $this->_property_overrides[$this->id][$name];
		}

		return $value;
	}

}