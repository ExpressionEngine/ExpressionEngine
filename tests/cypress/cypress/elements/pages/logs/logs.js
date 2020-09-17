import ControlPanel from '../ControlPanel'

class Logs extends ControlPanel {
    constructor() {
        super()
       
        this.selectors = Object.assign(this.selectors, {
              "heading" : 'div.w-12 div.box div.tbl-ctrls h1',
              "username_filter": 'div.filters a[data-filter-label^="username"]',
              "username_filter_menu": 'div.filters a[data-filter-label^="username"] + div.sub-menu ul',
              "username_manual_filter": 'input[name="filter_by_username"]',
              "date_filter": 'div.filters a[data-filter-label^="date"]',
              "date_filter_menu": 'div.filters a[data-filter-label^="date"] + div.sub-menu ul',
              "date_manual_filter": 'input[name="filter_by_date"]',
              "perpage_filter": 'div.filters a[data-filter-label^="show"]',
              "perpage_filter_menu": 'div.filters a[data-filter-label^="show"] + div.sub-menu ul',
              "perpage_manual_filter": 'input[name="perpage"]',
              "no_results": 'p.no-results',
              "remove_all": 'button.btn.action',
              "items": 'section.item-wrap div.item'
        }) 
      } //close constructor

        
      hide_filters(){
        cy.get('div.filters a.open').click()
      }

      load(){
          cy.get('div.alert.standard a.close').click()
          
      }
}
export default Logs;