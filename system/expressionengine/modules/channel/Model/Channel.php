<?php

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
			'ChannelFieldGroup', 'field_group', 'group_id');	
	}

	/**
	 * Relationship to ChannelEntries for this Channel.
	 */
	public function getChannelEntries()
	{
		return $this->oneToMany(
			'ChannelEntry', 'channel_id', 'channel_id');
	}

	/**
	 * Display the specified settings section
	 *
	 * @return String   HTML Settings form
	 */
	public function getSettings($name = NULL)
	{
		$set = new SettingsSet($this, array(
			'path'           => 'ChannelPathSettings',
			'commentPosting' => 'ChannelCommentPostingSettings',
			// ... more settings
		));

		if (isset($name))
		{
			return $set->getSetting($name);
		}

		return $set->getSettings();
	}

	/**
	 * Save the channel settings data.
	 *
	 * Should call validateSettings() before saving
	 *
	 * @throws SettingsInvalidException if object was not validated before saving
	 *									and validation fails on save.
	 * @return void
	 */
	public function save()
	{
		$valid = $this->validateSettings();

		if ( ! $valid)
		{
			throw new SettingsInvalidException();
		}

		// save
	}

	/**
	 * Validate the setting data
	 *
	 * @return Errors
	 */
	public function validate()
	{

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

	/**
	 * Delete this channel and all of its content
	 *
	 * @return void
	 */
	public function delete()
	{

	}
}
