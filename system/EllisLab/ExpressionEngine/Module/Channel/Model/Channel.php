<?php
namespace EllisLab\ExpressionEngine\Module\Channel\Model;

use EllisLab\ExpressionEngine\Model\Model as Model;
use EllisLab\ExpressionEngine\Model\Interfaces\Content\ContentStructure
	as ContentStructure;


class Channel extends Model implements ContentStructure {
	protected static $meta = array(
		'primary_key' => 'channel_id',
		'entity_names' => array('ChannelEntity'),
		'key_map' => array(
			'channel_id' => 'ChannelEntity',
			'site_id' => 'ChannelEntity',
			'field_group' => 'ChannelEntity'
		)	
	);

	/**
	 * Relationship to the FieldGroup for this Channel.
	 */
	public function getChannelFieldGroup()
	{
		return $this->manyToOne(
			'ChannelFieldGroup', 'ChannelFieldGroup', 'field_group', 'group_id');	
	}

	/**
	 * Relationship to ChannelEntries for this Channel.
	 */
	public function getChannelEntries()
	{
		return $this->oneToMany(
			'ChannelEntries', 'ChannelEntry', 'channel_id', 'channel_id');
	}

	/**
	 * Display the CP entry form
	 *
	 * @param Content $content  An object implementing the Content interface
	 * @return Array of HTML field elements for the entry / edit form
	 */
	public function getPublishForm($content)
	{
		$form_elements = array();
		// populate from custom fields

		return $form_elements;
	}

}
