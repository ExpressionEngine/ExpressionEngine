<?php

namespace EllisLab\ExpressionEngine\Module\Channel\Model\Display;

use EllisLab\ExpressionEngine\Model\Content\Display\DefaultLayout;
use EllisLab\ExpressionEngine\Model\Content\Display\LayoutDisplay;
use EllisLab\ExpressionEngine\Model\Content\Display\LayoutTab;

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

		$layout[] = array(
			'id' => 'date',
			'name' => 'date',
			'visible' => TRUE,
			'fields' => array(
				array(
					'field' => 'entry_date',
					'visible' => TRUE,
					'collapsed' => FALSE
				),
				array(
					'field' => 'expiration_date',
					'visible' => TRUE,
					'collapsed' => FALSE
				),
				array(
					'field' => 'comment_expiration_date',
					'visible' => TRUE,
					'collapsed' => FALSE
				)
			)
		);

		$layout[] = array(
			'id' => 'categories',
			'name' => 'categories',
			'visible' => TRUE,
			'fields' => array(
				array(
					'field' => 'categories',
					'visible' => TRUE,
					'collapsed' => FALSE
				)
			)
		);

		$layout[] = array(
			'id' => 'options',
			'name' => 'options',
			'visible' => TRUE,
			'fields' => array(
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
				),
				array(
					'field' => 'allow_comments',
					'visible' => TRUE,
					'collapsed' => FALSE
				)
			)
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

		return $layout;
	}
}