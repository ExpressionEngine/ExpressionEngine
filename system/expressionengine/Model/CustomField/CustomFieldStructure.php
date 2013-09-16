<?php

/**
 * An implementation of a custom field's structure.
 * 
 * TODO-MODEL Need to think about implementation details,
 * 	this is just a stub of the interface right now.
 */
class CustomFieldStructure
	extends Model
		implements FieldStructure {

	/**
	 * Get the settings object for this field.
	 *
	 * @param	string	$name	Optional.  The name of the group of settings 
	 * 							you wish to retrieve.
	 */
	public function getSettings($name=NULL)
	{

	}

	/**
	 * Get the form that defines this field (usually required properties).
	 *
	 * @return	string|View		Either the HTML string of the form partial, or 
	 * 							a view object representing it.
	 */
	public function getForm()
	{

	}

    /**
     * Save the data for this field
     *
     * Should call validate() before saving
     *
     * @return void
     */
    public function save()
	{

	}

    /**
     * Validate the data for this field
     *
     * @throws FieldStructureInvalidException if missing / invalid data
     * @return void
     */
    public function validate()
	{

	}

    /**
     * Display the settings form for this field
     *
     * @param FieldContent   $field_content   An object implementing the FieldContent interface
	 * 
     * @return String   HTML for the entry / edit form
     */
    public function getPublishForm($field_content)
	{

	}

    /**
     * Delete settings and all content for this field
     *
     * @return void
     */
    public function delete()
	{

	}

}
