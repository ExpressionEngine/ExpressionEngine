import ControlPanel from '../ControlPanel'

class ForumTab extends ControlPanel {
  constructor() {
    super()
    this.elements({
      'forum_title': 'input[name="forum__forum_title"]',
      'forum_body': 'textarea[name="forum__forum_body"]',
      'forum_id': 'div[data-input-value="forum__forum_id"]',
      'forum_id_choices': 'div[data-input-value="forum__forum_id"] .field-drop-choices label',
      'forum_topic_id': 'input[name="forum__forum_topic_id"]'
    })
  }

  // Install forum, create a board, category, and forum
  install_forum() {
    cy.authVisit('/admin.php?/cp/addons')
    cy.get('ul.toolbar a[data-post-url*="cp/addons/install/forum"]').click()
    cy.get('ul.toolbar a[href*="cp/addons/settings/forum"]').click()

    // Create board
    cy.get('.w-12 a[href*="cp/addons/settings/forum/create/board"]').click()
    cy.get('input[name="board_label"]').type('Board')
    cy.get('.w-12 button[value="save_and_close"]:first-child').click()

    // Create category
    cy.get('.tbl-search a[href*="cp/addons/settings/forum/create/category/1"]').click()
    cy.get('input[name="forum_name"]').type('Category')
    cy.get('.w-12 button[value="save_and_close"]:first-child').click()

    // Create forum
    cy.get('.tbl-action a[href*="cp/addons/settings/forum/create/forum/1"]').click()
    cy.get('input[name="forum_name"]').type('Forum')
    cy.get('.w-12 button[value="save_and_close"]:first-child').click()
  }
}
export default ForumTab;