<?php

namespace EllisLab\ExpressionEngine\Model\Revision;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Revision Tracker Model
 *
 * @package		ExpressionEngine
 * @subpackage	Revision
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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
			'from_key' => 'item_author_id'
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
