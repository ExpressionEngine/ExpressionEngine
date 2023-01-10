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
 * Perpage Filter
 *
 * This will provide the HTML for a filter that will list a set of "<<number>>
 * results" options, a custom <input> element to specify a custom perpage number,
 * and a "All <<total>> results" option.
 */
class Perpage extends Filter
{
    protected $total_threshold = 1000;
    protected $confirm_show_all = false;
    protected $hide_reset = false;

    /**
     * Initializes our Perpage filter
     *
     * @todo inject ee()->cp (for ee()->cp->add_js_script)
     *
     * @param  int $total The total number of items available
     * @param  string $lang_key The optional lang key to use for the "All
     *                          <<$total>> items" option
     * @param  bool $is_modal Is this Perpage filter in/for a modal?
     * @param  bool $hide_reset Should we force hiding 'clear filters' button?
     * @return void
     */
    public function __construct($total, $all_lang_key = 'all_items', $is_modal = false, $hide_reset = false)
    {
        $total = (int) $total;

        if ($total >= $this->total_threshold) {
            $this->confirm_show_all = true;
            ee()->cp->add_js_script(array(
                'file' => array('cp/perpage'),
            ));
        }

        $this->hide_reset = $hide_reset;
        $this->name = 'perpage';
        $this->label = 'perpage_filter';
        $this->placeholder = lang('custom_limit');
        $this->options = array(
            '25' => '25 ' . lang('results'),
            '50' => '50 ' . lang('results'),
            '75' => '75 ' . lang('results'),
            '100' => '100 ' . lang('results'),
            '150' => '150 ' . lang('results'),
            $total => sprintf(lang($all_lang_key), $total)
        );
        $this->default_value = 25;

        if ($is_modal) {
            $this->options = array('10' => '10 ' . lang('results')) + $this->options;
            $this->default_value = 25;
        }
    }

    /**
     * Determines if the value set for this filter is the default value or not.
     *
     * @return bool TRUE if the value is not the default, FALSE otherwise
     */
    public function canReset()
    {
        if ($this->hide_reset) {
            return !$this->hide_reset;
        }

        return ($this->value() != $this->default_value);
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
        $value = parent::value();

        if (! (int) $value) {
            $value = $this->default_value;
        }

        return (int) $value;
    }

    /**
     * Validation:
     *   - if value is a number, then it is valid
     *   - otherwise it is invalid
     */
    public function isValid()
    {
        $value = $this->value();

        if (is_int($value) && $value > 0) {
            return true;
        }

        return false;
    }

    /**
     * @see Filter::render
     *
     * Overriding the abstract class's render method in order to render a custom
     * perpage view which includes a modal for show-all
     */
    public function render(ViewFactory $view, URL $url)
    {
        $options = $this->prepareOptions($url);

        if (empty($options)) {
            return;
        }

        $value = $this->value();
        if (is_null($value)) {
            $value = (array_key_exists($this->value(), $this->options)) ?
                $this->options[$this->value()] :
                $this->value();
        }

        $urls = array_keys($options);
        $show_all_url = end($urls);

        $filter = array(
            'label' => $this->label,
            'name' => $this->name,
            'value' => $value,
            'has_custom_value' => $this->has_custom_value,
            'custom_value' => (array_key_exists($this->name, $_POST)) ? $_POST[$this->name] : false,
            'placeholder' => $this->placeholder,
            'options' => $options,
            'show_all_url' => $show_all_url,
            'confirm_show_all' => $this->confirm_show_all,
            'threshold' => $this->total_threshold
        );

        return $view->make('_shared/filters/perpage')->render($filter);
    }
}

// EOF
