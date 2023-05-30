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

class Slider extends Field
{
    /**
     * @var null[]
     */
    protected $field_prototype = [
        'min' => null,
        'max' => null,
        'step' => null,
        'unit' => null,
    ];

    /**
     * @param int $min
     * @return $this
     */
    public function setMin(int $min): Slider
    {
        $this->set('min', $min);
        return $this;
    }

    /**
     * @return int|null
     */
    public function getMin(): ?int
    {
        return $this->get('min');
    }

    /**
     * @param int $max
     * @return $this
     */
    public function setMax(int $max): Slider
    {
        $this->set('max', $max);
        return $this;
    }

    /**
     * @return int|null
     */
    public function getMax(): ?int
    {
        return $this->get('max');
    }

    /**
     * @param $step
     * @return $this
     */
    public function setStep($step): Slider
    {
        $this->set('step', $step);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStep()
    {
        return $this->get('step');
    }

    /**
     * @param $unit
     * @return $this
     */
    public function setUnit($unit): Slider
    {
        $this->set('unit', $unit);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUnit()
    {
        return $this->get('unit');
    }
}
