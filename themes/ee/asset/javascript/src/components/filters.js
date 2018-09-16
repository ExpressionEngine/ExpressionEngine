"use strict";

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

function FieldTools(props) {
  return React.createElement(
    "div",
    { className: "field-tools" },
    props.children
  );
}

function FilterBar(props) {
  return React.createElement(
    "div",
    { className: "filter-bar" },
    props.children
  );
}

var FilterToggleAll = function (_React$Component) {
  _inherits(FilterToggleAll, _React$Component);

  function FilterToggleAll(props) {
    _classCallCheck(this, FilterToggleAll);

    var _this = _possibleConstructorReturn(this, (FilterToggleAll.__proto__ || Object.getPrototypeOf(FilterToggleAll)).call(this, props));

    _this.handleClick = function () {
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
    };

    _this.state = {
      checked: false
    };
    return _this;
  }

  _createClass(FilterToggleAll, [{
    key: "render",
    value: function render() {
      return React.createElement(
        "div",
        { className: "field-ctrl" },
        React.createElement(
          "label",
          { className: (this.props.checkAll ? "field-toggle-all" : "field-clear-all") + (this.state.checked ? " act" : ""),
            onClick: this.handleClick },
          this.props.checkAll ? EE.lang.check_all : EE.lang.clear_all
        )
      );
    }
  }]);

  return FilterToggleAll;
}(React.Component);

function FilterSearch(props) {
  return React.createElement(
    "div",
    { className: "filter-item filter-item__search" },
    React.createElement("input", { type: "text", placeholder: EE.lang.keyword_search, onChange: props.onSearch })
  );
}

var FilterSelect = function (_React$Component2) {
  _inherits(FilterSelect, _React$Component2);

  function FilterSelect(props) {
    _classCallCheck(this, FilterSelect);

    var _this2 = _possibleConstructorReturn(this, (FilterSelect.__proto__ || Object.getPrototypeOf(FilterSelect)).call(this, props));

    _this2.handleSearch = function (event) {
      _this2.setState({ items: _this2.initialItems.filter(function (item) {
          return item.label.toLowerCase().includes(event.target.value.toLowerCase());
        }) });
    };

    _this2.selectItem = function (event, item) {
      if (_this2.props.keepSelectedState) {
        _this2.setState({ selected: item });
      }
      _this2.props.onSelect(item ? item.value : null);
      $(event.target).closest('.filter-item').find('.js-filter-link').click();
      event.preventDefault();
    };

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

      return React.createElement(
        "div",
        { className: "filter-item" + (this.props.center ? ' filter-item--center' : '') },
        React.createElement(
          "a",
          { href: "#", className: "js-filter-link filter-item__link filter-item__link--has-submenu" + (this.props.action ? ' filter-item__link--action' : ''), onClick: this.toggle },
          this.props.title
        ),
        React.createElement(
          "div",
          { className: "filter-submenu" },
          this.state.items.length > 7 && React.createElement(
            "div",
            { className: "filter-submenu__search" },
            React.createElement(
              "form",
              null,
              React.createElement("input", { type: "text", placeholder: this.props.placeholder, onChange: this.handleSearch })
            )
          ),
          this.state.selected && React.createElement(
            "div",
            { className: "filter-submenu__selected" },
            React.createElement(
              "a",
              { href: "#", onClick: function onClick(e) {
                  return _this3.selectItem(e, null);
                } },
              this.state.selected.label
            )
          ),
          React.createElement(
            "div",
            { className: "filter-submenu__scroll" },
            this.state.items.map(function (item) {
              return React.createElement(
                "a",
                { href: "#", key: item.value, className: "filter-submenu__link filter-submenu__link---active", onClick: function onClick(e) {
                    return _this3.selectItem(e, item);
                  } },
                item.label
              );
            })
          )
        )
      );
    }
  }]);

  return FilterSelect;
}(React.Component);