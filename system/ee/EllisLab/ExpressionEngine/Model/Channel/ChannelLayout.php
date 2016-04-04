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

	protected static $_typed_columns = array(
		'field_layout' => 'serialized',
	);

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

	public function transform(array $fields)
	{
		$display = new LayoutDisplay();

		// Fields known to the layout
		$layout = $this->getProperty('field_layout');
		foreach ($layout as $section)
		{
			// Tabs have 4 pieces of data: an id, a name, a list of fields,
			// and a visibility flag. If any of them are missing this is not
			// a tab (but visiblity is non-essential so...) we'll skip it. This
			// is better than a PHP error.
			if ( ! (isset($section['id'])
					&& isset($section['name'])
					&& isset($section['fields']))
				)
			{
				continue;
			}

			$tab = new LayoutTab($section['id'], $section['name']);

			// If they don't havea 'visible' key we'll assume it is visible
			// and just move on.
			if (isset($section['visible']) && ! $section['visible'])
			{
				$tab->hide();
			}

			foreach ($section['fields'] as $field_info)
			{
				// If the field_info does'nt have a 'field' key, this isn't
				// really a field: skip it.
				if (empty($field_info) || ! isset($field_info['field']))
				{
					continue;
				}

				$field_id = $field_info['field'];

				// Looking for a field that is not there...skip it for now
				if ( ! array_key_exists($field_id, $fields))
				{
					continue;
				}

				$field = $fields[$field_id];

				// Collapsed is "optional" and defaults to "not collapsed"
				if (isset($field_info['collapsed']) && $field_info['collapsed'])
				{
					$field->collapse();
				}

				// Visible is "optional" and defaults to "I can see you!"
				if (isset($field_info['visible']) && ! $field_info['visible'])
				{
					$field->hide();
				}

				$tab->addField($field);

				unset($fields[$field_id]);
			}
			$display->addTab($tab);
		}

		// "New" (unknown) fields
		try
		{
			$publish_tab = $display->getTab('publish');
		}
		catch (InvalidArgumentException $e)
		{
			$publish_tab = new LayoutTab('publish', 'publish');
			$display->addTab($publish_tab);
		}

		try
		{
			$categories_tab = $display->getTab('categories');
		}
		catch (InvalidArgumentException $e)
		{
			$categories_tab = new LayoutTab('categories', 'categories');
			$display->addTab($categories_tab);
		}

		foreach ($fields as $field_id => $field)
		{
			if (strpos($field_id, 'categories[') == 0)
			{
				$tab = $categories_tab;
			}
			elseif (strpos($field_id, '__') === FALSE)
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