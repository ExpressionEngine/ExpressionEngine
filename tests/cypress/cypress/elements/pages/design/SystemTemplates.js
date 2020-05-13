import ControlPanel from '../ControlPanel'

class SystemTemplates extends ControlPanel {
    constructor() {
            super()
            this.url = 'admin.php?/cp/design';

            this.elements({
                "header": 'form h1',
                "theme_chooser": 'select[name="theme"]',
                "templates": 'table tbody tr',
                /*
            sections :templates, 'table tbody tr' do
    element :name, 'td:first-child'
    section :manage, 'td:nth-child(2)' do
      element :edit, '.edit a'
  */
                "form.codemirror": '.CodeMirror',
                "form.template_contents": 'textarea[name="template_data"]',
                "form.save_button": 'button[type="submit"][value="update"]',
                "form.save_and_finish_button": 'button[type="submit"][value="finish"]',
            })
        }
        /*
        load(group = 'system') {
            visit '/admin.php?/cp/addons'
            find('ul.toolbar a[data-post-url*="cp/addons/install/forum"]').click

            this.open_dev_menu()
            click_link 'Templates'
            find('.edit a[href*="cp/design/' + group + '"]').click
        }*/
}
export default SystemTemplates;