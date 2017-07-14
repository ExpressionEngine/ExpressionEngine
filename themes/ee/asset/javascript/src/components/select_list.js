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
        _this.props.itemsChanged(_this.props.initialItems.filter(function (item) {
          return item.label.toLowerCase().includes(search_term.toLowerCase());
        }));
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
            _this.props.itemsChanged(formatItems(data));
          },
          error: function error() {} // Defined to prevent error on .abort above
        });
      }, 300);
    };

    _this.handleChange = function (event, item) {
      var selected = [];
      if (_this.props.multi) {
        if (event.target.checked) {
          selected = _this.props.selected.concat([item]);
        } else {
          selected = _this.props.selected.filter(function (thisItem) {
            return thisItem.value != item.value;
          });
        }
      } else {
        selected = [item];
      }
      _this.props.selectionChanged(selected);
    };

    _this.handleRemove = function (event, item) {
      _this.props.selectionChanged(_this.props.items.filter(function (thisItem) {
        return thisItem.value != item.value;
      }));
      event.preventDefault();
    };

    _this.clearSelection = function (event) {
      _this.props.selectionChanged([]);
      event.preventDefault();
    };

    _this.selectable = props.selectable !== undefined ? props.selectable : true;
    _this.reorderable = props.reorderable !== undefined ? props.reorderable : false;
    _this.removable = props.removable !== undefined ? props.removable : false;
    _this.tooMany = props.tooMany ? props.tooMany : 8;

    // If the intial state is less than the limit, use DOM filtering
    _this.ajaxFilter = _this.props.initialItems.length >= props.limit && props.filter_url;
    _this.ajaxTimer = null;
    _this.ajaxRequest = null;

    _this.bindSortable();
    return _this;
  }

  _createClass(SelectList, [{
    key: 'bindSortable',
    value: function bindSortable() {
      $('.field-inputs').sortable({
        axis: 'y',
        containment: 'parent',
        handle: '.icon-reorder',
        items: 'label',
        stop: function stop(event, ui) {
          // TODO
        }
      });
    }
  }, {
    key: 'render',
    value: function render() {
      var _this2 = this;

      var props = this.props;

      return React.createElement(
        'div',
        { className: "fields-select" + (props.items.length > this.tooMany ? ' field-resizable' : '') },
        React.createElement(SelectFilter, { handleSearch: this.handleSearch }),
        React.createElement(
          SelectInputs,
          null,
          props.items.length == 0 && React.createElement(NoResults, { text: props.noResults }),
          props.items.map(function (item) {
            return React.createElement(SelectItem, { key: item.value,
              item: item,
              name: props.name,
              selected: props.selected,
              multi: props.multi,
              selectable: _this2.selectable,
              reorderable: _this2.reorderable,
              removable: _this2.removable,
              handleSelect: function handleSelect(e) {
                return _this2.handleChange(e, item);
              },
              handleRemove: function handleRemove(e) {
                return _this2.handleRemove(e, item);
              }
            });
          })
        ),
        !props.multi && props.selected[0] && React.createElement(SelectedItem, { name: props.name,
          item: props.selected[0],
          clearSelection: this.clearSelection
        })
      );
    }
  }, {
    key: 'componentDidUpdate',
    value: function componentDidUpdate() {
      if (this.reorderable) this.bindSortable();
    }
  }], [{
    key: 'formatItems',
    value: function formatItems(items) {
      if (!items) return [];

      var items_array = [];
      var _iteratorNormalCompletion = true;
      var _didIteratorError = false;
      var _iteratorError = undefined;

      try {
        for (var _iterator = Object.keys(items)[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
          key = _step.value;

          items_array.push({
            value: key,
            label: items[key].label ? items[key].label : items[key],
            instructions: items[key].instructions ? items[key].instructions : ''
          });
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
    return props.selected.find(function (item) {
      return item.value == value;
    });
  }

  return React.createElement(
    'label',
    { className: checked(props.item.value) ? 'act' : '' },
    props.reorderable && React.createElement(
      'span',
      { className: 'icon-reorder' },
      ' '
    ),
    props.selectable && React.createElement('input', { type: props.multi ? "checkbox" : "radio",
      name: props.name,
      value: props.item.value,
      onChange: props.handleSelect,
      checked: checked(props.item.value) ? 'checked' : '' }),
    props.item.label + " ",
    props.item.instructions && React.createElement(
      'i',
      null,
      props.item.instructions
    ),
    props.removable && React.createElement(
      'ul',
      { className: 'toolbar' },
      React.createElement(
        'li',
        { className: 'remove' },
        React.createElement('a', { href: '', onClick: props.handleRemove })
      )
    )
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