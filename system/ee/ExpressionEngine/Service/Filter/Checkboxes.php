<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Filter;

use ExpressionEngine\Library\CP\URL;
use ExpressionEngine\Service\View\ViewFactory;
use ExpressionEngine\Model\Content\StructureModel;

/**
 * Checkboxes Filter
 */
class Checkboxes extends Filter
{
    public $view_id = null;
    public $channel_id = null;
    public $shortLabel = null;

    public function __construct($name, $label, array $options, $shortLabel = null)
    {
        $this->name = $name;
        $this->label = empty($label) ? lang($name) : $label;
        $this->shortLabel = empty($shortLabel) ? $this->label : $shortLabel;
        $this->options = $options;
        $this->default_value = array_keys($options);
    }

    /**
     * @see Filter::render
     */
    public function render(ViewFactory $view, URL $url)
    {
        //selected options go first in chosen order
        $options = [];
        $selected = $this->value();
        if (!is_array($selected)) {
            $selected = json_decode($selected);
        }

        $filter = array(
            'label' => $this->label,
            'short_label' => $this->shortLabel,
            'name' => $this->name,
            'value' => $selected,
            'display_value' => array_filter($this->options, function ($key) use ($selected) {
                return in_array($key, $selected);
            }, ARRAY_FILTER_USE_KEY),
            'options' => $this->options
        );

        return $view->make('_shared/filters/checkboxes')->render($filter);
    }
}

// EOF
