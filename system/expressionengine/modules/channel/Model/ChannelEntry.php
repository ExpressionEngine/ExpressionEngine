<?php

class ChannelEntry extends Model implements Content {
	protected static $meta = array(
		'primary_key' => 'entry_id',
		'entity_names' => array('ChannelTitleEntity', 'ChannelDataEntity'),
		'key_map' => array(
			'entry_id' => 'ChannelTitleEntity',
			'channel_id' => 'ChannelTitleEntity',
			'site_id' => 'ChannelTitleEntity',
		)
	);

	public function getFields()
	{

	}

	/**
	 * Renders the piece of content for the front end, parses the tag data
	 * called by the module when rendering tagdata.
	 *
	 * @param	ParsedTemplate|string	$template	The parsed template from
	 * 						the template engine or a string of tagdata.
	 *
	 * @return	Template|string	The parsed template with relevant tags replaced
	 *							or the tagdata string with relevant tags replaced.
	 */
	public function render($template)
	{
		// call render on all custom fields
	}

	/**
	 * A link back to the owning channel object.
	 *
	 * @return	Structure	A link to the Structure objects that defines this
	 * 						Content's structure.
	 */
	public function getStructure()
	{
		$this->manyToOne('Channel', 'channel_id', 'channel_id');
	}

	/**
	 * Validates the channel entry before saving
	 *
	 * @return	void
	 *
	 * @throws	ContentInvalidException If content fails to validate a
	 * 						ContentInvalidException will be thrown with errors.
	 */
	public function validate()
	{

	}
}
