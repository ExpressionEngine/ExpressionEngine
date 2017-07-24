'use strict';

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var SelectList = function (_React$Component) {
  _inherits(SelectList, _React$Component);

  function SelectList(props) {
    _classCallCheck(this, SelectList);

    var _this = _possibleConstructorReturn(this, (SelectList.__proto__ || Object.getPrototypeOf(SelectList)).call(this, props));

    _this.handleSelect = function (event, item) {
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

    _this.filterChange = function (name, value) {
      _this.filterState[name] = value;

      // DOM filter
      if (!_this.ajaxFilter && name == 'search') {
        _this.props.itemsChanged(_this.filterItems(_this.props.initialItems, value));
        return;
      }

      // Debounce AJAX filter
      clearTimeout(_this.ajaxTimer);
      if (_this.ajaxRequest) _this.ajaxRequest.abort();

      var params = _this.filterState;
      params.selected = _this.props.selected.map(function (item) {
        return item.value;
      });

      _this.setState({ loading: true });

      _this.ajaxTimer = setTimeout(function () {
        _this.ajaxRequest = $.ajax({
          url: _this.props.filterUrl,
          data: $.param(params),
          dataType: 'json',
          success: function success(data) {
            _this.setState({ loading: false });
            _this.props.initialItemsChanged(SelectList.formatItems(data));
          },
          error: function error() {} // Defined to prevent error on .abort above
        });
      }, 300);
    };

    _this.handleToggleAll = function (check) {
      // If checking, merge the newly-selected items on to the existing stack
      // in case the current view is limited by a filter
      if (check) {
        newly_selected = _this.props.items.filter(function (thisItem) {
          found = _this.props.selected.find(function (item) {
            return item.value == thisItem.value;
          });
          return !found;
        });
        _this.props.selectionChanged(_this.props.selected.concat(newly_selected));
      } else {
        _this.props.selectionChanged([]);
      }
    };

    _this.filterable = props.filterable !== undefined ? props.filterable : false;
    _this.selectable = props.selectable !== undefined ? props.selectable : true;
    _this.reorderable = props.reorderable !== undefined ? props.reorderable : false;
    _this.removable = props.removable !== undefined ? props.removable : false;
    _this.tooMany = props.tooMany ? props.tooMany : SelectList.limit;

    _this.state = {
      loading: false
    };

    _this.filterState = {};

    // If the intial state is less than the limit, use DOM filtering
    _this.ajaxFilter = _this.props.initialItems.length >= props.limit && props.filterUrl;
    _this.ajaxTimer = null;
    _this.ajaxRequest = null;
    return _this;
  }

  _createClass(SelectList, [{
    key: 'componentDidMount',
    value: function componentDidMount() {
      if (this.reorderable) this.bindSortable();
    }
  }, {
    key: 'bindSortable',
    value: function bindSortable() {
      var _this2 = this;

      $('.field-inputs', this.container).sortable({
        axis: 'y',
        containment: 'parent',
        handle: '.icon-reorder',
        items: 'label',
        stop: function stop(event, ui) {
          var items = ui.item.closest('.field-inputs').find('label').toArray();

          _this2.props.selectionChanged(items.map(function (element) {
            return _this2.props.items[element.dataset.sortableIndex];
          }));
        }
      });
    }
  }, {
    key: 'filterItems',
    value: function filterItems(items, searchTerm) {
      var _this3 = this;

      items = items.map(function (item) {
        // Clone item so we don't modify reference types
        item = Object.assign({}, item);

        // If any children contain the search term, we'll keep the parent
        if (item.children) item.children = _this3.filterItems(item.children, searchTerm);

        var itemFoundInChildren = item.children && item.children.length > 0;
        var itemFound = item.label.toLowerCase().includes(searchTerm.toLowerCase());

        return itemFound || itemFoundInChildren ? item : false;
      });

      return items.filter(function (item) {
        return item;
      });
    }
  }, {
    key: 'render',
    value: function render() {
      var _this4 = this;

      var props = this.props;
      var tooMany = props.items.length > this.tooMany && !this.state.loading;
      var shouldShowToggleAll = (props.multi || !this.selectable) && props.toggleAll !== null;
      var shouldShowFieldTools = this.props.items.length > SelectList.limit;

      return React.createElement(
        'div',
        { className: "fields-select" + (tooMany ? ' field-resizable' : ''),
          ref: function ref(container) {
            _this4.container = container;
          } },
        this.filterable && React.createElement(
          FieldTools,
          null,
          React.createElement(
            FilterBar,
            null,
            props.filters && props.filters.map(function (filter) {
              return React.createElement(FilterSelect, { key: filter.name,
                name: filter.name,
                title: filter.title,
                placeholder: filter.placeholder,
                items: filter.items,
                onSelect: function onSelect(value) {
                  return _this4.filterChange(filter.name, value);
                }
              });
            }),
            React.createElement(FilterSearch, { onSearch: function onSearch(e) {
                return _this4.filterChange('search', e.target.value);
              } })
          ),
          shouldShowToggleAll && React.createElement('hr', null),
          shouldShowToggleAll && React.createElement(FilterToggleAll, { checkAll: props.toggleAll, onToggleAll: function onToggleAll(check) {
              return _this4.handleToggleAll(check);
            } })
        ),
        React.createElement(
          FieldInputs,
          { nested: props.nested },
          props.items.length == 0 && React.createElement(NoResults, { text: props.noResults }),
          this.state.loading && React.createElement(Loading, { text: EE.lang.loading }),
          !this.state.loading && props.items.map(function (item, index) {
            return React.createElement(SelectItem, { key: item.value ? item.value : item.section,
              sortableIndex: index,
              item: item,
              name: props.name,
              selected: props.selected,
              multi: props.multi,
              nested: props.nested,
              selectable: _this4.selectable,
              reorderable: _this4.reorderable,
              removable: _this4.removable,
              handleSelect: _this4.handleSelect,
              handleRemove: _this4.handleRemove
            });
          })
        ),
        !props.multi && props.selected[0] && React.createElement(SelectedItem, { name: props.name,
          item: props.selected[0],
          clearSelection: this.clearSelection
        }),
        props.multi && this.selectable && React.createElement('input', { type: 'hidden', name: props.name + '[]', value: '' }),
        props.multi && this.selectable && props.selected.map(function (item) {
          return React.createElement('input', { type: 'hidden', key: item.value, name: props.name + '[]', value: item.value });
        })
      );
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

          if (items[key].section) {
            items_array.push({
              section: items[key].section,
              label: ''
            });
          } else {
            items_array.push({
              value: items[key].value ? items[key].value : key,
              label: items[key].label ? items[key].label : items[key],
              instructions: items[key].instructions ? items[key].instructions : '',
              children: items[key].children ? SelectList.formatItems(items[key].children) : null
            });
          }
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

SelectList.limit = 8;


function FieldInputs(props) {
  if (props.nested) {
    return React.createElement(
      'ul',
      { className: 'field-inputs field-nested' },
      props.children
    );
  }

  return React.createElement(
    'div',
    { className: 'field-inputs' },
    props.children
  );
}

var SelectItem = function (_React$Component2) {
  _inherits(SelectItem, _React$Component2);

  function SelectItem() {
    _classCallCheck(this, SelectItem);

    return _possibleConstructorReturn(this, (SelectItem.__proto__ || Object.getPrototypeOf(SelectItem)).apply(this, arguments));
  }

  _createClass(SelectItem, [{
    key: 'checked',
    value: function checked(value) {
      return this.props.selected.find(function (item) {
        return item.value == value;
      });
    }
  }, {
    key: 'componentDidMount',
    value: function componentDidMount() {
      if (this.props.reorderable) this.node.dataset.sortableIndex = this.props.sortableIndex;
    }
  }, {
    key: 'componentDidUpdate',
    value: function componentDidUpdate() {
      this.componentDidMount();
    }
  }, {
    key: 'render',
    value: function render() {
      var _this6 = this;

      var props = this.props;
      var checked = this.checked(props.item.value);

      if (props.item.section) {
        return React.createElement(
          'div',
          { className: 'field-group-head', key: props.item.section },
          props.item.section
        );
      }

      var listItem = React.createElement(
        'label',
        { className: checked ? 'act' : '', ref: function ref(label) {
            _this6.node = label;
          } },
        props.reorderable && React.createElement(
          'span',
          { className: 'icon-reorder' },
          ' '
        ),
        props.selectable && React.createElement('input', { type: props.multi ? "checkbox" : "radio",
          value: props.item.value,
          onChange: function onChange(e) {
            return props.handleSelect(e, props.item);
          },
          checked: checked ? 'checked' : '' }),
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
            React.createElement('a', { href: '', onClick: function onClick(e) {
                return props.handleRemove(e, props.item);
              } })
          )
        )
      );

      if (props.nested) {
        return React.createElement(
          'li',
          null,
          listItem,
          props.item.children && React.createElement(
            'ul',
            null,
            props.item.children.map(function (item, index) {
              return React.createElement(SelectItem, _extends({}, props, {
                key: item.value,
                item: item
              }));
            })
          )
        );
      }

      return listItem;
    }
  }]);

  return SelectItem;
}(React.Component);

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