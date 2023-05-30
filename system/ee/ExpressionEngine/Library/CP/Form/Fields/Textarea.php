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

class Textarea extends Field
{
    /**
     * @var null[]
     */
    protected $field_prototype = [
        'kill_pipes' => null,
        'cols' => null,
        'rows' => null
    ];

    /**
     * @param bool $kill
     * @return $this
     */
    public function setKillPipes(bool $kill = false): Textarea
    {
        $this->set('kill_pipes', $kill);
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getKillPipes(): ?bool
    {
        return $this->get('kill_pipes');
    }

    /**
     * @param int $cols
     * @return $this
     */
    public function setCols(int $cols): Textarea
    {
        $this->set('cols', $cols);
        return $this;
    }

    /**
     * @return int|null
     */
    public function getCols(): ?int
    {
        return $this->get('cols');
    }

    /**
     * @param int $rows
     * @return $this
     */
    public function setRows(int $rows): Textarea
    {
        $this->set('rows', $rows);
        return $this;
    }

    /**
     * @return int|null
     */
    public function getRows(): ?int
    {
        return $this->get('rows');
    }
}
