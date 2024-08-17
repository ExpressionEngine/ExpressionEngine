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
        $value = $mathEvaluator->evaluate(ee()->TMPL->fetch_param('expression'));

        if (is_nan($value)) {
            $this->return_data = ee()->TMPL->no_results;
            return $this->return_data;
        }

        $formatOptions = [];
        if (ee()->TMPL->fetch_param('decimals')) {
            $formatOptions['decimals'] = ee()->TMPL->fetch_param('decimals');
        }
        if (ee()->TMPL->fetch_param('decimal_point')) {
            $formatOptions['decimal_point'] = ee()->TMPL->fetch_param('decimal_point');
        }
        if (ee()->TMPL->fetch_param('thousands_separator')) {
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
