require 'securerandom'

# Common error language
$required_error = 'This field is required.'
$integer_error = 'This field must contain an integer.'
$natural_number = 'This field must contain only positive numbers.'
$natural_number_not_zero = 'This field must contain a number greater than zero.'
$numeric = 'This field must contain only numeric characters.'
$greater_than = 'This field must be greater than'
$invalid_path = 'The path you submitted is not valid.'
$not_writable = 'The path you submitted is not writable.'
$alpha_dash = 'This field may only contain alpha-numeric characters, underscores, and dashes.'
$hex_color = 'This field must contain a valid hex color code.'
$unique = 'This field must be unique.'

$xss_error = 'The data you submitted did not pass our security check.'
$xss_vector = '"><script>alert(\'stored xss\')<%2fscript>'

# Use this to visit pages no ensure to PHP errors exist on page load
def go_to(url)
  Capybara.current_session.visit url
  no_php_js_errors
end

# Runs when a page is visted and after every example to ensure no
# PHP or JavaScript errors are present
def no_php_js_errors
	# Search for "on line" or "Line Number:" since they're in pretty much
  # in every PHP error
  if not page.current_url.include? 'logs/developer'
    page.should have_no_content('on line')
  end
  page.should have_no_content('Line Number:')

  page.should have_no_content('Exception Caught')

	# Capybara makes JS error messages available in this array,
	# let's make sure it's empty; we can also check for any console
	# message but we're just checking for errors now
  if page.driver.error_messages.any?
    raise StandardError, "JS Error: " + page.driver.error_messages.join(" ")
  end
end

# Checks the form for errors
def should_have_form_errors(page_obj, errors=true)
  page_obj.submit_enabled?.should eq !errors
  page_obj.has_fieldset_errors?.should eq errors
end

# Checks that a form has no errors
def should_have_no_form_errors(page_obj)
  should_have_form_errors(page_obj, false)
end

# Checks for show_error()
def should_have_show_error(message)
  page.has_content?('An Error Was Encountered').should == true
  page.has_content?(message).should == true
  page.status_code.should == 500
end

def should_have_error_text(node, text)
  node.first(:xpath, ".//ancestor::fieldset[1]")[:class].should include 'invalid'
  node.first(:xpath, ".//..").should have_css 'em.ee-form-error-message'
  node.first(:xpath, ".//..").should have_text text
end

def should_have_no_error_text(node)
  node.first(:xpath, ".//ancestor::fieldset[1]")[:class].should_not include 'invalid'
  node.first(:xpath, ".//..").should have_no_css 'em.ee-form-error-message'
end

# Grabs div.setting-txt to check for invalid class, as opposed to the parent fieldset
def grid_should_have_error(node)
  node.first(:xpath, ".//ancestor::div[3]/div[1]")[:class].should include 'invalid'
end

def grid_should_have_no_error(node)
  node.first(:xpath, ".//ancestor::div[3]/div[1]")[:class].should_not include 'invalid'
end

def grid_cell_should_have_error_text(node, text)
  node.first(:xpath, ".//ancestor::td[1]")[:class].should include 'invalid'
  node.first(:xpath, ".//..").should have_css 'em.ee-form-error-message'
  node.first(:xpath, ".//..").should have_text text
end

def grid_cell_should_have_no_error_text(node)
  node.first(:xpath, ".//ancestor::td[1]")[:class].should_not include 'invalid'
  node.first(:xpath, ".//..").should have_no_css 'em.ee-form-error-message'
end

# Wait for any pending AJAX requests
def wait_for_ajax
  ajax = false
  while ajax == false do
    ajax = (page.evaluate_script('$.active') == 0)
  end
end

# Wait for DOM to be ready
def wait_for_dom
  uuid = SecureRandom.uuid
  page.find("body")
  page.evaluate_script <<-EOS
    _.defer(function() {
      $('body').append("<div id='#{uuid}'></div>");
    });
  EOS
  page.find("##{uuid}")
end

# Cleans the datbase and resets Capybara sessions, takes a block that's executed
# after cleaning the database
#
# @return [void]
def clean_db
  $db.query(IO.read('sql/truncate_db.sql'))
  clear_db_result

  yield if block_given?

  Capybara.reset_sessions!
end

# Reset the DB to a clean slate and reset sessions
def reset_db(test_file = '')
  clean_db do
    if test_file == 'updater'
      $db.query(IO.read('sql/database_2.10.1.sql'))
      clear_db_result
    elsif test_file != 'installer'
      $db.query(IO.read('sql/database.sql'))
      clear_db_result
    end
  end
end

# Clear the DB result so we can use the DB object again
def clear_db_result
  while $db.next_result
    $db.store_result rescue ''
  end
end

# Sets up a CP session for CP tests; could also be used for front-end
def cp_session
  Login::login
end

# Given a filename in the support folder, returns the whole path relative
# to the CP index.php
def asset_path(file)
  '../tests/rspec/support/' + file
end

# Silly thing for comparing HTML in a textarea, Capybara will return the
# value with brackets encoded and whitespace replaced with a single space
def capybaraify_string(str)
  str = str.gsub('<', '&lt;')
  str = str.gsub('>', '&gt;')
  str = str.gsub('/\s+/', ' ')
  return str
end

def add_member(group_id: 5, username: 'johndoe', screen_name: 'John Doe', email: nil)
  command = "cd fixtures && php member.php"

  if group_id
    command += " --group-id " + group_id.to_s
  end

  if username
    command += " --username '" + username.to_s + "'"
  end

  if screen_name
    command += " --screen-name '" + screen_name.to_s + "'"
  end

  if email
    command += " --email '" + email.to_s + "'"
  end

  command += " > /dev/null 2>&1"

  system(command)
end

def ee_config(site_id: nil, item: nil, value: nil)
  if item
    command = "cd fixtures && php config.php " + item.to_s

    if value
      command += " '" + value.to_s + "'"
    end

    if site_id
      command += " --site-id " + site_id.to_s
    end

    # Capture stdout but ignore stderr
    Open3.popen3(command) do |stdin, stdout, stderr, thread|
      value = stdout.read.lines.last
      if value == 'empty'
        return ''
      end
      return value
    end
  end
end
