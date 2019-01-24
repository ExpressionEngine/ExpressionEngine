<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Channel\Display;

use EllisLab\ExpressionEngine\Model\Content\Display\DefaultLayout;
use EllisLab\ExpressionEngine\Model\Content\Display\LayoutDisplay;
use EllisLab\ExpressionEngine\Model\Content\Display\LayoutTab;

/**
 * Default Channel Layout
 */
class DefaultChannelLayout extends DefaultLayout {

	protected $channel_id;
	protected $entry_id;

	public function __construct($channel_id, $entry_id)
	{
		$this->channel_id = $channel_id;
		$this->entry_id = $entry_id;

		parent::__construct();
	}

	public function getDefaultTab()
	{
		return 'publish';
	}

	/**
	 * This is what you'll want to be overriding, if anything
	 */
	protected function createLayout()
	{
		$layout = array();

		$layout[] = array(
			'id' => 'publish',
			'name' => 'publish',
			'visible' => TRUE,
			'fields' => array(
				array(
					'field' => 'title',
					'visible' => TRUE,
					'collapsed' => FALSE
				),
				array(
					'field' => 'url_title',
					'visible' => TRUE,
					'collapsed' => FALSE
				)
			)
		);

		$channel = ee('Model')->get('Channel', $this->channel_id)->first();

		// Date Tab ------------------------------------------------------------

		$date_fields = array(
			array(
				'field' => 'entry_date',
				'visible' => TRUE,
				'collapsed' => FALSE
			),
			array(
				'field' => 'expiration_date',
				'visible' => TRUE,
				'collapsed' => FALSE
			)
		);

		if (bool_config_item('enable_comments') && $channel->comment_system_enabled)
		{
			$date_fields[] = array(
				'field' => 'comment_expiration_date',
				'visible' => TRUE,
				'collapsed' => FALSE
			);
		}

		$layout[] = array(
			'id' => 'date',
			'name' => 'date',
			'visible' => TRUE,
			'fields' => $date_fields
		);

		// Category Tab --------------------------------------------------------

		$cat_groups = ee('Model')->get('CategoryGroup')
			->filter('group_id', 'IN', explode('|', $channel->cat_group))
			->all();

		$category_group_fields = array();
		foreach ($cat_groups as $cat_group)
		{
			$category_group_fields[] = array(
				'field' => 'categories[cat_group_id_'.$cat_group->getId().']',
				'visible' => TRUE,
				'collapsed' => FALSE
			);
		}

		$layout[] = array(
			'id' => 'categories',
			'name' => 'categories',
			'visible' => TRUE,
			'fields' => $category_group_fields
		);

		// Options Tab ---------------------------------------------------------

		$option_fields = array(
			array(
				'field' => 'channel_id',
				'visible' => TRUE,
				'collapsed' => FALSE
			),
			array(
				'field' => 'status',
				'visible' => TRUE,
				'collapsed' => FALSE
			),
			array(
				'field' => 'author_id',
				'visible' => TRUE,
				'collapsed' => FALSE
			),
			array(
				'field' => 'sticky',
				'visible' => TRUE,
				'collapsed' => FALSE
			)
		);

		if (bool_config_item('enable_comments') && $channel->comment_system_enabled)
		{
			$option_fields[] = array(
				'field' => 'allow_comments',
				'visible' => TRUE,
				'collapsed' => FALSE
			);
		}

		$layout[] = array(
			'id' => 'options',
			'name' => 'options',
			'visible' => TRUE,
			'fields' => $option_fields
		);

		if ($this->channel_id)
		{
			// Here comes the ugly! @TODO don't do this
			ee()->legacy_api->instantiate('channel_fields');

			$module_tabs = ee()->api_channel_fields->get_module_fields(
				$this->channel_id,
				$this->entry_id
			);
			$module_tabs = $module_tabs ?: array();

			foreach ($module_tabs as $tab_id => $fields)
			{
				$tab = array(
					'id' => $tab_id,
					'name' => $tab_id,
					'visible' => TRUE,
					'fields' => array()
				);

				foreach ($fields as $key => $field)
				{
					$tab['fields'][] = array(
						'field' => $field['field_id'],
						'visible' => TRUE,
						'collapsed' => FALSE
					);
				}

				$layout[] = $tab;
			}
		}

		if ($channel->enable_versioning)
		{
			$layout[] = array(
				'id' => 'revisions',
				'name' => 'revisions',
				'visible' => TRUE,
				'fields' => array(
					array(
						'field' => 'versioning_enabled',
						'visible' => TRUE,
						'collapsed' => FALSE
					),
					array(
						'field' => 'revisions',
						'visible' => TRUE,
						'collapsed' => FALSE
					)
				)
			);
		}

		return $layout;
	}

	public function transform(array $fields)
	{
		$display = parent::transform($fields);

		$tab = $display->getTab('categories');
		$fields = $tab->getFields();
		if (count($fields) == 0)
		{
			$url = ee('CP/URL', 'channels/edit/' . $this->channel_id)->compile();
			$alert = ee('CP/Alert')->makeInline('empty-category-tab')
				->asWarning()
				->cannotClose()
				->withTitle(lang('no_categories_assigned'))
				->addToBody(sprintf(lang('no_categories_assigned_desc'), $url));

			$tab->setAlert($alert);
		}

		return $display;
	}
}

// EOF
