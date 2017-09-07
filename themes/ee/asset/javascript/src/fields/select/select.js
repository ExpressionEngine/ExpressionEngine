'use strict';

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var FilterableSelectList = makeFilterableComponent(SelectList);

var SelectField = function (_React$Component) {
  _inherits(SelectField, _React$Component);

  function SelectField(props) {
    _classCallCheck(this, SelectField);

    var _this = _possibleConstructorReturn(this, (SelectField.__proto__ || Object.getPrototypeOf(SelectField)).call(this, props));

    _this.selectionChanged = function (selected) {
      _this.setState({
        selected: selected
      });
    };

    _this.setEditingMode = function (editing) {
      _this.setState({
        editing: editing
      });
    };

    _this.initialItems = SelectList.formatItems(props.items);
    _this.state = {
      selected: SelectList.formatItems(props.selected, null, props.multi),
      editing: false
    };
    return _this;
  }

  _createClass(SelectField, [{
    key: 'countItems',


    // Get count of all items including nested
    value: function countItems(items) {
      var _this2 = this;

      items = items || this.initialItems;

      count = items.length + items.reduce(function (sum, item) {
        if (item.children) {
          return sum + _this2.countItems(item.children);
        }
        return sum;
      }, 0);

      return count;
    }
  }, {
    key: 'render',
    value: function render() {
      var _this3 = this;

      var selectItem = React.createElement(FilterableSelectList, { items: this.initialItems,
        limit: this.props.limit,
        name: this.props.name,
        multi: this.props.multi,
        nested: this.props.nested,
        autoSelectParents: this.props.autoSelectParents,
        selected: this.state.selected,
        filterUrl: this.props.filterUrl,
        selectionChanged: this.selectionChanged,
        noResults: this.props.noResults,
        filters: this.props.filters,
        toggleAll: this.props.toggleAll,
        filterable: this.countItems() > SelectList.defaultProps.tooMany,
        reorderable: this.props.reorderable || this.state.editing,
        removable: this.props.removable || this.state.editing,
        editable: this.props.editable || this.state.editing,
        selectable: this.props.selectable,
        groupToggle: this.props.groupToggle,
        manageLabel: this.props.manageLabel,
        reorderAjaxUrl: this.props.reorderAjaxUrl,
        loading: this.props.loading
      });

      if (this.props.manageable) {
        return React.createElement(
          'div',
          null,
          selectItem,
          React.createElement(
            ToggleTools,
            { label: this.props.manageLabel },
            React.createElement(Toggle, { on: false, handleToggle: function handleToggle(toggle) {
                return _this3.setEditingMode(toggle);
              } })
          )
        );
      }

      return selectItem;
    }
  }], [{
    key: 'renderFields',
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

Grid.bind('relationship', 'displaySettings', function (cell) {
  SelectField.renderFields(cell);
});

Grid.bind('checkboxes', 'display', function (cell) {
  SelectField.renderFields(cell);
});

Grid.bind('radio', 'display', function (cell) {
  SelectField.renderFields(cell);
});

Grid.bind('multi_select', 'display', function (cell) {
  SelectField.renderFields(cell);
});