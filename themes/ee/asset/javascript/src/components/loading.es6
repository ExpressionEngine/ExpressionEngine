/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

function Loading (props) {
  return (
    <label className="field-loading">
      {(props.text ? props.text : EE.lang.loading)}<span></span>
    </label>
  )
}
