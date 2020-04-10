/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

class BulkEditEntries extends React.Component {
  static defaultProps = {
    items: [],
    limit: 50
  }

  static render (context, props) {
    $('div[data-bulk-edit-entries-react]', context).each(function () {
      ReactDOM.unmountComponentAtNode(this)
      ReactDOM.render(React.createElement(FilterableBulkEditEntries, props, null), this)
    })
  }

  componentDidUpdate (prevProps, prevState) {
    if (prevProps.initialItems.length != this.props.initialItems.length) {
      this.props.entriesChanged(this.props.initialItems)
    }
  }

  handleRemove (item) {
    this.props.itemsChanged(
      this.props.initialItems.filter((thisItem) => {
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
    const lang = this.props.lang

    return (
      <div>
        <h2>{totalItems} {lang.selectedEntries}</h2>
        <form class="field-search add-mrg-top">
          <input type="text" placeholder={lang.filterSelectedEntries} onChange={(e) => this.handleSearch(e.target.value)} />
        </form>
        <ul class="entry-list">
          {limitedItems.length == 0 &&
            <li class="entry-list__item entry-list__item---empty" dangerouslySetInnerHTML={{__html: lang.noEntriesFound}} />
          }
          {limitedItems.map((item) =>
            <BulkEditEntryItem
              item={item}
              handleRemove={(item) => this.handleRemove(item)}
              lang={lang}
            />
          )}
        </ul>
        <div class="entry-list__note">{lang.showing} {limitedItems.length} {lang.of} {totalItems} &mdash; <a href=""><span class="icon--remove"></span>{lang.clearAll}</a></div>
      </div>
    )
  }
}

function BulkEditEntryItem (props) {
  return (
    <li class="entry-list__item">
      <h2>{props.item.label}</h2>
      <a href="#" onClick={(e) => props.handleRemove(props.item)}><span class="icon--remove"></span>{props.lang.removeFromSelection}</a>
    </li>
  )
}

const FilterableBulkEditEntries = makeFilterableComponent(BulkEditEntries)
