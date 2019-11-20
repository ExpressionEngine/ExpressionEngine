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

var DropDownButton =
/*#__PURE__*/
function (_React$Component) {
  _inherits(DropDownButton, _React$Component);

  function DropDownButton(props) {
    var _this;

    _classCallCheck(this, DropDownButton);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(DropDownButton).call(this, props));

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

      $(event.target).closest('.dropdown').prev('a.button').click();
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

      return React.createElement(React.Fragment, null, React.createElement("a", {
        href: "#",
        className: "button js-dropdown-toggle " + this.props.buttonClass,
        onClick: this.toggle
      }, this.state.selected ? this.state.selected.label : this.props.title, " ", React.createElement("i", {
        "class": "fas fa-caret-down icon-right"
      })), React.createElement("div", {
        className: "dropdown"
      }, this.state.items.length > 7 && React.createElement("div", {
        className: "dropdown__search"
      }, React.createElement("form", null, React.createElement("input", {
        type: "text",
        placeholder: this.props.placeholder,
        onChange: this.handleSearch
      }))), React.createElement("div", {
        className: "dropdown__scroll"
      }, this.state.items.map(function (item) {
        return React.createElement("a", {
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