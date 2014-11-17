<?php
namespace EllisLab\ExpressionEngine\Service\Model\Query;

use EllisLab\ExpressionEngine\Service\Model\Relationship\Types\Relationship;

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
 * ExpressionEngine Reference Class
 *
 * A class to manage the model references of the query. A reference is simply
 * a model name or alias during the query.
 *
 * Example:
 *
 *    get('Template')->with('LastAuthor as LA')
 *
 * Here 'Template' and 'LastAuthor as LA' are references to the model.
 *
 * Each time a model is referenced we need to keep track of the model and alias.
 * However, frequently, as in the example above we only have the relating name
 * no the actual model name (LastAuthor points to Member), so we keep track of
 * the parent (in this case Template) and the name of the relationship (in this
 * case LastAuthor). The alias chain will then fill in anything we don't have
 * through use of the relationship graph.
 *
 * The alias is always set, so it becomes our unique point of reference,
 * immune to the name <-> model difference.
 *
 * So above we end up with:
 *
 *    Template   - parent
 *    LastAuthor - name
 *    LA         - alias
 *    Member     - model
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Reference {

	public $alias;
	public $model;

	public $parent;
	public $connecting_edge; // edge from the parent model to this model

	public $instance;

	public function __construct($alias)
	{
		$this->alias = $alias;
	}

	public function setObject($instance)
	{
		$this->instance = $instance;
	}

	/**
	 *
	 */
	public function setParent($parent)
	{
		$this->parent = $parent;
		$this->parent_alias = $parent->alias;
	}

	/**
	 *
	 */
	public function setRelationship(Relationship $edge)
	{
		$this->connecting_edge = $edge;
		$this->model = $edge->to;

	}

	/**
	 *
	 */
	public function connectWithQuery($query)
	{
		if (isset($this->parent->instance))
		{
			return $this->connecting_edge->lazyQuery(
				$query,
				$this->parent->instance,
				$this->alias
			);
		}

		return $this->connecting_edge->eagerQuery(
			$query,
			$this->parent->alias,
			$this->alias
		);
	}

}