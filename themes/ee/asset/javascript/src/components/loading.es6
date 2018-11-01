/*!
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

function Loading (props) {
  return (
    <label className="field-loading">
      {(props.text ? props.text : EE.lang.loading)}<span></span>
    </label>
  )
}
