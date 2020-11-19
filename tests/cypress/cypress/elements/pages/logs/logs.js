import ControlPanel from '../ControlPanel'

class Logs extends ControlPanel {
    constructor() {
        super()

        this.elements({
              "heading" : 'div.w-12 div.box div.tbl-ctrls h1',
              "username_filter": 'div.filter-bar [data-filter-label^="username"]',
              "username_filter_menu": 'div.filter-bar [data-filter-label^="username"] + div.sub-menu ul',
              "username_manual_filter": 'input[type!=hidden][name="filter_by_username"]',
              "date_filter": 'div.filter-bar [data-filter-label^="date"]',
              "date_filter_menu": 'div.filter-bar [data-filter-label^="date"] + div.sub-menu ul',
              "date_manual_filter": 'input[type!=hidden][name="filter_by_date"]',
              "perpage_filter": 'div.filter-bar [data-filter-label^="show"]',
              "perpage_filter_menu": 'div.filter-bar [data-filter-label^="show"] + div.sub-menu ul',
              "perpage_manual_filter": 'input[type!=hidden][name="perpage"]',
              "no_results": 'p.no-results',
              "remove_all": 'button[rel=modal-confirm-all]',
              "items": '.panel-body .list-item',
              'search' : 'input[name="filter_by_keyword"]',
              'filter_user' : 'input[name="filter_by_username"]',
              'custom_limit' : 'input[name="perpage"]',
        }) // should be closing selectors ?
      } //close constructor


      hide_filters(){
        cy.get('div.filters a.open').click()
      }

      load(){
          cy.get('div.alert.standard a.close').click()

      }
}
export default Logs;