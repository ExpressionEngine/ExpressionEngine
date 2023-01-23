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

class ActionButton extends Field
{
    /**
     * @var string[]
     */
    protected $field_prototype = [
        'link' => '',
        'text' => '',
    ];

    /**
     * @return string|null
     */
    public function getLink(): ?string
    {
        return $this->get('link');
    }

    /**
     * @param string $link
     * @return $this
     */
    public function setLink(string $link): ActionButton
    {
        $this->set('link', $link);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getText(): ?string
    {
        return $this->get('text');
    }

    /**
     * @param string $text
     * @return $this
     */
    public function setText(string $text): ActionButton
    {
        $this->set('text', $text);
        return $this;
    }
}
