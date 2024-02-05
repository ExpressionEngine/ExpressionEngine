/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */


class Relationship extends React.Component {

    constructor(props) {
        super(props)
        this.initialItems = SelectList.formatItems(props.items)
        this.state = {
            selected: props.selected,
            items: props.items,
            channelFilter: false,
            filterTerm: false,
            filterValues: {}
        };
        this.ajaxFilter = (SelectList.countItems(this.initialItems) >= props.limit && props.filter_url)
        this.ajaxTimer = null
        this.ajaxRequest = null
        this.lang = typeof(props.lang) !== 'undefined' ? props.lang : EE.relationship.lang
        this.showCreateDropdown = props.channels.length > 1 ? (typeof(props.showCreateDropdown) !== 'undefined' && props.showCreateDropdown == false ? false : true) : false
    }

    static renderFields(context) {
        $('div[data-relationship-react]:not(.react-deferred-loading)', context).each(function () {
            let props = JSON.parse(window.atob($(this).data('relationshipReact')))
            props.name = $(this).data('inputValue')

            ReactDOM.render(React.createElement(Relationship, props, null), this)
        });

        $('.react-deferred-loading--relationship', context).each(function () {
            var $wrapper = $(this);
            var $button = $wrapper.find('.js-dropdown-toggle');

            $button.on('click', function () {
                $('div[data-relationship-react]', $wrapper).each(function () {
                    var props = JSON.parse(window.atob($(this).data('relationshipReact')));
                    props.name = $(this).data('inputValue');
                    ReactDOM.render(React.createElement(Relationship, props, null), this);
                });
            });
        });
	}

	componentDidMount() {
        this.bindSortable()
        EE.cp.formValidation.bindInputs(ReactDOM.findDOMNode(this).parentNode);
	}

    componentDidUpdate(prevProps, prevState) {
        if (this.state.selected !== prevState.selected) {
            // Refresh the sortable items when the selected items change
            this.bindSortable()
            EE.cp.formValidation._sendAjaxRequest($(ReactDOM.findDOMNode(this).parentNode).find('input[type=hidden]').first());
        }
    }

    selectItem(item) {
        const index = this.state.selected.findIndex((obj) => obj.value === item.value);

        // Don't add duplicate items
        if (index !== -1) {
            return
        }

        // Add the item to the selection
        this.setState({ selected: [...this.state.selected, item] })

        // Because the add field button shifts down when an item is added, we need to tell
        // the dropdown controller to update the dropdown positions so the dropdown stays under the button
        DropdownController.updateDropdownPositions()
    }

    deselect(itemId) {
        this.setState({ selected: this.state.selected.filter(function(item) {
            return item.value !== itemId
        })});
    }

    // Opens a modal to create a new entry
    openPublishFormForChannel (channel) {
        const channelTitle = channel.title
        const channelId = channel.id
        const publishCreateUrl = typeof(this.props.publishCreateUrl) !== 'undefined' ? this.props.publishCreateUrl : EE.relationship.publishCreateUrl;

        EE.cp.ModalForm.openForm({
            url: publishCreateUrl.replace('###', channelId),
            full: true,
            iframe: true,
            success: this.entryWasCreated,
            load: (modal) => {
                const entryTitle = $(this.field.closest('[data-publish]')).find('input[name=title]').val()

                let title = this.lang.creatingNew
                    .replace('#to_channel#', channelTitle)
                    .replace('#from_channel#', EE.publish.channel_title)

                if (entryTitle) {
                    title += '<b>: ' + entryTitle + '</b>'
                }

                EE.cp.ModalForm.setTitle(title)
            }
        })
    }

    // Opens a modal to edit an entry
    openPublishEditForm (id) {
        const publishEditUrl = typeof(this.props.publishEditUrl) !== 'undefined' ? this.props.publishEditUrl : EE.relationship.publishEditUrl;
        EE.cp.ModalForm.openForm({
            url: publishEditUrl.replace('###', id + '&' + $.param({ entry_ids: [id] })),
            full: true,
            iframe: true,
            dataType: 'json',
            success: this.entryWasEdited,
            load: function (modal) {}
        })
    }

    filterItems (items, searchTerm) {
        items = items.map(item => {
            // Clone item so we don't modify reference types
            item = Object.assign({}, item)

            // If any children contain the search term, we'll keep the parent
            if (item.children) item.children = this.filterItems(item.children, searchTerm)

            let itemFoundInChildren = (item.children && item.children.length > 0)
            let itemFound = String(item.label).toLowerCase().includes(searchTerm.toLowerCase());
            let itemValue = (item.value).toString().includes(searchTerm.toLowerCase());

            return (itemFound || itemFoundInChildren) || itemValue ? item : false
        })

        return items.filter(item => item);
    }

    getSelectedValues (selected) {
        let values = []
        if (selected instanceof Array) {
            values = selected.map(item => {
                return item.value
            })
        } else if (selected.value) {
            values = [selected.value]
        }
        return values.join('|')
    }

    forceAjaxRefresh (params) {
        if ( ! params) {
            params = this.state.filterValues
            params.selected = this.getSelectedValues(this.props.selected)
        }

        return $.ajax({
            method: 'POST',
            url: this.props.filter_url,
            data: $.param(params),
            dataType: 'json',
            success: (data) => {
                this.setState({ loading: false })
                this.initialItemsChanged(SelectList.formatItems(data))
            },
            error: () => {} // Defined to prevent error on .abort above
        })
    }

    // Event when a new entry was created by the channel modal
    entryWasCreated = (result, modal) => {
        let selected = this.state.selected

        if (this.props.multi) {
            selected.push(result.item)
        } else {
            selected = [result.item]
        }

        this.setState({ selected: selected, items: [...this.state.items, result.item] })

        modal.trigger('modal:close')
    }

    // Event when a entry was edited by the channel modal
    entryWasEdited = (result, modal) => {
        let selected = this.state.selected

        if (this.props.multi) {
            $.each(selected, function(i, el){
               if (el.value == result.item.value) {
                el.label = result.item.label
               }
            })
        } else {
            selected = [result.item]
        }
        this.setState({ selected: selected, items: [...this.state.items, result.item] })

        modal.trigger('modal:close')
    }

    channelFilterChange = (newValue) => {
        this.setState({ channelFilter: newValue })
    }

    handleSearch = (event) => {
        this.setState({ filterTerm: event.target.value || false })
    }

    itemsChanged = (items) => {
        this.setState({ items: items })
    }

    initialItemsChanged = (items) => {
        this.initialItems = items

        if ( ! this.ajaxFilter && this.state.filterValues.search) {
            items = this.filterItems(items, this.state.filterValues.search)
        }

        this.setState({ items: items })

        if (this.props.itemsChanged) {
            this.props.itemsChanged(items)
        }
    }

    filterChange = (name, value) => {
        let filterState = this.state.filterValues
            filterState[name] = value

        this.setState({ filterValues: filterState }) // DOM filter
        if ( ! this.ajaxFilter && name == 'search') {
            this.itemsChanged(this.filterItems(this.initialItems, value))
            return
        } // Debounce AJAX filter
        clearTimeout(this.ajaxTimer)
        if (this.ajaxRequest) this.ajaxRequest.abort()

        let params = filterState
            params.selected = this.getSelectedValues(this.props.selected)

        this.setState({ loading: true })

        this.ajaxTimer = setTimeout(() => {
            this.ajaxRequest = this.forceAjaxRefresh(params)
        }, 300)
    }

	bindSortable = () => {
		let thisRef = this

		$(this.listGroup).sortable({
			axis: 'y',
			// containment: 'parent',
			handle: '.list-item__handle',
			items: '.list-item',
			sort: (event, ui) => {
				try {
					EE.sortable_sort_helper(event, ui)
				} catch (error) {}
			},
			start: (event, ui) => {
				// Save the start index for later
				$(this).attr('data-start-index', ui.item.index());
			},
			stop: (event, ui) => {

				var newIndex = ui.item.index();
				var oldIndex = $(this).attr('data-start-index');

				// Cancel the sort so jQeury doesn't move the items
				// This needs to be done by react since it handles the dom
				$(thisRef.listGroup).sortable('cancel')

				let selected = thisRef.state.selected

				// Move the item to the new position
				selected.splice(newIndex, 0, selected.splice(oldIndex, 1)[0]);

				thisRef.setState({ selected: selected })

				$(document).trigger('entry:preview');

				$("[data-publish] > form").trigger("entry:startAutosave");
			}
		})
	}

    render() {
        let props = this.props

        // Determine what items show up in the add dropdown
        let dropdownItems = this.state.items.filter((el) => {
            let allowedChannel = true
            // Is the user filtering by channel?
            if (this.state.channelFilter) {
                allowedChannel = (el.channel_id == this.state.channelFilter)
            }

            let filterName = true

            // Is the user filtering by name
            if (this.state.filterTerm) {
                filterName = el.label.toLowerCase().includes(this.state.filterTerm.toLowerCase())
            }

            // Only show items that are not already added
            let notInSelected = (! this.state.selected.some(e => e.value === el.value))

            return notInSelected && allowedChannel && filterName
        })

        let showAddButton = ( (this.props.multi || this.state.selected.length==0) && ( this.props.rel_max == 0 || this.props.rel_max > this.state.selected.length) );

        let channelFilterItems = props.channels.map((channel) => {
            return { label: channel.title, value: channel.id}
        })

        let handleSearchItem = this.handleSearch

        return (
            <div ref={el => this.field = el}>
                {this.state.selected.length > 0 &&
                <ul className="list-group list-group--connected mb-s" ref={el => this.listGroup = el}>
                    {
                        this.state.selected.map((item) => {
                            return (
                                <li className="list-item">
									{this.state.selected.length > 1 &&
									<div class="list-item__handle"><i class="fal fa-bars"></i></div>
									}
                                    <div className="list-item__content">
                                        <div class="list-item__title">{item.label} {this.state.selected.length > 10 && <small className="meta-info ml-s float-right"> {item.instructions}</small>}</div>
                                        {this.state.selected.length <= 10 &&
                                        <div class="list-item__secondary">{props.display_entry_id && <span> #{item.value} / </span>}{item.instructions}{props.display_status && <span className="status-indicator" style={{borderColor: '#'+ EE.statuses[item.status], color: '#'+ EE.statuses[item.status]}}>{item.status}</span>}</div>
                                        }
                                    </div>
                                    <div class="list-item__content-right">
                                        <div className="button-group">
                                            {this.props.can_edit_items && item.can_edit && item.editable &&
                                            <button type="button" title={this.lang.edit} className="button button--small button--default" onClick={() => this.openPublishEditForm(item.value)}><i class="fal fa-pencil-alt"></i></button>
                                            }

                                            <button type="button" title={this.lang.remove} onClick={() => this.deselect(item.value)} className="button button--small button--default"><i class="fal fa-fw fa-trash-alt"></i></button>
                                        </div>
                                    </div>
                                </li>
                            )
                        })
                    }
                </ul>
                }

                {/* Keep an empty input when no items are selected */}
                {this.state.selected.length == 0 &&
                    <input type="hidden" name={props.multi ? props.name + '[]' : props.name} value=""/>
                }

                {this.state.selected.map((item) => {
                        return (<input type="hidden" name={props.multi ? props.name + '[]' : props.name} value={item.value}/>)
                    })
                }

                <div style={{display: showAddButton ? 'block' : 'none' }}>
                <button type="button" className="js-dropdown-toggle button button--default"><i class="fal fa-plus icon-left"></i> {props.button_label ? props.button_label : this.lang.relateEntry}</button>
                    <div className="dropdown js-dropdown-auto-focus-input">
                        <div className="dropdown__search d-flex">
                            <div className="filter-bar flex-grow">
                                <div className="filter-bar__item flex-grow">
                                    <div className="search-input">
                                        <input type="text" class="search-input__input input--small" onChange={(handleSearchItem) => this.filterChange('search', handleSearchItem.target.value)} placeholder={this.lang.search} />
                                    </div>
                                </div>
                                {props.channels.length > 1 &&
                                <div className="filter-bar__item">
                                    <DropDownButton
                                        keepSelectedState={true}
                                        title={this.lang.channel}
                                        items={channelFilterItems}
                                        onSelect={(value) => this.filterChange('channel_id', value)}
                                        buttonClass="filter-bar__button"
                                    />
                                </div>
                                }
                                {this.props.can_add_items &&
                                <div className="filter-bar__item">
                                    {!this.showCreateDropdown &&
                                    <button type="button" className="button button--primary button--small" onClick={() => this.openPublishFormForChannel(this.props.channels[0])}>{ this.props.new_entry }</button>
                                    }
                                    {this.showCreateDropdown &&
                                    <div>
                                    <button type="button" className="js-dropdown-toggle button button--primary button--small" data-dropdown-pos="bottom-end">{ this.props.new_entry } <i class="fal fa-chevron-down icon-right"></i></button>
                                    <div className="dropdown">
                                        {props.channelsForNewEntries.map((channel) => {
                                            return (
                                                <a href className="dropdown__link" onClick={() => this.openPublishFormForChannel(channel)}>{channel.title}</a>
                                            )
                                        })}
                                    </div>
                                    </div>
                                    }
                                </div>
                                }
                            </div>
                        </div>

                        <div className="dropdown__scroll dropdown__scroll--small">
                        {
                            dropdownItems.map((item) => {
                                return (
                                    <a href="" onClick={(e) => { e.preventDefault(); this.selectItem(item)}} className="dropdown__link">{item.label}{props.display_entry_id && <span class="dropdown__link-entryId"> (#{item.value})</span>}{props.display_status && <span className="dropdown__link-status-indicator" style={{borderColor: '#'+ EE.statuses[item.status], color: '#'+ EE.statuses[item.status]}}>{item.status}</span>} <span className="dropdown__link-right">{item.instructions}</span></a>
                                )
                            })
                        }
                        {dropdownItems.length == 0 &&
                            <div class="dropdown__header text-center">{ this.props.no_results }</div>
                        }
                        </div>
                    </div>
                </div>
            </div>
        );
    }
}

$(document).ready(function () {
    Relationship.renderFields();
});

Grid.bind("relationship", "display", function (cell) {
    Relationship.renderFields(cell);
});
Grid.bind("member", "display", function (cell) {
    Relationship.renderFields(cell);
});

FluidField.on("relationship", "add", function (field) {
    Relationship.renderFields(field);
});
FluidField.on("member", "add", function (field) {
    Relationship.renderFields(field);
});
