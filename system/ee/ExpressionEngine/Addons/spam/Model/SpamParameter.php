<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Spam\Model;

use ExpressionEngine\Service\Model\Model;

/**
 * SpamParameter Model
 */
class SpamParameter extends Model
{
    protected static $_table_name = 'spam_parameters';
    protected static $_primary_key = 'parameter_id';

    protected static $_relationships = array(
        'Kernel' => array(
            'type' => 'belongsTo',
            'model' => 'SpamKernel',
            'to_key' => 'kernel_id'
        )
    );

    protected $parameter_id;
    protected $kernel_id;
    protected $index;
    protected $term;
    protected $class;
    protected $mean;
    protected $variance;
}

// EOF
