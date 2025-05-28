<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Dependency\Webit\Util\EvalMath\EvalMath;

/**
 * Math Module
 */
class Math
{
    public $return_data = '';

    public function __construct()
    {
        $mathEvaluator = new EvalMath();

        $debug = (bool) (DEBUG or (isset(ee()->config) && ee()->config->item('debug') > 1) or (isset(ee()->session) && ee('Permission')->isSuperAdmin()));
        if (!$debug) {
            $mathEvaluator->suppress_errors = true;
        }

        $mathEvaluator->fb = array_merge($mathEvaluator->fb, [
            'round',
            'ceil',
            'floor'
        ]);
        $expression = ee()->TMPL->fetch_param('expression');
        if (ee()->TMPL->fetch_param('function') !== false && in_array((string) ee()->TMPL->fetch_param('function'), $mathEvaluator->fb)) {
            $expression = (string) ee()->TMPL->fetch_param('function') . '(' . $expression . ')';
        }
        $value = $mathEvaluator->evaluate($expression);

        if (is_nan($value)) {
            $this->return_data = ee()->TMPL->no_results;
            return $this->return_data;
        }

        $formatOptions = [];
        if (ee()->TMPL->fetch_param('decimals') !== false) {
            $formatOptions['decimals'] = ee()->TMPL->fetch_param('decimals');
        }
        if (ee()->TMPL->fetch_param('decimal_point') !== false) {
            $formatOptions['decimal_point'] = ee()->TMPL->fetch_param('decimal_point');
        }
        if (ee()->TMPL->fetch_param('thousands_separator') !== false) {
            $formatOptions['thousands_separator'] = ee()->TMPL->fetch_param('thousands_separator');
        }

        $var = (string) ee('Format')->make('Number', $value)->number_format($formatOptions);

        if (!empty(ee()->TMPL->tagdata)) {
            $this->return_data = ee()->TMPL->parse_variables(ee()->TMPL->tagdata, [['result' => $var]]);
        } else {
            $this->return_data = $var;
        }

        return $this->return_data;
    }
}
// END CLASS

// EOF
