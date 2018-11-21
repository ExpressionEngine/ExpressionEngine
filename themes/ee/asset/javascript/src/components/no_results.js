"use strict";

/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

function NoResults(props) {
  return React.createElement("label", { className: "field-empty", dangerouslySetInnerHTML: { __html: props.text } });
}