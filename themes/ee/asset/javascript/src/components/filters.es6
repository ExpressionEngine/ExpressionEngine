/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
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
    if (!this.props.checkAll) {
      this.props.onToggleAll(false)
      return
    }

    let checked = !this.state.checked
    this.setState({ checked: checked })
    this.props.onToggleAll(checked)
  }

  handleInputChange = (event) => {
    this.handleClick()
  }

  render () {
    return (
        <label className={(this.props.checkAll ? "ctrl-all" : "ctrl-all") + (this.state.checked ? " act" : "")}>
            <span>{this.props.checkAll ? EE.lang.check_all : EE.lang.clear_all}</span>
            <input onChange={this.handleInputChange} value={this.state.checked} type="checkbox" class="checkbox--small" />
        </label>
    )
  }
}

function FilterSearch (props) {
  return (
    <div className="filter-bar__item">
      <div className="search-input">
        <input type="text" className="search-input__input input--small" placeholder={EE.lang.keyword_search} onChange={props.onSearch} />
      </div>
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
    $(event.target).closest('.filter-bar__item').find('.js-dropdown-toggle').click()
    event.preventDefault()
  }

  render () {
    return (
      <div className="filter-bar__item">
        <a href="#" className={"js-dropdown-toggle filter-bar__button has-sub" + (this.props.action ? ' filter-item__link--action' : '')} onClick={this.toggle}>{this.props.title}</a>
      <div className="dropdown">
          {this.state.items.length > 7 &&
            <div className="dropdown__search">
              <form>
                <div className="search-input">
                  <input className="search-input__input input--small" type="text" placeholder={this.props.placeholder} onChange={this.handleSearch} />
                </div>
              </form>
            </div>
          }
          {this.state.selected && <>
              <a href="#" className="dropdown__link dropdown__link--selected" onClick={(e) => this.selectItem(e, null)}>{this.state.selected.label}</a>
              <div className="dropdown__divider"></div>
          </> }
          <div className="dropdown__scroll">
            {this.state.items.map(item =>
              <a href="#" key={item.value} className={"dropdown__link " + this.props.itemClass} rel={this.props.rel} onClick={(e) => this.selectItem(e, item)}>{item.label}</a>
            )}
          </div>
        </div>
      </div>
    )
  }
}
