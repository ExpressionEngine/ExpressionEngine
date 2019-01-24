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
function FieldTools(props) {
  return React.createElement("div", {
    className: "field-tools"
  }, props.children);
}

function FilterBar(props) {
  return React.createElement("div", {
    className: "filter-bar"
  }, props.children);
}

var FilterToggleAll =
/*#__PURE__*/
function (_React$Component) {
  _inherits(FilterToggleAll, _React$Component);

  function FilterToggleAll(props) {
    var _this;

    _classCallCheck(this, FilterToggleAll);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(FilterToggleAll).call(this, props));

    _defineProperty(_assertThisInitialized(_assertThisInitialized(_this)), "handleClick", function () {
      // Clear all will always be "unchecked" to the parent
      if (!_this.props.checkAll) {
        _this.props.onToggleAll(false);

        return;
      }

      var checked = !_this.state.checked;

      _this.setState({
        checked: checked
      });

      _this.props.onToggleAll(checked);
    });

    _this.state = {
      checked: false
    };
    return _this;
  }

  _createClass(FilterToggleAll, [{
    key: "render",
    value: function render() {
      return React.createElement("div", {
        className: "field-ctrl"
      }, React.createElement("label", {
        className: (this.props.checkAll ? "field-toggle-all" : "field-clear-all") + (this.state.checked ? " act" : ""),
        onClick: this.handleClick
      }, this.props.checkAll ? EE.lang.check_all : EE.lang.clear_all));
    }
  }]);

  return FilterToggleAll;
}(React.Component);

function FilterSearch(props) {
  return React.createElement("div", {
    className: "filter-item filter-item__search"
  }, React.createElement("input", {
    type: "text",
    placeholder: EE.lang.keyword_search,
    onChange: props.onSearch
  }));
}

var FilterSelect =
/*#__PURE__*/
function (_React$Component2) {
  _inherits(FilterSelect, _React$Component2);

  function FilterSelect(props) {
    var _this2;

    _classCallCheck(this, FilterSelect);

    _this2 = _possibleConstructorReturn(this, _getPrototypeOf(FilterSelect).call(this, props));

    _defineProperty(_assertThisInitialized(_assertThisInitialized(_this2)), "handleSearch", function (event) {
      _this2.setState({
        items: _this2.initialItems.filter(function (item) {
          return item.label.toLowerCase().includes(event.target.value.toLowerCase());
        })
      });
    });

    _defineProperty(_assertThisInitialized(_assertThisInitialized(_this2)), "selectItem", function (event, item) {
      if (_this2.props.keepSelectedState) {
        _this2.setState({
          selected: item
        });
      }

      _this2.props.onSelect(item ? item.value : null);

      $(event.target).closest('.filter-item').find('.js-filter-link').click();
      event.preventDefault();
    });

    _this2.initialItems = SelectList.formatItems(props.items);
    _this2.state = {
      items: _this2.initialItems,
      selected: null
    };
    return _this2;
  }

  _createClass(FilterSelect, [{
    key: "render",
    value: function render() {
      var _this3 = this;

      return React.createElement("div", {
        className: "filter-item" + (this.props.center ? ' filter-item--center' : '')
      }, React.createElement("a", {
        href: "#",
        className: "js-filter-link filter-item__link filter-item__link--has-submenu" + (this.props.action ? ' filter-item__link--action' : ''),
        onClick: this.toggle
      }, this.props.title), React.createElement("div", {
        className: "filter-submenu"
      }, this.state.items.length > 7 && React.createElement("div", {
        className: "filter-submenu__search"
      }, React.createElement("form", null, React.createElement("input", {
        type: "text",
        placeholder: this.props.placeholder,
        onChange: this.handleSearch
      }))), this.state.selected && React.createElement("div", {
        className: "filter-submenu__selected"
      }, React.createElement("a", {
        href: "#",
        onClick: function onClick(e) {
          return _this3.selectItem(e, null);
        }
      }, this.state.selected.label)), React.createElement("div", {
        className: "filter-submenu__scroll"
      }, this.state.items.map(function (item) {
        return React.createElement("a", {
          href: "#",
          key: item.value,
          className: "filter-submenu__link filter-submenu__link---active " + _this3.props.itemClass,
          rel: _this3.props.rel,
          onClick: function onClick(e) {
            return _this3.selectItem(e, item);
          }
        }, item.label);
      }))));
    }
  }]);

  return FilterSelect;
}(React.Component);