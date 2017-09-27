<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Controller\Fields;

use EllisLab\ExpressionEngine\Controller\Fields\AbstractFields as AbstractFieldsController;

/**
 * Categories Controller
 */
class Fields extends AbstractFieldsController {

	public function index()
	{
		ee()->session->benjaminButtonFlashdata();

		$cat_group = ee('Model')->get('CategoryGroup')
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ($cat_group)
		{
			ee()->functions->redirect(ee('CP/URL')->make('categories/group/'.$cat_group->getId()));
		}

		ee()->functions->redirect(ee('CP/URL')->make('categories/group'));
	}
}

// EOF
