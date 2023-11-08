class DropDownButton extends React.Component {
    constructor(props) {
        super(props)

        this.initialItems = SelectList.formatItems(props.items)

        this.state = {
            items: this.initialItems,
            selected: null,
            search: false,
        }
    }

    handleSearch = (event) => {
        this.setState({
            items: this.initialItems.filter(item =>
                item.label.toLowerCase().includes(event.target.value.toLowerCase())
            )
        })

        this.setState({
            search: true
        })
    }

    selectItem = (event, item) => {
        if (this.props.keepSelectedState) {
            this.setState({ selected: item })
        }

        this.props.onSelect(item ? item.value : null)

        let dropdown = this.dropdown

        if (dropdown) {
            DropdownController.hideDropdown(dropdown, $(dropdown).prev('.js-dropdown-toggle')[0])
        }

        event.preventDefault()
    }

    dropdownRecursion = (items) => {
        return (
            <React.Fragment>
            <ul>
            {items.map(item => (

                <li>
                    <a href="#" key={item.value} className={"dropdown__link " + this.props.itemClass} rel={this.props.rel} onClick={(e) => this.selectItem(e, item)}>
                    {item.path.trim() == "" ? <i class="fal fa-hdd"></i> : <i class="fal fa-folder"></i>}
                    {item.label}
                    </a>
                    {item.children && !this.props.ignoreChild && item.children.length ? this.dropdownRecursion(item.children) : null}
                </li>
            ))}
            </ul>
            </React.Fragment>
        )
    }

    render() {
        let dropdownItems = this.state.items.filter(el => el != this.state.selected);

        return (
            <>
                <button type="button" className={"button js-dropdown-toggle has-sub " + this.props.buttonClass} onClick={this.toggle}>{this.state.selected ? this.state.selected.label : this.props.title}</button>
                <div ref={(el) => this.dropdown = el} className="dropdown">
                    {(this.state.items.length > 7 || this.state.search) &&
                        <div className="dropdown__search">
                            <form>
                                <div className="search-input">
                                    <input className="search-input__input" type="text" placeholder={this.props.placeholder} onChange={this.handleSearch} />
                                </div>
                            </form>
                        </div>
                    }
                    {this.state.selected && <>
                        <a href="#" className="dropdown__link dropdown__link--selected" onClick={(e) => this.selectItem(e, null)}>{this.state.selected.label}</a>

                        {dropdownItems.length > 0 &&
                        <div className="dropdown__divider"></div>
                        }
                    </> }
                    <div className="dropdown__scroll">
                        {this.props.addInput &&
                            <label htmlFor="f_open-filepicker_id" className="sr-only">{EE.lang.hidden_input}</label> && <input id="f_open-filepicker_id" type="file" className="f_open-filepicker" style={{display: 'none'}} data-upload_location_id={''} data-path={''} />
                        }
                        {this.dropdownRecursion(dropdownItems)}
                    </div>
                    {this.props.createNewDirectory &&
                        <p className="create_new_direction">
                            <a href="#" rel="add_new" className='js-modal-link--side submit'><i className="fal fa-plus icon-left"></i> {EE.lang.file_dnd_create_directory}</a>
                        </p>
                    }
                </div>
            </>
        )
    }
}
