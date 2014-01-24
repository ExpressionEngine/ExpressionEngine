<?php
namespace EllisLab\ExpressionEngine\Model\Interfaces\Field;

/**
 * Field Structure Interface
 *
 * Defines a structure of a field and stores its settings.
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
     * Validate the data for this field
     *
     * @throws FieldStructureInvalidException if missing / invalid data
     * @return void
     */
    public function validate();

    /**
	 * Get the form that defines this field (usually required properties).
     *
     * @param FieldContent   $field_content   An object implementing the FieldContent interface
     * @return String   HTML for the entry / edit form
     */
    public function getPublishForm(FieldContent $field_content);

    /**
     * Delete settings and all content for this field
     *
     * @return void
     */
    public function delete();
}
