import ControlPanel from '../ControlPanel'

class Statuses extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/channels';

        this.elements({
            "statuses": '[data-input-value="statuses"] .checkbox-label',
            "status_names": '[data-input-value="statuses"] .checkbox-label .status-tag',
        })
    }
    load_view_for_status_group(number) {
        cy.visit('admin.php?/cp/channels')
        cy.get('ul.list-group li:nth-child(' + number + ') a.list-item__content').first().click()
        cy.get('.js-tab-button:contains("Statuses")').click()
    }

    get_statuses_for_group(group) {
        return cy.task('db:query', 'SELECT status FROM exp_statuses LEFT JOIN exp_channels_statuses ON exp_channels_statuses.status_id=exp_statuses.status_id WHERE channel_id = ' + group + ' ORDER BY status_order ASC').then(function([rows, fields]) {
            return rows.map(function(row) {
                return row.status.toLowerCase();
            });
        })
    }
}
export default Statuses;