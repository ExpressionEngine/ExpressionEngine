<?php

namespace EllisLab\ExpressionEngine\Service\Model\Column;

use EllisLab\ExpressionEngine\Library\Data\Entity;

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
 * ExpressionEngine Column Interface
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
abstract class CollectionRow extends Entity {

	protected $_parent;

	public function setParentColumn($parent)
	{
		$this->_parent = $parent;
	}

	public function delete()
	{
		$this->_parent->deleteRow($this);
	}
}