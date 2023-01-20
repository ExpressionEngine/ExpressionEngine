/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
function Loading(props) {
  return React.createElement("label", {
    className: "field-loading"
  }, props.text ? props.text : EE.lang.loading, React.createElement("span", null));
}
