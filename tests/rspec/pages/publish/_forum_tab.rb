class ForumTab < SitePrism::Section
  element :forum_title, 'input[name="forum__forum_title"]'
  element :forum_body, 'textarea[name="forum__forum_body"]'
  element :forum_id, 'div[data-dropdown-react][data-input-value="forum__forum_id"]'
  element :forum_topic_id, 'input[name="forum__forum_topic_id"]'

  # Install forum, create a board, category, and forum
  def install_forum
    visit '/system/index.php?/cp/addons'
    find('ul.toolbar a[data-post-url*="cp/addons/install/forum"]').click
    find('ul.toolbar a[href*="cp/addons/settings/forum"]').click

    # Create board
    find('.w-12 a[href*="cp/addons/settings/forum/create/board"]').click
    find('input[name="board_label"]').set 'Board'
    all('.w-12 input[type="submit"]')[0].click

    # Create category
    find('.tbl-search a[href*="cp/addons/settings/forum/create/category/1"]').click
    find('input[name="forum_name"]').set 'Category'
    all('.w-12 input[type="submit"]')[0].click

    # Create forum
    find('.tbl-action a[href*="cp/addons/settings/forum/create/forum/1"]').click
    find('input[name="forum_name"]').set 'Forum'
    all('.w-12 input[type="submit"]')[0].click
  end
end
