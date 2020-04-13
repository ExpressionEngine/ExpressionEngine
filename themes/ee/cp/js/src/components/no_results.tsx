/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
function NoResults(props) {
<<<<<<< HEAD:themes/ee/cp/js/src/components/no_results.tsx
    return (
        <label className="field-empty" dangerouslySetInnerHTML={{ __html: props.text }} />
    )
}
=======
  return React.createElement("label", {
    className: "field-empty",
    dangerouslySetInnerHTML: {
      __html: props.text
    }
  });
}
>>>>>>> release/5.3.1:themes/ee/asset/javascript/src/components/no_results.js
