<?php

/**
 * Deals with most of what is displayed in the MyAccount area
 */
class MemberStructure extends Model implements ContentStructure {

	/**
	 * Display the main member entry form
	 *
	 * @param Content $content  An object implementing the Content interface
	 * @return Array  Arroy of HTML fields for the entry / edit form
	 */
	public function getPublishForm($content)
	{
		return $fields;
	}

	/**
	 * Display the specified settings section
	 *
	 * @return String   HTML Settings form
	 */
	public function getSettings($name = NULL)
	{
		$set = new SettingsSet($this, array(
			'member'       => 'MemberAvatarSettings',
			'localization' => 'MemberLocalizationSettings',
			// ... more settings
		));

		if (isset($name))
		{
			return $set->getSetting($name);
		}

		return $set->getSettings();
	}

	/**
	 * Save the member settings
	 *
	 * Should call validate() before saving
	 *
	 * @return void
	 */
	public function save()
	{

	}

	/**
	 * Validate the member settings
	 *
	 * @throws StructureInvalidException if missing / invalid data
	 * @return void
	 */
	public function validate()
	{

	}

	/**
	 * Delete the current member
	 *
	 * @return void
	 */
	public function delete()
	{

	}
}