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

/**
 * ViewType Filter
 *
 * This will provide the HTML for a filter that will display a set of buttons
 * to change the view mode of the current result set into either list, thumbnail,
 * or a hybrid mini-thumbnail / list format.
 */
class ViewType extends Filter
{
    /**
     * @param  array $options options
     * @param  string $default_value ['list', 'thumb']
     * @return void
     */
    public function __construct(array $options = array(), $default_value = 'list')
    {
        $this->name = 'viewtype';
        $this->label = 'viewtype_filter';
        $this->placeholder = 'view type';
        $this->options = $options;

        $this->options = array(
            'list' => lang('viewtype_list'),
            'thumb' => lang('viewtype_thumb'),
        );

        $this->default_value = $default_value;
    }

    /**
     * @see Filter::render() for the logic/behavior
     * Overriding the parent value to coerce the value into an int
     * and if we did not get one we will fall back and use the default value.
     *
     * @return int The number of items per page
     */
    public function value()
    {
        return !empty(parent::value()) ? parent::value() : $this->default_value;
    }

    /**
     * Validation
     */
    public function isValid()
    {
        return in_array($this->value(), ['list', 'thumb']);
    }

    /**
     * @see Filter::render
     *
     * Overriding the abstract class's render method in order to render a custom
     * perpage view which includes a modal for show-all
     */
    public function render(ViewFactory $view, URL $url)
    {
        $original_options = $this->options;
        $options = $this->prepareOptions($url);
        $new_options = [];
        foreach ($options as $url => $label) {
            $new_options[] = [
                'url' => $url,
                'label' => $label
            ];
        }

        // Merge the url and label with the viewtype so that all three options can be accessed in the view
        $options = array_combine(array_keys($original_options), $new_options);

        $filter = [
            'name' => $this->name,
            'value' => str_replace('"', '&quot;', $this->value()),
            'placeholder' => $this->placeholder,
            'options' => $options
        ];

        return $view->make('_shared/filters/viewtype')->render($filter);
    }
}

// EOF
