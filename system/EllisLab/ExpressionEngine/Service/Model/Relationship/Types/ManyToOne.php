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
 * ExpressionEngine Many-To-One Relationship
 *
 * The many-to-one relationship type.
 *
 * @package		ExpressionEngine
 * @subpackage	Model\Relationship\Types
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class ManyToOne extends Relationship {

	public $type	= 'many_to_one';
	public $inverse	= 'one_to_many';

	/**
	 * Set the related ids to correctly connect the models.
	 *
	 * For manyToOne, this means setting a field on from_instance, since the
	 * other side can't logically have a column for each of its related models.
	 *
	 * @param Model  $from_instance  Model that the data is being set on.
	 * @param Model  $to_model_or_collecion  Related data that is being set.
	 * @return void
	 */
	public function connect($from_instance, $to_model)
	{
		$from_instance->{$this->key} = $to_model->{$this->to_key};
	}

	public function disconnect($from_instance, $to_model)
	{
		$from_instance->{$this->key} = NULL;
	}

	/**
	 * Determine whether the edge accepts a given action.
	 *
	 * For this edge that means only accepting set and remove.
	 */
	public function assertAcceptsAction($action)
	{
		// weak relationships are always set/remove
		if ($action == 'create' || $action == 'delete')
		{
			$alt = ($action == 'create') ? 'set' : 'remove';
			throw new \Exception("Cannot {$action}{$this->name}, did you mean {$alt}{$this->name}?");
		}
		// add is not ok for a *-to-one, must be set
		elseif ($action == 'add')
		{
			throw new \Exception("Cannot add{$this->name}, did you mean set{$this->name}?");
		}
	}

	/**
	 * Figure out optional key settings as well as the parent.
	 *
	 * The parent must always be a single item, so this can never be the parent.
	 * For keys, this side will always contain a reference to the parent, as the
	 * parent will not have a separate column for each child item. By default,
	 * the key used matches the primary key it references.
	 *
	 * @return void
	 */
	protected function normalizeKeys()
	{
		$to = $this->to;

		$this->is_parent = FALSE;
		$this->to_key = $this->to_key ?: $this->factory->getMetaData($to, 'primary_key');
		$this->key = $this->key ?: $this->to_key;
	}
}