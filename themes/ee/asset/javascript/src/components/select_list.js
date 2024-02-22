"use strict";

function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _extends() { _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
var SelectList = /*#__PURE__*/function (_React$Component) {
  _inherits(SelectList, _React$Component);

  function SelectList(props) {
    var _this;

    _classCallCheck(this, SelectList);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(SelectList).call(this, props)); // In the rare case we need to force a full-rerender of the component, we'll
    // increment this variable which is set as a key on the root element,
    // telling React to destroy it and start anew

    _defineProperty(_assertThisInitialized(_this), "handleSelect", function (event, item) {
      var selected = [],
          checked = event.target.checked,
          XORvalue = '--';

      if (_this.props.multi && item.value != XORvalue) {
        if (checked) {
          selected = _this.props.selected.concat([item]).filter(function (item) {
            return item.value != XORvalue;
          }); // uncheck XOR value
          // check if item has toggles object
          // toggles are present on the Channel->Edit->Categories

          if (item.toggles && Object.keys(item.toggles).length) {
            var _loop = function _loop(_key) {
              if (item.toggles[_key]) {
                i = _this.state.toggles.filter(function (toggle) {
                  return toggle[_key] == item.value;
                });

                if (!i.length) {
                  var _this$state$toggles$p;

                  _this.state.toggles.push((_this$state$toggles$p = {}, _defineProperty(_this$state$toggles$p, _key, item.value), _defineProperty(_this$state$toggles$p, 'name', _key), _defineProperty(_this$state$toggles$p, 'value', item.value), _this$state$toggles$p));
                }
              }
            };

            for (var _key in item.toggles) {
              var i;

              _loop(_key);
            }
          } // Sort selection?


          if (_this.props.selectionShouldRetainItemOrder) {
            selected = _this.getOrderedSelection(selected);
          } // Select parents?


          if (item.parent && _this.props.autoSelectParents) {
            selected = selected.concat(_this.diffItems(_this.props.selected, _this.getFlattenedParentsOfItem(item)));
          }

          if (item.children && _this.props.autoSelectParents) {
            selected = selected.concat(_this.getFlattenedChildrenOfItem(item));
          }
        } else {
          var deselect = [item];

          if (item.children && _this.props.autoSelectParents) {
            deselect = deselect.concat(_this.getFlattenedChildrenOfItem(item));
          }

          selected = _this.diffItems(deselect, _this.props.selected);
        }
      } else {
        selected = checked ? [item] : [];
      }

      _this.props.selectionChanged(selected);

      if (_this.props.groupToggle) EE.cp.form_group_toggle(event.target);
    });

    _defineProperty(_assertThisInitialized(_this), "clearSelection", function (event) {
      _this.props.selectionChanged([]);

      event.preventDefault();
    });

    _defineProperty(_assertThisInitialized(_this), "filterChange", function (name, value) {
      _this.props.filterChange(name, value);
    });

    _defineProperty(_assertThisInitialized(_this), "handleToggleAll", function (check) {
      // If checking, merge the newly-selected items on to the existing stack
      // in case the current view is limited by a filter
      if (check) {
        newlySelected = _this.props.items.filter(function (thisItem) {
          // Do not attempt to select disabled choices
          if (_this.props.disabledChoices && _this.props.disabledChoices.includes(thisItem.value)) {
            return false;
          }

          found = _this.props.selected.find(function (item) {
            return item.value == thisItem.value;
          });
          return !found;
        });
        newlySelected.forEach(function (item) {
          if (item.children && _this.props.autoSelectParents) {
            newlySelected = newlySelected.concat(_this.getFlattenedChildrenOfItem(item));
          }
        });

        _this.props.selected.forEach(function (item) {
          if (item.children && _this.props.autoSelectParents) {
            newlySelected = newlySelected.concat(_this.getFlattenedChildrenOfItem(item));
          }
        });

        _this.props.selectionChanged(_this.props.selected.concat(newlySelected));
      } else {
        // Do not uncheck disabled choices if they are selected
        if (_this.props.disabledChoices) {
          _this.props.selectionChanged(_this.props.selected.filter(function (item) {
            return _this.props.disabledChoices.includes(item.value);
          }));
        } else {
          _this.props.selectionChanged([]);
        }
      }
    });

    _this.version = 0;
    var toggles = [];
    var values = props.selected.length ? props.selected.map(function (item) {
      return item.value;
    }) : [];

    if (props.selectable && props.items.length != 0 && props.selected.length != 0 && props.toggles && props.toggles.length != 0) {
      props.items.filter(function (item) {
        return values.includes(item.value);
      }).forEach(function (item) {
        props.toggles.filter(function (toggle) {
          if (item.toggles[toggle] == true) {
            var _toggles$push;

            toggles.push((_toggles$push = {}, _defineProperty(_toggles$push, toggle, item.value), _defineProperty(_toggles$push, 'name', toggle), _defineProperty(_toggles$push, 'value', item.value), _toggles$push));
          }
        });
      });
    }

    _this.state = {
      toggles: toggles
    };
    return _this;
  }

  _createClass(SelectList, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      if (this.props.nestableReorder) {
        this.bindNestable();
      } else if (this.props.reorderable) {
        this.bindSortable();
      }
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps, prevState) {
      if (this.props.multi && prevProps.selected.length != this.props.selected.length || !this.props.multi && prevProps.selected != this.props.selected) {
        $(this.input).trigger('change');
      }

      if (this.props.nestableReorder) {
        this.bindNestable();
      }
    }
  }, {
    key: "bindSortable",
    value: function bindSortable() {
      var _this2 = this;

      var selector = this.props.nested ? '.field-nested' : '.field-inputs';
      $(selector, this.container).sortable({
        axis: 'y',
        containment: 'parent',
        handle: '.icon-reorder',
        items: this.props.nested ? '> li' : 'label',
        placeholder: 'field-reorder-placeholder',
        sort: EE.sortable_sort_helper,
        start: function start(event, ui) {
          ui.helper.addClass('field-reorder-drag');
        },
        stop: function stop(event, ui) {
          ui.item.removeClass('field-reorder-drag').addClass('field-reorder-drop');
          setTimeout(function () {
            ui.item.removeClass('field-reorder-drop');
          }, 1000);

          var getNestedItems = function getNestedItems(nodes) {
            var serialized = [];
            nodes.forEach(function (node) {
              var item = {
                id: node.dataset.id
              };
              var children = $(node).find('> ul > [data-id]');

              if (children.length) {
                item['children'] = getNestedItems(children.toArray());
              }

              serialized.push(item);
            });
            return serialized;
          };

          var items = ui.item.closest('.field-inputs').find('> [data-id]').toArray();

          var itemsHash = _this2.getItemsHash(_this2.props.items);

          var nestedItems = getNestedItems(items);

          _this2.props.itemsChanged(_this2.getItemsArrayForNestable(itemsHash, nestedItems));

          if (_this2.props.reorderAjaxUrl) {
            $.ajax({
              url: _this2.props.reorderAjaxUrl,
              data: {
                'order': nestedItems
              },
              type: 'POST',
              dataType: 'json'
            });
          }
        }
      });
    } // Allows for changing of parents and children, whereas sortable() will only
    // let you change the order constrained to a level

  }, {
    key: "bindNestable",
    value: function bindNestable() {
      var _this3 = this;

      // Make sure the draggable container is positioned relatively so that the nestable drag item is positioned correctly
      this.container.parentNode.style.position = 'relative';
      $(this.container).nestable({
        listNodeName: 'ul',
        listClass: 'field-nested',
        itemClass: 'nestable-item',
        rootClass: 'field-select',
        dragClass: 'field-inputs.field-reorder-drag',
        handleClass: 'icon-reorder',
        placeElement: $('<li class="field-reorder-placeholder"></li>'),
        expandBtnHTML: '',
        collapseBtnHTML: '',
        maxDepth: 10,
        constrainToRoot: true
      }).on('change', function (event) {
        if (!$(event.target).data("nestable")) return; // React will not be able to handle Nestable changing a node's children,
        // so force a full re-render if it happens

        _this3.version++;

        var itemsHash = _this3.getItemsHash(_this3.props.items);

        var nestableData = $(event.target).nestable('serialize');

        _this3.props.itemsChanged(_this3.getItemsArrayForNestable(itemsHash, nestableData));

        if (_this3.props.reorderAjaxUrl) {
          $.ajax({
            url: _this3.props.reorderAjaxUrl,
            data: {
              'order': nestableData
            },
            type: 'POST',
            dataType: 'json'
          });
        }
      });
    }
  }, {
    key: "getItemsHash",
    value: function getItemsHash(items) {
      var _this4 = this;

      var itemsHash = {};
      items.forEach(function (item) {
        itemsHash[item.value] = item;
        if (item.children) itemsHash = Object.assign(itemsHash, _this4.getItemsHash(item.children));
      });
      return itemsHash;
    }
  }, {
    key: "getItemsArrayForNestable",
    value: function getItemsArrayForNestable(itemsHash, nestable, parent) {
      var _this5 = this;

      var items = [];
      nestable.forEach(function (orderedItem) {
        var item = itemsHash[orderedItem.id];
        var newItem = Object.assign({}, item);
        newItem.parent = parent ? parent : null;
        newItem.children = orderedItem.children ? _this5.getItemsArrayForNestable(itemsHash, orderedItem.children, newItem) : null;
        items.push(newItem);
      });
      return items;
    }
  }, {
    key: "getOrderedSelection",
    // Orders the selection array based on the items' order in the list
    value: function getOrderedSelection(selected) {
      var _this6 = this;

      orderedSelection = [];
      return selected.sort(function (a, b) {
        a = _this6.props.initialItems.findIndex(function (item) {
          return item.value == a.value;
        });
        b = _this6.props.initialItems.findIndex(function (item) {
          return item.value == b.value;
        });
        return a < b ? -1 : 1;
      });
    } // Returns all items in items2 that aren't present in items1

  }, {
    key: "diffItems",
    value: function diffItems(items1, items2) {
      var values = items1.map(function (item) {
        return item.value;
      });
      return items2.filter(function (item) {
        // Would use .includes() here but we can't rely on types being
        // the same, so we need to do a manual loose type check
        return values.every(function (value) {
          return value != item.value;
        });
      });
    }
  }, {
    key: "getFlattenedParentsOfItem",
    value: function getFlattenedParentsOfItem(item) {
      var items = [];

      while (item.parent) {
        items.push(item.parent);
        item = item.parent;
      }

      return items;
    }
  }, {
    key: "getFlattenedChildrenOfItem",
    value: function getFlattenedChildrenOfItem(item) {
      var _this7 = this;

      var items = [];
      item.children.forEach(function (child) {
        items.push(child);

        if (child.children) {
          items = items.concat(_this7.getFlattenedChildrenOfItem(child));
        }
      });
      return items;
    }
  }, {
    key: "getFullItem",
    // You may have an item without complete metadata (component, parents, etc.),
    // this can happen with initial selections passed into the component. This function
    // will try to find the corresponding item in what we have available and return it.
    // It may not be available though if this list is AJAX-filtered.
    value: function getFullItem(item) {
      var itemsHash = this.getItemsHash(this.props.initialItems);

      if (itemsHash[item.value] !== undefined) {
        return itemsHash[item.value];
      }

      return item;
    }
  }, {
    key: "render",
    value: function render() {
      var _this8 = this;

      var props = this.props;
      var shouldShowToggleAll = (props.multi || !props.selectable) && props.toggleAll !== null;
      var values = props.selected.length ? props.selected.map(function (item) {
        return item.value;
      }) : [];
      return React.createElement("div", {
        className: props.tooMany ? ' lots-of-checkboxes' : '',
        ref: function ref(container) {
          _this8.container = container;
        },
        key: this.version
      }, props.tooMany && React.createElement("div", {
        "class": "lots-of-checkboxes__search"
      }, React.createElement("div", {
        "class": "lots-of-checkboxes__search-inner"
      }, props.tooMany && React.createElement("div", {
        "class": "lots-of-checkboxes__search-input"
      }, React.createElement(FilterBar, null, props.filters && props.filters.map(function (filter) {
        return React.createElement(FilterSelect, {
          key: filter.name,
          name: filter.name,
          keepSelectedState: true,
          title: filter.title,
          placeholder: filter.placeholder,
          items: filter.items,
          onSelect: function onSelect(value) {
            return _this8.filterChange(filter.name, value);
          }
        });
      }), React.createElement(FilterSearch, {
        onSearch: function onSearch(e) {
          return _this8.filterChange('search', e.target.value);
        }
      }))), shouldShowToggleAll && props.tooMany && React.createElement(FilterToggleAll, {
        checkAll: props.toggleAll,
        onToggleAll: function onToggleAll(check) {
          return _this8.handleToggleAll(check);
        }
      }))), React.createElement(FieldInputs, {
        nested: props.nested,
        tooMany: props.tooMany,
        splitForTwo: props.splitForTwo,
        list: props.items,
        selectedItems: props.selected,
        handle: this.handleSelect
      }, !props.loading && props.items.length == 0 && React.createElement(NoResults, {
        text: props.noResults
      }), props.loading && React.createElement(Loading, {
        text: EE.lang.loading
      }), !props.loading && props.items.map(function (item, index) {
        return React.createElement(SelectItem, {
          key: item.value ? item.value : item.section,
          item: item,
          name: props.name,
          selected: props.selected,
          disabledChoices: props.disabledChoices,
          multi: props.multi,
          nested: props.nested,
          selectable: props.selectable,
          reorderable: props.reorderable,
          removable: props.removable && (!props.unremovableChoices || !props.unremovableChoices.includes(item.value)),
          editable: props.editable,
          handleSelect: _this8.handleSelect,
          handleRemove: function handleRemove(e, item) {
            return props.handleRemove(e, item);
          },
          groupToggle: props.groupToggle,
          toggles: props.toggles,
          state: _this8.state,
          toggleChanged: props.toggleChanged
        });
      })), !props.multi && props.tooMany && props.selected[0] && React.createElement(SelectedItem, {
        item: this.getFullItem(props.selected[0]),
        clearSelection: this.clearSelection,
        selectionRemovable: props.selectionRemovable
      }), !props.jsonify && props.selectable && props.selected.length == 0 && React.createElement("input", {
        type: "hidden",
        name: props.multi ? props.name + '[]' : props.name,
        value: "",
        ref: function ref(input) {
          _this8.input = input;
        }
      }), !props.jsonify && props.selectable && props.selected.map(function (item) {
        return React.createElement("input", {
          type: "hidden",
          key: item.value,
          name: props.multi ? props.name + '[]' : props.name,
          value: item.value,
          ref: function ref(input) {
            _this8.input = input;
          }
        });
      }), this.state.toggles.length != 0 && this.state.toggles.map(function (toggle) {
        return React.createElement("input", {
          type: "hidden",
          key: toggle.name + '[' + toggle.value + ']',
          name: props.multi ? toggle.name + '[]' : toggle.name,
          value: toggle.value,
          ref: function ref(input) {
            _this8.input = input;
          }
        });
      }), props.jsonify && props.selectable && React.createElement("input", {
        type: "hidden",
        name: props.name,
        value: JSON.stringify(values),
        ref: function ref(input) {
          _this8.input = input;
        }
      }));
    }
  }], [{
    key: "formatItems",
    value: function formatItems(items, parent, multi) {
      if (!items) return [];
      var itemsArray = [];
      var currentSection = null;

      for (var _i = 0, _Object$keys = Object.keys(items); _i < _Object$keys.length; _i++) {
        key = _Object$keys[_i];

        if (items[key].section) {
          currentSection = items[key].section;
          itemsArray.push({
            section: currentSection,
            label: ''
          });
        } else {
          // When formatting selected items lists, selections will likely be a flat
          // array of values for multi select
          var value = multi ? items[key] : key;
          var newItem = {
            value: items[key].value || items[key].value === '' ? items[key].value : value,
            label: items[key].label !== undefined ? items[key].label : items[key],
            instructions: items[key].instructions ? items[key].instructions : '',
            children: null,
            parent: parent ? parent : null,
            component: items[key].component != undefined ? items[key].component : null,
            sectionLabel: currentSection,
            entry_id: items[key].entry_id ? items[key].entry_id : '',
            upload_location_id: items[key].upload_location_id ? items[key].upload_location_id : '',
            path: items[key].path ? items[key].path : '',
            toggles: items[key].toggles ? items[key].toggles : null,
            status: items[key].status ? items[key].status : null,
            editable: items[key].editable ? items[key].editable : false
          };

          if (items[key].children) {
            newItem.children = SelectList.formatItems(items[key].children, newItem);
          }

          itemsArray.push(newItem);
        }
      }

      return itemsArray;
    } // Counts items including any nested items to get a total count for the field

  }, {
    key: "countItems",
    value: function countItems(items) {
      return items.length + items.reduce(function (sum, item) {
        if (item.children) {
          return sum + SelectList.countItems(item.children);
        }

        return sum;
      }, 0);
    }
  }]);

  return SelectList;
}(React.Component);

_defineProperty(SelectList, "defaultProps", {
  reorderable: false,
  nestableReorder: false,
  removable: false,
  selectable: true,
  tooManyLimit: 8,
  toggleAllLimit: 3,
  selectionRemovable: false,
  selectionShouldRetainItemOrder: true
});

function FieldInputs(props) {
  var divClass = props.tooMany ? ' lots-of-checkboxes__items--too-many' : '';

  if (props.tooMany && props.splitForTwo && props.nested) {
    return React.createElement(React.Fragment, null, React.createElement("ul", {
      className: 'field-inputs lots-of-checkboxes__items field-nested splitForTwo' + divClass
    }, props.children), React.createElement("ul", {
      className: 'field-inputs lots-of-checkboxes__items field-nested splitForTwo second-list' + divClass
    }, React.createElement("h3", null, EE.lang.extra_title), props.list.map(function (item, index) {
      return React.createElement(ListOfSelectedCategories, {
        key: item.value,
        item: item,
        name: props.name,
        selected: props.selectedItems,
        disabledChoices: props.disabledChoices,
        nested: props.nested,
        selectable: true,
        reorderable: false,
        removable: false,
        editable: false,
        handleSelect: props.handle,
        handleRemove: function handleRemove(e, item) {
          return props.handleRemove(e, item);
        },
        groupToggle: props.groupToggle
      });
    })));
  }

  if (props.nested) {
    return React.createElement("ul", {
      className: 'field-inputs lots-of-checkboxes__items field-nested' + divClass
    }, props.children);
  }

  return React.createElement("div", {
    className: 'field-inputs lots-of-checkboxes__items' + divClass
  }, props.children);
}

var SelectItem = /*#__PURE__*/function (_React$Component2) {
  _inherits(SelectItem, _React$Component2);

  function SelectItem() {
    _classCallCheck(this, SelectItem);

    return _possibleConstructorReturn(this, _getPrototypeOf(SelectItem).apply(this, arguments));
  }

  _createClass(SelectItem, [{
    key: "checked",
    value: function checked(value) {
      return this.props.selected.find(function (item) {
        return item.value == value;
      });
    }
  }, {
    key: "bindToggleChange",
    value: function bindToggleChange(e, item) {
      e.preventDefault();
      $(e.currentTarget).toggleClass('active');
      $(e.currentTarget).find('i').toggleClass('fa-toggle-on fa-toggle-off');
      var toggleName = $(e.currentTarget).attr('data-toggle-name');
      item.toggles[toggleName] = !item.toggles[toggleName];

      if (item.toggles[toggleName]) {
        var _this$props$state$tog;

        this.props.state.toggles.push((_this$props$state$tog = {}, _defineProperty(_this$props$state$tog, toggleName, item.value), _defineProperty(_this$props$state$tog, 'name', toggleName), _defineProperty(_this$props$state$tog, 'value', item.value), _this$props$state$tog));
      } else {
        this.props.state.toggles = this.props.state.toggles.filter(function (object) {
          if (object[toggleName] != item.value) return object;
        });
      }

      this.props.toggleChanged(this.props.state.toggles);
    }
  }, {
    key: "toggleOn",
    value: function toggleOn() {
      return React.createElement("svg", {
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 576 384"
      }, React.createElement("path", {
        fill: "#171feb",
        d: "m0,192C0,86,86,0,192,0h192c106,0,192,86,192,192s-86,192-192,192h-192C86,384,0,298,0,192Z"
      }), React.createElement("circle", {
        fill: "#fff",
        cx: "384",
        cy: "192",
        r: "96"
      }));
    }
  }, {
    key: "toggleOff",
    value: function toggleOff() {
      return React.createElement("svg", {
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 576 512"
      }, React.createElement("path", {
        d: "M384 112c79.5 0 144 64.5 144 144s-64.5 144-144 144H192c-79.5 0-144-64.5-144-144s64.5-144 144-144H384zM576 256c0-106-86-192-192-192H192C86 64 0 150 0 256S86 448 192 448H384c106 0 192-86 192-192zM192 352a96 96 0 1 0 0-192 96 96 0 1 0 0 192z"
      }));
    }
  }, {
    key: "render",
    value: function render() {
      var _this9 = this;

      var props = this.props;
      var checked = this.checked(props.item.value);
      var label = props.item.label;
      var disabled = props.disabledChoices && props.disabledChoices.includes(props.item.value);

      if (props.item.section) {
        return React.createElement("div", {
          className: "field-group-head",
          key: props.item.section
        }, props.item.section);
      }

      var listItem = React.createElement("label", {
        className: 'checkbox-label',
        "data-id": props.reorderable && !props.nested ? props.item.value : null
      }, props.selectable && React.createElement("input", {
        type: props.multi ? "checkbox" : "radio",
        value: props.item.value,
        onChange: function onChange(e) {
          return props.handleSelect(e, props.item);
        },
        checked: checked ? 'checked' : '',
        "data-group-toggle": props.groupToggle ? JSON.stringify(props.groupToggle) : '[]',
        disabled: disabled ? 'disabled' : ''
      }), React.createElement("div", {
        className: props.editable ? "checkbox-label__text checkbox-label__text-editable" : "checkbox-label__text"
      }, props.reorderable && React.createElement("span", {
        className: "icon-reorder icon-left"
      }), props.editable && React.createElement("a", {
        href: "#",
        "class": "flyout-edit",
        dangerouslySetInnerHTML: {
          __html: label
        }
      }), !props.editable && React.createElement("div", {
        dangerouslySetInnerHTML: {
          __html: label
        }
      }), " ", props.item.instructions && React.createElement("span", {
        className: "meta-info"
      }, props.item.instructions), React.createElement("div", {
        "class": "button-group button-group-xsmall button-group-flyout-right"
      }, props.toggles && props.toggles.length != 0 && props.toggles.map(function (toggleName, index) {
        return React.createElement("a", {
          href: "",
          className: 'button button--default extra-flyout-button flyout-' + toggleName + (props.item.toggles[toggleName] == true ? ' active' : ''),
          onClick: function onClick(e) {
            return _this9.bindToggleChange(e, props.item);
          },
          disabled: checked ? false : true,
          "data-toggle-name": toggleName
        }, EE.lang[toggleName], " ", props.item.toggles[toggleName] == true ? _this9.toggleOn() : _this9.toggleOff());
      }), props.editable && React.createElement("a", {
        href: "",
        className: "button button--default flyout-edit flyout-edit-icon",
        "data-id": props.item.value
      }, React.createElement("span", {
        className: "sr-only"
      }, EE.lang.edit_element), React.createElement("i", {
        "class": "fal fa-pencil-alt"
      })), props.removable && React.createElement("a", {
        href: "",
        className: "button button--default js-button-delete",
        onClick: function onClick(e) {
          return props.handleRemove(e, props.item);
        }
      }, React.createElement("span", {
        className: "sr-only"
      }, EE.lang.remove_btn), React.createElement("i", {
        "class": "fal fa-fw fa-trash-alt"
      })))));

      if (props.nested) {
        return React.createElement("li", {
          className: "nestable-item",
          "data-id": props.item.value
        }, listItem, props.item.children && React.createElement("ul", {
          className: "field-nested"
        }, props.item.children.map(function (item, index) {
          return React.createElement(SelectItem, _extends({}, props, {
            key: item.value,
            item: item,
            handleRemove: function handleRemove(e, item) {
              return props.handleRemove(e, item);
            }
          }));
        })));
      }

      return listItem;
    }
  }]);

  return SelectItem;
}(React.Component);

var SelectedItem = /*#__PURE__*/function (_React$Component3) {
  _inherits(SelectedItem, _React$Component3);

  function SelectedItem() {
    _classCallCheck(this, SelectedItem);

    return _possibleConstructorReturn(this, _getPrototypeOf(SelectedItem).apply(this, arguments));
  }

  _createClass(SelectedItem, [{
    key: "render",
    value: function render() {
      var props = this.props;
      var label = props.item.label;
      return React.createElement("div", {
        className: "lots-of-checkboxes__selection"
      }, React.createElement("i", {
        className: "fal fa-check-circle"
      }), " ", label, props.selectionRemovable && React.createElement("a", {
        className: "button button--default float-right",
        href: "",
        onClick: props.clearSelection
      }, React.createElement("i", {
        "class": "fal fa-trash-alt"
      })));
    }
  }]);

  return SelectedItem;
}(React.Component); // This class we will use only for Entry page
// Category tab, to show selected category as a single list
// add don't break the main category order


var ListOfSelectedCategories = /*#__PURE__*/function (_React$Component4) {
  _inherits(ListOfSelectedCategories, _React$Component4);

  function ListOfSelectedCategories() {
    _classCallCheck(this, ListOfSelectedCategories);

    return _possibleConstructorReturn(this, _getPrototypeOf(ListOfSelectedCategories).apply(this, arguments));
  }

  _createClass(ListOfSelectedCategories, [{
    key: "checked",
    value: function checked(value) {
      return this.props.selected.find(function (item) {
        return item.value == value;
      });
    }
  }, {
    key: "render",
    value: function render() {
      var props = this.props;
      var checked = this.checked(props.item.value);
      var label = props.item.label;
      var disabled = props.disabledChoices && props.disabledChoices.includes(props.item.value);
      var listItem;

      if (checked) {
        listItem = React.createElement("label", {
          className: 'checkbox-label',
          "data-id": props.item.value
        }, props.selectable && checked && React.createElement("input", {
          type: "checkbox",
          value: props.item.value,
          checked: 'checked',
          onChange: function onChange(e) {
            return props.handleSelect(e, props.item);
          },
          "data-group-toggle": props.groupToggle ? JSON.stringify(props.groupToggle) : '[]'
        }), React.createElement("div", {
          className: props.editable ? "checkbox-label__text checkbox-label__text-editable" : "checkbox-label__text"
        }, !props.editable && React.createElement("div", {
          dangerouslySetInnerHTML: {
            __html: label
          }
        }), " "));
      }

      if (props.nested) {
        return React.createElement("li", {
          className: "nestable-item",
          "data-id": props.item.value
        }, listItem, props.item.children && React.createElement("ul", {
          className: "field-nested"
        }, props.item.children.map(function (item, index) {
          return React.createElement(ListOfSelectedCategories, _extends({}, props, {
            key: item.value,
            item: item
          }));
        })));
      }

      return listItem;
    }
  }]);

  return ListOfSelectedCategories;
}(React.Component);