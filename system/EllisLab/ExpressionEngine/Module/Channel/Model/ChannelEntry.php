<?php
namespace EllisLab\ExpressionEngine\Module\Channel\Model;

use EllisLab\ExpressionEngine\Model\FieldDataContentModel as Model;

class ChannelEntry extends FieldDataContentModel {
	protected static $meta = array(
		'primary_key' => 'entry_id',
		'entity_names' => array('ChannelTitleEntity', 'ChannelDataEntity'),
		'key_map' => array(
			'entry_id' => 'ChannelTitleEntity',
			'channel_id' => 'ChannelTitleEntity',
			'site_id' => 'ChannelTitleEntity',
			'author_id' => 'ChannelTitleEntity'
		)
		'field_content_class' => 'ChannelFieldContent',
		'field_content_entity' => 'ChannelDataEntity'		
	);

	public function getChannel()
	{
		return $this->manyToOne('Channel', 'channel_id', 'channel_id');
	}

	public function getAuthor()
	{
		return $this->getMember();
	}

	public function getMember()
	{
		return $this->manyToOne('Member', 'author_id', 'member_id', 'Author');
	}

	/**
	 * A link back to the owning channel object.
	 *
	 * @return	Structure	A link to the Structure objects that defines this
	 * 						Content's structure.
	 */
	public function getContentStructure()
	{
		return $this->getChannel();
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

}
