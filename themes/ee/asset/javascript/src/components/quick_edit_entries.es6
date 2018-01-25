/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

class QuickEditEntries extends React.Component {
  static defaultProps = {
    items: [],
    limit: 50
  }

  static render (context, props) {
    $('div[data-quick-edit-entries-react]', context).each(function () {
      ReactDOM.unmountComponentAtNode(this)
      ReactDOM.render(React.createElement(FilterableQuickEditEntries, props, null), this)
    })
  }

  componentDidUpdate (prevProps, prevState) {
    if (prevProps.initialItems.length != this.props.initialItems.length) {
      this.props.entriesChanged(this.props.initialItems)
    }
  }

  handleRemove (item) {
    this.props.itemsChanged(
      this.props.items.filter((thisItem) => {
        return thisItem.value != item.value
      })
    )
  }

  handleSearch (searchTerm) {
    this.props.filterChange('search', searchTerm)
  }

  render () {
    const limitedItems = this.props.items.slice(0, this.props.limit)
    const totalItems = this.props.initialItems.length

    return (
      <div>
        <h2>{totalItems} Selected Entries</h2>
        <form class="field-search add-mrg-top">
          <input type="text" placeholder="Filter selected entries" onChange={(e) => this.handleSearch(e.target.value)} />
        </form>
        <ul class="entry-list">
          {limitedItems.map((item) =>
            <QuickEditEntryItem
              item={item}
              handleRemove={(item) => this.handleRemove(item)}
            />
          )}
        </ul>
        <div class="entry-list__note">Showing {limitedItems.length} of {totalItems} &mdash; <a href=""><span class="icon--remove"></span>Clear All</a></div>
      </div>
    )
  }
}

function QuickEditEntryItem (props) {
  return (
    <li class="entry-list__item">
      <h2>{props.item.label}</h2>
      <a href="#" onClick={(e) => props.handleRemove(props.item)}><span class="icon--remove"></span>Remove from selection</a>
    </li>
  )
}

const FilterableQuickEditEntries = makeFilterableComponent(QuickEditEntries)
