<?php

namespace EllisLab\ExpressionEngine\Model\Channel;

use InvalidArgumentException;
use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Model\Content\Display\LayoutDisplay;
use EllisLab\ExpressionEngine\Model\Content\Display\LayoutInterface;
use EllisLab\ExpressionEngine\Model\Content\Display\LayoutTab;

class ChannelLayout extends Model implements LayoutInterface {

	protected static $_primary_key = 'layout_id';
	protected static $_table_name = 'layout_publish';

	protected static $_relationships = array(
		'Channel' => array(
			'type' => 'belongsTo',
			'key' => 'channel_id'
		),
		'MemberGroups' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'MemberGroup',
			'pivot' => array(
				'table' => 'layout_publish_member_groups',
				'key' => 'group_id',
			)
		),
	);

	protected static $_validation_rules = array(
		'site_id'      => 'required|isNatural',
		'channel_id'   => 'required|isNatural',
		'layout_name'  => 'required',
	);

	protected $layout_id;
	protected $site_id;
	protected $channel_id;
	protected $layout_name;
	protected $field_layout;

	// @TODO Make this a typed/composite column
	public function set__field_layout($field_layout)
	{
		$this->setRawProperty('field_layout', serialize($field_layout));
	}

	public function get__field_layout()
	{
		return unserialize($this->field_layout);
	}

	public function transform(array $fields)
	{
		$display = new LayoutDisplay();

		// Fields known to the layout
		$layout = $this->getProperty('field_layout');
		foreach ($layout as $section)
		{
			$tab = new LayoutTab($section['id'], $section['name']);

			if ( ! $section['visible'])
			{
				$tab->hide();
			}

			foreach ($section['fields'] as $field_info)
			{
				$field_id = $field_info['field'];

				$field = $fields[$field_id];

				if ($field_info['collapsed'])
				{
					$field->collapse();
				}

				if ( ! $field_info['visible'])
				{
					$field->hide();
				}

				$tab->addField($field);

				unset($fields[$field_id]);
			}
			$display->addTab($tab);
		}

		// "New" (unknown) fields
		$publish_tab = $display->getTab('publish');

		foreach ($fields as $field_id => $field)
		{
			if (strpos($field_id, '__') === FALSE)
			{
				$tab = $publish_tab;
			}
			else
			{
				list($tab_id, $garbage) = explode('__', $field_id);

				try
				{
					$tab = $display->getTab($tab_id);
				}
				catch (InvalidArgumentException $e)
				{
					$tab = new LayoutTab($tab_id, $tab_id);
					$display->addTab($tab);
				}
			}

			$tab->addField($field);
		}

		return $display;
	}

}