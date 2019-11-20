
class DropDownButton extends React.Component {
    constructor(props) {
        super(props)

        this.initialItems = SelectList.formatItems(props.items)

        this.state = {
            items: this.initialItems,
            selected: null
        }
    }

    handleSearch = (event) => {
        this.setState({
            items: this.initialItems.filter(item =>
                item.label.toLowerCase().includes(event.target.value.toLowerCase())
            )
        })
    }

    selectItem = (event, item) => {
        if (this.props.keepSelectedState) {
            this.setState({ selected: item })
        }

        this.props.onSelect(item ? item.value : null)

        $(event.target).closest('.dropdown').prev('a.button').click()

        event.preventDefault()
    }

    render() {
        return (
            <>
                <a href="#" className={"button js-dropdown-toggle " + this.props.buttonClass} onClick={this.toggle}>{this.state.selected ? this.state.selected.label : this.props.title} <i class="fas fa-caret-down icon-right"></i></a>
                <div className="dropdown">
                    {this.state.items.length > 7 &&
                        <div className="dropdown__search">
                            <form>
                                <input type="text" placeholder={this.props.placeholder} onChange={this.handleSearch} />
                            </form>
                        </div>
                    }
                    {/* {this.state.selected &&
                        <div className="filter-submenu__selected">
                            <a href="#" onClick={(e) => this.selectItem(e, null)}>{this.state.selected.label}</a>
                        </div>
                    } */}
                    <div className="dropdown__scroll">
                        {this.state.items.map(item =>
                            <a href="#" key={item.value} className={"dropdown__link " + this.props.itemClass} rel={this.props.rel} onClick={(e) => this.selectItem(e, item)}>{item.label}</a>
                        )}
                    </div>
                </div>
            </>
        )
    }
}
