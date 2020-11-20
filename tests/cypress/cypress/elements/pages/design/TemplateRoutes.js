import ControlPanel from '../ControlPanel'

class TemplateRoutes extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/design/routes';

        this.elements({
            "routes": 'table tbody tr.setting-field',
            // "reorder": 'td:first-child .reorder',
            // "template": 'td:nth-child(2)',
            // "template_choice": 'div[data-input-value*="routes[rows]"]',
            // "template_choices": 'div[data-input-value*="routes[rows]"] .field-drop-choices label',
            // "template_choice": 'td:nth-child(2) div[data-input-value*="routes[rows]"] input[type="radio"]',
            // "group": 'td:nth-child(3)',
            // "route": 'td:nth-child(4) input',
            // "segments_required": 'td:nth-child(5) a[data-toggle-for=required]',
            // "delete": 'td:nth-child(6) a[rel=remove_row]',

            "no_results": 'tr.no-results',

            "new_route_button": 'table tr.tbl-action td a.btn.action',
            "update_button": 'fieldset.tbl-bulk-act input[type=submit]',


        })
    }

    add_route(options = {}) {
        let defaults = {
            template: '1',
            route: 'foo/bar',
            segments_required: false
        }

        options = Object.assign(defaults, options)

        this.get('new_route_button').click()
        let route = this.$('routes').eq(-1)
        this.get('routes').eq(-1).find('div[data-input-value*="routes[rows]"] .field-drop-selected').click()
        this.get('routes').eq(-1).find('div[data-input-value*="routes[rows]"] .field-drop-choices label').contains(options.template).click({ force: true })
        this.get('routes').eq(-1).find('td:nth-child(4) input').type(options.route)

        if (options.segments_required) {
            if (route.find('td:nth-child(5) a[data-toggle-for=required]').hasClass('off')) {
                route.find('td:nth-child(5) a[data-toggle-for=required]').click();
            }
        } else {
            if (route.find('td:nth-child(5) a[data-toggle-for=required]').hasClass('on')) {
                route.find('td:nth-child(5) a[data-toggle-for=required]').click();
            }
        }
    }
}
export default TemplateRoutes;