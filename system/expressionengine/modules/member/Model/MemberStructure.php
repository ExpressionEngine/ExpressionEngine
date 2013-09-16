<?php

class MemberStructure extends Model implements Structure {

	/**
	 * Display the settings forms
	 *
	 * @return String   HTML Settings form
	 */
	public function displaySettings()
	{
		// TODO figure out how to display different groups of settings
	}

	/**
	 * Save the setting forms
	 *
	 * Should call validateSettings() before saving
	 *
	 * @return void
	 */
	public function saveSettings()
	{

	}

	/**
	 * Validate the setting data
	 *
	 * @throws StructureInvalidException if missing / invalid data
	 * @return void
	 */
	public function validateSettings()
	{

	}

	/**
	 * Display the member entry form
	 *
	 * @param Content $content  An object implementing the Content interface
	 * @return Array  Arroy of HTML fields for the entry / edit form
	 */
	public function form($content)
	{
		return $fields;
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