"use strict";

function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _extends() { _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

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

var SelectField =
/*#__PURE__*/
function (_React$Component) {
  _inherits(SelectField, _React$Component);

  function SelectField(props) {
    var _this;

    _classCallCheck(this, SelectField);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(SelectField).call(this, props));

    _defineProperty(_assertThisInitialized(_assertThisInitialized(_this)), "selectionChanged", function (selected) {
      _this.setState({
        selected: selected
      });
    });

    _defineProperty(_assertThisInitialized(_assertThisInitialized(_this)), "setEditingMode", function (editing) {
      _this.setState({
        editing: editing
      });
    });

    _defineProperty(_assertThisInitialized(_assertThisInitialized(_this)), "handleRemove", function (event, item) {
      event.preventDefault();
      $(event.target).closest('[data-id]').trigger('select:removeItem', [item]);
    });

    _this.props.items = SelectList.formatItems(props.items);
    _this.state = {
      selected: SelectList.formatItems(props.selected, null, props.multi),
      editing: props.editing || false
    };
    return _this;
  }

  _createClass(SelectField, [{
    key: "render",
    value: function render() {
      var _this2 = this;

      var selectItem = React.createElement(FilterableSelectList, _extends({}, this.props, {
        selected: this.state.selected,
        selectionChanged: this.selectionChanged,
        tooMany: SelectList.countItems(this.props.items) > SelectList.defaultProps.tooManyLimit,
        reorderable: this.props.reorderable || this.state.editing,
        removable: this.props.removable || this.state.editing,
        handleRemove: function handleRemove(e, item) {
          return _this2.handleRemove(e, item);
        },
        editable: this.props.editable || this.state.editing
      }));

      if (this.props.manageable) {
        return React.createElement("div", null, selectItem, this.props.addLabel && React.createElement("a", {
          className: "btn action submit",
          rel: "add_new",
          href: "#"
        }, this.props.addLabel), React.createElement(ToggleTools, {
          label: this.props.manageLabel
        }, React.createElement(Toggle, {
          on: this.props.editing,
          handleToggle: function handleToggle(toggle) {
            return _this2.setEditingMode(toggle);
          }
        })));
      }

      return selectItem;
    }
  }], [{
    key: "renderFields",
    value: function renderFields(context) {
      $('div[data-select-react]', context).each(function () {
        var props = JSON.parse(window.atob($(this).data('selectReact')));
        props.name = $(this).data('inputValue');
        ReactDOM.render(React.createElement(SelectField, props, null), this);
      });
    }
  }]);

  return SelectField;
}(React.Component);

$(document).ready(function () {
  SelectField.renderFields();
});
Grid.bind('relationship', 'displaySettings', SelectField.renderFields);
Grid.bind('file', 'displaySettings', SelectField.renderFields);
Grid.bind('checkboxes', 'display', SelectField.renderFields);
FluidField.on('checkboxes', 'add', SelectField.renderFields);
Grid.bind('radio', 'display', SelectField.renderFields);
FluidField.on('radio', 'add', SelectField.renderFields);
Grid.bind('multi_select', 'display', SelectField.renderFields);
FluidField.on('multi_select', 'add', SelectField.renderFields);