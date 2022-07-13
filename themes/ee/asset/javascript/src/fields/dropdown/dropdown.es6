/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

class Dropdown extends React.Component {
  static defaultProps = {
    tooMany: 8
  }

  constructor (props) {
    super(props)

    window.selectedEl;
    var selected;

    // use different function for file manager part and other site pages
    if(props.fileManager) {
      selected = this.checkChildDirectory(this.props.initialItems, props.selected);
    } else {
      selected = this.getItemForSelectedValue(props.selected)
    }
    this.state = {
      selected: selected,
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

      if (window.selectedFolder) {
        props.selected = window.selectedFolder;
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

    if (this.props.conditionalRule == 'rule') {
      EE.cp.show_hide_rule_operator_field(selected, this.input);
    }

    if (this.props.conditionalRule == 'operator') {
      EE.cp.check_operator_value(selected, this.input);
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

  checkChildDirectory = (items, value) => {
    items.map(item => {
      if (item.value == value) {
        return window.selectedEl = item;
      } else if(item.value != value && (Array.isArray(item.children) && item.children.length)) {
        this.checkChildDirectory(item.children, value);
      }
    })

    return window.selectedEl;
  }

  handleSearch(searchTerm) {
    this.props.filterChange('search', searchTerm)
  }

  selectRecursion = (items) => {
    return (
      <React.Fragment>
      {items.map(item => (
        <div className="select__dropdown-item-parent">
          <DropdownItem key={item.value ? item.value : item.section}
            item={item}
            selected={this.state.selected && item.value == this.state.selected.value}
            onClick={(e) => this.selectionChanged(item)}
            name ={this.props.name} />
          {item.children && item.children.length ? this.selectRecursion(item.children) : null}
        </div>
      ))}
      </React.Fragment>
    )
  }

  render () {
    const tooMany = this.props.items.length > this.props.tooMany && ! this.state.loading
    let selected;

    if (window.selectedFolder) {
       selected = this.checkChildDirectory(this.props.initialItems, window.selectedFolder);
       this.state.selected = selected;
    } else {
      selected = this.state.selected;
    }

    return (
      <div className={"select button-segment" + (tooMany ? ' select--resizable' : '') + (this.state.open ? ' select--open' : '')}>
        <div className={"select__button js-dropdown-toggle"} onClick={this.toggleOpen} tabIndex="0">
          <label className={'select__button-label' + (this.state.selected ? ' act' : '')}>
            {selected &&
              <span>{selected.sectionLabel ? selected.sectionLabel + ' / ' : ''}
                <span dangerouslySetInnerHTML={{__html: selected.label}}></span>
                {this.props.name == 'condition-rule-field' && <span className="short-name">{`{${selected.value}}`}</span>}
              </span>
            }
            { ! selected && <i>{this.props.emptyText}</i>}
            <input type="hidden"
              ref={(input) => { this.input = input }}
              name={this.props.name}
              value={this.state.selected ? this.state.selected.value : ''}
              data-group-toggle={this.props.groupToggle ? JSON.stringify(this.props.groupToggle) : '[]'}
            />
          </label>

          {selected && this.props.name.includes('[condition_field_id]') && 
            <span className="tooltiptext">
              {`${selected.label.replace(/<.*/g, "")} ${selected.label.match(/(?:\{).+?(?:\})/g)}`}
            </span>
          }
        </div>

        <div className="select__dropdown dropdown">
          {this.props.initialCount > this.props.tooMany &&
            <div className="select__dropdown-search">
            <FieldTools>
              <FilterBar>
                <FilterSearch onSearch={(e) => this.handleSearch(e.target.value)} />
              </FilterBar>
            </FieldTools>
            </div>
          }
          <div className="select__dropdown-items">
            {this.props.items.length == 0 &&
              <NoResults text={this.props.noResults} />
            }
            {this.state.loading &&
              <Loading text={EE.lang.loading} />
            }

            {this.selectRecursion(this.props.items)}
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
      <div className="select__dropdown-item select__dropdown-item--head">
        <span className="icon--folder"></span> {item.section}
      </div>
    )
  }
  return (
    <div onClick={props.onClick} className={'select__dropdown-item' + (props.selected ? ' select__dropdown-item--selected' : '')} tabIndex="0">
      <span dangerouslySetInnerHTML={{__html: item.label}}></span>{item.instructions && <i>{item.instructions}</i>}
      {props.name == 'condition-rule-field' && <span className="short-name">{`{${item.value}}`}</span>}
    </div>
  )
}


$(document).ready(function () {
  Dropdown.renderFields()

  // Close when clicked elsewhere
  $(document).on('click',function(e) {
    $('.select.select--open')
        .not($(e.target).closest('.select'))
        .find('.select__button')
        .click();
  })
})

Grid.bind('select', 'display', function(cell) {
  Dropdown.renderFields(cell)
});

FluidField.on('select', 'add', function(field) {
  Dropdown.renderFields(field)
});

const FilterableDropdown = makeFilterableComponent(Dropdown)
