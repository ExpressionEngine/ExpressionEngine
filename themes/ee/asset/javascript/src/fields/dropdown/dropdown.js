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
var Dropdown =
/*#__PURE__*/
function (_React$Component) {
  _inherits(Dropdown, _React$Component);

  function Dropdown(props) {
    var _this;

    _classCallCheck(this, Dropdown);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(Dropdown).call(this, props));

    _defineProperty(_assertThisInitialized(_assertThisInitialized(_this)), "selectionChanged", function (selected) {
      _this.setState({
        selected: selected,
        open: false
      });

      if (_this.props.groupToggle) {
        EE.cp.form_group_toggle(_this.input);
      }
    });

    _defineProperty(_assertThisInitialized(_assertThisInitialized(_this)), "toggleOpen", function () {
      _this.setState(function (prevState, props) {
        return {
          open: !prevState.open
        };
      });
    });

    _this.state = {
      selected: _this.getItemForSelectedValue(props.selected),
      open: false
    };
    return _this;
  }

  _createClass(Dropdown, [{
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps, prevState) {
      if (!prevState.selected && this.state.selected || prevState.selected && prevState.selected.value != this.state.selected.value) {
        if (this.props.groupToggle) {
          EE.cp.form_group_toggle(this.input);
        }

        $(this.input).trigger('change');
      }
    }
  }, {
    key: "componentDidMount",
    value: function componentDidMount() {
      if (this.props.groupToggle) {
        EE.cp.form_group_toggle(this.input);
      }
    }
  }, {
    key: "getItemForSelectedValue",
    value: function getItemForSelectedValue(value) {
      return this.props.initialItems.find(function (item) {
        return String(item.value) == String(value);
      });
    }
  }, {
    key: "handleSearch",
    value: function handleSearch(searchTerm) {
      this.props.filterChange('search', searchTerm);
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      var tooMany = this.props.items.length > this.props.tooMany && !this.state.loading;
      var selected = this.state.selected;
      return React.createElement("div", {
        className: "fields-select-drop" + (tooMany ? ' field-resizable' : '')
      }, React.createElement("div", {
        className: "field-drop-selected" + (this.state.open ? ' field-open' : ''),
        onClick: this.toggleOpen
      }, React.createElement("label", {
        className: this.state.selected ? 'act' : ''
      }, selected && React.createElement("i", null, selected.sectionLabel ? selected.sectionLabel + ' / ' : '', selected.label), !selected && React.createElement("i", null, this.props.emptyText), React.createElement("input", {
        type: "hidden",
        ref: function ref(input) {
          _this2.input = input;
        },
        name: this.props.name,
        value: this.state.selected ? this.state.selected.value : '',
        "data-group-toggle": this.props.groupToggle ? JSON.stringify(this.props.groupToggle) : '[]'
      }))), React.createElement("div", {
        className: "field-drop-choices",
        style: this.state.open ? {
          display: 'block'
        } : {}
      }, this.props.initialCount > this.props.tooMany && React.createElement(FieldTools, null, React.createElement(FilterBar, null, React.createElement(FilterSearch, {
        onSearch: function onSearch(e) {
          return _this2.handleSearch(e.target.value);
        }
      }))), React.createElement("div", {
        className: "field-inputs"
      }, this.props.items.length == 0 && React.createElement(NoResults, {
        text: this.props.noResults
      }), this.state.loading && React.createElement(Loading, {
        text: EE.lang.loading
      }), this.props.items.map(function (item) {
        return React.createElement(DropdownItem, {
          key: item.value ? item.value : item.section,
          item: item,
          selected: _this2.state.selected && item.value == _this2.state.selected.value,
          onClick: function onClick(e) {
            return _this2.selectionChanged(item);
          }
        });
      }))));
    }
  }], [{
    key: "renderFields",
    value: function renderFields(context) {
      $('div[data-dropdown-react]', context).each(function () {
        var props = JSON.parse(window.atob($(this).data('dropdownReact')));
        props.name = $(this).data('inputValue'); // In the case a Dropdown has been dynamically created, allow an initial
        // value to be set other than the one in the initial config

        if ($(this).data('initialValue')) {
          props.selected = $(this).data('initialValue');
        }

        ReactDOM.render(React.createElement(FilterableDropdown, props, null), this);
      });
    }
  }]);

  return Dropdown;
}(React.Component);

_defineProperty(Dropdown, "defaultProps", {
  tooMany: 8
});

function DropdownItem(props) {
  var item = props.item;

  if (item.section) {
    return React.createElement("div", {
      className: "field-group-head"
    }, React.createElement("span", {
      className: "icon--folder"
    }), " ", item.section);
  }

  return React.createElement("label", {
    onClick: props.onClick,
    className: props.selected ? 'act' : ''
  }, item.label, " ", item.instructions && React.createElement("i", null, item.instructions));
}

$(document).ready(function () {
  Dropdown.renderFields(); // Close when clicked elsewhere

  $(document).on('click', function (e) {
    $('.field-drop-selected.field-open').not($(e.target).parents('.fields-select-drop').find('.field-drop-selected.field-open')).click();
  });
});
Grid.bind('select', 'display', function (cell) {
  Dropdown.renderFields(cell);
});
FluidField.on('select', 'add', function (field) {
  Dropdown.renderFields(field);
});
var FilterableDropdown = makeFilterableComponent(Dropdown);