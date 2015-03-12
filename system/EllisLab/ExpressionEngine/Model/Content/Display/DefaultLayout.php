<?php

namespace EllisLab\ExpressionEngine\Model\Content\Display;

use EllisLab\ExpressionEngine\Model\Content\Display\LayoutDisplay;
use EllisLab\ExpressionEngine\Model\Content\Display\LayoutTab;

class DefaultLayout implements LayoutInterface {

	protected $layout;

	public function __construct($channel_id = NULL, $entry_id = NULL)
	{
		$this->layout = array();

		$this->layout[] = array(
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

		$this->layout[] = array(
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

		$this->layout[] = array(
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

		$this->layout[] = array(
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

		if ($channel_id)
		{
			// Here comes the ugly! @TODO don't do this
			ee()->legacy_api->instantiate('channel_fields');
			$module_tabs = ee()->api_channel_fields->get_module_fields($channel_id, $entry_id);
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
				$this->layout[] = $tab;
			}
		}
	}

	public function getLayout()
	{
		return $this->layout;
	}

	public function transform(array $fields)
	{
		$display = new LayoutDisplay();

		// Non-custom fields
		foreach ($this->layout as $section)
		{
			$tab = new LayoutTab($section['id'], $section['name']);
			foreach ($section['fields'] as $field)
			{
				$field_id = $field['field'];
				$tab->addField($fields[$field_id]);
				unset($fields[$field_id]);
			}
			$display->addTab($tab);
		}

		// Custom fields
		$tab = $display->getTab('publish');

		foreach ($fields as $field_id => $field)
		{
			$tab->addField($field);
		}

		return $display;
	}

}