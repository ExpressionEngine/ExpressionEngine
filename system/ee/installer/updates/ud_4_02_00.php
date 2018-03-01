<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Updater\Version_4_2_0;

/**
 * Update
 */
class Updater {

	var $version_suffix = '';

	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		$steps = new \ProgressIterator(
			[
				'updateHtmlButtonClasses',
			]
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	private function updateHtmlButtonClasses()
	{
		$classes = [
			'html-bold'       => 'bold',
			'html-ins'        => 'ins',
			'html-italic'     => 'italic',
			'html-link'       => 'link',
			'html-list'       => 'list',
			'html-order-list' => 'olist',
			'html-quote'      => 'quote',
			'html-strike'     => 'strikethrough',
			'html-upload'     => 'upload',
		];

		foreach ($classes as $original => $new)
		{
			ee()->db->where('classname', $original)
				->set('classname', $new)
				->update('html_buttons');
		}
	}
}

// EOF
