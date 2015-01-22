<?php

namespace EllisLab\ExpressionEngine\Service\Model\Interfaces\Field;

use EllisLab\ExpressionEngine\Service\Model\Interfaces\Field\FieldContent;

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
 * ExpressionEngine Field Structure Interface
 *
 * Defines a structure of a field and stores its settings.
 *
 * @package		ExpressionEngine
 * @subpackage	Model\Field
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
interface FieldStructure {

	/**
     * Display the settings form for this field
	 *
	 * @return	string|View		Either the HTML string of the form partial, or
	 * 							a view object representing it.
	 */
	public function getForm();

    /**
     * Save the data for this field
     *
     * Should call validate() before saving
     *
     * @return void
     */
    public function save();

    /**
	 * Get the form that defines this field (usually required properties).
     *
     * @param FieldContent   $field_content   An object implementing the FieldContent interface
     * @return String   HTML for the entry / edit form
     */
    public function getPublishForm($field_content = NULL);

    /**
     * Delete settings and all content for this field
     *
     * @return void
     */
    public function delete();
}
