/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

function FieldTools (props) {
  return (
    <div className="field-tools">
      {props.children}
    </div>
  )
}

function FilterBar (props) {
  return (
    <div className="filter-bar">
      {props.children}
    </div>
  )
}

class FilterToggleAll extends React.Component {
  constructor (props) {
    super(props)

    this.state = {
      checked: false
    }
  }

  handleClick = () => {
    // Clear all will always be "unchecked" to the parent
    if ( ! this.props.checkAll) {
      this.props.onToggleAll(false)
      return
    }

    let checked = ! this.state.checked
    this.setState({
      checked: checked
    })
    this.props.onToggleAll(checked)
  }

  render () {
    return (
      <div className="field-ctrl">
        <label className={
            (this.props.checkAll ? "field-toggle-all" : "field-clear-all")
            + (this.state.checked ? " act" : "")
          }
          onClick={this.handleClick}>
          {this.props.checkAll ? EE.lang.check_all : EE.lang.clear_all}
        </label>
      </div>
    )
  }
}

function FilterSearch (props) {
  return (
    <div className="filter-item filter-item__search">
      <input type="text" placeholder={EE.lang.keyword_search} onChange={props.onSearch} />
    </div>
  )
}

class FilterSelect extends React.Component {
  constructor (props) {
    super(props)

    this.initialItems = SelectList.formatItems(props.items)
    this.state = {
      items: this.initialItems,
      selected: null
    }
  }

  handleSearch = (event) => {
    this.setState({ items: this.initialItems.filter(item =>
      item.label.toLowerCase().includes(event.target.value.toLowerCase())
    )})
  }

  selectItem = (event, item) => {
    if (this.props.keepSelectedState) {
      this.setState({ selected: item })
    }
    this.props.onSelect(item ? item.value : null)
    $(event.target).closest('.filter-item').find('.js-filter-link').click()
    event.preventDefault()
  }

  render () {
    return (
      <div className={"filter-item" + (this.props.center ? ' filter-item--center' : '')}>
        <a href="#" className={"js-filter-link filter-item__link filter-item__link--has-submenu" + (this.props.action ? ' filter-item__link--action' : '')} onClick={this.toggle}>{this.props.title}</a>
        <div className="filter-submenu">
          {this.state.items.length > 7 &&
            <div className="filter-submenu__search">
              <form>
                <input type="text" placeholder={this.props.placeholder} onChange={this.handleSearch} />
              </form>
            </div>
          }
          {this.state.selected &&
            <div className="filter-submenu__selected">
              <a href="#" onClick={(e) => this.selectItem(e, null)}>{this.state.selected.label}</a>
            </div>
          }
          <div className="filter-submenu__scroll">
            {this.state.items.map(item =>
              <a href="#" key={item.value} className={"filter-submenu__link filter-submenu__link---active " + this.props.itemClass} rel={this.props.rel} onClick={(e) => this.selectItem(e, item)}>{item.label}</a>
            )}
          </div>
        </div>
      </div>
    )
  }
}
