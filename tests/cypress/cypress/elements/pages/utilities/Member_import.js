import ControlPanel from '../ControlPanel'

class MemberImport extends ControlPanel {
    constructor() {
        super()
       // this.url = no url given in coresponding rb file;

        this.selectors = Object.assign(this.selectors, {

		  "file_location": 'input[type!=hidden][name=xml_file]',
		  "member_group": 'input[type!=hidden][name=group_id]',
		  "language": 'input[type!=hidden][name=language]',
		  "tz_country": 'select[name=tz_country]',
		  "timezone": 'select[name=timezones]',
		  "date_format": 'input[type!=hidden][name=date_format]',
		  "time_format": 'input[type!=hidden][name=time_format]',
		  "auto_custom_field": 'input[type!=hidden][name=auto_custom_field]',
		  "auto_custom_field_toggle": '[data-toggle-for=auto_custom_field]',
		  "include_seconds": 'input[type!=hidden][name=include_seconds]',
		  "include_seconds_toggle": '[data-toggle-for=include_seconds]',
		  "table": 'table',
		  "options": 'table tr td:first-child',
		  "values": 'table tr td:nth-child(2)',
		  // Custom field creation
		  "select_all": 'input[type!=hidden][name=select_all]',
		  "custom_field_1": 'input[type!=hidden][name="create_ids[0]"]',
		  "custom_field_2": 'input[type!=hidden][name="create_ids[1]"]',
		  "custom_field_1_name": 'input[type!=hidden][name="m_field_name[0]"]',
		  "custom_field_2_name": 'input[type!=hidden][name="m_field_name[1]"]'
        })
    }

    load(){
    	this.open_dev_menu()
    	this.get('main_menu').find('a:contains("Utilities")').click()
    	this.get('wrap').find('a:contains("Member Import")').click()
    }


}

export default MemberImport;