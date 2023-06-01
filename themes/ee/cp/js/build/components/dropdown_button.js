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

var DropDownButton = /*#__PURE__*/function (_React$Component) {
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

      _this.setState({
        search: true
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

    _defineProperty(_assertThisInitialized(_this), "dropdownRecursion", function (items) {
      return React.createElement(React.Fragment, null, React.createElement("ul", null, items.map(function (item) {
        return React.createElement("li", null, React.createElement("a", {
          href: "#",
          key: item.value,
          className: "dropdown__link " + _this.props.itemClass,
          rel: _this.props.rel,
          onClick: function onClick(e) {
            return _this.selectItem(e, item);
          }
        }, item.path.trim() == "" ? React.createElement("i", {
          "class": "fal fa-hdd"
        }) : React.createElement("i", {
          "class": "fal fa-folder"
        }), item.label), item.children && !_this.props.ignoreChild && item.children.length ? _this.dropdownRecursion(item.children) : null);
      })));
    });

    _this.initialItems = SelectList.formatItems(props.items);
    _this.state = {
      items: _this.initialItems,
      selected: null,
      search: false
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
      return React.createElement(React.Fragment, null, React.createElement("button", {
        type: "button",
        className: "button js-dropdown-toggle has-sub " + this.props.buttonClass,
        onClick: this.toggle
      }, this.state.selected ? this.state.selected.label : this.props.title), React.createElement("div", {
        ref: function ref(el) {
          return _this2.dropdown = el;
        },
        className: "dropdown"
      }, (this.state.items.length > 7 || this.state.search) && React.createElement("div", {
        className: "dropdown__search"
      }, React.createElement("form", null, React.createElement("div", {
        className: "search-input"
      }, React.createElement("input", {
        className: "search-input__input",
        type: "text",
        placeholder: this.props.placeholder,
        onChange: this.handleSearch
      })))), this.state.selected && React.createElement(React.Fragment, null, React.createElement("a", {
        href: "#",
        className: "dropdown__link dropdown__link--selected",
        onClick: function onClick(e) {
          return _this2.selectItem(e, null);
        }
      }, this.state.selected.label), dropdownItems.length > 0 && React.createElement("div", {
        className: "dropdown__divider"
      })), React.createElement("div", {
        className: "dropdown__scroll"
      }, this.props.addInput && React.createElement("label", {
        htmlFor: "f_open-filepicker_id",
        className: "sr-only"
      }, EE.lang.hidden_input) && React.createElement("input", {
        id: "f_open-filepicker_id",
        type: "file",
        className: "f_open-filepicker",
        style: {
          display: 'none'
        },
        "data-upload_location_id": '',
        "data-path": ''
      }), this.dropdownRecursion(dropdownItems)), this.props.createNewDirectory && React.createElement("p", {
        className: "create_new_direction"
      }, React.createElement("a", {
        href: "#",
        rel: "add_new",
        className: "js-modal-link--side submit"
      }, React.createElement("i", {
        className: "fal fa-plus icon-left"
      }), " ", EE.lang.file_dnd_create_directory))));
    }
  }]);

  return DropDownButton;
}(React.Component);