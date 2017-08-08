'use strict';

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var Dropdown = function (_React$Component) {
  _inherits(Dropdown, _React$Component);

  function Dropdown(props) {
    _classCallCheck(this, Dropdown);

    var _this = _possibleConstructorReturn(this, (Dropdown.__proto__ || Object.getPrototypeOf(Dropdown)).call(this, props));

    _this.limit = 8;

    _this.itemsChanged = function (items) {
      _this.setState({
        items: items
      });
    };

    _this.selectionChanged = function (selected) {
      _this.setState({
        selected: selected,
        open: false
      });

      if (_this.props.groupToggle) EE.cp.form_group_toggle(_this.input);
    };

    _this.toggleOpen = function () {
      _this.setState(function (prevState, props) {
        return {
          open: !prevState.open
        };
      });
    };

    _this.handleSearch = function (searchTerm) {
      if (!_this.ajaxFilter) {
        _this.setState({ items: _this.initialItems.filter(function (item) {
            return String(item.label).toLowerCase().includes(searchTerm.toLowerCase());
          }) });
        return;
      }

      // Debounce AJAX filter
      clearTimeout(_this.ajaxTimer);
      if (_this.ajaxRequest) _this.ajaxRequest.abort();

      _this.setState({ loading: true });

      _this.ajaxTimer = setTimeout(function () {
        _this.ajaxRequest = $.ajax({
          url: _this.props.filterUrl,
          data: $.param({ 'search': searchTerm }),
          dataType: 'json',
          success: function success(data) {
            _this.setState({
              items: SelectList.formatItems(data),
              loading: false
            });
          },
          error: function error() {} // Defined to prevent error on .abort above
        });
      }, 300);
    };

    _this.initialItems = SelectList.formatItems(props.items);
    _this.state = {
      items: _this.initialItems,
      selected: _this.getItemForSelectedValue(props.selected),
      open: false,
      loading: false
    };

    _this.ajaxFilter = _this.initialItems.length >= props.limit && props.filterUrl;
    _this.ajaxTimer = null;
    _this.ajaxRequest = null;
    _this.tooMany = props.tooMany ? props.tooMany : _this.limit;
    return _this;
  }

  _createClass(Dropdown, [{
    key: 'componentDidUpdate',
    value: function componentDidUpdate(prevProps, prevState) {
      if (!prevState.selected && this.state.selected || prevState.selected && prevState.selected.value != this.state.selected.value) {

        if (this.props.groupToggle) EE.cp.form_group_toggle(this.input);

        $(this.input).trigger('change');
      }
    }
  }, {
    key: 'getItemForSelectedValue',
    value: function getItemForSelectedValue(value) {
      return this.initialItems.find(function (item) {
        return String(item.value) == String(value);
      });
    }
  }, {
    key: 'render',
    value: function render() {
      var _this2 = this;

      var tooMany = this.state.items.length > this.tooMany && !this.state.loading;

      return React.createElement(
        'div',
        { className: "fields-select-drop" + (tooMany ? ' field-resizable' : '') },
        React.createElement(
          'div',
          { className: "field-drop-selected" + (this.state.open ? ' field-open' : ''), onClick: this.toggleOpen },
          React.createElement(
            'label',
            { className: this.state.selected ? 'act' : '' },
            React.createElement(
              'i',
              null,
              this.state.selected ? this.state.selected.label : this.props.emptyText
            ),
            React.createElement('input', { type: 'hidden',
              ref: function ref(input) {
                _this2.input = input;
              },
              name: this.props.name,
              value: this.state.selected ? this.state.selected.value : '',
              'data-group-toggle': this.props.groupToggle ? JSON.stringify(this.props.groupToggle) : '[]'
            })
          )
        ),
        React.createElement(
          'div',
          { className: 'field-drop-choices', style: this.state.open ? { display: 'block' } : {} },
          this.initialItems.length > this.tooMany && React.createElement(
            FieldTools,
            null,
            React.createElement(
              FilterBar,
              null,
              React.createElement(FilterSearch, { onSearch: function onSearch(e) {
                  return _this2.handleSearch(e.target.value);
                } })
            )
          ),
          React.createElement(
            'div',
            { className: 'field-inputs' },
            this.state.items.length == 0 && React.createElement(NoResults, { text: this.props.noResults }),
            this.state.loading && React.createElement(Loading, { text: EE.lang.loading }),
            this.state.items.map(function (item) {
              return React.createElement(DropdownItem, { key: item.value ? item.value : item.section, item: item, onClick: function onClick(e) {
                  return _this2.selectionChanged(item);
                } });
            })
          )
        )
      );
    }
  }], [{
    key: 'renderFields',
    value: function renderFields(context) {
      $('div[data-dropdown-react]', context).each(function () {
        var props = JSON.parse(window.atob($(this).data('dropdownReact')));
        props.name = $(this).data('inputValue');
        ReactDOM.render(React.createElement(Dropdown, props, null), this);
      });
    }
  }]);

  return Dropdown;
}(React.Component);

function DropdownItem(props) {
  var item = props.item;

  if (item.section) {
    return React.createElement(
      'div',
      { className: 'field-group-head' },
      item.section
    );
  }

  return React.createElement(
    'label',
    { onClick: props.onClick },
    item.label,
    ' ',
    item.instructions && React.createElement(
      'i',
      null,
      item.instructions
    )
  );
}

$(document).ready(function () {
  Dropdown.renderFields();

  // Close when clicked elsewhere
  $(document).on('click', function (e) {
    $('.field-drop-selected.field-open').not($(e.target).closest('.field-drop-selected.field-open')).click();
  });
});

Grid.bind('select', 'display', function (cell) {
  Dropdown.renderFields(cell);
});