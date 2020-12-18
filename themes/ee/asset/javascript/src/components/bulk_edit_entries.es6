/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
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

  handleRemoveAll () {
    this.props.itemsChanged([])
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
        <div className="title-bar">
            <h2 className="title-bar__title">{totalItems} {lang.selectedEntries}</h2>
        </div>
        <form className="add-mrg-top">
          <input type="text" placeholder={lang.filterSelectedEntries} onChange={(e) => this.handleSearch(e.target.value)} />
        </form>
        <ul className="list-group add-mrg-top">
          {limitedItems.length == 0 &&
            <li>
                <div className="no-results" dangerouslySetInnerHTML={{__html: lang.noEntriesFound}}></div>
            </li>
          }
          {limitedItems.map((item) =>
            <BulkEditEntryItem
              item={item}
              handleRemove={(item) => this.handleRemove(item)}
              lang={lang}
            />
          )}
        </ul>
        <div className="meta-info">
          {lang.showing} {limitedItems.length} {lang.of} {totalItems} &mdash; <a href className="danger-link" onClick={(e) => this.handleRemoveAll()}><i className="fas fa-sm fa-times"></i> {lang.clearAll}</a>
        </div>
      </div>
    )
  }
}

function BulkEditEntryItem (props) {
  return (
    <li className="list-item">
        <div className="list-item__content">
            <div>{props.item.label}</div>
            <div className="list-item__secondary">
                <a href="#" className="danger-link" onClick={(e) => props.handleRemove(props.item)}><i className="fas fa-sm fa-times"></i> {props.lang.removeFromSelection}</a>
            </div>
        </div>
    </li>
  )
}

const FilterableBulkEditEntries = makeFilterableComponent(BulkEditEntries)
