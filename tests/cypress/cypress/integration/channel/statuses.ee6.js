/// <reference types="Cypress" />

import Statuses from '../../elements/pages/channel/Statuses';
const page = new Statuses;
const { _, $ } = Cypress

context('Statuses', () => {

    before(function() {
        cy.task('db:seed')
    })

    beforeEach(function() {
        // skip "Needs fleshing out for new channel manager" do
        cy.authVisit(page.url);
        page.load_view_for_status_group(1)
    })



    it('should list the statuses', function() {
        page.get_statuses_for_group(1).then(function(statuses){
            let statusNames = [...page.$('status_names').map(function(index, el) {
                return $(el).text().toLowerCase();
            })];
            expect(statuses).to.deep.equal(statusNames);
            page.get('status_names').its('length').should('eq', statuses.length)

            // Also, this table should not be sortable; since you can reorder
            // the statuses, having an option to sort them is confusing, you
            // don't know if sorting them changes the order in the DB or not
            // page.should have_no_sort_col
        })
    })

    it('should delete a status', function() {
        cy.server()
        page.get_statuses_for_group(1).then(function(statuses){

            page.get('statuses').eq(2).find('.button.float-right').click()

            cy.route("POST", "**/channels/render-statuses-field").as("ajax");
            page.get('modal').contains(statuses[2], {matchCase: false})
            
            //page.get('modal_submit_button').click()AJ
            cy.get('input[value="Confirm, and Remove"]').click()
            cy.wait("@ajax");
            cy.hasNoErrors()

            /*page.hasAlert('success')
            page.get('alert').contains('Statuses removed')
            page.get('alert').contains('1 statuses were removed.')*/
            page.get('status_names').its('length').should('eq', statuses.length - 1).then(() => {


                // statuses.delete_at(2)
                let statusNames = [...page.$('status_names').map(function(index, el) {
                    return $(el).text().toLowerCase();
                })];
                expect(statusNames).to.not.contain(statuses[2]);
            })
        })
    })

})