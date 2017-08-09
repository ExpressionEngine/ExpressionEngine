"use strict";

function Loading(props) {
  return React.createElement(
    "label",
    { className: "field-loading" },
    props.text ? props.text : EE.lang.loading,
    React.createElement("span", null)
  );
}