/// <reference types="Cypress" />

import CategoryGroup from '../../elements/pages/channel/CategoryGroup';
const page = new CategoryGroup;
const { _, $ } = Cypress

context('Category Groups', () => {

    before(function() {
        cy.task('db:seed')
    })

    beforeEach(function() {
        cy.authVisit(page.url);
    })

    it('shows the Category Groups page', function() {
        cy.contains('Categories')
    })

    it('should list the category groups', function() {
        page.groupNamesWithCatCount().then(function(groups) {
            let groupNames = [...page.$('group_names').map(function(index, el) {
                return $(el).contents().filter(function(){ return this.nodeType == 3; }).text().trim();
            })];
            expect(groupNames).to.deep.equal(groups.map(function(group) { return group.name; }))
            page.get('group_names').its('length').should('eq', groups.length)
        });
    })

    it('should delete a category group', function() {
        page.groupNames().then(function(groups) {
            //mouseover does not work, so just force clicking the invisible button
            page.get('category_groups').eq(0).find('a[rel="modal-confirm-categories"]').click({force: true})


            //page.get('modal_submit_button').click()
            cy.get('input[value="Confirm and Delete"]').filter(':visible').first().click()
            cy.wait(500)
            cy.hasNoErrors()

            page.hasAlert('success')

            page.get('group_names').its('length').should('eq', groups.length - 1)
        })
    })
})