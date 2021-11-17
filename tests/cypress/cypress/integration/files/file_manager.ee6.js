/// <reference types="Cypress" />

import FileManager from '../../elements/pages/files/FileManager';
import EditFile from '../../elements/pages/files/EditFile';
import CropFile from '../../elements/pages/files/CropFile';
const page = new FileManager;
const { _, $ } = Cypress

context('File Manager', () => {

	before(function() {
		cy.task('db:seed')

		cy.task('filesystem:create', Cypress.env("TEMP_DIR")+'/about');
		cy.task('filesystem:create', Cypress.env("TEMP_DIR")+'/avatars');
		cy.task('filesystem:copy', { from: '../../images/about/*', to: Cypress.env("TEMP_DIR")+'/about' })
		cy.task('filesystem:copy', { from: '../../images/avatars/*', to: Cypress.env("TEMP_DIR")+'/avatars' })
	})

	after(function() {
			cy.task('filesystem:delete', Cypress.env("TEMP_DIR")+'/about')
			cy.task('filesystem:delete', Cypress.env("TEMP_DIR")+'/avatars')
	})

	beforeEach(function() {

			cy.auth();
			page.load();
			cy.hasNoErrors()

			cy.url().should('match', page.urlMatch)

			// Check that the heder data is intact
			page.get('page_title').invoke('text').then((text) => {
				expect(text.trim()).equal('Files')
			})



			// Check that we have a sidebar
			page.get('sidebar').should('exist')


			//page.get('new_watermark_button').should('exist')
	});

	//For general and "All Files" specific tests
	function beforeEach_all_files() {
		//page.get('breadcrumb').should('not.exist')
		page.get('sync_button').should('not.exist')
		page.get('heading').invoke('text').then((text) => {
			expect(text.trim()).equal('All Files')
		})
		page.get('upload_new_file_button').should('exist')
		page.get('upload_new_file_filter').should('exist')
		page.get('files').should('exist')
		page.get('no_results').should('not.exist')

	}

	//For tests specific to a particular directory
	function beforeEach_not_all_files() {
		page.get('sidebar').contains('About').click()
		cy.hasNoErrors()
		//page.get('breadcrumb').should('not.exist')
		page.get('sync_button').should('exist')
		page.get('files').should('exist')

		page.get('download_all').should('exist')
	}

	function beforeEach_perpage_50() {
		page.get('perpage_filter').click()
		//page.wait_until_perpage_filter_menu_visible
		page.get('perpage_filter_menu').contains('50 results').click()
		cy.hasNoErrors()
	}

	afterEach(function() {
		cy.task('filesystem:delete', '../../images/about/')
		cy.task('filesystem:create', Cypress.env("TEMP_DIR")+'/about');
		cy.task('filesystem:copy', { from: Cypress.env("TEMP_DIR")+'/about/*', to: '../../images/about' })
		//FileUtils.chmod_R 0777, upload_dir

		cy.task('filesystem:delete', '../../images/avatars/')
		cy.task('filesystem:create', Cypress.env("TEMP_DIR")+'/avatars');
		cy.task('filesystem:copy', { from: Cypress.env("TEMP_DIR")+'/avatars/*', to: '../../images/avatars' })
	})

	it('shows the "All Files" File Manager page', () => {
		beforeEach_all_files();
		page.get('perpage_filter').contains('show (25)')
		page.get('date_added_header').should('have.class', 'column-sort-header--active')
		page.get('files').should('have.length', 11)
	});

	// General Tests

	it('can change the page size using the menu', () => {
		beforeEach_all_files();
		page.get('perpage_filter').click()
		//page.wait_until_perpage_filter_menu_visible
		page.get('perpage_filter_menu').contains("50 results", {timeout: 2000}).click()
		cy.hasNoErrors()

		page.get('perpage_filter').find('.has-sub').invoke('text').then((text) => {
			return text.trim()
		}).should('match', /show(\s)*\(50\)/)
		page.get('have_pagination').should('not.exist');
		page.get('files').should('have.length', 11)
	});

	it('can change the page size manually', () => {
		beforeEach_all_files();
		page.get('perpage_filter').click()
		//page.wait_until_perpage_filter_menu_visible
		page.get('perpage_manual_filter').type('5')
		page.get('perpage_manual_filter').closest('form').submit()
		cy.hasNoErrors()

		page.get('perpage_filter').find('.has-sub').invoke('text').then((text) => {
			return text.trim()
		}).should('match', /show(\s)*\(5\)/)

		page.get('pagination').should('exist')
		page.get('pages').should('have.length', 2)
		const pages = ["1", "2"]
		page.get('pages').each(function(el, i){
			expect(el).text(pages[i])
		})
		page.get('files').should('have.length', 6)
	});

	it('can change pages', () => {
		beforeEach_all_files();
		page.get('perpage_filter').click()
		//page.wait_until_perpage_filter_menu_visible
		page.get('perpage_manual_filter').type('5')
		page.get('perpage_manual_filter').closest('form').submit()
		cy.hasNoErrors()

		page.get('pages').last().click()
		cy.hasNoErrors()

		page.get('perpage_filter').find('.has-sub').invoke('text').then((text) => {
			return text.trim()
		}).should('match', /show(\s)*\(5\)/)
		page.get('pagination').should('exist')
		page.get('pages').should('have.length', 2)
		const pages = ["1", "2"]
		page.get('pages').each(function(el, i){
			expect(el).text(pages[i])
		})
		page.get('files').should('have.length', 6)
	});

	it('can reverse sort by title/name', () => {
		beforeEach_all_files();
		beforeEach_perpage_50();

		page.get('title_name_header').find('a.column-sort').click()
		cy.hasNoErrors()

		let sorted_files = [];
		page.get('title_names').then(function($td) {
			sorted_files = _.map($td, function(el) {
					return $(el).text();
			})
		})

		page.get('title_name_header').find('a.column-sort').click()
		cy.hasNoErrors()

		page.get('title_name_header').should('have.class', 'column-sort-header--active')
		page.get('title_names').then(function($td) {
			let files_reversed = _.map($td, function(el) {
					return $(el).text();
			})
			expect(files_reversed).to.deep.equal(sorted_files.reverse())
		})

	});

	it('can sort by file type', () => {
		beforeEach_all_files();
		beforeEach_perpage_50();

		page.get('file_type_header').find('a.column-sort').click()
		cy.hasNoErrors()
		page.get('file_type_header').should('have.class', 'column-sort-header--active')
		let sorted_files = [];
		page.get('file_types').then(function($td) {
			sorted_files = _.map($td, function(el) {
					return $(el).text();
			})
		})

		page.get('file_type_header').find('a.column-sort').click()
		cy.hasNoErrors()
		page.get('file_type_header').should('have.class', 'column-sort-header--active')
		page.get('file_types').then(function($td) {
			let files_reversed = _.map($td, function(el) {
					return $(el).text();
			})
			expect(files_reversed).to.deep.equal(sorted_files.reverse())
		})

		page.get('file_type_header').find('a.column-sort').click()
		cy.hasNoErrors()
		page.get('file_type_header').should('have.class', 'column-sort-header--active')
		page.get('file_types').then(function($td) {
			let files_reversed = _.map($td, function(el) {
					return $(el).text();
			})
			expect(files_reversed).to.deep.equal(files_reversed.reverse())
		})

	});


	it('can sort by date added', () => {
		beforeEach_all_files();
		beforeEach_perpage_50();
		page.get('date_added_header').find('a.column-sort').click()
		cy.hasNoErrors()

		page.get('date_added_header').should('have.class', 'column-sort-header--active')

		let sorted_files = [];
		page.get('dates_added').then(function($td) {
			sorted_files = _.map($td, function(el) {
					return $(el).text();
			})
		})

		page.get('date_added_header').find('a.column-sort').click()
		cy.hasNoErrors()

		page.get('date_added_header').should('have.class', 'column-sort-header--active')
		page.get('dates_added').then(function($td) {
			let files_reversed = _.map($td, function(el) {
					return $(el).text();
			})
			expect(files_reversed).to.deep.equal(sorted_files.reverse())
		})

		page.get('date_added_header').find('a.column-sort').click()
		cy.hasNoErrors()

		page.get('date_added_header').should('have.class', 'column-sort-header--active')
		page.get('dates_added').then(function($td) {
			let files_reversed = _.map($td, function(el) {
					return $(el).text();
			})
			expect(files_reversed).to.deep.equal(files_reversed.reverse())
		})
	});

	/*it('can view an image', () => {
		beforeEach_all_files();
		page.get('manage_actions').eq(0).find('li.view a').click()
		//page.wait_until_view_modal_visible
		//page.wait_for_view_modal_header(5)
		let filename = '';
		page.get('title_names').eq(0).find('em').invoke('text').then((text) => {
			filename = text.trim()
			page.get('view_modal_header').invoke('text').then((text) => {
				expect(text).contains(filename)
			})
		})

	});*/

	it('can edit file', () => {
		beforeEach_all_files();

		//page.get('manage_actions').eq(0).find('li.edit a').click()
		cy.get('a[title="Edit"]').filter(':visible').first().click()
		cy.hasNoErrors()


	});

	it('can crop an image', () => {
		beforeEach_all_files();
		//page.get('manage_actions').eq(0).find('li.crop a').click()
		cy.get('a[title="Crop"]').filter(':visible').first().click()
		cy.hasNoErrors()
	});

	it('displays an itemzied modal when attempting to remove 5 or less files', () => {

		let filename = '';
		page.get('title_names').eq(0).find('a').invoke('text').then((text) => {
			filename = text.trim()
		})

		page.get('files').eq(1).find('input[type="checkbox"]').check()
		//page.get('bulk_action').should('be.visible')
		page.get('bulk_action').select("Delete")
		page.get('action_submit_button').click()

		//page.get('modal').should('be.visible')
		page.get('modal_title').invoke('text').then((text) => {
			expect(text.trim()).equal('Are You Sure?')
		})
		page.get('modal').invoke('text').then((text) => {
			expect(text).contains('You are attempting to delete the following')
			expect(text).contains(filename)
		})
		page.get('modal').find('.checklist li').should('have.length', 1)
	});

	it('displays a bulk confirmation modal when attempting to remove more than 5 files', () => {
		page.get('checkbox_header').click()
		//page.get('bulk_action').should('be.visible')
		page.get('bulk_action').select("Delete")
		page.get('action_submit_button').click()

		//page.get('modal').should('be.visible')
		page.get('modal_title').invoke('text').then((text) => {
			expect(text.trim()).equal('Are You Sure?')
		})
		page.get('modal').invoke('text').then((text) => {
			expect(text).contains('You are attempting to delete')
			expect(text).contains('File: 10 Files')
		})
	});

	it('can remove a single file', () => {
		beforeEach_all_files();
		let filename = '';
		page.get('title_names').eq(0).invoke('text').then((text) => {
			filename = text
		})

		page.get('files').eq(1).find('input[type="checkbox"]').check()
		//page.get('bulk_action').should('be.visible')
		page.get('bulk_action').select("Delete")
		page.get('action_submit_button').click()
		//page.get('modal').should('be.visible')
		//page.get('modal_submit_button').click() // Submits a form
		cy.get('[value="Confirm and Delete"]').filter(':visible').first().click()
		cy.hasNoErrors()

		page.get('wrap').invoke('text').then((text) => {
			expect(text).not.contains(filename)
		})
	});

	it('can remove multiple files', () => {
		beforeEach_all_files();
		beforeEach_perpage_50();
		page.get('checkbox_header').click()
		//page.get('bulk_action').should('be.visible')
		page.get('bulk_action').select("Delete")
		page.get('action_submit_button').click()
		//page.get('modal').should('be.visible')
		//page.get('modal_submit_button').click() // Submits a form
		cy.get('[value="Confirm and Delete"]').filter(':visible').first().click()
		cy.hasNoErrors()

	});


	it('can add a new directory', () => {
		cy.task('db:seed').then(() => {
			cy.auth();
			page.load();

			beforeEach_all_files();
			//page.get('new_directory_button').click()
			cy.get('a[href="admin.php?/cp/files/uploads/create"]').first().click()
			cy.hasNoErrors()

			cy.url().should('match', /files\/uploads\/create/)
		})
	});

	it('can view a single directory', () => {
		beforeEach_all_files();
		page.get('sidebar').contains('Main Upload Directory').click()
		cy.hasNoErrors()

		cy.url().should('match', /files\/directory\//)
		page.get('heading').invoke('text').then((text) => {
			expect(text.trim()).equal('Files in Main Upload Directory')
		})
		page.get('sync_button').should('exist')
		page.get('no_results').should('exist')
	});

	it('displays an itemized modal when attempting to remove a directory', () => {
		beforeEach_all_files();

		page.get('sidebar').find('.folder-list > div:first-child').trigger('mouseover')
		cy.get('a[rel="modal-confirm-directory"]').first().click({force: true})

		//page.wait_until_remove_directory_modal_visible
		page.get('modal_title').invoke('text').then((text) => {
			expect(text.trim()).equal('Are You Sure?')
		})
		page.get('modal').find('ul[class="checklist"]').should('have.length', 1)
	});

	it('can remove a directory', () => {
		beforeEach_all_files();
		page.get('sidebar').find('.folder-list > div:first-child').trigger('mouseover')

		page.get('sidebar').find('.folder-list > div:first-child').find('a[rel="modal-confirm-directory"]').first().click({force: true})


		//page.wait_until_remove_directory_modal_visible
		//page.get('modal_submit_button').click() // Submits a form
		cy.get('[value="Confirm and Delete"]').filter(':visible').first().click()
		cy.hasNoErrors()

		page.get('sidebar').invoke('text').then((text) => {
			expect(text).not.contains('About')
		})
		page.get('alert').should('exist')
		page.get('alert').invoke('text').then((text) => {
			expect(text).contains('Upload directory deleted')
			expect(text).contains('has been deleted.')
		})

		cy.task('db:seed').then(() => {})

	});

	it('can remove the directory you are viewing', () => {

		beforeEach_all_files();
		page.get('sidebar').contains("About").click()
		cy.hasNoErrors()

		page.get('sidebar').find('.active > a').invoke('text').then((text) => {
			expect(text.trim()).equal('About')
		})

		page.get('sidebar').find('.folder-list > div:first-child').trigger('mouseover')
		cy.get('a[rel="modal-confirm-directory"]').first().click({force: true})

		//page.wait_until_remove_directory_modal_visible
		//page.get('modal_submit_button').click() // Submits a form
		cy.get('button').contains('Confirm and Delete').click()
		cy.hasNoErrors()

		page.get('sidebar').invoke('text').then((text) => {
			expect(text).not.contains('About')
		})
		page.get('alert').should('exist')
		page.get('alert').invoke('text').then((text) => {
			expect(text).contains('Upload directory deleted')
			expect(text).contains('The upload directory About has been deleted.')
		})

		

	});

	// Tests specific to the "All Files" view

	it('must choose where to upload a new file when viewing All Files', () => {

		cy.task('db:seed').then(() => {

			cy.auth();
			page.load();
			cy.hasNoErrors()

			cy.url().should('match', page.urlMatch)

			// Check that the heder data is intact
			page.get('page_title').invoke('text').then((text) => {
				expect(text.trim()).equal('Files')
			})

			// Check that we have a sidebar
			page.get('sidebar').should('exist')

			beforeEach_all_files();
			page.get('upload_new_file_button').click()
			//page.wait_until_upload_new_file_filter_menu_visible
			page.get('upload_new_file_filter_menu_items').eq(0).click()
			cy.hasNoErrors()

			cy.url().should('match', /files\/upload/)
		})

	});

	it('can filter the Upload New File menu', () => {
		beforeEach_all_files();
	});

	// Tests specific to a directory view

	it('can synchronize a directory', () => {
		beforeEach_not_all_files();
		page.get('sync_button').click()
		cy.hasNoErrors()

		cy.url().should('match', /files\/uploads\/sync\//)

	});

	it('marks all missing files in index view', () => {
		beforeEach_all_files();
		cy.task('filesystem:delete', '../../images/about/*.jpg')
		page.load();
		cy.hasNoErrors()

		page.get('alert').should('exist')
		page.get('alert_important').should('exist')
		page.get('alert').invoke('text').then((text) => {
			expect(text).contains('Files Not Found')
			expect(text).contains('Highlighted files cannot be found on the server.')
		})

		page.get('wrap').find('tr.missing').should('exist')

		cy.task('db:seed').then(() => {})


	});

	it('marks all missing files in directory view', () => {

		beforeEach_not_all_files();

		cy.task('filesystem:delete', '../../images/about/*.jpg')
		page.load();
		cy.hasNoErrors()

		page.get('alert').should('exist')
		page.get('alert_important').should('exist')
		page.get('alert').invoke('text').then((text) => {
			expect(text).contains('Files Not Found')
			expect(text).contains('Highlighted files cannot be found on the server.')
		})

		page.get('wrap').find('tr.missing').should('exist')
  });




})
