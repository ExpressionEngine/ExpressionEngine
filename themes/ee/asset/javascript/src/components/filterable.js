"use strict";

function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _extends() { _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
function makeFilterableComponent(WrappedComponent) {
  var _temp;

  return _temp = /*#__PURE__*/function (_React$Component) {
    _inherits(_temp, _React$Component);

    function _temp(props) {
      var _this;

      _classCallCheck(this, _temp);

      _this = _possibleConstructorReturn(this, _getPrototypeOf(_temp).call(this, props));

      _defineProperty(_assertThisInitialized(_this), "itemsChanged", function (items) {
        _this.setState({
          items: items
        });
      });

      _defineProperty(_assertThisInitialized(_this), "initialItemsChanged", function (items) {
        _this.initialItems = items;

        if (!_this.ajaxFilter && _this.state.filterValues.search) {
          items = _this.filterItems(items, _this.state.filterValues.search);
        }

        _this.setState({
          items: items
        });

        if (_this.props.itemsChanged) {
          _this.props.itemsChanged(items);
        }
      });

      _defineProperty(_assertThisInitialized(_this), "filterChange", function (name, value) {
        var filterState = _this.state.filterValues;
        filterState[name] = value;

        _this.setState({
          filterValues: filterState
        }); // DOM filter


        if (!_this.ajaxFilter && name == 'search') {
          _this.itemsChanged(_this.filterItems(_this.initialItems, value));

          return;
        } // Debounce AJAX filter


        clearTimeout(_this.ajaxTimer);
        if (_this.ajaxRequest) _this.ajaxRequest.abort();
        var params = filterState;
        params.selected = _this.getSelectedValues(_this.props.selected);

        _this.setState({
          loading: true
        });

        _this.ajaxTimer = setTimeout(function () {
          _this.ajaxRequest = _this.forceAjaxRefresh(params);
        }, 300);
      });

      _this.initialItems = SelectList.formatItems(props.items);
      _this.state = {
        items: _this.initialItems,
        initialCount: _this.initialItems.length,
        filterValues: {},
        loading: false
      };
      _this.ajaxFilter = SelectList.countItems(_this.initialItems) >= props.limit && props.filterUrl;
      _this.ajaxTimer = null;
      _this.ajaxRequest = null; // We need this function only for checkbox that have selected elements and there are more than tooMany
      // excluding categories on the Entry page

      if (props.tooMany && props.multi && _this.props.selected.length && !props.name.startsWith("categories[")) {
        _this.moveSelectableToTop();
      }

      return _this;
    }

    _createClass(_temp, [{
      key: "filterItems",
      value: function filterItems(items, searchTerm) {
        var _this2 = this;

        items = items.map(function (item) {
          // Clone item so we don't modify reference types
          item = Object.assign({}, item); // If any children contain the search term, we'll keep the parent

          if (item.children) item.children = _this2.filterItems(item.children, searchTerm);
          var itemFoundInChildren = item.children && item.children.length > 0;
          var itemFound = String(item.label).toLowerCase().includes(searchTerm.toLowerCase());
          return itemFound || itemFoundInChildren ? item : false;
        });
        return items.filter(function (item) {
          return item;
        });
      }
    }, {
      key: "getSelectedValues",
      value: function getSelectedValues(selected) {
        var values = [];

        if (selected instanceof Array) {
          values = selected.map(function (item) {
            return item.value;
          });
        } else if (selected.value) {
          values = [selected.value];
        }

        return values.join('|');
      }
    }, {
      key: "forceAjaxRefresh",
      value: function forceAjaxRefresh(params) {
        var _this3 = this;

        if (!params) {
          params = this.state.filterValues;
          params.selected = this.getSelectedValues(this.props.selected);
        }

        return $.ajax({
          url: this.props.filterUrl,
          data: $.param(params),
          dataType: 'json',
          success: function success(data) {
            _this3.setState({
              loading: false
            });

            _this3.initialItemsChanged(SelectList.formatItems(data));
          },
          error: function error() {} // Defined to prevent error on .abort above

        });
      }
    }, {
      key: "moveSelectableToTop",
      value: function moveSelectableToTop() {
        var regularItems = this.state.items;
        var selectedItems = this.props.selected;
        var checked = [];
        var unchecked = regularItems.filter(function (i) {
          return selectedItems.every(function (item) {
            return item.value != i.value;
          });
        });
        var checkedIndex = selectedItems.map(function (el) {
          return el.value;
        });
        regularItems.filter(function (item) {
          selectedItems.forEach(function (el) {
            if (item.value == el.value) {
              checked.push(item);
            }
          });
        }); // first shows checked elements then elements that are not checked

        var newImemsOrder = checked.concat(unchecked);
        this.setState({
          items: newImemsOrder
        });
        this.state.items = newImemsOrder;
      }
    }, {
      key: "render",
      value: function render() {
        var _this4 = this;

        return React.createElement(WrappedComponent, _extends({}, this.props, {
          loading: this.state.loading,
          filterChange: function filterChange(name, value) {
            return _this4.filterChange(name, value);
          },
          initialItems: this.initialItems,
          initialCount: this.state.initialCount,
          items: this.state.items,
          itemsChanged: this.initialItemsChanged
        }));
      }
    }]);

    return _temp;
  }(React.Component), _temp;
}