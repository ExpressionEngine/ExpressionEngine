import ControlPanel from '../ControlPanel'

class ChannelMangerPage extends ControlPanel {
    constructor() {
        super()
        //this.url = 'NA';

        this.selectors = Object.assign(this.selectors, {
			  // Title/header box elements
			  "manager_title" : 'div.box.full.mb form h1',
			  "title_toolbar" : 'div.box.full.mb form h1 ul.toolbar',
			  "channel_manager_settings" : 'div.box.full.mb form h1 ul.toolbar li.settings',
			  "phrase_search" : 'fieldset.tbl-search input[type!=hidden][name=search]',
			  "search_submit_button" : 'fieldset.tbl-search input.submit',
			  // Sidebar elements
			  "sidebar_channels" : 'div.sidebar h2:first-child a:first-child',
			  "sidebar_new_channels_button" : 'div.sidebar h2:first-child a.button.action',
			  "sidebar_custom_fields" : 'div.sidebar h2:nth-child(2) a:first-child',
			  "sidebar_new_custom_fileds_button" : 'div.sidebar h2:nth-child(2) a.button.action',
			  "sidebar_field_groups" : 'div.sidebar ul li:first-child a',
			  "sidebar_category_groups" : 'div.sidebar h2:nth-child(4) a:first-child',
			  "sidebar_new_category_groups_button" : 'div.sidebar h2:nth-child(4) a.button.action',
			  "sidebar_status_grouops" : 'div.sidebar h2:nth-child(5) a:first-child',
			  "sidebar_new_status_grouops_button" : 'div.sidebar h2:nth-child(5) a.button.action',
			  // Main box elements
			  "heading" : 'div.col.w-12 div.box .tbl-ctrls h1'
        })

     }



 }

 export default ChannelMangerPage;