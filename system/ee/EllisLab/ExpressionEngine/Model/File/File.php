<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\File;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * File Model
 *
 * A model representing one of many possible upload destintations to which
 * files may be uploaded through the file manager or from the publish page.
 * Contains settings for this upload destination which describe what type of
 * files may be uploaded to it, as well as essential information, such as the
 * server paths where those files actually end up.
 */
class File extends Model {

	protected static $_primary_key = 'file_id';
	protected static $_table_name = 'files';
	protected static $_events = array('beforeDelete');
	protected static $_binary_comparisons = array('file_name');

	protected static $_hook_id = 'file';

	protected static $_relationships = array(
		'Site' => array(
			'type' => 'belongsTo'
		),
		'UploadDestination' => array(
			'type' => 'belongsTo',
			'to_key' => 'id',
			'from_key' => 'upload_location_id',
		),
		'UploadAuthor' => array(
			'type'     => 'BelongsTo',
			'model'    => 'Member',
			'from_key' => 'uploaded_by_member_id'
		),
		'ModifyAuthor' => array(
			'type'     => 'BelongsTo',
			'model'    => 'Member',
			'from_key' => 'modified_by_member_id'
		),
		'Categories' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'Category',
			'pivot' => array(
				'table' => 'file_categories',
				'left' => 'file_id',
				'right' => 'cat_id'
			)
		),
	);

	protected static $_validation_rules = array(
		'title'       => 'xss',
		'description' => 'xss',
		'credit'      => 'xss',
		'location'    => 'xss',
	);

	protected $file_id;
	protected $site_id;
	protected $title;
	protected $upload_location_id;
	protected $mime_type;
	protected $file_name;
	protected $file_size;
	protected $description;
	protected $credit;
	protected $location;
	protected $uploaded_by_member_id;
	protected $upload_date;
	protected $modified_by_member_id;
	protected $modified_date;
	protected $file_hw_original;

	public function get__width()
	{
		$dimensions = explode(" ", $this->getProperty('file_hw_original'));
		return $dimensions[1];
	}

	public function get__height()
	{
		$dimensions = explode(" ", $this->getProperty('file_hw_original'));
		return $dimensions[0];
	}

	public function get__file_hw_original()
	{
		if (empty($this->file_hw_original))
		{
			ee()->load->library('filemanager');
			$image_dimensions = ee()->filemanager->get_image_dimensions($this->getAbsolutePath());
			if ($image_dimensions !== FALSE)
			{
				$this->setRawProperty('file_hw_original', $image_dimensions['height'] . ' ' . $image_dimensions['width']);
			}
		}

		return $this->file_hw_original;
	}

	/**
	 * Uses the file's mime-type to determine if the file is an image or not.
	 *
	 * @return bool TRUE if the file is an image, FALSE otherwise
	 */
	public function isImage()
	{
		return (strpos($this->mime_type, 'image/') === 0);
	}

	/**
	 * Uses the file's mime-type to determine if the file is an SVG or not.
	 *
	 * @return bool TRUE if the file is an SVG, FALSE otherwise
	 */
	public function isSVG()
	{
		return (strpos($this->mime_type, 'image/svg') === 0);
	}

	/**
	 * Uses the file's upload destination's server path to compute the absolute
	 * path of the file
	 *
	 * @return string The absolute path to the file
	 */
	public function getAbsolutePath()
	{
		return rtrim($this->UploadDestination->server_path, '/') . '/' . $this->file_name;
	}

	/**
	 * Uses the file's upload destination's server path to compute the absolute
	 * thumbnail path of the file
	 *
	 * @return string The absolute path to the file
	 */
	public function getAbsoluteThumbnailPath()
	{
		return rtrim($this->UploadDestination->server_path, '/') . '/_thumbs/' . $this->file_name;
	}

	/**
	 * Uses the file's upload destination's url to compute the absolute URL of
	 * the file
	 *
	 * @return string The absolute URL to the file
	 */
	public function getAbsoluteURL()
	{
		return rtrim($this->UploadDestination->url, '/') . '/' . rawurlencode($this->file_name);
	}

	/**
	 * Uses the file's upload destination's URL to compute the absolute thumbnail
	 *  URL of the file
	 *
	 * @return string The absolute thumbnail URL to the file
	 */
	public function getAbsoluteThumbnailURL()
	{
		if ( ! file_exists($this->getAbsoluteThumbnailPath()))
		{
			return $this->getAbsoluteURL();
		}

		return rtrim($this->UploadDestination->url, '/') . '/_thumbs/' . rawurlencode($this->file_name);
	}

	public function getThumbnailUrl()
	{
		return $this->getAbsoluteThumbnailURL();
	}

	public function onBeforeDelete()
	{
		if ($this->exists())
		{
			// Remove the file
			unlink($this->getAbsolutePath());
		}

		// Remove the thumbnail if it exists
		if (file_exists($this->getAbsoluteThumbnailPath()))
		{
			unlink($this->getAbsoluteThumbnailPath());
		}

		// Remove any manipulated files as well
		foreach ($this->UploadDestination->FileDimensions as $file_dimension)
		{
			$file = rtrim($file_dimension->getAbsolutePath(), '/') . '/' . $this->file_name;

			if (file_exists($file))
			{
				unlink($file);
			}
		}
	}

	/**
	* Determines if the member group (by ID) has access permission to this
	* upload destination.
	* @see UploadDestination::memberGroupHasAccess
	*
	* @throws InvalidArgumentException
	* @param int|MemberGroup $group_id The Member Group ID
	* @return bool TRUE if access is granted; FALSE if access denied
	*/
	public function memberGroupHasAccess($group)
	{
		$dir = $this->UploadDestination;
		if ( ! $dir)
		{
			return FALSE;
		}

		return $dir->memberGroupHasAccess($group);
	}

	/**
	 * Determines if the file exists
	 *
	 * @return bool TRUE if it does FALSE otherwise
	 */
	public function exists()
	{
		return file_exists($this->getAbsolutePath());
	}

	/**
	 * Determines if the file is writable
	 *
	 * @return bool TRUE if it is FALSE otherwise
	 */
	public function isWritable()
	{
		return is_writable($this->getAbsolutePath());
	}

	/**
	 * Cleans the values by stripping tags and trimming
	 *
	 * @param string $str The string to be cleaned
	 * @return string A clean string
	 */
	private function stripAndTrim($str)
	{
		return trim(strip_tags($str));
	}

	public function set__title($value)
	{
		$this->setRawProperty('title', $this->stripAndTrim($value));
	}

	public function set__description($value)
	{
		$this->setRawProperty('description', $this->stripAndTrim($value));
	}

	public function set__credit($value)
	{
		$this->setRawProperty('credit', $this->stripAndTrim($value));
	}

	public function set__location($value)
	{
		$this->setRawProperty('location', $this->stripAndTrim($value));
	}

	/**
	 * Category setter for convenience to intercept the
	 * 'categories' post array.
	 */
	public function setCategoriesFromPost($categories)
	{
		// Currently cannot get multiple category groups through relationships
		$cat_groups = array();

		if ($this->UploadDestination->cat_group)
		{
			$cat_groups = explode('|', $this->UploadDestination->cat_group);
		}

		if (empty($categories))
		{
			$this->Categories = NULL;

			return;
		}

		$set_cats = array();

		// Set the data on the fields in case we come back from a validation error
		foreach ($cat_groups as $cat_group)
		{
			if (array_key_exists('cat_group_id_'.$cat_group, $categories))
			{
				$group_cats = $categories['cat_group_id_'.$cat_group];

				$cats = implode('|', $group_cats);

				$group_cat_objects = $this->getModelFacade()
					->get('Category')
					->filter('site_id', ee()->config->item('site_id'))
					->filter('cat_id', 'IN', $group_cats)
					->all();

				foreach ($group_cat_objects as $cat)
				{
					$set_cats[] = $cat;
				}
			}
		}

		$this->Categories = $set_cats;
	}
}

// EOF
