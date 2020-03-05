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
        cy.contains('Category Manager')
    })

    it('should list the category groups', function() {
        page.groupNamesWithCatCount().then(function(groups) {
            let groupNames = [...page.$('group_names').map(function(index, el) {
                return $(el).text();
            })];
            expect(groupNames).to.deep.equal(groups.map(function(group) { return group.name; }))
            page.get('group_names').its('length').should('eq', groups.length)
        });
    })

    it('should delete a category group', function() {
        page.groupNames().then(function(groups) {
            page.get('category_groups').eq(0).find('li.remove a').click()
            page.get('modal').contains('Category Group: ' + groups[0].name)
            page.get('modal_submit_button').click()
            cy.hasNoErrors()

            page.hasAlert('success')
            page.get('alert').contains('Category group removed')
            page.get('group_names').its('length').should('eq', groups.length - 1)
        })
    })
})