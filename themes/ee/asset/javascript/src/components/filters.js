"use strict";

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

function FilterBar(props) {
  return React.createElement(
    "div",
    { className: "field-tools" },
    React.createElement(
      "div",
      { className: "filter-bar" },
      props.children
    )
  );
}

function FilterSearch(props) {
  return React.createElement(
    "div",
    { className: "filter-item filter-item__search" },
    React.createElement("input", { type: "text", placeholder: "Keyword Search", onChange: props.handleSearch })
  );
}

var FilterSelect = function (_React$Component) {
  _inherits(FilterSelect, _React$Component);

  function FilterSelect(props) {
    _classCallCheck(this, FilterSelect);

    var _this = _possibleConstructorReturn(this, (FilterSelect.__proto__ || Object.getPrototypeOf(FilterSelect)).call(this, props));

    _this.selectItem = function (event, item) {
      _this.setState({ selected: item });
      $(event.target).closest('.js-filter-link').trigger('click'); // Not working
      event.preventDefault();
    };

    _this.clearSelection = function (event) {
      _this.setState({ selected: null });
      event.preventDefault();
    };

    _this.initialItems = SelectList.formatItems(props.items);
    _this.state = {
      items: _this.initialItems,
      selected: null,
      open: false
    };
    return _this;
  }

  _createClass(FilterSelect, [{
    key: "render",
    value: function render() {
      var _this2 = this;

      return React.createElement(
        "div",
        { className: "filter-item" },
        React.createElement(
          "a",
          { href: "#", className: "js-filter-link filter-item__link filter-item__link--has-submenu", onClick: this.toggle },
          this.props.name
        ),
        React.createElement(
          "div",
          { className: "filter-submenu" },
          React.createElement(
            "div",
            { className: "filter-submenu__search" },
            React.createElement(
              "form",
              null,
              React.createElement("input", { type: "text", placeholder: this.props.placeholder })
            )
          ),
          this.state.selected && React.createElement(
            "div",
            { className: "filter-submenu__selected" },
            React.createElement(
              "a",
              { href: "#", onClick: this.clearSelection },
              this.state.selected.label
            )
          ),
          React.createElement(
            "div",
            { className: "filter-submenu__scroll" },
            this.state.items.map(function (item) {
              return React.createElement(
                "a",
                { href: "#", key: item.value, className: "filter-submenu__link filter-submenu__link---active", onClick: function onClick(e) {
                    return _this2.selectItem(e, item);
                  } },
                item.label
              );
            })
          )
        )
      );
    }
  }]);

  return FilterSelect;
}(React.Component);