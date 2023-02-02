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
 * Sort Filter
 */
class Sort extends Filter
{
    public function __construct(array $options = array(), $default = null)
    {
        $this->name = 'sort';
        $this->label = lang('sort_filter');
        $this->options = $options;
        $this->has_custom_value = false;

        $this->default_value = $default;
    }

    public function value()
    {
        $value = parent::value();

        if (ee('Request')->get('sort_col') != '') {
            $values = [
                'sort_col' => ee('Request')->get('sort_col'),
                'sort_dir' => 'desc'
            ];
            if (ee('Request')->get('sort_dir') != '') {
                $values['sort_dir'] = ee('Request')->get('sort_dir');
            }
            $value = implode('|', $values);
        }

        if (empty($value) || ! array_key_exists($value, $this->options)) {
            $value = $this->default_value;
        }

        return $value;
    }

    /**
     * @see Filter::render
     */
    public function render(ViewFactory $view, URL $url)
    {
        $options = array();
        $url->removeQueryStringVariable('columns');
        $url->removeQueryStringVariable('sort');
        $url->removeQueryStringVariable('sort_col');
        $url->removeQueryStringVariable('sort_dir');
        foreach ($this->options as $show => $label) {
            $url = clone $url;
            $sort = explode('|', $show);
            $sort_col = $sort[0];
            $sort_dir = $sort[1];
            $url->addQueryStringVariables(['sort_col' => $sort_col, 'sort_dir' => $sort_dir]);
            $options[$url->compile()] = $label;
        }

        $filter = array(
            'label' => $this->label,
            'name' => $this->name,
            'value' => $this->options[$this->value()],
            'has_list_filter' => $this->has_list_filter,
            'has_custom_value' => $this->has_custom_value,
            'custom_value' => (array_key_exists($this->name, $_POST)) ? $_POST[$this->name] : false,
            'placeholder' => $this->placeholder,
            'options' => $options,
        );

        return $view->make('_shared/filters/sort')->render($filter);
    }
}

// EOF
