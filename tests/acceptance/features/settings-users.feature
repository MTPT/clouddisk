Feature: settings-users

  Scenario: create a new user with a custom display name
    Given I am logged in as the admin
    And I open the User settings
    When I click the New user button
    And I see that the new user form is shown
    And I set the user name for the new user to "test"
    And I set the display name for the new user to "Test display name"
    And I set the password for the new user to "123456acb"
    And I create the new user
    Then I see that the list of users contains the user "test"
    And I see that the display name for the user "test" is "Test display name"
