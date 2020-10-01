<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed.');

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

if( ! function_exists('queue') ) {

	function queue($obj)
	{
		if( ! $obj instanceof Queue\Traits\Queueable ) {
			throw new Queue\Exceptions\QueueException('Object of type ' . get_class($obj) . ' does not implement Queueable');
		}

		$obj->create();
	}

}