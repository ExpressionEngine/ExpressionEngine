<?php
namespace EllisLab\ExpressionEngine\Module\Model;

use EllisLab\ExpresionEngine\Model\Model as Model;
use EllisLab\ExpressionEngine\Model\Interfaces\Field\FieldStructure as FieldStructure;

class ChannelFieldStructure 
	extends Model 
		implements FieldStructure {

	protected static $meta = array(
		'primary_key' => 'field_id',
		'entity_names' => array('ChannelFieldEntity'),
		'key_map' => array(
			'field_id' => 'ChannelFieldEntity',
			'site_id' => 'ChannelFieldEntity',
			'group_id' => 'ChannelFieldEntity'
		)
	);	

	
	/**
	 * Get the settings object for this field.
	 *
	 * @param	string	$name	Optional.  The name of the group of settings 
	 * 							you wish to retrieve.
	 */
	public function getSettings($name=NULL)
	{}

	/**
	 * Get the form that defines this field (usually required properties).
	 *
	 * @return	string|View		Either the HTML string of the form partial, or 
	 * 							a view object representing it.
	 */
	public function getForm()
	{}


    /**
     * Validate the data for this field
     *
     * @throws FieldStructureInvalidException if missing / invalid data
     * @return void
     */
    public function validate()
	{}

    /**
     * Display the settings form for this field
     *
     * @param FieldContent   $field_content   An object implementing the FieldContent interface
     * @return String   HTML for the entry / edit form
     */
    public function getPublishForm($field_content)
	{}

	
}

