<?php

namespace EllisLab\ExpressionEngine\Service\Event;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Reflexive Event Subscriber Interface
 *
 * Marker interface to implement if you're using the event mixin and want
 * to automatically subscribe your class itself.
 *
 * @package		ExpressionEngine
 * @subpackage	Event
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
interface ReflexiveSubscriber extends Subscriber {}