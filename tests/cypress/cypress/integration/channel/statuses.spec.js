/// <reference types="Cypress" />

import Statuses from '../../elements/pages/channel/Statuses';
const page = new Statuses;
const { _, $ } = Cypress

context.skip('Statuses', () => {

    before(function() {
        cy.task('db:seed')
    })

    beforeEach(function() {
        // skip "Needs fleshing out for new channel manager" do
        cy.authVisit(page.url);
        page.load_view_for_status_group(1)
        let statuses = page.get_statuses_for_group(1)
    })



    it('should list the statuses', function() {
        let statusNames = [...page.$('status_names').map(function(index, el) {
            return $(el).text();
        })];
        page.get('status_names').its('length').should('eq', statuses.length)

        // Also, this table should not be sortable; since you can reorder
        // the statuses, having an option to sort them is confusing, you
        // don't know if sorting them changes the order in the DB or not
        // page.should have_no_sort_col
    })

    // This test for some reason doesn't work in versions of jQuery UI that set
    // a fixed placeholder height on table rows, Google suggests these headless
    // browser drivers don't work with Sortable very well
    //  it('should drag and drop statuses to reorder', function() {
    //    // Drag the drag handle to the third row
    //    page.statuses[0].find('td:first-child').drag_to page.statuses[2]
    //
    //    // Make our statuses array match what the table SHOULD be, and
    //    // check the table for it
    //    moved_status = @statuses.delete_at(0)
    //    page.status_names.map {|source| source.text}.should('eq', @statuses.insert(2, moved_status)
    //
    //    // Reload the page and make sure it stuck
    //    page.load_view_for_status_group(1)
    //    page.status_names.map {|source| source.text}.should('eq', @statuses
    //  })

    it('should delete a status', function() {
        page.get('statuses').eq(2).find('input[type="checkbox"]').check()
        page.get('bulk_action').select('Delete')
        page.get('action_submit_button').click()

        page.get('modal').contains('Status: ' + statuses[2])
        page.get('modal_submit_button').click()
        cy.hasNoErrors()

        page.hasAlert('success')
        page.get('alert').contains('Statuses removed')
        page.get('alert').contains('1 statuses were removed.')
        page.get('status_names').its('length').should('eq', statuses.length - 1)

        // statuses.delete_at(2)
        let statusNames = [...page.$('status_names').map(function(index, el) {
            return $(el).text();
        })];
        page.get('status_names').its('length').should('eq', statuses.length)
    })

    it('should bulk delete statuses, except the default statuses', function() {
        page.get('select_all').click()
        page.get('bulk_action').select('Delete')
        page.get('action_submit_button').click()

        // Minus two for default statuses
        /*
        if @statuses.count - 2 <= 5
          for status in @statuses
            if (['open', 'closed'].include? status.downcase) == false
              page.get('modal').contains('Status: ' + status)
            })
          })
        })*/

        page.get('modal').contains('Status: open')
        page.get('modal').contains('Status: closed')

        page.get('modal_submit_button').click()
        cy.hasNoErrors()

        page.hasAlert('success')
        page.get('alert').contains('Statuses removed')
        page.get('alert').contains((statuses.count - 2) + ' statuses were removed.')
        page.get('status_names').its('length').should('eq', 2)
        let statusNames = [...page.$('status_names').map(function(index, el) {
            return $(el).text();
        })];
        expect(statusNames).to.deep.equal(['open', 'closed'])
    })
})