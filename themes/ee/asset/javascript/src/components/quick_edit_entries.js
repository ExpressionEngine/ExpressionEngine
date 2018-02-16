'use strict';

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

var QuickEditEntries = function (_React$Component) {
  _inherits(QuickEditEntries, _React$Component);

  function QuickEditEntries() {
    _classCallCheck(this, QuickEditEntries);

    return _possibleConstructorReturn(this, (QuickEditEntries.__proto__ || Object.getPrototypeOf(QuickEditEntries)).apply(this, arguments));
  }

  _createClass(QuickEditEntries, [{
    key: 'componentDidUpdate',
    value: function componentDidUpdate(prevProps, prevState) {
      if (prevProps.initialItems.length != this.props.initialItems.length) {
        this.props.entriesChanged(this.props.initialItems);
      }
    }
  }, {
    key: 'handleRemove',
    value: function handleRemove(item) {
      this.props.itemsChanged(this.props.initialItems.filter(function (thisItem) {
        return thisItem.value != item.value;
      }));
    }
  }, {
    key: 'handleSearch',
    value: function handleSearch(searchTerm) {
      this.props.filterChange('search', searchTerm);
    }
  }, {
    key: 'render',
    value: function render() {
      var _this2 = this;

      var limitedItems = this.props.items.slice(0, this.props.limit);
      var totalItems = this.props.initialItems.length;
      var lang = this.props.lang;

      return React.createElement(
        'div',
        null,
        React.createElement(
          'h2',
          null,
          totalItems,
          ' ',
          lang.selectedEntries
        ),
        React.createElement(
          'form',
          { 'class': 'field-search add-mrg-top' },
          React.createElement('input', { type: 'text', placeholder: lang.filterSelectedEntries, onChange: function onChange(e) {
              return _this2.handleSearch(e.target.value);
            } })
        ),
        React.createElement(
          'ul',
          { 'class': 'entry-list' },
          limitedItems.length == 0 && React.createElement('li', { 'class': 'entry-list__item entry-list__item---empty', dangerouslySetInnerHTML: { __html: lang.noEntriesFound } }),
          limitedItems.map(function (item) {
            return React.createElement(QuickEditEntryItem, {
              item: item,
              handleRemove: function handleRemove(item) {
                return _this2.handleRemove(item);
              },
              lang: lang
            });
          })
        ),
        React.createElement(
          'div',
          { 'class': 'entry-list__note' },
          lang.showing,
          ' ',
          limitedItems.length,
          ' ',
          lang.of,
          ' ',
          totalItems,
          ' \u2014 ',
          React.createElement(
            'a',
            { href: '' },
            React.createElement('span', { 'class': 'icon--remove' }),
            lang.clearAll
          )
        )
      );
    }
  }], [{
    key: 'render',
    value: function render(context, props) {
      $('div[data-quick-edit-entries-react]', context).each(function () {
        ReactDOM.unmountComponentAtNode(this);
        ReactDOM.render(React.createElement(FilterableQuickEditEntries, props, null), this);
      });
    }
  }]);

  return QuickEditEntries;
}(React.Component);

QuickEditEntries.defaultProps = {
  items: [],
  limit: 50
};


function QuickEditEntryItem(props) {
  return React.createElement(
    'li',
    { 'class': 'entry-list__item' },
    React.createElement(
      'h2',
      null,
      props.item.label
    ),
    React.createElement(
      'a',
      { href: '#', onClick: function onClick(e) {
          return props.handleRemove(props.item);
        } },
      React.createElement('span', { 'class': 'icon--remove' }),
      props.lang.removeFromSelection
    )
  );
}

var FilterableQuickEditEntries = makeFilterableComponent(QuickEditEntries);