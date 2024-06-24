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
use ExpressionEngine\Library\Date\DateTrait;

/**
 * Date Filter
 *
 * This will provide the HTML for a filter that will list a set of "in the last
 * <<period>>" options as well as a custom <input> element for a specific date.
 * That <input> element will trigger a JS date picker to assist which will
 * ensure the date is correctly formatted.
 *
 * This will also interpret incoming date strings and will convert them to a
 * UNIX timestamp for use in the value() method.
 */
class Date extends Filter
{
    use DateTrait;

    /**
     * @var int The unix timestamp value of the filter
     */
    private $timestamp;

    /**
     * @todo inject $date_format (removes session & config dedpencies)
     * @todo inject ee()->localize (for string_to_timestamp and format_date)
     * @todo inject ee()->javascript (for set_global)
     * @todo inject ee()->cp (for ee()->cp->add_js_script)
     */
    public function __construct()
    {
        $this->name = 'filter_by_date';
        $this->label = 'date_filter';
        $this->placeholder = lang('custom_date');
        $this->options = array(
            '86400' => ucwords(lang('last') . ' 24 ' . lang('hours')),
            '604800' => ucwords(lang('last') . ' 7 ' . lang('days')),
            '2592000' => ucwords(lang('last') . ' 30 ' . lang('days')),
            '15552000' => ucwords(lang('last') . ' 180 ' . lang('days')),
            '31536000' => ucwords(lang('last') . ' 365 ' . lang('days')),
        );

        $date_format = ee()->session->userdata('date_format', ee()->config->item('date_format'));
        ee()->javascript->set_global('date.date_format', $date_format);

        $this->addDatePickerScript();

        $value = $this->value();
        if ($value && ! array_key_exists($value, $this->options)) {
            if (is_numeric($value)) {
                ee()->load->library('relative_date');

                $relative_date = ee()->relative_date->create(ee()->localize->now - $value);
                $relative_date->past = lang('last') . ' %s';
                $relative_date->about = '';
                $relative_date->calculate();
                $this->display_value = $relative_date->render();
            } else {
                $date = ee()->localize->string_to_timestamp($value . ' 0:00', true, $date_format . ' G:i');
                $this->timestamp = $date;
                $this->display_value = ee()->localize->format_date($date_format, $date);
                $this->selected_value = array($date, $date + 86400);
            }
        }
    }

    /**
     * Validation:
     *   - if the value of the filter is in the options then it is valid
     *   - if not and the value is an integer, then it is valid
     *   - otherwise it is invalid
     */
    public function isValid()
    {
        $value = $this->value();
        if (array_key_exists($value, $this->options)) {
            return true;
        }

        if (is_int($value)) {
            return true;
        }

        return false;
    }

    /**
     * @see Filter::render
     *
     * Overriding the abstract class's render method in order to pass in the
     * timestamp value to a custom 'date' view
     */
    public function render(ViewFactory $view, URL $url)
    {
        $options = $this->prepareOptions($url);

        if (empty($options)) {
            return;
        }

        $value = $this->display_value;
        if (is_null($value)) {
            $value = (array_key_exists($this->value(), $this->options)) ?
                $this->options[$this->value()] :
                $this->value();
        }

        // Create a filter URL without this filter (per-filter clear).
        $url_without_filter = clone $url;
        $url_without_filter->removeQueryStringVariable($this->name);

        $filter = array(
            'label' => $this->label,
            'name' => $this->name,
            'value' => $value,
            'custom_value' => (array_key_exists($this->name, $_POST)) ? $_POST[$this->name] : false,
            'placeholder' => $this->placeholder,
            'options' => $options,
            'timestamp' => $this->timestamp,
            'url_without_filter' => $url_without_filter,
        );

        return $view->make('_shared/filters/date')->render($filter);
    }
}

// EOF
