context('Secondary Sidebar - Collapsed', () => {
	before(function(){
		cy.task('db:seed')
		cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
		cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' }).then(() => {
			cy.authVisit('admin.php?/cp/design')
		})
		cy.eeConfig({ item: 'show_profiler', value: 'y' })
	})

	beforeEach(function(){
		cy.auth();
		cy.hasNoErrors()
	})

	after(function(){
		cy.eeConfig({ item: 'show_profiler', value: 'n' })
	})

	// default, the sidebar should be open everywhere
	it('check that secondary sidebar is open on different pages', () => {
		cy.visit('admin.php?/cp/files')
		cy.get('.secondary-sidebar').should('exist')
		cy.get('.secondary-sidebar').should('be.visible')
		cy.get('.secondary-sidebar-toggle').should('exist')
		cy.get('.secondary-sidebar-toggle > a').should('not.have.class', 'collapsed')

		cy.visit('admin.php?/cp/categories/group/1')
		cy.get('.secondary-sidebar').should('exist')
		cy.get('.secondary-sidebar').should('be.visible')
		cy.get('.secondary-sidebar-toggle').should('exist')
		cy.get('.secondary-sidebar-toggle > a').should('not.have.class', 'collapsed')

		cy.visit('admin.php?/cp/utilities/communicate')
		cy.get('.secondary-sidebar').should('exist')
		cy.get('.secondary-sidebar').should('be.visible')
		cy.get('.secondary-sidebar-toggle').should('exist')
		cy.get('.secondary-sidebar-toggle > a').should('not.have.class', 'collapsed')

		cy.visit('admin.php?/cp/settings/general')
		cy.get('.secondary-sidebar').should('exist')
		cy.get('.secondary-sidebar').should('be.visible')
		cy.get('.secondary-sidebar-toggle').should('exist')
		cy.get('.secondary-sidebar-toggle > a').should('not.have.class', 'collapsed')
	})

	it('close sidebar on one page, but left open on other', () => {
		cy.visit('admin.php?/cp/fields')
		cy.get('.secondary-sidebar-toggle').invoke('show');
		cy.get('.secondary-sidebar-toggle > a').first().click();
		cy.get('.secondary-sidebar').should('have.class', 'secondary-sidebar__collapsed');
		cy.get('.secondary-sidebar-toggle__target').should('have.class', 'collapsed');
		cy.get('.secondary-sidebar-toggle__target > i').should('have.class', 'fa-angle-right');

		cy.visit('admin.php?/cp/utilities/communicate')
		cy.get('.secondary-sidebar').should('exist')
		cy.get('.secondary-sidebar').should('be.visible')
		cy.get('.secondary-sidebar-toggle').should('exist')
		cy.get('.secondary-sidebar-toggle > a').should('not.have.class', 'collapsed')

		cy.visit('admin.php?/cp/settings/general')
		cy.get('.secondary-sidebar').should('exist')
		cy.get('.secondary-sidebar').should('be.visible')
		cy.get('.secondary-sidebar-toggle').should('exist')
		cy.get('.secondary-sidebar-toggle > a').should('not.have.class', 'collapsed')

		cy.visit('admin.php?/cp/fields')
		cy.get('.secondary-sidebar').should('have.class', 'secondary-sidebar__collapsed');
		cy.get('.secondary-sidebar-toggle__target').should('have.class', 'collapsed');
		cy.get('.secondary-sidebar-toggle__target > i').should('have.class', 'fa-angle-right');

		cy.get('.secondary-sidebar-toggle > a').first().click();
		cy.get('.secondary-sidebar').should('not.have.class', 'secondary-sidebar__collapsed');
		cy.get('.secondary-sidebar-toggle__target').should('not.have.class', 'collapsed');
		cy.get('.secondary-sidebar-toggle__target > i').should('not.have.class', 'fa-angle-right');
	})

	it('close sidebar on one page, and check subpages', () => {
		cy.intercept('**toggle-secondary-sidebar-nav**').as('ajax')
		cy.visit('admin.php?/cp/fields')
		cy.get('.secondary-sidebar-toggle').invoke('show');
		cy.get('.secondary-sidebar-toggle > a').first().click();
		cy.wait('@ajax')
		cy.get('.secondary-sidebar').should('have.class', 'secondary-sidebar__collapsed');
		cy.get('.secondary-sidebar-toggle__target').should('have.class', 'collapsed');
		cy.get('.secondary-sidebar-toggle__target > i').should('have.class', 'fa-angle-right');

		cy.get('.list-group li:first-child').click();
		cy.get('.secondary-sidebar').should('have.class', 'secondary-sidebar__collapsed');
		cy.get('.secondary-sidebar-toggle__target').should('have.class', 'collapsed');
		cy.get('.secondary-sidebar-toggle__target > i').should('have.class', 'fa-angle-right');

		cy.get('.breadcrumb-wrapper a').contains('Fields').click();
		cy.get('.secondary-sidebar').should('have.class', 'secondary-sidebar__collapsed');
		cy.get('.secondary-sidebar-toggle__target').should('have.class', 'collapsed');
		cy.get('.secondary-sidebar-toggle__target > i').should('have.class', 'fa-angle-right');

		cy.get('.list-group li:nth-child(2)').click();
		cy.get('.secondary-sidebar').should('have.class', 'secondary-sidebar__collapsed');
		cy.get('.secondary-sidebar-toggle__target').should('have.class', 'collapsed');
		cy.get('.secondary-sidebar-toggle__target > i').should('have.class', 'fa-angle-right');

		cy.visit('admin.php?/cp/settings/general')
		cy.get('.secondary-sidebar').should('exist')
		cy.get('.secondary-sidebar').should('be.visible')
		cy.get('.secondary-sidebar-toggle').should('exist')
		cy.get('.secondary-sidebar-toggle > a').should('not.have.class', 'collapsed')

		cy.visit('admin.php?/cp/fields')
		cy.get('.secondary-sidebar').should('have.class', 'secondary-sidebar__collapsed');
		cy.get('.secondary-sidebar-toggle__target').should('have.class', 'collapsed');
		cy.get('.secondary-sidebar-toggle__target > i').should('have.class', 'fa-angle-right');
	})

	it('close sidbar for two pages', () => {
		cy.visit('admin.php?/cp/fields')
		cy.get('.secondary-sidebar-toggle').invoke('show');
		cy.get('.secondary-sidebar-toggle > a').first().click();
		cy.get('.secondary-sidebar').should('have.class', 'secondary-sidebar__collapsed');
		cy.get('.secondary-sidebar-toggle__target').should('have.class', 'collapsed');
		cy.get('.secondary-sidebar-toggle__target > i').should('have.class', 'fa-angle-right');

		cy.visit('admin.php?/cp/utilities/communicate')
		cy.get('.secondary-sidebar-toggle').invoke('show');
		cy.get('.secondary-sidebar-toggle > a').first().click();
		cy.get('.secondary-sidebar').should('have.class', 'secondary-sidebar__collapsed');
		cy.get('.secondary-sidebar-toggle__target').should('have.class', 'collapsed');
		cy.get('.secondary-sidebar-toggle__target > i').should('have.class', 'fa-angle-right');

		cy.visit('admin.php?/cp/files')
		cy.get('.secondary-sidebar').should('exist')
		cy.get('.secondary-sidebar').should('be.visible')
		cy.get('.secondary-sidebar-toggle').should('exist')
		cy.get('.secondary-sidebar-toggle > a').should('not.have.class', 'collapsed')

		cy.visit('admin.php?/cp/fields')
		cy.get('.secondary-sidebar-toggle__target').first().click();
		cy.get('.secondary-sidebar-toggle__target').should('not.have.class', 'collapsed')
		cy.get('.secondary-sidebar').should('not.have.class', 'secondary-sidebar__collapsed')
		cy.get('.secondary-sidebar').should('be.visible')

		cy.visit('admin.php?/cp/utilities/communicate')
		cy.get('.secondary-sidebar').should('have.class', 'secondary-sidebar__collapsed');
		cy.get('.secondary-sidebar-toggle__target').should('have.class', 'collapsed');
		cy.get('.secondary-sidebar-toggle__target > i').should('have.class', 'fa-angle-right');
	})

})
