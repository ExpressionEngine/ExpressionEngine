'use strict';

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var Relationship = function (_React$Component) {
  _inherits(Relationship, _React$Component);

  function Relationship(props) {
    _classCallCheck(this, Relationship);

    var _this = _possibleConstructorReturn(this, (Relationship.__proto__ || Object.getPrototypeOf(Relationship)).call(this, props));

    _this.itemsChanged = function (items) {
      _this.setState({
        items: items
      });
    };

    _this.selectionChanged = function (selected) {
      _this.setState({
        selected: selected
      });
    };

    _this.initialItems = SelectList.formatItems(props.items);
    _this.state = {
      items: _this.initialItems,
      selected: SelectList.formatItems(props.selected)
    };
    return _this;
  }

  _createClass(Relationship, [{
    key: 'render',
    value: function render() {
      return React.createElement(
        'div',
        { className: "fields-relate" + (this.props.multi ? ' fields-relate-multi' : '') },
        React.createElement(SelectList, { items: this.state.items,
          initialItems: this.initialItems,
          name: this.props.name,
          multi: this.props.multi,
          selected: this.state.selected,
          itemsChanged: this.itemsChanged,
          selectionChanged: this.selectionChanged
        }),
        this.props.multi && React.createElement(SelectList, { items: this.state.selected,
          selected: [],
          initialItems: this.props.selected,
          selectable: false,
          reorderable: true,
          removable: true,
          itemsChanged: this.itemsChanged,
          selectionChanged: this.selectionChanged
        })
      );
    }
  }]);

  return Relationship;
}(React.Component);

$(document).ready(function () {
  $('div[data-relationship-react]').each(function () {
    var props = JSON.parse(window.atob($(this).data('relationshipReact')));
    ReactDOM.render(React.createElement(Relationship, props, null), this);
  });
});