<?php
namespace EllisLab\ExpressionEngine\Service\Model\Relationship\Types;

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
 * ExpressionEngine One-To-Many Relationship
 *
 * The one-to-many relationship type.
 *
 * @package		ExpressionEngine
 * @subpackage	Model\Relationship\Types
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class OneToMany extends AbstractRelationship {

	public $type	= 'one_to_many';
	public $inverse	= 'many_to_one';

	/**
	 * Set the related ids to correclty connect the models.
	 *
	 * For OneToMany, this means setting a field on each item in $to_collection,
	 * since the other side can't logically have a column for each of its
	 * related models.
	 *
	 * @param Model  $from_instance  Model that the data is being set on.
	 * @param Model  $to_model_or_collecion  Related data that is being set.
	 * @return void
	 */
	public function connect($from_instance, $to_collection)
	{
		foreach ($to_collection as $model)
		{
			$model->{$this->to_key} = $from_instance->{$this->key};
		}
	}

	/**
	 * Figure out optional key settings as well as the parent.
	 *
	 * The parent must always be a single item, so this must always be the parent.
	 * For keys, this side can never contain a reference to each child, so the
	 * children must contain the reference to this side. By default, the key used
	 * matches the primary key it references.
	 *
	 * @return void
	 */
	protected function normalizeKeys()
	{
		$from = $this->from;

		$this->is_parent = TRUE;
		$this->key = $this->key ?: $from::getMetaData('primary_key');
		$this->to_key = $this->to_key ?: $this->key;
	}

}