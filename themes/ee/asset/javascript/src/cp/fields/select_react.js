'use strict';

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var SelectList = function (_React$Component) {
  _inherits(SelectList, _React$Component);

  function SelectList(props) {
    _classCallCheck(this, SelectList);

    var _this = _possibleConstructorReturn(this, (SelectList.__proto__ || Object.getPrototypeOf(SelectList)).call(this, props));

    _this.handleSearch = function (event) {
      var search_term = event.target.value;

      // DOM filter
      if (!_this.ajaxFilter) {
        _this.setState({ items: _this.intialItems.filter(function (item) {
            return item.label.toLowerCase().includes(search_term.toLowerCase());
          }) });
        return;
      }

      // Debounce AJAX filter
      clearTimeout(_this.ajaxTimer);
      if (_this.ajaxRequest) _this.ajaxRequest.abort();

      var params = { search: search_term };

      _this.ajaxTimer = setTimeout(function () {
        _this.ajaxRequest = $.ajax({
          url: _this.props.filter_url,
          data: $.param(params),
          dataType: 'json',
          success: function success(data) {
            _this.setState({
              items: _this._formatItems(data)
            });
          },
          error: function error() {} // Defined to prevent error on .abort above
        });
      }, 300);
    };

    _this.handleSelect = function (event, label, value) {
      if (_this.props.multi) {
        // handle multi-select
      } else {
        _this.setState({
          selected: [{ value: value, label: label }],
          values: [value]
        });
      }
    };

    _this.clearSelection = function (event) {
      _this.setState({
        selected: [],
        values: []
      });
      event.preventDefault();
    };

    _this.intialItems = _this._formatItems(props.items);
    _this.state = {
      items: _this.intialItems,
      selected: _this._formatItems(props.selected)
    };
    _this.state.values = _this.state.selected.map(function (item) {
      return item.value;
    });

    // If the intial state is less than the limit, use DOM filtering
    _this.ajaxFilter = _this.intialItems.length >= _this.props.limit && _this.props.filter_url;
    _this.ajaxTimer = null;
    _this.ajaxRequest = null;
    return _this;
  }

  _createClass(SelectList, [{
    key: '_formatItems',
    value: function _formatItems(items) {
      var items_array = [];
      var _iteratorNormalCompletion = true;
      var _didIteratorError = false;
      var _iteratorError = undefined;

      try {
        for (var _iterator = Object.keys(items)[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
          key = _step.value;

          items_array.push({ value: key, label: items[key] });
        }
      } catch (err) {
        _didIteratorError = true;
        _iteratorError = err;
      } finally {
        try {
          if (!_iteratorNormalCompletion && _iterator.return) {
            _iterator.return();
          }
        } finally {
          if (_didIteratorError) {
            throw _iteratorError;
          }
        }
      }

      return items_array;
    }
  }, {
    key: 'render',
    value: function render() {
      var _this2 = this;

      return React.createElement(
        'div',
        { className: "fields-select" + (this.state.items.length > this.props.too_many ? ' field-resizable' : '') },
        React.createElement(SelectFilter, { handleSearch: this.handleSearch }),
        React.createElement(
          SelectInputs,
          null,
          this.state.items.length == 0 && React.createElement(NoResults, { text: this.props.no_results }),
          this.state.items.map(function (item) {
            return React.createElement(SelectItem, { key: item.value,
              item: item,
              name: _this2.props.name,
              values: _this2.state.values,
              handleSelect: function handleSelect(e) {
                return _this2.handleSelect(e, item.label, item.value);
              } });
          })
        ),
        !this.props.multi && this.state.selected[0] && React.createElement(SelectedItem, { name: this.props.name,
          item: this.state.selected[0],
          clearSelection: this.clearSelection })
      );
    }
  }]);

  return SelectList;
}(React.Component);

function SelectInputs(props) {
  return React.createElement(
    'div',
    { className: 'field-inputs' },
    props.children
  );
}

function SelectFilter(props) {
  return React.createElement(
    'div',
    { className: 'field-tools' },
    React.createElement(
      'div',
      { className: 'filter-bar' },
      React.createElement(
        'div',
        { className: 'filter-item filter-item__search' },
        React.createElement('input', { type: 'text', placeholder: 'Keyword Search', onChange: props.handleSearch })
      )
    )
  );
}

function SelectItem(props) {
  function checked(value) {
    return props.values.includes(value);
  }

  return React.createElement(
    'label',
    { className: checked(props.item.value) ? 'act' : '' },
    React.createElement('input', { type: 'radio',
      name: props.name,
      value: props.item.value,
      onChange: props.handleSelect,
      checked: checked(props.item.value) ? 'checked' : '' }),
    ' ',
    props.item.label
  );
}

function SelectedItem(props) {
  return React.createElement(
    'div',
    { className: 'field-input-selected' },
    React.createElement(
      'label',
      null,
      React.createElement('span', { className: 'icon--success' }),
      ' ',
      props.item.label,
      React.createElement('input', { type: 'hidden', name: props.name, value: props.item.value }),
      React.createElement(
        'ul',
        { className: 'toolbar' },
        React.createElement(
          'li',
          { className: 'remove' },
          React.createElement('a', { href: '', onClick: props.clearSelection })
        )
      )
    )
  );
}

function NoResults(props) {
  return React.createElement('label', { className: 'field-empty', dangerouslySetInnerHTML: { __html: props.text } });
}

$(document).ready(function () {
  $('div[data-select-react]').each(function () {
    var props = JSON.parse(window.atob($(this).data('selectReact')));
    ReactDOM.render(React.createElement(SelectList, props, null), this);
  });
});