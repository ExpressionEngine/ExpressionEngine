"use strict";

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

function GridImagesProgressTable(props) {
  return React.createElement(
    "div",
    { className: "field-file-upload__table" },
    React.createElement(
      "div",
      { className: "tbl-wrap" },
      React.createElement(
        "table",
        null,
        React.createElement(
          "tbody",
          null,
          React.createElement(
            "tr",
            null,
            React.createElement(
              "th",
              null,
              "File Name"
            ),
            React.createElement(
              "th",
              null,
              "Progress"
            )
          ),
          props.files.map(function (file) {
            return React.createElement(
              "tr",
              { key: file.name },
              React.createElement(
                "td",
                null,
                file.name
              ),
              React.createElement(
                "td",
                null,
                React.createElement(
                  "div",
                  { className: "progress-bar" },
                  React.createElement("div", { className: "progress", style: { width: file.progress + '%' } })
                )
              )
            );
          })
        )
      )
    )
  );
}