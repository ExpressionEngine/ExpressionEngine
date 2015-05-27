<?php

namespace EllisLab\ExpressionEngine\Model\Content\Display;

use EllisLab\ExpressionEngine\Model\Content\Display\LayoutDisplay;
use EllisLab\ExpressionEngine\Model\Content\Display\LayoutTab;

class DefaultLayout implements LayoutInterface {

	protected $layout;
	protected $assigned = array();

	public function __construct()
	{
		$this->layout = $this->createLayout();
	}

	/**
	 * Create the basic layout structure. Here you can add
	 * tabs, fields, etc.
	 */
	protected function createLayout()
	{
		return array(array(
			'id' => 'main',
			'name' => 'Main',
			'fields' => array()
		));
	}

	/**
	 * There must be a single tab that accepts unassigned field
	 * by default.
	 */
	public function getDefaultTab()
	{
		return 'main';
	}

	/**
	 * Fetch the layout array
	 */
	public function getLayout()
	{
		return $this->layout;
	}

	/**
	 * Transform a field array into a LayoutDisplay object
	 */
	public function transform(array $fields)
	{
		$display = new LayoutDisplay();

		// add the tabs they wanted
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

		// add any leftover fields to the default tab
		$tab = $display->getTab($this->getDefaultTab());

		foreach ($fields as $field_id => $field)
		{
			$tab->addField($field);
		}

		return $display;
	}
}