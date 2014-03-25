<?php

class MemberGroup extends Model {

	/**
	 * Display the member group form
	 *
	 * This is only the required field (name and description).
	 * Everything else are settings.
	 *
	 * @return String   HTML Settings form
	 */
	public function getForm()
	{

	}

	/**
	 * Display the specified settings section
	 *
	 * @return String   HTML Settings form
	 */
	public function getSettings($name = NULL)
	{
		$set = new SettingsSet($this, array(
			'channelAccess'  => 'MemberGroupChannelAccessSettings',
			'templateAccess' => 'MemberGroupTemplateAccessSettings',
			// ... more settings
		));

		if (isset($name))
		{
			return $set->getSetting($name);
		}

		return $set->getSettings();
	}


	/**
	 * Save the member group form.
	 *
	 * Should call validateSettings() before saving
	 *
	 * @return void
	 */
	public function save()
	{

	}

	/**
	 * Validate the member group form
	 *
	 * @throws StructureInvalidException if missing / invalid data
	 * @return void
	 */
	public function validate()
	{

	}

}