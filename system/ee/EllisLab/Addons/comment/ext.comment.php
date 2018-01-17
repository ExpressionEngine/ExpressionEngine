<?php

/**
 * Extension for the Comment Module to add a user-friendly application sub nav
 */
class Comment_ext {

	public function __construct()
	{
		$addon = ee('Addon')->get('comment');
		$this->version = $addon->getVersion();
	}

	/**
	 * Add the comment Menu
	 *
	 * @param object $menu EllisLab\ExpressionEngine\Service\CustomMenu\Menu
	 */
	public function addCommentMenu($menu)
	{
		ee()->lang->load('comment');

		$sub = $menu->addSubmenu(lang('comments'));

		$new_comments = ee('Model')->get('Comment')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('comment_date', '>', ee()->session->userdata['last_visit'])
			->count();

		$sub->addItem(
			lang('new') . " ({$new_comments})",
			ee('CP/URL')->make('publish/comments', array('filter_by_date' => ee()->localize->now - ee()->session->userdata['last_visit']))
		);

		$pending_comments = ee('Model')->get('Comment')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('status', 'p')
			->count();

		$sub->addItem(
			lang('pending') . " ({$pending_comments})",
			ee('CP/URL')->make('publish/comments', array('filter_by_status' => 'p'))
		);

		$spam_addon = ee('Addon')->get('spam');
		if ($spam_addon && $spam_addon->isInstalled())
		{
			ee()->lang->load('spam');

			$spam_comments = ee('Model')->get('Comment')
				->filter('site_id', ee()->config->item('site_id'))
				->filter('status', 's')
				->count();

			$sub->addItem(
				lang('spam') . " ({$spam_comments})",
				ee('CP/URL')->make('addons/settings/spam', array('content_type' => 'comment'))
			);
		}

		$sub->addItem(
			lang('view_all'),
			ee('CP/URL')->make('publish/comments')
		);
	}

	/**
	 * Activate Extension
	 */
	public function activate_extension()
	{
		ee('Model')->make('Extension', [
			'class'		=> __CLASS__,
			'method'	=> 'addCommentMenu',
			'hook'		=> 'cp_custom_menu',
			'settings'	=> [],
			'version'	=> $this->version,
			'enabled'	=> 'y'
		])->save();
	}

	/**
	 * Disable Extension
	 */
	public function disable_extension()
	{
		ee('Model')->get('Extension')
			->filter('class', __CLASS__)
			->delete();
	}
}
// END CLASS

// EOF
