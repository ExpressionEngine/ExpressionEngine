"use strict";

function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

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

/**
 * VirtualList - A lightweight virtualization component for rendering large lists
 * Only renders items that are currently visible in the viewport
 */
var VirtualList = /*#__PURE__*/function (_React$Component) {
  _inherits(VirtualList, _React$Component);

  function VirtualList(props) {
    var _this;

    _classCallCheck(this, VirtualList);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(VirtualList).call(this, props));

    _defineProperty(_assertThisInitialized(_this), "handleScroll", function (event) {
      var scrollTop = event.target.scrollTop;

      _this.setState({
        scrollTop: scrollTop
      });
    });

    _this.state = {
      scrollTop: 0
    };
    _this.containerRef = React.createRef();
    return _this;
  }

  _createClass(VirtualList, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      // Set up scroll listener
      if (this.containerRef.current) {
        this.containerRef.current.addEventListener('scroll', this.handleScroll);
      }
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      // Clean up scroll listener
      if (this.containerRef.current) {
        this.containerRef.current.removeEventListener('scroll', this.handleScroll);
      }
    }
  }, {
    key: "getItemHeight",
    value: function getItemHeight(index) {
      var item = this.props.items[index];

      if (typeof this.props.itemHeight === 'function') {
        return this.props.itemHeight(item, index);
      }

      return this.props.itemHeight || 40;
    }
  }, {
    key: "calculateVisibleRange",
    value: function calculateVisibleRange() {
      var scrollTop = this.state.scrollTop;
      var containerHeight = this.props.height || 400;
      var items = this.props.items;
      var overscan = this.props.overscan || 5;
      var totalHeight = 0;
      var startIndex = 0;
      var endIndex = 0; // Find start index

      for (var i = 0; i < items.length; i++) {
        var itemHeight = this.getItemHeight(i);

        if (totalHeight + itemHeight > scrollTop) {
          startIndex = Math.max(0, i - overscan);
          break;
        }

        totalHeight += itemHeight;
      } // Find end index


      totalHeight = 0;

      for (var j = 0; j < items.length; j++) {
        var _itemHeight = this.getItemHeight(j);

        totalHeight += _itemHeight;

        if (totalHeight > scrollTop + containerHeight) {
          endIndex = Math.min(items.length - 1, j + overscan);
          break;
        }
      }

      if (endIndex === 0) {
        endIndex = items.length - 1;
      }

      return {
        startIndex: startIndex,
        endIndex: endIndex
      };
    }
  }, {
    key: "calculateTotalHeight",
    value: function calculateTotalHeight() {
      var items = this.props.items;
      var totalHeight = 0;

      for (var i = 0; i < items.length; i++) {
        totalHeight += this.getItemHeight(i);
      }

      return totalHeight;
    }
  }, {
    key: "calculateOffsetTop",
    value: function calculateOffsetTop(startIndex) {
      var offset = 0;

      for (var i = 0; i < startIndex; i++) {
        offset += this.getItemHeight(i);
      }

      return offset;
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      var items = this.props.items;
      var containerHeight = this.props.height || 400;

      if (!items || items.length === 0) {
        return this.props.children || null;
      }

      var _this$calculateVisibl = this.calculateVisibleRange(),
          startIndex = _this$calculateVisibl.startIndex,
          endIndex = _this$calculateVisibl.endIndex;

      var totalHeight = this.calculateTotalHeight();
      var offsetTop = this.calculateOffsetTop(startIndex);
      var visibleItems = [];

      for (var i = startIndex; i <= endIndex; i++) {
        if (i < items.length) {
          visibleItems.push({
            item: items[i],
            index: i
          });
        }
      }

      return React.createElement("div", {
        ref: this.containerRef,
        className: this.props.className || '',
        style: {
          height: "".concat(containerHeight, "px"),
          overflow: 'auto',
          position: 'relative'
        }
      }, React.createElement("div", {
        style: {
          height: "".concat(totalHeight, "px"),
          position: 'relative'
        }
      }, React.createElement("div", {
        style: {
          position: 'absolute',
          top: "".concat(offsetTop, "px"),
          left: 0,
          right: 0
        }
      }, visibleItems.map(function (visibleItem) {
        return _this2.props.renderItem(visibleItem.item, visibleItem.index);
      }))));
    }
  }]);

  return VirtualList;
}(React.Component);

_defineProperty(VirtualList, "defaultProps", {
  height: 400,
  itemHeight: 40,
  overscan: 5
});