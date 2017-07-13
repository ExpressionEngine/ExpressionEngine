"use strict";

function NoResults(props) {
  return React.createElement("label", { className: "field-empty", dangerouslySetInnerHTML: { __html: props.text } });
}