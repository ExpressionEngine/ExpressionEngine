class ForumTab < SitePrism::Section
  element :forum_title, 'input[name="forum__forum_title"]'
  element :forum_body, 'textarea[name="forum__forum_body"]'
  element :forum_id, 'select[name="forum__forum_id"]'
  element :forum_topic_id, 'input[name="forum__forum_topic_id"]'

  # Install forum, create a board, category, and forum
  def install_forum
    visit '/system/index.php?/cp/addons'
    find('ul.toolbar a[href*="cp/addons/install/forum"]').click
    find('ul.toolbar a[href*="cp/addons/settings/forum"]').click

    # Create board
    find('.w-12 a[href*="cp/addons/settings/forum/create/board"]').click
    find('input[name="board_label"]').set 'Board'
    find('.w-12 input[type="submit"]').click

    # Create category
    find('.tbl-search a[href*="cp/addons/settings/forum/create/category/1"]').click
    find('input[name="forum_name"]').set 'Category'
    find('.w-12 input[type="submit"]').click

    # Create forum
    find('.tbl-action a[href*="cp/addons/settings/forum/create/forum/1"]').click
    find('input[name="forum_name"]').set 'Forum'
    find('.w-12 input[type="submit"]').click
  end
end
