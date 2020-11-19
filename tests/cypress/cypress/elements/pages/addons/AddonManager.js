import ControlPanel from '../ControlPanel'

class AddonManager extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/addons';
        let first_party_prefix = 'body .ee-main__content .tab-open ';
        let third_party_prefix = 'body section.wrap div.col-group + div.col-group div.col.w-16 div.box:nth-child(2) ';
        this.selectors = Object.assign(this.selectors, {
            "title": '.main-nav__title',

            "uninstalled_addons": first_party_prefix + '.add-on-card--uninstalled',
            "addons": first_party_prefix + '.add-on-card',

            // First Party Add- Ons
            "first_party_section": first_party_prefix,
            "first_party_heading": first_party_prefix + 'form h1',
            "first_party_status_filter": first_party_prefix + 'div.filters ul li:first-child',
            "first_party_status_filter_menu": first_party_prefix + 'div.filters ul li:first-child div.sub-menu ul',
            "first_party_no_results": first_party_prefix + 'tr.no-results',
            "first_party_addons": first_party_prefix + ' .add-on-card-list',
            "first_party_addon_name_header": first_party_prefix + 'form div.tbl-wrap table thead tr th:first-child',
            "first_party_version_header": first_party_prefix + 'form div.tbl-wrap table thead tr th:nth-child(2)',
            "first_party_manage_header": first_party_prefix + 'form div.tbl-wrap table thead tr th:nth-child(3)',
            "first_party_checkbox_header": first_party_prefix + 'form div.tbl-wrap table thead tr th:nth-child(4)',
            "first_party_addon_names": first_party_prefix + 'form div.table-responsive table tbody tr td:first-child',
            "first_party_versions": first_party_prefix + 'form div.table-responsive table tbody tr td:nth-child(2)',
            "first_party_pagination": first_party_prefix + 'div.paginate',
            "first_party_pages": first_party_prefix + 'div.paginate ul li a',
            "first_party_bulk_action": first_party_prefix + ' select[name="bulk_action"]',
            "first_party_action_submit_button": first_party_prefix + 'form fieldset.bulk-action-bar button',

            // Third Party Add - Ons
            "third_party_section": third_party_prefix,
            "third_party_heading": third_party_prefix + 'form h1',
            "third_party_status_filter": third_party_prefix + 'div.filters ul li:first-child',
            "third_party_status_filter_menu": third_party_prefix + 'div.filters ul li:first-child div.sub-menu ul',
            "third_party_developer_filter": third_party_prefix + 'div.filters ul li:nth-child(2)',
            "third_party_developer_filter_menu": third_party_prefix + 'div.filters ul li:nth-child(2) div.sub-menu ul',
            "third_party_no_results": third_party_prefix + 'tr.no-results',
            "third_party_addons": third_party_prefix + 'form div.table-responsive table tbody tr',
            "third_party_addon_name_header": third_party_prefix + 'form div.tbl-wrap table thead tr th:first-child',
            "third_party_version_header": third_party_prefix + 'form div.tbl-wrap table thead tr th:nth-child(2)',
            "third_party_manage_header": third_party_prefix + 'form div.tbl-wrap table thead tr th:nth-child(3)',
            "third_party_checkbox_header": third_party_prefix + 'form div.tbl-wrap table thead tr th:nth-child(4)',
            "third_party_addon_names": third_party_prefix + 'form div.table-responsive table tbody tr td:first-child',
            "third_party_versions": third_party_prefix + 'form div.table-responsive table tbody tr td:nth-child(2)',
            "third_party_pagination": third_party_prefix + 'div.paginate',
            "third_party_pages": third_party_prefix + 'div.paginate ul li a',
            "third_party_bulk_action": third_party_prefix + 'form fieldset.bulk-action-bar select[name="bulk_action"]',
            "third_party_action_submit_button": third_party_prefix + 'form fieldset.bulk-action-bar button',
        })
    }

    installThirdPartyAddons() {
        this.get('third_party_checkbox_header').find('input[type="checkbox"]').check()
        this.get('third_party_bulk_action').select('Install')
        this.get('third_party_action_submit_button').click()
        cy.hasNoErrors()
    }
}

export default AddonManager;