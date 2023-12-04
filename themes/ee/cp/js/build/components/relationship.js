"use strict";

function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance"); }

function _iterableToArray(iter) { if (Symbol.iterator in Object(iter) || Object.prototype.toString.call(iter) === "[object Arguments]") return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = new Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } }

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
var Relationship = /*#__PURE__*/function (_React$Component) {
  _inherits(Relationship, _React$Component);

  function Relationship(props) {
    var _this;

    _classCallCheck(this, Relationship);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(Relationship).call(this, props));

    _defineProperty(_assertThisInitialized(_this), "entryWasCreated", function (result, modal) {
      var selected = _this.state.selected;

      if (_this.props.multi) {
        selected.push(result.item);
      } else {
        selected = [result.item];
      }

      _this.setState({
        selected: selected,
        items: [].concat(_toConsumableArray(_this.state.items), [result.item])
      });

      modal.trigger('modal:close');
    });

    _defineProperty(_assertThisInitialized(_this), "entryWasEdited", function (result, modal) {
      var selected = _this.state.selected;

      if (_this.props.multi) {
        $.each(selected, function (i, el) {
          if (el.value == result.item.value) {
            el.label = result.item.label;
          }
        });
      } else {
        selected = [result.item];
      }

      _this.setState({
        selected: selected,
        items: [].concat(_toConsumableArray(_this.state.items), [result.item])
      });

      modal.trigger('modal:close');
    });

    _defineProperty(_assertThisInitialized(_this), "channelFilterChange", function (newValue) {
      _this.setState({
        channelFilter: newValue
      });
    });

    _defineProperty(_assertThisInitialized(_this), "handleSearch", function (event) {
      _this.setState({
        filterTerm: event.target.value || false
      });
    });

    _defineProperty(_assertThisInitialized(_this), "itemsChanged", function (items) {
      _this.setState({
        items: items
      });
    });

    _defineProperty(_assertThisInitialized(_this), "initialItemsChanged", function (items) {
      _this.initialItems = items;

      if (!_this.ajaxFilter && _this.state.filterValues.search) {
        items = _this.filterItems(items, _this.state.filterValues.search);
      }

      _this.setState({
        items: items
      });

      if (_this.props.itemsChanged) {
        _this.props.itemsChanged(items);
      }
    });

    _defineProperty(_assertThisInitialized(_this), "filterChange", function (name, value) {
      var filterState = _this.state.filterValues;
      filterState[name] = value;

      _this.setState({
        filterValues: filterState
      }); // DOM filter


      if (!_this.ajaxFilter && name == 'search') {
        _this.itemsChanged(_this.filterItems(_this.initialItems, value));

        return;
      } // Debounce AJAX filter


      clearTimeout(_this.ajaxTimer);
      if (_this.ajaxRequest) _this.ajaxRequest.abort();
      var params = filterState;
      params.selected = _this.getSelectedValues(_this.props.selected);

      _this.setState({
        loading: true
      });

      _this.ajaxTimer = setTimeout(function () {
        _this.ajaxRequest = _this.forceAjaxRefresh(params);
      }, 300);
    });

    _defineProperty(_assertThisInitialized(_this), "bindSortable", function () {
      var thisRef = _assertThisInitialized(_this);

      $(_this.listGroup).sortable({
        axis: 'y',
        // containment: 'parent',
        handle: '.list-item__handle',
        items: '.list-item',
        sort: function sort(event, ui) {
          try {
            EE.sortable_sort_helper(event, ui);
          } catch (error) {}
        },
        start: function start(event, ui) {
          // Save the start index for later
          $(_assertThisInitialized(_this)).attr('data-start-index', ui.item.index());
        },
        stop: function stop(event, ui) {
          var newIndex = ui.item.index();
          var oldIndex = $(_assertThisInitialized(_this)).attr('data-start-index'); // Cancel the sort so jQeury doesn't move the items
          // This needs to be done by react since it handles the dom

          $(thisRef.listGroup).sortable('cancel');
          var selected = thisRef.state.selected; // Move the item to the new position

          selected.splice(newIndex, 0, selected.splice(oldIndex, 1)[0]);
          thisRef.setState({
            selected: selected
          });
          $(document).trigger('entry:preview');
          $("[data-publish] > form").trigger("entry:startAutosave");
        }
      });
    });

    _this.initialItems = SelectList.formatItems(props.items);
    _this.state = {
      selected: props.selected,
      items: props.items,
      channelFilter: false,
      filterTerm: false,
      filterValues: {}
    };
    _this.ajaxFilter = SelectList.countItems(_this.initialItems) >= props.limit && props.filter_url;
    _this.ajaxTimer = null;
    _this.ajaxRequest = null;
    _this.lang = typeof props.lang !== 'undefined' ? props.lang : EE.relationship.lang;
    _this.showCreateDropdown = props.channels.length > 1 ? typeof props.showCreateDropdown !== 'undefined' && props.showCreateDropdown == false ? false : true : false;
    return _this;
  }

  _createClass(Relationship, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      this.bindSortable();
      EE.cp.formValidation.bindInputs(ReactDOM.findDOMNode(this).parentNode);
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps, prevState) {
      if (this.state.selected !== prevState.selected) {
        // Refresh the sortable items when the selected items change
        this.bindSortable();

        EE.cp.formValidation._sendAjaxRequest($(ReactDOM.findDOMNode(this).parentNode).find('input[type=hidden]').first());
      }
    }
  }, {
    key: "selectItem",
    value: function selectItem(item) {
      var index = this.state.selected.findIndex(function (obj) {
        return obj.value === item.value;
      }); // Don't add duplicate items

      if (index !== -1) {
        return;
      } // Add the item to the selection


      this.setState({
        selected: [].concat(_toConsumableArray(this.state.selected), [item])
      }); // Because the add field button shifts down when an item is added, we need to tell
      // the dropdown controller to update the dropdown positions so the dropdown stays under the button

      DropdownController.updateDropdownPositions();
    }
  }, {
    key: "deselect",
    value: function deselect(itemId) {
      this.setState({
        selected: this.state.selected.filter(function (item) {
          return item.value !== itemId;
        })
      });
    } // Opens a modal to create a new entry

  }, {
    key: "openPublishFormForChannel",
    value: function openPublishFormForChannel(channel) {
      var _this2 = this;

      var channelTitle = channel.title;
      var channelId = channel.id;
      var publishCreateUrl = typeof this.props.publishCreateUrl !== 'undefined' ? this.props.publishCreateUrl : EE.relationship.publishCreateUrl;
      EE.cp.ModalForm.openForm({
        url: publishCreateUrl.replace('###', channelId),
        full: true,
        iframe: true,
        success: this.entryWasCreated,
        load: function load(modal) {
          var entryTitle = $(_this2.field.closest('[data-publish]')).find('input[name=title]').val();

          var title = _this2.lang.creatingNew.replace('#to_channel#', channelTitle).replace('#from_channel#', EE.publish.channel_title);

          if (entryTitle) {
            title += '<b>: ' + entryTitle + '</b>';
          }

          EE.cp.ModalForm.setTitle(title);
        }
      });
    } // Opens a modal to edit an entry

  }, {
    key: "openPublishEditForm",
    value: function openPublishEditForm(id) {
      var publishEditUrl = typeof this.props.publishEditUrl !== 'undefined' ? this.props.publishEditUrl : EE.relationship.publishEditUrl;
      EE.cp.ModalForm.openForm({
        url: publishEditUrl.replace('###', id + '&' + $.param({
          entry_ids: [id]
        })),
        full: true,
        iframe: true,
        dataType: 'json',
        success: this.entryWasEdited,
        load: function load(modal) {}
      });
    }
  }, {
    key: "filterItems",
    value: function filterItems(items, searchTerm) {
      var _this3 = this;

      items = items.map(function (item) {
        // Clone item so we don't modify reference types
        item = Object.assign({}, item); // If any children contain the search term, we'll keep the parent

        if (item.children) item.children = _this3.filterItems(item.children, searchTerm);
        var itemFoundInChildren = item.children && item.children.length > 0;
        var itemFound = String(item.label).toLowerCase().includes(searchTerm.toLowerCase());
        var itemValue = item.value.toString().includes(searchTerm.toLowerCase());
        return itemFound || itemFoundInChildren || itemValue ? item : false;
      });
      return items.filter(function (item) {
        return item;
      });
    }
  }, {
    key: "getSelectedValues",
    value: function getSelectedValues(selected) {
      var values = [];

      if (selected instanceof Array) {
        values = selected.map(function (item) {
          return item.value;
        });
      } else if (selected.value) {
        values = [selected.value];
      }

      return values.join('|');
    }
  }, {
    key: "forceAjaxRefresh",
    value: function forceAjaxRefresh(params) {
      var _this4 = this;

      if (!params) {
        params = this.state.filterValues;
        params.selected = this.getSelectedValues(this.props.selected);
      }

      return $.ajax({
        method: 'POST',
        url: this.props.filter_url,
        data: $.param(params),
        dataType: 'json',
        success: function success(data) {
          _this4.setState({
            loading: false
          });

          _this4.initialItemsChanged(SelectList.formatItems(data));
        },
        error: function error() {} // Defined to prevent error on .abort above

      });
    } // Event when a new entry was created by the channel modal

  }, {
    key: "render",
    value: function render() {
      var _this5 = this;

      var props = this.props; // Determine what items show up in the add dropdown

      var dropdownItems = this.state.items.filter(function (el) {
        var allowedChannel = true; // Is the user filtering by channel?

        if (_this5.state.channelFilter) {
          allowedChannel = el.channel_id == _this5.state.channelFilter;
        }

        var filterName = true; // Is the user filtering by name

        if (_this5.state.filterTerm) {
          filterName = el.label.toLowerCase().includes(_this5.state.filterTerm.toLowerCase());
        } // Only show items that are not already added


        var notInSelected = !_this5.state.selected.some(function (e) {
          return e.value === el.value;
        });
        return notInSelected && allowedChannel && filterName;
      });
      var showAddButton = (this.props.multi || this.state.selected.length == 0) && (this.props.rel_max == 0 || this.props.rel_max > this.state.selected.length);
      var channelFilterItems = props.channels.map(function (channel) {
        return {
          label: channel.title,
          value: channel.id
        };
      });
      var handleSearchItem = this.handleSearch;
      return React.createElement("div", {
        ref: function ref(el) {
          return _this5.field = el;
        }
      }, this.state.selected.length > 0 && React.createElement("ul", {
        className: "list-group list-group--connected mb-s",
        ref: function ref(el) {
          return _this5.listGroup = el;
        }
      }, this.state.selected.map(function (item) {
        return React.createElement("li", {
          className: "list-item"
        }, _this5.state.selected.length > 1 && React.createElement("div", {
          "class": "list-item__handle"
        }, React.createElement("i", {
          "class": "fal fa-bars"
        })), React.createElement("div", {
          className: "list-item__content"
        }, React.createElement("div", {
          "class": "list-item__title"
        }, item.label, " ", _this5.state.selected.length > 10 && React.createElement("small", {
          className: "meta-info ml-s float-right"
        }, " ", item.instructions)), _this5.state.selected.length <= 10 && React.createElement("div", {
          "class": "list-item__secondary"
        }, props.display_entry_id && React.createElement("span", null, " #", item.value, " / "), item.instructions, props.display_status && React.createElement("span", {
          className: "status-indicator",
          style: {
            borderColor: '#' + EE.statuses[item.status],
            color: '#' + EE.statuses[item.status]
          }
        }, item.status))), React.createElement("div", {
          "class": "list-item__content-right"
        }, React.createElement("div", {
          className: "button-group"
        }, _this5.props.can_edit_items && item.can_edit && item.editable && React.createElement("button", {
          type: "button",
          title: _this5.lang.edit,
          className: "button button--small button--default",
          onClick: function onClick() {
            return _this5.openPublishEditForm(item.value);
          }
        }, React.createElement("i", {
          "class": "fal fa-pencil-alt"
        })), React.createElement("button", {
          type: "button",
          title: _this5.lang.remove,
          onClick: function onClick() {
            return _this5.deselect(item.value);
          },
          className: "button button--small button--default"
        }, React.createElement("i", {
          "class": "fal fa-fw fa-trash-alt"
        })))));
      })), this.state.selected.length == 0 && React.createElement("input", {
        type: "hidden",
        name: props.multi ? props.name + '[]' : props.name,
        value: ""
      }), this.state.selected.map(function (item) {
        return React.createElement("input", {
          type: "hidden",
          name: props.multi ? props.name + '[]' : props.name,
          value: item.value
        });
      }), React.createElement("div", {
        style: {
          display: showAddButton ? 'block' : 'none'
        }
      }, React.createElement("button", {
        type: "button",
        className: "js-dropdown-toggle button button--default"
      }, React.createElement("i", {
        "class": "fal fa-plus icon-left"
      }), " ", props.button_label ? props.button_label : this.lang.relateEntry), React.createElement("div", {
        className: "dropdown js-dropdown-auto-focus-input"
      }, React.createElement("div", {
        className: "dropdown__search d-flex"
      }, React.createElement("div", {
        className: "filter-bar flex-grow"
      }, React.createElement("div", {
        className: "filter-bar__item flex-grow"
      }, React.createElement("div", {
        className: "search-input"
      }, React.createElement("input", {
        type: "text",
        "class": "search-input__input input--small",
        onChange: function onChange(handleSearchItem) {
          return _this5.filterChange('search', handleSearchItem.target.value);
        },
        placeholder: this.lang.search
      }))), props.channels.length > 1 && React.createElement("div", {
        className: "filter-bar__item"
      }, React.createElement(DropDownButton, {
        keepSelectedState: true,
        title: this.lang.channel,
        items: channelFilterItems,
        onSelect: function onSelect(value) {
          return _this5.filterChange('channel_id', value);
        },
        buttonClass: "filter-bar__button"
      })), this.props.can_add_items && React.createElement("div", {
        className: "filter-bar__item"
      }, !this.showCreateDropdown && React.createElement("button", {
        type: "button",
        className: "button button--primary button--small",
        onClick: function onClick() {
          return _this5.openPublishFormForChannel(_this5.props.channels[0]);
        }
      }, this.props.new_entry), this.showCreateDropdown && React.createElement("div", null, React.createElement("button", {
        type: "button",
        className: "js-dropdown-toggle button button--primary button--small",
        "data-dropdown-pos": "bottom-end"
      }, this.props.new_entry, " ", React.createElement("i", {
        "class": "fal fa-chevron-down icon-right"
      })), React.createElement("div", {
        className: "dropdown"
      }, props.channelsForNewEntries.map(function (channel) {
        return React.createElement("a", {
          href: true,
          className: "dropdown__link",
          onClick: function onClick() {
            return _this5.openPublishFormForChannel(channel);
          }
        }, channel.title);
      })))))), React.createElement("div", {
        className: "dropdown__scroll dropdown__scroll--small"
      }, dropdownItems.map(function (item) {
        return React.createElement("a", {
          href: "",
          onClick: function onClick(e) {
            e.preventDefault();

            _this5.selectItem(item);
          },
          className: "dropdown__link"
        }, item.label, props.display_entry_id && React.createElement("span", {
          "class": "dropdown__link-entryId"
        }, " (#", item.value, ")"), props.display_status && React.createElement("span", {
          className: "dropdown__link-status-indicator",
          style: {
            borderColor: '#' + EE.statuses[item.status],
            color: '#' + EE.statuses[item.status]
          }
        }, item.status), " ", React.createElement("span", {
          className: "dropdown__link-right"
        }, item.instructions));
      }), dropdownItems.length == 0 && React.createElement("div", {
        "class": "dropdown__header text-center"
      }, this.props.no_results)))));
    }
  }], [{
    key: "renderFields",
    value: function renderFields(context) {
      $('div[data-relationship-react]:not(.react-deferred-loading)', context).each(function () {
        var props = JSON.parse(window.atob($(this).data('relationshipReact')));
        props.name = $(this).data('inputValue');
        ReactDOM.render(React.createElement(Relationship, props, null), this);
      });
      $('.react-deferred-loading--relationship', context).each(function () {
        var $wrapper = $(this);
        var $button = $wrapper.find('.js-dropdown-toggle');
        $button.on('click', function () {
          $('div[data-relationship-react]', $wrapper).each(function () {
            var props = JSON.parse(window.atob($(this).data('relationshipReact')));
            props.name = $(this).data('inputValue');
            ReactDOM.render(React.createElement(Relationship, props, null), this);
          });
        });
      });
    }
  }]);

  return Relationship;
}(React.Component);

$(document).ready(function () {
  Relationship.renderFields();
});
Grid.bind("relationship", "display", function (cell) {
  Relationship.renderFields(cell);
});
Grid.bind("member", "display", function (cell) {
  Relationship.renderFields(cell);
});
FluidField.on("relationship", "add", function (field) {
  Relationship.renderFields(field);
});
FluidField.on("member", "add", function (field) {
  Relationship.renderFields(field);
});