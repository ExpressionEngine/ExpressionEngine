<?php

namespace EllisLab\ExpressionEngine\Model\Content\Display;

class DefaultLayout implements LayoutInterface {

	protected $layout;

	public function __construct()
	{
		$this->layout = array();

		$this->layout[] = array(
			'name' => 'publish',
			'show' => TRUE,
			'fields' => array(
				array('title', TRUE),
				array('url_title', TRUE)
			)
		);

		$this->layout[] = array(
			'name' => 'date',
			'show' => TRUE,
			'fields' => array(
				array('entry_date', TRUE),
				array('expiration_date', TRUE),
				array('comment_expiration_date', TRUE)
			)
		);

		$this->layout[] = array(
			'name' => 'categories',
			'show' => TRUE,
			'fields' => array(
				array('categories', TRUE)
			)
		);

		$this->layout[] = array(
			'name' => 'options',
			'show' => TRUE,
			'fields' => array(
				array('channel_id', TRUE),
				array('status', TRUE),
				array('author_id', TRUE),
				array('sticky', TRUE),
				array('allow_comments', TRUE)
			)
		);
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
			$tab = new LayoutTab($section['name'], $section['name']);
			foreach ($section['fields'] as list($field_id, $visible))
			{
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