import FileModal from '../../elements/pages/publish/FileModal';
let file_modal = new FileModal;

context('File Manager / Usage Tab', () => {

  before(function() {
    cy.task('db:seed')
  })

  beforeEach(function() {
    cy.auth();

    cy.visit('admin.php?/cp/files')
    cy.hasNoErrors()
  })

  it('check is usage tab is on', () => {
    cy.get('a').contains('staff_jane').filter(':visible').first().click()
    cy.get('button').contains('Usage').should('exist')
    cy.get('.tab-notification-generic').contains('0')
  })

  it('add image to 3 entries, image usage tab has include 3 items', () => {
    cy.visit('admin.php?/cp/publish/edit')
    cy.get('a').contains('Getting to Know ExpressionEngine').filter(':visible').first().click()
    cy.get('.fields-upload-chosen').should('exist')
    cy.get('.fields-upload-chosen .remove').first().click()
    cy.get('.fields-upload-chosen').should('not.be.visible')
    cy.get('input[name="field_id_3"]').parent('.field-control').find('div[data-file-field-react]').should('be.visible')
    cy.get('input[name="field_id_3"]').parent('.field-control').find('div[data-file-field-react] button:contains("Choose Existing")').click()
    cy.wait(1000)
    cy.get('input[name="field_id_3"]').parent('.field-control').find('div[data-file-field-react] .dropdown--open a:contains("About")').click()
    cy.wait(1000)
    file_modal.get('files').should('be.visible')
    file_modal.get('files').contains('staff_jane.png').click()
    file_modal.get('files').should('not.be.visible')
    cy.get('.fields-upload-chosen').should('be.visible')
    cy.get('body').type('{ctrl}', {release: false}).type('s')

    cy.visit('admin.php?/cp/publish/edit')
    cy.get('a').contains('Welcome to the Example Site!').filter(':visible').first().click()
    cy.get('.fields-upload-chosen').should('exist')
    cy.get('.fields-upload-chosen .remove').first().click()
    cy.get('.fields-upload-chosen').should('not.be.visible')
    cy.get('input[name="field_id_3"]').parent('.field-control').find('div[data-file-field-react]').should('be.visible')
    cy.get('input[name="field_id_3"]').parent('.field-control').find('div[data-file-field-react] button:contains("Choose Existing")').click()
    cy.wait(1000)
    cy.get('input[name="field_id_3"]').parent('.field-control').find('div[data-file-field-react] .dropdown--open a:contains("About")').click()
    cy.wait(1000)
    file_modal.get('files').should('be.visible')
    file_modal.get('files').contains('staff_jane.png').click()
    file_modal.get('files').should('not.be.visible')
    cy.get('.fields-upload-chosen').should('be.visible')
    cy.get('body').type('{ctrl}', {release: false}).type('s')

    cy.visit('admin.php?/cp/publish/edit')
    cy.get('a').contains('About the Label').filter(':visible').first().click()
    cy.get('.fields-upload-chosen').should('exist')
    cy.get('.fields-upload-chosen .remove').first().click()
    cy.get('.fields-upload-chosen').should('not.be.visible')
    cy.get('input[name="field_id_5"]').parent('.field-control').find('div[data-file-field-react]').should('be.visible')
    cy.get('input[name="field_id_5"]').parent('.field-control').find('div[data-file-field-react] button:contains("Choose Existing")').click()
    cy.wait(1000)
    cy.get('input[name="field_id_5"]').parent('.field-control').find('div[data-file-field-react] .dropdown--open a:contains("About")').click()
    cy.wait(1000)
    file_modal.get('files').should('be.visible')
    file_modal.get('files').contains('staff_jane.png').click()
    file_modal.get('files').should('not.be.visible')
    cy.get('.fields-upload-chosen').should('be.visible')
    cy.get('body').type('{ctrl}', {release: false}).type('s')

    cy.visit('admin.php?/cp/files')
    cy.get('a').contains('staff_jane').filter(':visible').first().click()
    cy.get('button').contains('Usage').should('exist')
    cy.get('.tab-notification-generic').contains('3')

    cy.get('button').contains('Usage').first().click()
    cy.get('.f_entries-table tbody tr').should('have.length', 3)
    cy.get('.f_entries-table tbody tr td a').contains('Getting to Know ExpressionEngine')
    cy.get('.f_entries-table tbody tr td a').contains('About the Label')
    cy.get('.f_entries-table tbody tr td a').contains('Welcome to the Example Site!')
  })

  it('add image to category, usage tab have 4 items', () => {
    cy.visit('admin.php?/cp/categories/group/1')
    cy.get('.list-item__title').contains('News').first().click();
    cy.get('#fieldset-cat_image').find('div[data-file-field-react] button:contains("Choose Existing")').click()
    cy.wait(1000)
    cy.get('#fieldset-cat_image').find('div[data-file-field-react] .dropdown--open a:contains("About")').click()
    cy.wait(1000)
    file_modal.get('files').should('be.visible')
    file_modal.get('files').contains('staff_jane.png').click()
    file_modal.get('files').should('not.be.visible')
    cy.get('.fields-upload-chosen').should('be.visible')
    cy.get('body').type('{ctrl}', {release: false}).type('s')

    cy.visit('admin.php?/cp/files')
    cy.get('a').contains('staff_jane').filter(':visible').first().click()
    cy.get('button').contains('Usage').should('exist')
    cy.get('.tab-notification-generic').contains('4')

    cy.get('button').contains('Usage').first().click()
    cy.get('.f_entries-table tbody tr').should('have.length', 3)
    cy.get('.f_entries-table tbody tr td a').contains('Getting to Know ExpressionEngine')
    cy.get('.f_entries-table tbody tr td a').contains('About the Label')
    cy.get('.f_entries-table tbody tr td a').contains('Welcome to the Example Site!')

    cy.get('.f_category-table tbody tr').should('have.length', 1)
    cy.get('.f_entries-table tbody tr td a').contains('News')
  })
})