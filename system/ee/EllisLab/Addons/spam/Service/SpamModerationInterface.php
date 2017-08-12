<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\Addons\Spam\Service;

/**
 * Spam Moderation
 */
interface SpamModerationInterface {

	public function approve($entity, $optiona_data);

	public function reject($entity, $optiona_data);
}
