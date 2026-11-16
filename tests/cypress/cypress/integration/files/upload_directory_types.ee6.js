/// <reference types="Cypress" />

import UploadEdit from '../../elements/pages/files/UploadEdit';
import ChannelFieldForm from '../../elements/pages/channel/ChannelFieldForm';

const uploadEdit = new UploadEdit;
const channelFieldForm = new ChannelFieldForm;
const uploadPath = '../../images/uploads/issue-5447';
const roleName = 'UploadDirectoryTester';
const directoryTypes = {
  Documents: ['doc'],
  Images: ['img'],
  Mixed: ['doc', 'img'],
  General: ['--'],
  'Restricted Images': ['img']
};

context('File field upload directory types (#5447)', () => {
  const fixture = { channel: 1, directories: {} }
  const field = type => cy.get('[data-field_id="' + fixture[type + 'Field'] + '"]')
  const visitEntry = () => cy.visit('admin.php?/cp/publish/create/' + fixture.channel)

  before(() => {
    cy.task('db:seed')
    cy.task('filesystem:delete', uploadPath)
    cy.auth()

    Object.entries(directoryTypes).forEach(([name, types]) => {
      const slug = name.toLowerCase().replace(/ /g, '-')
      cy.task('filesystem:create', `${uploadPath}/${slug}`)
      cy.exec(`chmod 777 ${uploadPath}/${slug}`)
      cy.visit('admin.php?/cp/files/uploads/create')
      uploadEdit.get('adapter').find('.select__button').click()
      uploadEdit.get('adapter').find('.select__dropdown-item').contains('Local').click()
      uploadEdit.get('name').clear().type(name)
      uploadEdit.get('url').clear().type(`${Cypress.config().baseUrl}/images/uploads/issue-5447/${slug}/`)
      uploadEdit.get('server_path').clear().type(`{base_path}/images/uploads/issue-5447/${slug}/`, { parseSpecialCharSequences: false })
      uploadEdit.get('allowed_types').find('input[type=checkbox]').uncheck({ force: true })
      types.forEach(type => uploadEdit.get('allowed_types').find(`[value="${type}"]`).check({ force: true }))
      uploadEdit.submit()
      uploadEdit.get('alert_success').should('contain', 'Upload directory saved')
      cy.hasNoErrors()
    })

    cy.task('db:query', 'SELECT id, name FROM exp_upload_prefs').then(([rows]) => {
      rows.filter(row => Object.prototype.hasOwnProperty.call(directoryTypes, row.name)).forEach(row => {
        fixture.directories[row.name.toLowerCase().replace(/ /g, '-')] = row.id
      })
      expect(Object.keys(fixture.directories)).to.have.length(5)
    })

    // Add both fields to the seeded channel's existing field group.
    const fieldTypes = ['image', 'all']
    fieldTypes.forEach(type => {
      cy.visit('admin.php?/cp/fields/create/1')
      channelFieldForm.createField({
        type: 'File',
        label: 'Upload directory ' + type,
        fields: { allowed_directories: 'all', field_content_type: type }
      })
    })
    cy.task('db:query', "SELECT field_id, field_name FROM exp_channel_fields WHERE field_name IN ('upload_directory_image', 'upload_directory_all')").then(([rows]) => {
      expect(rows).to.have.length(2)
      rows.forEach(row => {
        fixture[row.field_name.replace('upload_directory_', '') + 'Field'] = row.field_id
      })
    })

    cy.addRole(roleName)
    cy.addMembers(roleName, 1)
    cy.visit('admin.php?/cp/members/roles')
    cy.get('.list-item__title').contains(roleName).click()
    cy.contains('button', 'CP Access').click()
    cy.get('#fieldset-can_access_cp .toggle-btn').click()
    cy.get('#fieldset-can_access_files .toggle-btn').click()
    cy.get('#fieldset-files input[value="can_upload_new_files"]').check()
    cy.get('#fieldset-channel_access input[value="can_create_entries_channel_id_1"]').check()
    cy.get('#fieldset-channel_access input[value="can_edit_self_entries_channel_id_1"]').check()
    cy.then(() => {
      Object.entries(fixture.directories).filter(([name]) => name !== 'restricted-images').forEach(([, id]) => {
        cy.get(`#fieldset-upload_destination_access input[value="${id}"]`).check()
      })
    })
    cy.get('button[type=submit][value=save]').first().click()
    cy.hasNoErrors()
    cy.logout()
  })

  beforeEach(() => {
    cy.auth({ email: roleName + '1', password: 'password' })
    visitEntry()
    cy.hasNoErrors()
  })

  it('offers only image-compatible destinations for image uploads', () => {
    field('image').contains('button', 'Upload New').click()
    field('image').find('.dropdown:visible').should('contain', 'Images')
      .and('contain', 'Mixed').and('contain', 'General')
      .and('not.contain', 'Documents').and('not.contain', 'Restricted Images')
  })

  it('keeps all accessible destinations for unrestricted fields and existing files', () => {
    field('all').contains('button', 'Upload New').click()
    field('all').find('.dropdown:visible').should('contain', 'Documents')
      .and('contain', 'Images').and('not.contain', 'Restricted Images')
    cy.get('.title-bar__title').first().click()
    field('image').contains('button', 'Choose Existing').click()
    field('image').find('.dropdown:visible').should('contain', 'Documents')
  })

  it('holds a dropped image until an accepting directory is chosen and uploads it', () => {
    cy.intercept('POST', '**/filepicker/ajax-upload').as('upload')
    field('image').find('.file-field__dropzone').selectFile('support/file/programming.gif', { action: 'drag-drop' })
    field('image').should('contain', 'Choose')
    cy.get('@upload.all').should('have.length', 0)
    field('image').find('.file-field__dropzone button').click()
    field('image').find('.dropdown:visible').should('not.contain', 'Documents')
    field('image').find('.dropdown:visible a').contains(/^Images$/).click()
    cy.wait('@upload').its('response.body.status').should('eq', 'success')
    field('image').should('contain', 'programming.gif')
  })

  it('names the rejecting directory for a direct image upload', () => {
    cy.readFile('support/file/programming.gif', null).then(bytes => {
      cy.window().then(win => {
        const data = new win.FormData()
        data.append('csrf_token', win.EE.CSRF_TOKEN)
        data.append('upload_location_id', fixture.directories.documents)
        data.append('directory_id', 0)
        data.append('path', '')
        data.append('file', new win.File([bytes], 'directory-rejection.gif', { type: 'image/gif' }))
        return win.fetch(win.EE.dragAndDrop.endpoint, { method: 'POST', body: data })
          .then(response => response.json()).then(response => {
            expect(response.status).to.eq('error')
            expect(response.error).to.contain('Documents')
            expect(response.error).to.contain('does not allow this file type')
          })
      })
    })
  })

  it('still denies direct uploads to directories outside the role permissions', () => {
    cy.readFile('support/file/programming.gif', null).then(bytes => {
      cy.window().then(win => {
        const data = new win.FormData()
        data.append('csrf_token', win.EE.CSRF_TOKEN)
        data.append('upload_location_id', fixture.directories['restricted-images'])
        data.append('directory_id', 0)
        data.append('file', new win.File([bytes], 'denied.gif', { type: 'image/gif' }))
        return win.fetch(win.EE.dragAndDrop.endpoint, { method: 'POST', body: data })
          .then(response => expect(response.status).to.eq(403))
      })
    })
  })

  after(() => {
    const types = { documents: 'doc', images: 'img', mixed: 'doc|img', general: 'all', 'restricted-images': 'img' }
    Object.entries(types).forEach(([name, allowed]) => {
      cy.task('db:query', `UPDATE exp_upload_prefs SET allowed_types = '${allowed}' WHERE id = ${fixture.directories[name]}`)
    })
  })

  it('shows an empty upload state when no accessible directory accepts images', () => {
    cy.task('db:query', `UPDATE exp_upload_prefs SET allowed_types = 'doc' WHERE id IN (${Object.values(fixture.directories).join(',')})`)
    visitEntry()
    field('image').should('contain', 'No upload directories')
    field('image').should('not.contain', 'Upload New')
    field('image').contains('button', 'Choose Existing').should('exist')
  })
})
