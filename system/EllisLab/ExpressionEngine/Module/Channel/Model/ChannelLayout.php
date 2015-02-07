<?php

namespace EllisLab\ExpressionEngine\Module\Channel\Model;

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
		'MemberGroup' => array(
			'type' => 'belongsTo',
			'from_key' 	=> 'member_group'
		),
	);

	protected static $_validation_rules = array(
		'site_id'      => 'required|isNatural',
		'member_group' => 'required|isNatural',
		'channel_id'   => 'required|isNatural',
		'layout_name'  => 'required',
	);

	protected $layout_id;
	protected $site_id;
	protected $member_group;
	protected $channel_id;
	protected $layout_name;
	protected $field_layout;

	public function set__field_layout($field_layout)
	{
		$this->field_layout = serialize($field_layout);
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
			$tab = new LayoutTab($section['name'], $section['name']);
			foreach ($section['fields'] as $field_info)
			{
				$field_id = $field_info['field'];
				if ($field_info['visible'])
				{
					$field = $fields[$field_id];
					if ($field_info['collapsed'])
					{
						$field->collapse();
					}

					$tab->addField($field);
				}
				unset($fields[$field_id]);
			}
			$display->addTab($tab);
		}

		// "New" (unknown) fields
		$tab = $display->getTab('publish');

		foreach ($fields as $field_id => $field)
		{
			$tab->addField($field);
		}

		return $display;
	}

}