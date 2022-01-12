"use strict";

function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } Object.defineProperty(subClass, "prototype", { value: Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }), writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
var BulkEditEntries = /*#__PURE__*/function (_React$Component) {
  _inherits(BulkEditEntries, _React$Component);

  var _super = _createSuper(BulkEditEntries);

  function BulkEditEntries() {
    _classCallCheck(this, BulkEditEntries);

    return _super.apply(this, arguments);
  }

  _createClass(BulkEditEntries, [{
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps, prevState) {
      if (prevProps.initialItems.length != this.props.initialItems.length) {
        this.props.entriesChanged(this.props.initialItems);
      }
    }
  }, {
    key: "handleRemove",
    value: function handleRemove(item) {
      this.props.itemsChanged(this.props.initialItems.filter(function (thisItem) {
        return thisItem.value != item.value;
      }));
    }
  }, {
    key: "handleRemoveAll",
    value: function handleRemoveAll() {
      this.props.itemsChanged([]);
    }
  }, {
    key: "handleSearch",
    value: function handleSearch(searchTerm) {
      this.props.filterChange('search', searchTerm);
    }
  }, {
    key: "render",
    value: function render() {
      var _this = this;

      var limitedItems = this.props.items.slice(0, this.props.limit);
      var totalItems = this.props.initialItems.length;
      var lang = this.props.lang;
      return /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("div", {
        className: "title-bar"
      }, /*#__PURE__*/React.createElement("h2", {
        className: "title-bar__title"
      }, totalItems, " ", lang.selectedEntries)), /*#__PURE__*/React.createElement("form", {
        className: "add-mrg-top"
      }, /*#__PURE__*/React.createElement("input", {
        type: "text",
        placeholder: lang.filterSelectedEntries,
        onChange: function onChange(e) {
          return _this.handleSearch(e.target.value);
        }
      })), /*#__PURE__*/React.createElement("ul", {
        className: "list-group add-mrg-top"
      }, limitedItems.length == 0 && /*#__PURE__*/React.createElement("li", null, /*#__PURE__*/React.createElement("div", {
        className: "no-results",
        dangerouslySetInnerHTML: {
          __html: lang.noEntriesFound
        }
      })), limitedItems.map(function (item) {
        return /*#__PURE__*/React.createElement(BulkEditEntryItem, {
          item: item,
          handleRemove: function handleRemove(item) {
            return _this.handleRemove(item);
          },
          lang: lang
        });
      })), /*#__PURE__*/React.createElement("div", {
        className: "meta-info"
      }, lang.showing, " ", limitedItems.length, " ", lang.of, " ", totalItems, " \u2014 ", /*#__PURE__*/React.createElement("a", {
        href: true,
        className: "danger-link",
        onClick: function onClick(e) {
          return _this.handleRemoveAll();
        }
      }, /*#__PURE__*/React.createElement("i", {
        className: "fas fa-sm fa-times"
      }), " ", lang.clearAll)));
    }
  }], [{
    key: "render",
    value: function render(context, props) {
      $('div[data-bulk-edit-entries-react]', context).each(function () {
        ReactDOM.unmountComponentAtNode(this);
        ReactDOM.render(React.createElement(FilterableBulkEditEntries, props, null), this);
      });
    }
  }]);

  return BulkEditEntries;
}(React.Component);

_defineProperty(BulkEditEntries, "defaultProps", {
  items: [],
  limit: 50
});

function BulkEditEntryItem(props) {
  return /*#__PURE__*/React.createElement("li", {
    className: "list-item"
  }, /*#__PURE__*/React.createElement("div", {
    className: "list-item__content"
  }, /*#__PURE__*/React.createElement("div", null, props.item.label), /*#__PURE__*/React.createElement("div", {
    className: "list-item__secondary"
  }, /*#__PURE__*/React.createElement("a", {
    href: "#",
    className: "danger-link",
    onClick: function onClick(e) {
      return props.handleRemove(props.item);
    }
  }, /*#__PURE__*/React.createElement("i", {
    className: "fas fa-sm fa-times"
  }), " ", props.lang.removeFromSelection))));
}

var FilterableBulkEditEntries = makeFilterableComponent(BulkEditEntries);