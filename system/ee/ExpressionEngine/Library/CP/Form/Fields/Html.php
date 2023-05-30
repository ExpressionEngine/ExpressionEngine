<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\Form\Fields;

use ExpressionEngine\Library\CP\Form\Field;

class Html extends Field
{
    /**
     * @var null[]
     */
    protected $field_prototype = [
        'content' => '',
    ];

    /**
     * @param $content
     * @return $this
     */
    public function setContent($content): Html
    {
        $this->set('content', $content);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->get('content');
    }
}
