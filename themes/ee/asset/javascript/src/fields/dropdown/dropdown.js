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
var Dropdown = /*#__PURE__*/function (_React$Component) {
  _inherits(Dropdown, _React$Component);

  function Dropdown(props) {
    var _this;

    _classCallCheck(this, Dropdown);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(Dropdown).call(this, props));

    _defineProperty(_assertThisInitialized(_this), "selectionChanged", function (selected) {
      _this.setState({
        selected: selected,
        open: false
      });

      if (_this.props.groupToggle) {
        EE.cp.form_group_toggle(_this.input);
      }

      if (_this.props.conditionalRule == 'rule') {
        EE.cp.show_hide_rule_operator_field(selected, _this.input);
      }

      if (_this.props.conditionalRule == 'operator') {
        EE.cp.check_operator_value(selected, _this.input);
      }

      if (_this.props.conditionalRule == 'rx-redactor-dropdown') {
        var $rx_react_parent = $(_this.input).parents('.rx-form-div').parent();
        var $rx_url_input = $($rx_react_parent).next('input.rx-form-input');
        $rx_url_input.val(selected.value);
      }
    });

    _defineProperty(_assertThisInitialized(_this), "toggleOpen", function () {
      _this.setState(function (prevState, props) {
        return {
          open: !prevState.open
        };
      });
    });

    _defineProperty(_assertThisInitialized(_this), "checkChildDirectory", function (items, value) {
      items.map(function (item) {
        if (item.value == value) {
          return window.selectedEl = item;
        } else if (item.value != value && Array.isArray(item.children) && item.children.length) {
          _this.checkChildDirectory(item.children, value);
        }
      });
      return window.selectedEl;
    });

    _defineProperty(_assertThisInitialized(_this), "selectRecursion", function (items) {
      return React.createElement(React.Fragment, null, items.map(function (item) {
        return React.createElement("div", {
          className: "select__dropdown-item-parent"
        }, React.createElement(DropdownItem, {
          key: item.value ? item.value : item.section,
          item: item,
          selected: _this.state.selected && item.value == _this.state.selected.value,
          onClick: function onClick(e) {
            return _this.selectionChanged(item);
          },
          name: _this.props.name
        }), item.children && item.children.length ? _this.selectRecursion(item.children) : null);
      }));
    });

    window.selectedEl;

    var _selected; // use different function for file manager part and other site pages


    if (props.fileManager) {
      _selected = _this.checkChildDirectory(_this.props.initialItems, props.selected);
    } else {
      _selected = _this.getItemForSelectedValue(props.selected);
    }

    _this.state = {
      selected: _selected,
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
      var selected;

      if (window.selectedFolder) {
        selected = this.checkChildDirectory(this.props.initialItems, window.selectedFolder);
        this.state.selected = selected;
      } else {
        selected = this.state.selected;
      }

      return React.createElement("div", {
        className: "select button-segment" + (tooMany ? ' select--resizable' : '') + (this.state.open ? ' select--open' : '')
      }, React.createElement("div", {
        className: "select__button js-dropdown-toggle",
        onClick: this.toggleOpen,
        tabIndex: "0"
      }, React.createElement("label", {
        className: 'select__button-label' + (this.state.selected ? ' act' : '')
      }, selected && React.createElement("span", null, selected.sectionLabel && !this.props.ignoreSectionLabel ? selected.sectionLabel + ' / ' : '', React.createElement("span", {
        dangerouslySetInnerHTML: {
          __html: selected.label
        }
      }), this.props.name == 'condition-rule-field' && React.createElement("span", {
        className: "short-name"
      }, "{".concat(selected.value, "}"))), !selected && React.createElement("i", null, this.props.emptyText), React.createElement("input", {
        type: "hidden",
        ref: function ref(input) {
          _this2.input = input;
        },
        name: this.props.name,
        value: this.state.selected ? this.state.selected.value : '',
        "data-group-toggle": this.props.groupToggle ? JSON.stringify(this.props.groupToggle) : '[]',
        disabled: this.props.disabledInput ? 'disabled' : null
      })), selected && this.props.name.includes('[condition_field_id]') && React.createElement("span", {
        className: "tooltiptext"
      }, "".concat(selected.label.replace(/<.*/g, ""), " ").concat(selected.label.match(/(?:\{).+?(?:\})/g)))), React.createElement("div", {
        className: "select__dropdown dropdown"
      }, this.props.initialCount > this.props.tooMany && React.createElement("div", {
        className: "select__dropdown-search"
      }, React.createElement(FieldTools, null, React.createElement(FilterBar, null, React.createElement(FilterSearch, {
        onSearch: function onSearch(e) {
          return _this2.handleSearch(e.target.value);
        }
      })))), React.createElement("div", {
        className: "select__dropdown-items"
      }, this.props.items.length == 0 && React.createElement(NoResults, {
        text: this.props.noResults
      }), this.state.loading && React.createElement(Loading, {
        text: EE.lang.loading
      }), this.selectRecursion(this.props.items))));
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

        if (window.selectedFolder) {
          props.selected = window.selectedFolder;
        }

        if ($(this).parents('tr.hidden').length) {
          props.disabledInput = true;
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
      className: "select__dropdown-item select__dropdown-item--head"
    }, React.createElement("span", {
      className: "icon--folder"
    }), " ", item.section);
  }

  return React.createElement("div", {
    onClick: props.onClick,
    className: 'select__dropdown-item' + (props.selected ? ' select__dropdown-item--selected' : ''),
    tabIndex: "0"
  }, React.createElement("span", {
    dangerouslySetInnerHTML: {
      __html: item.label
    }
  }), item.instructions && React.createElement("i", null, item.instructions), props.name == 'condition-rule-field' && React.createElement("span", {
    className: "short-name"
  }, "{".concat(item.value, "}")));
}

$(document).ready(function () {
  Dropdown.renderFields(); // Close when clicked elsewhere

  $(document).on('click', function (e) {
    $('.select.select--open').not($(e.target).closest('.select')).find('.select__button').click();
  });
});
Grid.bind('select', 'display', function (cell) {
  Dropdown.renderFields(cell);
});
FluidField.on('select', 'add', function (field) {
  Dropdown.renderFields(field);
});
var FilterableDropdown = makeFilterableComponent(Dropdown);