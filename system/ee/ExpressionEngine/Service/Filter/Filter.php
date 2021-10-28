<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Filter;

use ExpressionEngine\Library\CP\URL;
use ExpressionEngine\Service\View\ViewFactory;

/**
 * abstract Filter
 */
abstract class Filter
{
    /**
     * @var string The name="" attribute and query string parameter name for
     *             this filter
     */
    public $name;

    /**
     * @var stirng A language key to use for the display label
     */
    protected $label;

    /**
     * @var mixed The default value to use for this filter when no value is
     *   submitted
     */
    protected $default_value;

    /**
     * @var string The display-ready value of the filter (i.e. 'admin' instead
     *  of 1)
     */
    protected $display_value;

    /**
     * @var mixed The value to use for this filter (overrides any submitted data)
     */
    protected $selected_value;

    /**
     * @var array An associative array to use to build the option list. The
     *   keys will be used as the values passed back, and the values will be
     *   used for display. i.e.
     *     'installed'   => lang('installed'),
     *     'uninstalled' => lang('uninstalled')
     */
    protected $options = array();

    /**
     * @var string The value to use for the custom input's placeholder="" attribute
     */
    protected $placeholder;

    /**
     * @var bool Whether or not this filter has a custom <input> element
     */
    protected $has_custom_value = true;

    /**
     * @var bool Whether or not the list should be filterable. Cannot be used
     * together with has_custom_value.
     */
    protected $has_list_filter = false;

    /**
     * @var string The name of the view to use when rendering
     */
    protected $view = 'filter';

    /**
     * @var string Class to apply to parent list element
     */
    public $list_class = '';

    /**
     * Determines the value of this filter. If a selected_value was set, that
     * is used. Otherwise we'll determine the value by using the POST value, GET
     * vale or default value (in that order).
     *
     * @return mixed The value of the filter
     */
    public function value()
    {
        if (isset($this->selected_value)) {
            return $this->selected_value;
        }

        $value = $this->derivedValue();

        if (! $this->has_custom_value) {
            $value = $this->isValid() ? $value : null;
        }

        return is_null($value) ? null : htmlentities($value, ENT_NOQUOTES, 'UTF-8');
    }

    /**
     * Determines the value of this filter. If a selected_value was set, that
     * is used. Otherwise we'll determine the value by using the POST value, GET
     * valeu or default value (in that order).
     *
     * @return mixed The value of the filter
     */
    protected function derivedValue()
    {
        if (isset($this->selected_value)) {
            return $this->selected_value;
        }

        $value = $this->default_value;

        if (isset($_POST[$this->name]) && ! empty($_POST[$this->name])) {
            $value = $_POST[$this->name];
        } elseif (isset($_GET[$this->name])) {
            $value = $_GET[$this->name];
        }

        return $value;
    }

    /**
     * Determines if the value set for this filter is the default value or not.
     *
     * @return bool TRUE if the value is not the default, FALSE otherwise
     */
    public function canReset()
    {
        return ($this->value() != $this->default_value);
    }

    /**
     * This is a stub for validation.
     *
     * @return bool True (assumed to be valid)
     */
    public function isValid()
    {
        $value = $this->derivedValue();

        if (is_null($value)) {
            return true;
        }

        return (array_key_exists($value, $this->options));
    }

    /**
     * This renders the filter into HTML.
     *
     * @uses ViewFactory::make to create a View instance
     * @uses \ExpressionEngine\Service\View\View::render to generate HTML
     *
     * @param ViewFactory $view A view factory responsible for making a view
     * @param URL $url A URL object for use in generating URLs for the filter
     *   options
     * @return string Returns HTML
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

        if (! $this->isValid()) {
            $value = $this->default_value;
        }

        // Create a filter URL without this filter (per-filter clear).
        $url_without_filter = clone $url;
        $url_without_filter->removeQueryStringVariable($this->name);

        $filter = array(
            'label' => $this->label,
            'name' => $this->name,
            'value' => $value,
            'has_list_filter' => $this->has_list_filter,
            'has_custom_value' => $this->has_custom_value,
            'custom_value' => (array_key_exists($this->name, $_POST)) ? $_POST[$this->name] : false,
            'placeholder' => $this->placeholder,
            'options' => $options,
            'url_without_filter' => $url_without_filter,
        );

        return $view->make('_shared/filters/filter')->render($filter);
    }

    /**
     * Compiles URLs for all the options
     *
     * @uses URL::compile To generate a full URL i.e.
     *    http://example.com/admin.php?/cp/foo/bar&perpage=25&S=12345
     *
     * @param obj $base_url A CP/URL object that serves as the base of the URLs
     * @return array An associative array of the options where the key is a
     *   URL and the value is the label. i.e.
     *     'http://index/admin.php?cp/foo&filter_by_bar=2' => 'Baz'
     */
    protected function prepareOptions(URL $base_url)
    {
        $options = array();
        $base_url->removeQueryStringVariable('columns');
        foreach ($this->options as $show => $label) {
            $url = clone $base_url;
            $url->setQueryStringVariable($this->name, $show);
            $options[$url->compile()] = htmlentities($label, ENT_QUOTES, 'UTF-8');
        }

        return $options;
    }

    /**
     * Returns the options array
     *
     * @return array An associtive array
     */
    public function getOptions()
    {
        return $this->options;
    }
}
// END CLASS

// EOF
