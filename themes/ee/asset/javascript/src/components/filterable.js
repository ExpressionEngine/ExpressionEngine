'use strict';

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

/*!
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

function makeFilterableComponent(WrappedComponent) {
  return function (_React$Component) {
    _inherits(_class2, _React$Component);

    function _class2(props) {
      _classCallCheck(this, _class2);

      var _this = _possibleConstructorReturn(this, (_class2.__proto__ || Object.getPrototypeOf(_class2)).call(this, props));

      _this.itemsChanged = function (items) {
        _this.setState({
          items: items
        });
      };

      _this.initialItemsChanged = function (items) {
        _this.initialItems = items;

        if (_this.state.filterValues.search) {
          items = _this.filterItems(items, _this.state.filterValues.search);
        }

        _this.setState({
          items: items
        });

        if (_this.props.itemsChanged) {
          _this.props.itemsChanged(items);
        }
      };

      _this.filterChange = function (name, value) {
        var filterState = _this.state.filterValues;
        filterState[name] = value;
        _this.setState({ filterValues: filterState });

        // DOM filter
        if (!_this.ajaxFilter && name == 'search') {
          _this.itemsChanged(_this.filterItems(_this.initialItems, value));
          return;
        }

        // Debounce AJAX filter
        clearTimeout(_this.ajaxTimer);
        if (_this.ajaxRequest) _this.ajaxRequest.abort();

        var params = filterState;
        params.selected = _this.getSelectedValues(_this.props.selected);

        _this.setState({ loading: true });

        _this.ajaxTimer = setTimeout(function () {
          _this.ajaxRequest = _this.forceAjaxRefresh(params);
        }, 300);
      };

      _this.initialItems = SelectList.formatItems(props.items);
      _this.state = {
        items: _this.initialItems,
        initialCount: _this.initialItems.length,
        filterValues: {},
        loading: false
      };

      _this.ajaxFilter = SelectList.countItems(_this.initialItems) >= props.limit && props.filterUrl;
      _this.ajaxTimer = null;
      _this.ajaxRequest = null;
      return _this;
    }

    _createClass(_class2, [{
      key: 'filterItems',
      value: function filterItems(items, searchTerm) {
        var _this2 = this;

        items = items.map(function (item) {
          // Clone item so we don't modify reference types
          item = Object.assign({}, item);

          // If any children contain the search term, we'll keep the parent
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
      key: 'getSelectedValues',
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
      key: 'forceAjaxRefresh',
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
            _this3.setState({ loading: false });
            _this3.initialItemsChanged(SelectList.formatItems(data));
          },
          error: function error() {} // Defined to prevent error on .abort above
        });
      }
    }, {
      key: 'render',
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

    return _class2;
  }(React.Component);
}