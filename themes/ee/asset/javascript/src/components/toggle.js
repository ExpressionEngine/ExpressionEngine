'use strict';

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

var Toggle = function (_React$Component) {
  _inherits(Toggle, _React$Component);

  function Toggle(props) {
    _classCallCheck(this, Toggle);

    var _this = _possibleConstructorReturn(this, (Toggle.__proto__ || Object.getPrototypeOf(Toggle)).call(this, props));

    _initialiseProps.call(_this);

    _this.state = {
      on: props.on,
      value: props.value,
      onOff: props.on ? 'on' : 'off',
      trueFalse: props.on ? 'true' : 'false'
    };
    return _this;
  }

  _createClass(Toggle, [{
    key: 'render',
    value: function render() {
      return React.createElement(
        'a',
        { href: '#', className: "toggle-btn " + this.state.onOff, onClick: this.handleClick, alt: this.state.onOff, 'data-state': this.state.onOff, 'aria-checked': this.state.trueFalse, role: 'switch' },
        this.props.name && React.createElement('input', { type: 'hidden', name: this.props.name, value: this.state.value }),
        React.createElement('span', { className: 'slider' }),
        React.createElement('span', { className: 'option' })
      );
    }
  }]);

  return Toggle;
}(React.Component);

var _initialiseProps = function _initialiseProps() {
  var _this2 = this;

  this.handleClick = function (event) {
    event.preventDefault();
    _this2.setState(function (prevState, props) {
      if (props.handleToggle) props.handleToggle(!prevState.on);
      return {
        on: !prevState.on,
        value: !prevState.on ? props.offValue : props.onValue,
        onOff: !prevState.on ? 'on' : 'off',
        trueFalse: !prevState.on ? 'true' : 'false'
      };
    });
  };
};

function ToggleTools(props) {
  return React.createElement(
    'div',
    { className: 'toggle-tools' },
    React.createElement(
      'b',
      null,
      props.label
    ),
    props.children
  );
}