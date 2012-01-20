Feature: Authentication
  As a user
  I need to be able to work with my account
  So I can login and change my information

	Scenario: Login
    Given I am on the control panel login page
    And I am logged out
    # When I click the button Login
    Then I should see "password"

    # Given an action
    # When an action
    # Then an outcome
    # And ... (additional steps)
    # But ...

    # This is a table, you can use it to do multiple actions at once
    # When I fill out the following:
    #   | username | admin    |
    #   | password | password |

  # Scenario: Signup
  # Scenario: Forgot your password?