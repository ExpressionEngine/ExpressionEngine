"use strict";

function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
var Toggle =
/*#__PURE__*/
function (_React$Component) {
  _inherits(Toggle, _React$Component);

  function Toggle(_props) {
    var _this;

    _classCallCheck(this, Toggle);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(Toggle).call(this, _props));

    _defineProperty(_assertThisInitialized(_assertThisInitialized(_this)), "handleClick", function (event) {
      event.preventDefault();

      _this.setState(function (prevState, props) {
        if (props.handleToggle) props.handleToggle(!prevState.on);
        return {
          on: !prevState.on,
          value: !prevState.on ? props.offValue : props.onValue,
          onOff: !prevState.on ? 'on' : 'off',
          trueFalse: !prevState.on ? 'true' : 'false'
        };
      });
    });

    _this.state = {
      on: _props.on,
      value: _props.value,
      onOff: _props.on ? 'on' : 'off',
      trueFalse: _props.on ? 'true' : 'false'
    };
    return _this;
  }

  _createClass(Toggle, [{
    key: "render",
    value: function render() {
      return React.createElement("a", {
        href: "#",
        className: "toggle-btn " + this.state.onOff,
        onClick: this.handleClick,
        alt: this.state.onOff,
        "data-state": this.state.onOff,
        "aria-checked": this.state.trueFalse,
        role: "switch"
      }, this.props.name && React.createElement("input", {
        type: "hidden",
        name: this.props.name,
        value: this.state.value
      }), React.createElement("span", {
        className: "slider"
      }), React.createElement("span", {
        className: "option"
      }));
    }
  }]);

  return Toggle;
}(React.Component);

function ToggleTools(props) {
  return React.createElement("div", {
    className: "toggle-tools"
  }, React.createElement("b", null, props.label), props.children);
}