/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

class Dropdown extends React.Component {
  static defaultProps = {
    tooMany: 8
  }

  constructor (props) {
    super(props)

    this.state = {
      selected: this.getItemForSelectedValue(props.selected),
      open: false
    }
  }

  static renderFields(context) {
    $('div[data-dropdown-react]', context).each(function () {
      let props = JSON.parse(window.atob($(this).data('dropdownReact')))
      props.name = $(this).data('inputValue')

      // In the case a Dropdown has been dynamically created, allow an initial
      // value to be set other than the one in the initial config
      if ($(this).data('initialValue')) {
        props.selected = $(this).data('initialValue')
      }

      ReactDOM.render(React.createElement(FilterableDropdown, props, null), this)
    })
  }

  selectionChanged = (selected) => {
    this.setState({
      selected: selected,
      open: false
    })

    if (this.props.groupToggle) {
      EE.cp.form_group_toggle(this.input)
    }
  }

  componentDidUpdate (prevProps, prevState) {
    if (( ! prevState.selected && this.state.selected) ||
        (prevState.selected && prevState.selected.value != this.state.selected.value)
      ) {

      if (this.props.groupToggle) {
        EE.cp.form_group_toggle(this.input)
      }

      $(this.input).trigger('change')
    }
  }

  componentDidMount () {
    if (this.props.groupToggle) {
      EE.cp.form_group_toggle(this.input)
    }
  }

  toggleOpen = () => {
    this.setState((prevState, props) => ({
      open: ! prevState.open
    }))
  }

  getItemForSelectedValue (value) {
    return this.props.initialItems.find(item => {
      return String(item.value) == String(value)
    })
  }

  handleSearch(searchTerm) {
    this.props.filterChange('search', searchTerm)
  }

  render () {
    const tooMany = this.props.items.length > this.props.tooMany && ! this.state.loading
    const selected = this.state.selected

    return (
      <div className={"fields-select-drop" + (tooMany ? ' field-resizable' : '')}>
        <div className={"field-drop-selected" + (this.state.open ? ' field-open' : '')} onClick={this.toggleOpen}>
          <label className={this.state.selected ? 'act' : ''}>
            {selected &&
              <i>{selected.sectionLabel ? selected.sectionLabel + ' / ' : ''}{selected.label}</i>
            }
            { ! selected && <i>{this.props.emptyText}</i>}
            <input type="hidden"
              ref={(input) => { this.input = input }}
              name={this.props.name}
              value={this.state.selected ? this.state.selected.value : ''}
              data-group-toggle={this.props.groupToggle ? JSON.stringify(this.props.groupToggle) : '[]'}
            />
          </label>
        </div>
        <div className="field-drop-choices" style={this.state.open ? {display: 'block'} : {}}>
          {this.props.initialCount > this.props.tooMany &&
            <FieldTools>
              <FilterBar>
                <FilterSearch onSearch={(e) => this.handleSearch(e.target.value)} />
              </FilterBar>
            </FieldTools>
          }
          <div className="field-inputs">
            {this.props.items.length == 0 &&
              <NoResults text={this.props.noResults} />
            }
            {this.state.loading &&
              <Loading text={EE.lang.loading} />
            }
            {this.props.items.map((item) =>
              <DropdownItem key={item.value ? item.value : item.section}
                item={item}
                selected={this.state.selected && item.value == this.state.selected.value}
                onClick={(e) => this.selectionChanged(item)} />
            )}
          </div>
        </div>
      </div>
    )
  }
}

function DropdownItem (props) {
  var item = props.item

  if (item.section) {
    return (
      <div className="field-group-head">
        <span className="icon--folder"></span> {item.section}
      </div>
    )
  }

  return (
    <label onClick={props.onClick} className={props.selected ? 'act' : ''}>
      {item.label} {item.instructions && <i>{item.instructions}</i>}
    </label>
  )
}

$(document).ready(function () {
  Dropdown.renderFields()

  // Close when clicked elsewhere
  $(document).on('click',function(e) {
    $('.field-drop-selected.field-open')
      .not($(e.target).parents('.fields-select-drop').find('.field-drop-selected.field-open'))
      .click()
  })
})

Grid.bind('select', 'display', function(cell) {
  Dropdown.renderFields(cell)
});

FluidField.on('select', 'add', function(field) {
  Dropdown.renderFields(field)
});

const FilterableDropdown = makeFilterableComponent(Dropdown)
