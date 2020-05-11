import ControlPanel from '../ControlPanel'

class Statuses extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/channels';

        this.elements({
            "statuses": 'table tbody tr',
            "status_names": 'table tr td:nth-child(3)',
        })
    }
    load_view_for_status_group(number) {
        // self.open_dev_menu
        cy.contains('Channels').click()
        cy.contains('Status Groups').click()

        cy.get('tbody tr:nth-child(' + number + ') li.txt-only a').click()
    }

    get_statuses_for_group(group) {
        return cy.task('db:query', 'SELECT status FROM exp_statuses WHERE group_id = ' + group + ' ORDER BY status_order ASC').then(function([rows, fields]) {
            return rows[0];
        })
    }
}
export default Statuses;