<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Consent;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * Consent Request Version Model
 */
class ConsentRequestVersion extends Model {

	protected static $_primary_key = 'consent_request_version_id';
	protected static $_table_name = 'consent_request_versions';

	protected static $_typed_columns = [
		'consent_request_version_id' => 'int',
		'consent_request_id'         => 'int',
		'create_date'                => 'timestamp',
		'author_id'                  => 'int',
	];

	protected static $_relationships = [
		'ConsentRequest' => [
			'type' => 'belongsTo',
		],
		'CurrentVersion' => [
			'type' => 'belongsTo',
			'model' => 'ConsentRequest',
			'to_key' => 'consent_request_version_id'
		],
		'Consents' => [
			'type' => 'hasMany',
			'model' => 'Consent',
		],
		'Author' => [
			'type' => 'belongsTo',
			'model' => 'Member',
			'from_key' => 'author_id',
			'weak' => TRUE
		],
	];

	protected static $_validation_rules = [
		'create_date'    => 'required',
		'author_id'      => 'required',
	];

	// protected static $_events = [];

	// Properties
	protected $consent_request_version_id;
	protected $consent_request_id;
	protected $request;
	protected $request_format;
	protected $create_date;
	protected $author_id;

	public function render()
	{
		ee()->load->library('typography');
		ee()->typography->initialize(array(
			'bbencode_links' => FALSE,
			'parse_images'	=> FALSE,
			'parse_smileys'	=> FALSE
		));

		return ee()->typography->parse_type($this->request, array(
			'text_format'    => $this->request_format,
			'html_format'    => 'all',
			'auto_links'	 => 'n',
			'allow_img_url'  => 'y'
		));
	}

}

// EOF
