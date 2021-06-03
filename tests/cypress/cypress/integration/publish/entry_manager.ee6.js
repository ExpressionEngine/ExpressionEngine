/// <reference types="Cypress" />

import EntryManager from '../../elements/pages/publish/EntryManager';

const page = new EntryManager;


context('Entry Manager', () => {

  before(function(){
    cy.task('db:seed')
  })

  beforeEach(function(){
    cy.auth();
    /*page.load()
    cy.hasNoErrors()*/
  })

  it('displays properly when max_entries hit', () => {
    cy.createChannel({max_entries: 1}).then((channel_json) => {
      const channel = JSON.parse(channel_json)

      cy.createEntries({'n': 1, 'channel': channel.channel_id})
      cy.visit(Cypress._.replace(page.url, '{filter_by_channel}', 'filter_by_channel='+channel.channel_id), {failOnStatusCode: false})

      page.get('wrap').find('a:contains("New in")').should('not.exist')
      page.get('alert').contains("Channel limit reached")
    })

  })

  it('offers a create option for channels with max_entries not yet reached', () => {
    cy.createChannel({max_entries: 3}).then((channel_json) => {

      const channel = JSON.parse(channel_json)

      cy.createEntries({'n': 2, 'channel': channel.channel_id})
      cy.visit(Cypress._.replace(page.url, '{filter_by_channel}', 'filter_by_channel='+channel.channel_id), {failOnStatusCode: false})

      const btn_txt = 'New in ' + channel.channel_title;
      page.get('wrap').find('a:contains("'+btn_txt+'")').should('exist')
    })
  })

  it('create menu does not include channels when max_entries is hit', () => {
    cy.createChannel({max_entries: 3}).then((channel_json) => {
      const channel = JSON.parse(channel_json)

      cy.createEntries({'n': 3, 'channel': channel.channel_id})
      cy.visit(page.url)

      page.get('nav').find('a:contains("Entries")').trigger('mouseover')
      page.get('edit_menu').find('a').each(function(link, i) {
        cy.get(link).invoke('attr', 'href').then((href) => {
          expect(href).to.not.contain('admin.php?/cp/publish/create/' + channel.channel_id)
        })
      })
    })
  })

  it('edit menu goes straight to publish for max_entries 1 = 1', () => {
    cy.createChannel({max_entries: 1}).then((channel_json) => {
      const channel = JSON.parse(channel_json)

      cy.createEntries({'n': 1, 'channel': channel.channel_id})
      cy.visit(page.url)

      page.get('nav').find('a:contains("Entries")').trigger('mouseover')
      page.get('edit_menu').find('a:contains("'+channel.channel_title+'")').each(function(link, i) {
        cy.get(link).invoke('attr', 'href').then((href) => {
          expect(href).to.contain('admin.php?/cp/publish/edit/entry')
        })
      })
    })
  })

  it('creates entries', () => {

    const command = `cd support/fixtures && php deleteEntries.php`;
    cy.exec(command)
    cy.createEntries({})

    cy.visit(page.url)

    cy.task('db:query', 'SELECT count(entry_id) AS count FROM exp_channel_titles').then(function([rows, fields]) {
      expect(rows[0].count).to.be.equal(10);
      page.get('entry_rows').should('have.length', 10);
    })

    cy.createEntries({})
    cy.visit(page.url)

    cy.task('db:query', 'SELECT count(entry_id) AS count FROM exp_channel_titles').then(function([rows, fields]) {
      expect(rows[0].count).to.be.equal(20);
      page.get('entry_rows').should('have.length', 20);
    })
  })

  it('loads a page with 100 entries', () => {

    const command = `cd support/fixtures && php deleteEntries.php`;
    cy.exec(command)
    cy.createEntries({})

    cy.createEntries({n: 100})

    cy.visit(Cypress._.replace(page.url, '{perpage}', 'perpage='+100), {failOnStatusCode: false})

    cy.task('db:query', 'SELECT count(entry_id) AS count FROM exp_channel_titles').then(function([rows, fields]) {
      expect(rows[0].count).to.be.equal(110);
      page.get('entry_rows').should('have.length', 100);
    })
  })

  it('deletes a single entry', () => {

    const command = `cd support/fixtures && php deleteEntries.php`;
    cy.exec(command)
    cy.createEntries({})

    cy.visit(page.url)

    page.get('entry_rows').should('have.length', 10);

    page.get('entry_checkboxes').first().check()
    page.get('bulk_action').select('Delete')
    page.get('action_submit_button').click()
    //page.get('modal_submit_button').should('be.visible')
    //page.get('modal_submit_button').click()
     cy.get('[value="Confirm and Delete"]').click()


    page.get('entry_rows').should('have.length', 9);
    page.get('alert').contains('The following entries were deleted')
  })

  it('deletes all entries', () => {

    const command = `cd support/fixtures && php deleteEntries.php`;
    cy.exec(command)
    cy.createEntries({})

    cy.visit(page.url)

    page.get('entry_rows').should('have.length', 10);
    page.get('entry_checkboxes').each(function(el){
      cy.get(el).check()
    })

    page.get('bulk_action').select('Delete')
    page.get('action_submit_button').click()
    //page.get('modal_submit_button').should('be.visible')
   // page.get('modal_submit_button').click()
    cy.get('[value="Confirm and Delete"]').click()

    page.get('entry_rows').should('have.length', 1)
    page.get('entry_rows').first().contains('No Entries found.')
    page.get('alert').contains('The following entries were deleted')
  })

  it('deletes 100 entries', () => {

    const command = `cd support/fixtures && php deleteEntries.php`;
    cy.exec(command)
    cy.createEntries({})

    cy.createEntries({n: 100})
    cy.visit(Cypress._.replace(page.url, '{perpage}', 'perpage='+100), {failOnStatusCode: false})

    // ... leaves the last item out of the range
    page.get('entry_checkboxes').each(function(el){
      cy.get(el).check()
    })
    page.get('bulk_action').select('Delete')
    page.get('action_submit_button').click()
    //page.get('modal_submit_button').should('be.visible')
   // page.get('modal_submit_button').click()

   cy.get('[value="Confirm and Delete"]').click()

    page.get('entry_rows').should('have.length', 10);
    page.get('alert').contains('The following entries were deleted')
    page.get('alert').contains('and 96 others...')
  })

})
