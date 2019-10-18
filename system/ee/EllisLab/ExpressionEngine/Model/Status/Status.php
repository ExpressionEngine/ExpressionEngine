<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Status;

use EllisLab\ExpressionEngine\Service\Model\Model;
use Mexitek\PHPColors\Color;

/**
 * Status Model
 */
class Status extends Model {

	protected static $_primary_key = 'status_id';
	protected static $_table_name = 'statuses';

	protected static $_hook_id = 'status';

	protected static $_typed_columns = array(
		'site_id'         => 'int',
		'group_id'        => 'int',
		'status_order'    => 'int'
	);

	protected static $_relationships = array(
		'Channels' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'Channel',
			'pivot' => array(
				'table' => 'channels_statuses'
			),
			'weak' => TRUE,
		),
		'ChannelEntries' => [
			'type' => 'hasMany',
			'model' => 'ChannelEntry',
			'weak' => TRUE
		],
		'Site' => array(
			'type' => 'BelongsTo'
		),
		'NoAccess' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'MemberGroup',
			'pivot' => array(
				'table' => 'status_no_access',
				'left' => 'status_id',
				'right' => 'member_group'
			)
		)
	);

	protected static $_validation_rules = array(
		'status' => 'required|unique',
		'highlight' => 'required|hexColor'
	);

	protected static $_events = array(
		'beforeInsert'
	);

	protected $status_id;
	protected $status;
	protected $status_order;
	protected $highlight;

	/**
	 * Ensures the highlight field has a default value
	 *
	 * @param str $name The name of the property to fetch
	 * @return str The value of the property
	 */
	protected function get__highlight()
	{
		// Old data from before validation may be invalid
		$valid = (bool) preg_match('/^([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $this->highlight);

		return $valid ? $this->highlight : '000000';
	}

	/**
	 * New statuses get appended
	 */
	public function onBeforeInsert()
	{
		$status_order = $this->getProperty('status_order');

		if (empty($status_order))
		{
			$count = $this->getModelFacade()->get('Status')->count();
			$this->setProperty('status_order', $count + 1);
		}
	}

	/**
	 * Get Option Component for option input display (radio, etc.)
	 *
	 * @param  array  $options (bool) use_ids [default FALSE]
	 * @return array option component array
	 */
	public function getOptionComponent($options = [])
	{
		$use_ids = (isset($options['use_ids'])) ? $options['use_ids'] : FALSE;

		$status_component_style = [];

		if ( ! in_array($this->status, array('open', 'closed')) && $this->highlight != '')
		{
			$highlight = new Color($this->highlight);
			$foreground = ($highlight->isLight())
				? $highlight->darken(100)
				: $highlight->lighten(100);

			$status_component_style = [
				'backgroundColor' => '#'.$this->highlight,
				'borderColor' => '#'.$this->highlight,
				'color' => '#'.$foreground,
			];
		}

		$status_name = ($this->status == 'closed' OR $this->status == 'open')
			? lang($this->status)
			: $this->status;
		$status_class = str_replace(' ', '_', strtolower($this->status));

		$status_option = [
			'value' => ($use_ids) ? $this->status_id : $this->status,
			'label' => $status_name,
			'component' => [
				'tag' => 'span',
				'label' => $status_name,
				'class' => 'status-tag st-'.$status_class,
				'style' => $status_component_style,
			]
		];

		return $status_option;
	}
}

// EOF
