<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Revision;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * Revision Tracker Model
 */
class RevisionTracker extends Model {

	protected static $_primary_key = 'tracker_id';
	protected static $_table_name = 'revision_tracker';

	protected static $_typed_columns = array(
		'tracker_id'     => 'int',
		'item_id'        => 'int',
		'item_date'      => 'int',
		'item_author_id' => 'int'
	);

	protected static $_relationships = array(
		'Template' => array(
			'type' => 'BelongsTo',
			'from_key' => 'item_id',
		),
		'Author' => array(
			'type' => 'belongsTo',
			'model' => 'Member',
			'from_key' => 'item_author_id',
			'weak' => TRUE
		),
	);

	protected $tracker_id;
	protected $item_id;
	protected $item_table;
	protected $item_field;
	protected $item_date;
	protected $item_author_id;
	protected $item_data;

	public function getAuthorName()
	{
		return ($this->item_author_id && $this->Author) ? $this->Author->getMemberName() : '';
	}
}

// EOF
