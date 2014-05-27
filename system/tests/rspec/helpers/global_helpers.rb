# Use this to visit pages no ensure to PHP errors exist on page load
def go_to(url)
  Capybara.current_session.visit url
  no_php_js_errors
end

# Runs when a page is visted and after every example to ensure no
# PHP or JavaScript errors are present
def no_php_js_errors
	# Search for "on line" or "Line Number:" since they're in pretty much
  page.should have_no_content('on line')
  page.should have_no_content('Line Number:')

	# Capybara makes JS error messages available in this array,
	# let's make sure it's empty; we can also check for any console
	# message but we're just checking for errors now
  if page.driver.error_messages.any?
    raise StandardError, "JS Error: " + page.driver.error_messages.join(" ")
  end
end

# Reset the DB to a clean slate and reset sessions
def reset_db
  $db.query(IO.read('sql/truncate_db.sql'))
  clear_db_result

  $db.query(IO.read('sql/database.sql'))
  clear_db_result

  # Reset sessions
  Capybara.reset_sessions!
end

# Clear the DB result so we can use the DB object again
def clear_db_result
  while $db.next_result
    $db.store_result rescue ''
  end
end

# Sets up a CP session for CP tests; could also be used for front-end
def cp_session
  Login::visit
  Login::login
end