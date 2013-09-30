<?php

/**
 * Structure Interface
 *
 * Classes implementing this should define the structure of a collection of data.
 * For example, Channel is the structural element for ChannelEntries.
 */
interface ContentStructure extends Settings {

	/**
	 * Display the CP form form
	 *
	 * @param Content $content  An object implementing the Content interface
	 * @return String   HTML for the entry / edit form
	 */
	public function getPublishForm($content);

	/**
	 * Delete settings and all content
	 *
	 * @return void
	 */
	public function delete();

}
