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
var FilterableSelectList = makeFilterableComponent(SelectList);

var Relationship =
/*#__PURE__*/
function (_React$Component) {
  _inherits(Relationship, _React$Component);

  function Relationship(props) {
    var _this;

    _classCallCheck(this, Relationship);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(Relationship).call(this, props));

    _defineProperty(_assertThisInitialized(_assertThisInitialized(_this)), "selectedItemsChanged", function (selectedItems) {
      _this.setState({
        selectedVisible: selectedItems
      });
    });

    _defineProperty(_assertThisInitialized(_assertThisInitialized(_this)), "selectionChanged", function (selected) {
      _this.setState({
        selected: selected,
        selectedVisible: selected
      });
    });

    _defineProperty(_assertThisInitialized(_assertThisInitialized(_this)), "handleRemove", function (event, item) {
      _this.selectionChanged(_this.state.selected.filter(function (thisItem) {
        return thisItem.value != item.value;
      }));

      event.preventDefault();
    });

    _this.state = {
      selected: SelectList.formatItems(props.selected)
    };
    _this.state.selectedVisible = _this.state.selected;
    return _this;
  }

  _createClass(Relationship, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this2 = this;

      // Allow new entries to be added to this field on the fly
      new MutableRelationshipField($(this.container), {
        success: function success(result, modal) {
          var selected = _this2.state.selected;

          if (_this2.props.multi) {
            selected.push(result.item);
          } else {
            selected = [result.item];
          }

          _this2.selectionChanged(selected);

          _this2.entryList.forceAjaxRefresh();

          modal.trigger('modal:close');
        }
      });
    } // Items visible in the selection container changed via filtering

  }, {
    key: "render",
    value: function render() {
      var _this3 = this;

      // Force the selected pane to re-render because we need to pass in new
      // items as props which the filterable component doesn't expect...
      var SelectedFilterableSelectList = makeFilterableComponent(SelectList);
      return React.createElement("div", {
        className: "fields-relate" + (this.props.multi ? ' fields-relate-multi' : ''),
        ref: function ref(container) {
          _this3.container = container;
        }
      }, React.createElement(FilterableSelectList, {
        items: this.props.items,
        name: this.props.name,
        limit: this.props.limit,
        multi: this.props.multi,
        selected: this.state.selected,
        selectionChanged: this.selectionChanged,
        selectionRemovable: true,
        selectionShouldRetainItemOrder: false,
        noResults: this.props.no_results,
        filterable: true,
        tooMany: true,
        filters: this.props.select_filters,
        filterUrl: this.props.filter_url,
        toggleAll: this.props.multi && this.props.items.length > SelectList.defaultProps.toggleAllLimit ? true : null,
        ref: function ref(entryList) {
          _this3.entryList = entryList;
        }
      }), this.props.multi && React.createElement(SelectedFilterableSelectList, {
        items: this.state.selectedVisible,
        selected: [],
        filterable: true,
        tooMany: true,
        selectable: false,
        reorderable: true,
        removable: true,
        handleRemove: function handleRemove(e, item) {
          return _this3.handleRemove(e, item);
        },
        itemsChanged: this.selectionChanged,
        selectionChanged: this.selectionChanged,
        noResults: this.props.no_related,
        toggleAll: this.props.items.length > SelectList.defaultProps.toggleAllLimit ? false : null
      }));
    }
  }], [{
    key: "renderFields",
    value: function renderFields(context) {
      $('div[data-relationship-react]', context).each(function () {
        var props = JSON.parse(window.atob($(this).data('relationshipReact')));
        props.name = $(this).data('inputValue');
        ReactDOM.render(React.createElement(Relationship, props, null), this);
      });
      $.fuzzyFilter();
    }
  }]);

  return Relationship;
}(React.Component);

$(document).ready(function () {
  Relationship.renderFields();
});
Grid.bind('relationship', 'display', function (cell) {
  Relationship.renderFields(cell);
});
FluidField.on('relationship', 'add', function (field) {
  Relationship.renderFields(field);
});