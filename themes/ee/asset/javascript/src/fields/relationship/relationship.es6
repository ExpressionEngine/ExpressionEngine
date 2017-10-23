/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

const FilterableSelectList = makeFilterableComponent(SelectList)

class Relationship extends React.Component {
  constructor (props) {
    super(props)

    this.state = {
      selected: SelectList.formatItems(props.selected)
    }

    this.state.selectedVisible = this.state.selected
  }

  static renderFields(context) {
    $('div[data-relationship-react]', context).each(function () {
      let props = JSON.parse(window.atob($(this).data('relationshipReact')))
      props.name = $(this).data('inputValue')
      ReactDOM.render(React.createElement(Relationship, props, null), this)
    })
  }

  // Items visible in the selection container changed via filtering
  selectedItemsChanged = (selectedItems) => {
    this.setState({
      selectedVisible: selectedItems
    })
  }

  selectionChanged = (selected) => {
    this.setState({
      selected: selected,
      selectedVisible: selected
    })
  }

  handleRemove = (event, item) => {
    this.selectionChanged(
      this.state.selected.filter((thisItem) => {
        return thisItem.value != item.value
      })
    )
    event.preventDefault()
  }

  render () {
    // Force the selected pane to re-render because we need to pass in new
    // items as props which the filterable component doesn't expect...
    const SelectedFilterableSelectList = makeFilterableComponent(SelectList)

    return (
      <div className={"fields-relate" + (this.props.multi ? ' fields-relate-multi' : '')}>
        <FilterableSelectList
          items={this.props.items}
          name={this.props.name}
          limit={this.props.limit}
          multi={this.props.multi}
          selected={this.state.selected}
          selectionChanged={this.selectionChanged}
          noResults={this.props.no_results}
          filterable={true}
          tooMany={true}
          filters={this.props.select_filters}
          filterUrl={this.props.filter_url}
          toggleAll={this.props.multi && this.props.items.length > SelectList.defaultProps.tooMany ? true : null}
        />

        {this.props.multi &&
          <SelectedFilterableSelectList
            items={this.state.selectedVisible}
            selected={[]}
            filterable={true}
            tooMany={true}
            selectable={false}
            reorderable={true}
            removable={true}
            handleRemove={(e, item) => this.handleRemove(e, item)}
            itemsChanged={this.selectionChanged}
            noResults={this.props.no_related}
            toggleAll={this.props.items.length > SelectList.defaultProps.tooMany ? false : null}
          />
        }
      </div>
    )
  }
}

$(document).ready(function () {
  Relationship.renderFields()
})

Grid.bind('relationship', 'display', function(cell) {
  Relationship.renderFields(cell)
});

FluidField.on('relationship', 'add', function(field) {
  Relationship.renderFields(field)
});
