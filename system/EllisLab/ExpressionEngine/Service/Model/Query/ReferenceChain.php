<?php
namespace EllisLab\ExpressionEngine\Service\Model\Query;

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
 * ExpressionEngine Reference Bag Class
 *
 * A class to store all of the model references by alias. This allows
 * us to track alias uniqueness. It also automatically connects stored
 * references with their parent using the graph edge, chaining them
 * together. Hence the name.
 *
 * For more info on references, please refer to the comments on that class.
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
 class ReferenceChain {

 	protected $manager;
 	protected $references = array();

 	public function __construct($manager)
 	{
 		$this->manager = $manager;
 	}

 	/**
 	 *
 	 */
 	public function add(Reference $reference)
 	{
 		if (array_key_exists($reference->alias, $this->references))
 		{
 			throw new \Exception("Ambiguous model name or alias '{$reference->alias}'. Please choose a unique alias.");
 		}

 		$this->references[$reference->alias] = $reference;
 	}

 	/**
 	 *
 	 */
 	public function connect(Reference $parent, Reference $child, $name)
 	{
 		$edges = $this->manager->getRelationships($parent->model, $name);

 		if ( ! isset($edges[$name]))
 		{
 			throw new \Exception("No valid relationship '{$name}' found on {$parent->model}.");
 		}

 		$child->setParent($parent);
 		$child->setRelationship($edges[$name]);
 	}

 	/**
 	 *
 	 */
 	public function get($alias)
 	{
 		return $this->references[$alias];
 	}

 	/**
 	 *
 	 */
 	public function all()
 	{
 		return $this->references;
 	}
 }