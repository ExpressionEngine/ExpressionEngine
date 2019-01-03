"use strict";

function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
var BulkEditEntries =
/*#__PURE__*/
function (_React$Component) {
  _inherits(BulkEditEntries, _React$Component);

  function BulkEditEntries() {
    _classCallCheck(this, BulkEditEntries);

    return _possibleConstructorReturn(this, _getPrototypeOf(BulkEditEntries).apply(this, arguments));
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
      return React.createElement("div", null, React.createElement("h2", null, totalItems, " ", lang.selectedEntries), React.createElement("form", {
        class: "field-search add-mrg-top"
      }, React.createElement("input", {
        type: "text",
        placeholder: lang.filterSelectedEntries,
        onChange: function onChange(e) {
          return _this.handleSearch(e.target.value);
        }
      })), React.createElement("ul", {
        class: "entry-list"
      }, limitedItems.length == 0 && React.createElement("li", {
        class: "entry-list__item entry-list__item---empty",
        dangerouslySetInnerHTML: {
          __html: lang.noEntriesFound
        }
      }), limitedItems.map(function (item) {
        return React.createElement(BulkEditEntryItem, {
          item: item,
          handleRemove: function handleRemove(item) {
            return _this.handleRemove(item);
          },
          lang: lang
        });
      })), React.createElement("div", {
        class: "entry-list__note"
      }, lang.showing, " ", limitedItems.length, " ", lang.of, " ", totalItems, " \u2014 ", React.createElement("a", {
        href: ""
      }, React.createElement("span", {
        class: "icon--remove"
      }), lang.clearAll)));
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
  return React.createElement("li", {
    class: "entry-list__item"
  }, React.createElement("h2", null, props.item.label), React.createElement("a", {
    href: "#",
    onClick: function onClick(e) {
      return props.handleRemove(props.item);
    }
  }, React.createElement("span", {
    class: "icon--remove"
  }), props.lang.removeFromSelection));
}

var FilterableBulkEditEntries = makeFilterableComponent(BulkEditEntries);