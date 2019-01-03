<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Protocol\Spam;

/**
 * ExpressionEngine Spam Protocol interface
 */
interface Spam {

	/**
	 * Returns true if the string is classified as spam
	 *
	 * @param string $source Text to classify
	 * @return bool Is Spam?
	 */
	public function isSpam($source);

	/**
	 * Store flagged spam to await moderation. We store a serialized copy of a model entity
	 * as well as the content type (addon name) and namespace of the handler. When spam is
	 * moderated, that entity will be passed to the addon's approve()/reject() methods to
	 * take whatever action is necessary.
	 *
	 * @param string $content_type the content type (addon short name, e.g. comment, discuss, etc.)
	 * @param object $entity A valid model entity
	 * @param string $document The text that was classified as spam
	 * @param object $optional_data Any optional data the addon would like to store in the trap for later use
	 * @return void
	 */
	public function moderate($content_type, $entity, $document, $optional_data);
}
