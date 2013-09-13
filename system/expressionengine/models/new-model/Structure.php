<?php

/**
 * Structure Interface
 *
 * Classes implementing this should define the structure of a collection of data.
 * For example, Channel is the structural element for ChannelEntries.
 */
interface Structure {

	/**
	 * Display the settings form
	 *
	 * @return String   HTML Settings form
	 */
	public function displaySettings();

	/**
	 * Save the setting data.
	 *
	 * Should call validateSettings() before saving
	 *
	 * @return void
	 */
	public function saveSettings();

	/**
	 * Validate the setting data
	 *
	 * @throws StructureInvalidException if missing / invalid data
	 * @return void
	 */
	public function validateSettings();

	/**
	 * Display the CP form form
	 *
	 * @param Content $content  An object implementing the Content interface
	 * @return String   HTML for the entry / edit form
	 */
	public function form($content);

	/**
	 * Delete settings and all content
	 *
	 * @return void
	 */
	public function delete();

}
