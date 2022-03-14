import { LoremIpsum } from "lorem-ipsum";
const lorem = new LoremIpsum();

import ControlPanel from '../ControlPanel'

class FluidField extends ControlPanel {
  constructor() {
    super()
    this.elements({
      'actions_menu': '.fluid .fluid-actions .filters',

        'actions_menu.name': '.fluid .fluid-actions .filters a.has-sub',
        'actions_menu.filter': '.fluid .fluid-actions .filters .filter-search',
        'actions_menu.fields': '.fluid .fluid__footer .button',

      'items': '.fluid .fluid__item:visible',

        //section :actions_menu, FluidActionMenu, '.filters'

        'items.reorder': '.fluid .fluid__item .reorder',
        'items.title': '.fluid .fluid__item label',
        'items.remove': '.fluid .fluid__item .js-fluid-remove',
        'items.instructions': '.fluid .fluid__item .field-instruct em',
        'items.field': '.fluid .fluid__item .fluid__item-field'

    })
  }

  
  add_content(index, skew = 0) {

    this.get('items').eq(index).invoke('attr', 'data-field-type').then(data => {
      const field_type = data;
      const field = this.get('items').eq(index).find('.fluid__item-field')

      switch (field_type) {
        case 'date':
          field.find('input[type=text][rel=date-picker]').type((9 + skew).toString() + '/14/2017 2:56 PM')
          cy.get('[name=title]').click() // Dismiss the date picker
          break;
        case 'checkboxes':
          field.find('input[type=checkbox]').eq(0 + skew).check();
          break;
        case 'email_address':
          field.find('input').clear().type('rspec-' + skew.toString() + '@example.com')
          break;
        case 'url':
          field.find('input').clear().type('http://www.example.com/page/' + skew.toString())
          break;
        case 'file':
          field.find('button:contains("Choose Existing")').click()
          cy.wait(500)
          this.get('items').eq(index).find('button:contains("Choose Existing")').next('.dropdown').find('a:contains("About")').click()
          //page.get('modal').should('be.visible')
          cy.get('.modal-file table tbody td').should('be.visible')
          //page.file_modal.wait_for_files
          cy.wait(500)
          cy.get('.modal-file table tbody td').eq(0 + skew).click()
          cy.wait(500)
          this.get('modal').should('not.exist')
          //page.wait_until_modal_invisible
          break;
        case 'relationship':
          let rel_link = field.find('.js-dropdown-toggle:contains("Relate Entry")')
          rel_link.click()
          rel_link.next('.dropdown.dropdown--open').find('.dropdown__link:visible').eq(0 + skew).click();
          cy.get('[name=title]').click()
          break;
        case 'rte':
          field.find('.ck-content').type('Lorem ipsum dolor sit amet' + lorem.generateParagraphs(Cypress._.random(1, (2 + skew))));
          break;
        case 'multi_select':
          field.find('input[type=checkbox]').eq(0 + skew).check()
          break;
        case 'radio':
          field.find('input[type=radio]').eq(1 + skew).check()
          break;
        case 'select':
          field.find('div[data-dropdown-react]').click()
          let choice = 'Corndog'
          if (skew == 1) { choice = 'Burrito' }
          cy.wait(100)
          this.get('items').eq(index).find('.fluid__item-field div[data-dropdown-react] .select__dropdown-items span:contains("'+choice+'")').click({force:true})
          break;
        case 'grid':
          field.find('a[rel="add_row"]').first().click()
          this.get('items').eq(index).find('.fluid__item-field input:visible').eq(0).clear().type('Lorem' + skew.toString())
          this.get('items').eq(index).find('.fluid__item-field input:visible').eq(1).clear().type('ipsum' + skew.toString())
          break;
        case 'textarea':
          field.find('textarea').type('Lorem ipsum dolor sit amet' + lorem.generateParagraphs(Cypress._.random(1, (3 + skew))));
          break;
        case 'toggle':
          field.find('.toggle-btn').click()
          break;
        case 'text':
          field.find('input').clear().type('Lorem ipsum dolor sit amet' + skew.toString())
          break;
      }
    })
  }

  check_content(index, skew = 0)
  {

    this.get('items').eq(index).invoke('attr', 'data-field-type').then(data => {
      const field_type = data;
      let field = this.get('items').eq(index).find('.fluid__item-field')

      switch (field_type) {
        case 'date':
          field.find('input[type=text][rel=date-picker]').invoke('val').then((text) => {
            expect(text).equal((9 + skew).toString() + '/14/2017 2:56 PM')
          })
          break;
        case 'checkboxes':
          field.find('input[type=checkbox]').eq(0 + skew).should('be.checked')
          break;
        case 'email_address':
          field.find('input').invoke('val').then((text) => {
            expect(text).equal('rspec-' + skew.toString() + '@example.com')
          })
          break;
        case 'url':
          field.find('input').invoke('val').then((text) => {
            expect(text).equal('http://www.example.com/page/' + skew.toString())
          })
          break;
        case 'file':
          field.contains('staff_jane')
          break;
        case 'relationship':
          let expected_val = 'About the Label';
          if (skew==1) {
            expected_val = 'Band Title';
          }
          field.contains(expected_val)
          break;
        case 'rte':
          field.find('textarea').contains('Lorem ipsum')// {:visible => false}
          break;
        case 'multi_select':
          field.find('input[type=checkbox]').eq(0 + skew).should('be.checked')
          break;
        case 'radio':
          field.find('input[type=radio]').eq(1 + skew).should('be.checked')
          break;
        case 'select':
          let choice = 'Corndog'
          if (skew == 1) { choice = 'Burrito' }
          field.find('div[data-dropdown-react]').contains(choice)
          break;
        case 'grid':
          this.get('items').eq(index).find('.fluid__item-field input:visible').eq(0).invoke('val').then((text) => {
            expect(text).equal('Lorem' + skew.toString())
          })
          this.get('items').eq(index).find('.fluid__item-field input:visible').eq(1).invoke('val').then((text) => {
            expect(text).equal('ipsum' + skew.toString())
          })
          break;
        case 'textarea':
          field.find('textarea').contains('Lorem ipsum')
          break;
        case 'toggle':
          field.find('.toggle-btn').click()
          break;
        case 'text':
          field.find('input').invoke('val').then((text) => {
            expect(text).equal('Lorem ipsum dolor sit amet' + skew.toString())
          })
          break;
      }
    })
  }
  
}
export default FluidField;
