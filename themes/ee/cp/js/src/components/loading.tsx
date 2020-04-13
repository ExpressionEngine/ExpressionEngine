/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
<<<<<<< HEAD:themes/ee/cp/js/src/components/loading.tsx

declare var EE: any

function Loading(props) {
    return (
        <label className="field-loading">
            {(props.text ? props.text : EE.lang.loading)}<span></span>
        </label>
    )
}
=======
function Loading(props) {
  return React.createElement("label", {
    className: "field-loading"
  }, props.text ? props.text : EE.lang.loading, React.createElement("span", null));
}
>>>>>>> release/5.3.1:themes/ee/asset/javascript/src/components/loading.js
