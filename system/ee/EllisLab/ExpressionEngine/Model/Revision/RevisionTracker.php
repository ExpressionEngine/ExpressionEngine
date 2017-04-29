<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Model\Revision;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

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
