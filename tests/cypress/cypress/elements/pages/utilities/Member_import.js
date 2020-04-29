import ControlPanel from '../ControlPanel'

class MemberImport extends ControlPanel {
    constructor() {
        super()
       // this.url = no url given in coresponding rb file;

        this.selectors = Object.assign(this.selectors, {

		  "file_location": 'input[name=xml_file]',
		  "member_group": 'input[name=group_id]',
		  "language": 'input[name=language]',
		  "tz_country": 'select[name=tz_country]',
		  "timezone": 'select[name=timezones]',
		  "date_format": 'input[name=date_format]',
		  "time_format": 'input[name=time_format]',
		  "auto_custom_field": 'input[name=auto_custom_field]',
		  "auto_custom_field_toggle": 'a[data-toggle-for=auto_custom_field]',
		  "include_seconds": 'input[name=include_seconds]',
		  "include_seconds_toggle": 'a[data-toggle-for=include_seconds]',
		  "table": 'table',
		  "options": 'table tr td:first-child',
		  "values": 'table tr td:nth-child(2)',
		  // Custom field creation
		  "select_all": 'input[name=select_all]',
		  "custom_field_1": 'input[name="create_ids[0]"]',
		  "custom_field_2": 'input[name="create_ids[1]"]',
		  "custom_field_1_name": 'input[name="m_field_name[0]"]',
		  "custom_field_2_name": 'input[name="m_field_name[1]"]'
        })
    }

    load(){
    	this.open_dev_menu()
    	this.get('main_menu').find('a:contains("Utilities")').click()
    	this.get('wrap').find('a:contains("Member Import")').click()
    }


}

export default MemberImport;