/// <reference types="Cypress" />

import CreateField from '../../elements/pages/field/CreateField';
const page = new CreateField;
const { _, $ } = Cypress

context('Conditional Fields', () => {

    before(function() {
        cy.task('db:seed')

        cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
        //copy templates
        cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/default_site/' })
        cy.authVisit('admin.php?/cp/design')
    })

    after(function() {
        cy.task('filesystem:delete', '../../system/user/templates/default_site/modifiers.group')
        cy.task('filesystem:delete', '../../system/user/config/stopwords.php')
    })

    it('legacy textarea field', () => {
        cy.log('add conditional to field');

        cy.log('edit entry to conditionally hide the field');

        cy.log('field is hidden on entry page after save')

        cy.log('field is not shown in the template')

        cy.log('field shows up if conditionals not met anymore')
    })

    it('file field', function() {
        cy.log('add new field')
    })

    it('grid field', function() {

    })

    it('relationship field', function() {

    })

    it('fluid field', function() {

    })

    context('different combinations of rules', function() {

    })

})
