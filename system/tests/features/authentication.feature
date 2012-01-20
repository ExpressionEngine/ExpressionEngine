Feature: Authentication
  As a user
  I need to be able to work with my account
  So I can login and change my information

	Scenario: Login page
    Given I am on the control panel login page
    And I am logged out
    Then I should not see "peanut"
    And I should see "Username"
    And I should see "Password"
    And I should see "Forgot your password?"

  Scenario: Login attempt without credentials
    Given I am on the control panel login page
    And I am logged out
    When I login using the following:
      | username |  |
      | password |  |
    Then I should see "Forgot your password?"
    And I should see "The username field is required."
  
  Scenario: Login attempt with invalid credentials
    Given I am on the control panel login page
    And I am logged out
    When I login using the following:
      | username | noone   |
      | password | nowhere |
    Then I should see "Forgot your password?"
    And I should see "Invalid username or password."

  Scenario: Login attempt with valid credentials
    Given I am on the control panel login page
    And I am logged out
    When I login using the following:
      | username | admin    |
      | password | password |
    Then I should not see "Forgot your password?"
    And I should not see "The username field is required."
    And I should see "SQL queries used"
    And I should see "Modify or delete"