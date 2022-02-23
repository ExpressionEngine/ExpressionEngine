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

var DropDownButton = /*#__PURE__*/function (_React$Component) {
  _inherits(DropDownButton, _React$Component);

  var _super = _createSuper(DropDownButton);

  function DropDownButton(props) {
    var _this;

    _classCallCheck(this, DropDownButton);

    _this = _super.call(this, props);

    _defineProperty(_assertThisInitialized(_this), "handleSearch", function (event) {
      _this.setState({
        items: _this.initialItems.filter(function (item) {
          return item.label.toLowerCase().includes(event.target.value.toLowerCase());
        })
      });
    });

    _defineProperty(_assertThisInitialized(_this), "selectItem", function (event, item) {
      if (_this.props.keepSelectedState) {
        _this.setState({
          selected: item
        });
      }

      _this.props.onSelect(item ? item.value : null);

      var dropdown = _this.dropdown;

      if (dropdown) {
        DropdownController.hideDropdown(dropdown, $(dropdown).prev('.js-dropdown-toggle')[0]);
      }

      event.preventDefault();
    });

    _this.initialItems = SelectList.formatItems(props.items);
    _this.state = {
      items: _this.initialItems,
      selected: null
    };
    return _this;
  }

  _createClass(DropDownButton, [{
    key: "render",
    value: function render() {
      var _this2 = this;

      var dropdownItems = this.state.items.filter(function (el) {
        return el != _this2.state.selected;
      });
      return /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("button", {
        type: "button",
        className: "button js-dropdown-toggle has-sub " + this.props.buttonClass,
        onClick: this.toggle
      }, this.state.selected ? this.state.selected.label : this.props.title), /*#__PURE__*/React.createElement("div", {
        ref: function ref(el) {
          return _this2.dropdown = el;
        },
        className: "dropdown"
      }, this.state.items.length > 7 && /*#__PURE__*/React.createElement("div", {
        className: "dropdown__search"
      }, /*#__PURE__*/React.createElement("form", null, /*#__PURE__*/React.createElement("div", {
        className: "search-input"
      }, /*#__PURE__*/React.createElement("input", {
        className: "search-input__input",
        type: "text",
        placeholder: this.props.placeholder,
        onChange: this.handleSearch
      })))), this.state.selected && /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("a", {
        href: "#",
        className: "dropdown__link dropdown__link--selected",
        onClick: function onClick(e) {
          return _this2.selectItem(e, null);
        }
      }, this.state.selected.label), dropdownItems.length > 0 && /*#__PURE__*/React.createElement("div", {
        className: "dropdown__divider"
      })), /*#__PURE__*/React.createElement("div", {
        className: "dropdown__scroll"
      }, dropdownItems.map(function (item) {
        return /*#__PURE__*/React.createElement("a", {
          href: "#",
          key: item.value,
          className: "dropdown__link " + _this2.props.itemClass,
          rel: _this2.props.rel,
          onClick: function onClick(e) {
            return _this2.selectItem(e, item);
          }
        }, item.label);
      }))));
    }
  }]);

  return DropDownButton;
}(React.Component);