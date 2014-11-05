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
	protected static $_gateway_names = array('UploadPrefGateway');

	protected static $_relationships = array(
		'Site' => array(
			'type' => 'many_to_one'
		),
		'NoAccess' => array(
			'type' => 'many_to_many',
			'model' => 'MemberGroup'
		),
		'FileDimension' => array(
			'type' => 'one_to_many'
		)
	);

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


	public function getSite()
	{
		return $this->getRelated('Site');
	}

	public function setSite(Site $site)
	{
		return $this->setRelated('Site', $site);
	}

	public function getNoAccess()
	{
		return $this->getRelated('NoAccess');
	}

	public function setNoAccess($no_access)
	{
		return $this->setRelated('NoAccess', $no_access);
	}

	public function getFileDimension()
	{
		return $this->getRelated('FileDimension');
	}

	public function setFileDimension($file_dimension)
	{
		return $this->setRelated('FileDimension', $file_dimension);
	}
}
