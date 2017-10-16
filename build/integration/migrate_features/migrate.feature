Feature: user-migrate

  Scenario: migrate user files
    Given user "user0" exists
    And user "user1" exists
    And User "user0" moved file "/textfile0.txt" to "/target_textfile0.txt"
    When As an "admin"
    And sending "POST" to "/migrate/user" with
      | targetUser     | user1            |
      | remoteUrl      | http://localhost |
      | remoteUser     | user0            |
      | remotePassword | 123456           |
    Then the OCS status code should be "100"
    And as "user1" the file "/target_textfile0.txt" exists
