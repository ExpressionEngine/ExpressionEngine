/*I am working off the default site for this so that will be needed to run this I got it
from the most recent 5.3.2 on the site and then ran the upgrade process to 6*/
Cypress.config().baseUrl = 'localhost';

import CreateField from '../../elements/pages/field/CreateField';
import MainField from '../../elements/pages/field/MainField';
import CreateGroup from '../../elements/pages/field/CreateGroup';
const page = new CreateField;
const main = new MainField;
const group = new CreateGroup;

context('Create and test field groups', () => {

	beforeEach(function() {
      cy.visit('http://localhost/admin.php')
      cy.get('#username').type('admin')
      cy.get('#password').type('password')
      cy.get('.button').click()
  	})

  it('verifies fields page exists', () => {
  	cy.visit('http://localhost/admin.php?/cp/fields')
  	cy.get('.main-nav__title > h1').contains('Field')
  	cy.get('.main-nav__toolbar > .button').contains('New Field')
  	cy.get('.filter-bar').should('exist')
  	cy.get('.filter-bar').should('exist')
  })

  it('can create a new field that is required', () => {
  	cy.visit('http://localhost/admin.php?/cp/fields')
  	cy.get('.main-nav__toolbar > .button').contains('New Field').click()
  	page.get('Type').click()
  	page.get('Type_Options').eq(8).click() //Click on Grid
  	page.get('Name').type('Player Info (Required)')
  	page.get('Instructions').type('Use This test Grid for entering Player information')
  	page.get('Required').click() //Make it required
  	page.get('Search').click() // Allow it to be searched
  	page.get('GridName').eq(0).type('Player Name') 
  	//somehow it gets a visible and invisible elment the eq 0 ensures visible is picked
  	page.get('GridInstruction').eq(0).type('Type Player Name Here')
  	page.get('GridRequired').eq(0).click()
  	cy.hasNoErrors()
  	page.get('Save').eq(0).click()
  	cy.get('p').contains('The field Player Info (Required) has been created')
  })

  it('can create a new field that is not required', () => {
  	cy.visit('http://localhost/admin.php?/cp/fields')
  	cy.get('.main-nav__toolbar > .button').contains('New Field').click()
  	page.get('Type').click()
  	page.get('Type_Options').eq(8).click() //Click on Grid
  	page.get('Name').type('Coach Info (Not Required)')
  	page.get('Instructions').type('Use This test Grid for entering Coach information')
  	page.get('Search').click() // Allow it to be searched
  	page.get('GridName').eq(0).type('Coach Name') 
  	//somehow it gets a visible and invisible elment the eq 0 ensures visible is picked
  	page.get('GridInstruction').eq(0).type('Type Coach Name')
  	cy.hasNoErrors()
  	page.get('Save').eq(0).click()
  	cy.get('p').contains('The field Coach Info (Not Required) has been created')
  })

  it('Puts the two new Fields into a Feild Group', () => {
  	cy.visit('http://localhost/admin.php?/cp/fields')
  	main.get('NewGroup').eq(0).click()

  	group.get('GroupName').clear().type('Chess')
  	group.get('Options').contains('(Required)').click()
  	group.get('Options').contains('(Not Required)').click()
  	group.get('Save').eq(0).click()
  	cy.get('p').contains('The field group Chess has been created')
  	//eq 0 in this forces visible only objects
  })

  it('Makes a new Channel with that field group',() => {
  	cy.visit('http://localhost/admin.php?/cp/channels/create')
  	cy.get("input[name = 'channel_title']").type('TestChannel')
  	cy.get('button').contains('Fields').click()
  	cy.get('div').contains('Chess').click()
  	cy.get('button').contains('Save').eq(0).click()
  	cy.get('p').contains('The channel TestChannel has been created')
  })

  
  it('Adds an entry to the new Channel and ensures correct requirment logic', () => {
  	cy.visit('http://localhost/admin.php?/cp/publish/edit')
  	cy.get('button[data-dropdown-pos = "bottom-end"]').eq(0).click()
  	cy.get('a').contains('TestChannel').click()

  	cy.get('input[name="title"]').type('Test Entry')


  	group.get('Save').eq(0).click()
  	//Click the button before adding required fields expect error

  	cy.get('em').contains('This field is required')
  	cy.get('p').contains('We were unable to create this entry')

  	cy.get('a').contains('Add new row').eq(0).click()
  	cy.get(':nth-child(3) > [data-fieldtype="text"] > input').type('Hikaru')
  	cy.get('h1').contains('New Entry').click()

  	cy.get('em').contains('This field is required').should('not.exist')

  	group.get('Save').eq(0).click()

  	cy.get('p').contains('We were unable to create this entry').should('not.exist')
  	cy.get('p').contains('The entry Test Entry has been created')
  })

  it('Makes a template for the entry grid', () => {
    cy.visit('http://localhost/admin.php?/cp/design/group/create')
    cy.get('input[name="group_name"]').eq(0).type("templateGroup")
    cy.get('input[type="submit"]').eq(0).click()

    cy.visit('http://localhost/admin.php?/cp/design/manager/templateGroup')
    cy.get('a').contains('index').click()
    cy.get('.CodeMirror-scroll').type('{exp:channel:entries}  {player_info_required}<h2> {player_info_required:field_row_count}</h2><h3> {player_info_required:player_name} </h3>{/player_info_required}{/exp:channel:entries}',{ parseSpecialCharSequences: false })
    cy.get('[value="edit"]').click()
    cy.visit('http://localhost/index.php/templateGroup')
    cy.get('body').contains('1')
    cy.get('body').contains('Hikaru')
  })

  it('Updates the webpage when new content is added to the entry (no touching template should be needed)', () =>{
    cy.visit('http://localhost/admin.php?/cp/publish/edit')
    cy.get('a').contains('Test Entry').click()
    cy.get('button').contains('Add Row').eq(0).click()
    
    cy.get(':nth-child(4) > [data-fieldtype="text"] > input').type('Magnus')
    cy.get('[value="save"]').eq(0).click()
    cy.visit('http://localhost/index.php/templateGroup')

    cy.get('body').contains('1')
    cy.get('body').contains('Hikaru')

    cy.get('body').contains('2')
    cy.get('body').contains('Magnus')
  })


})//context