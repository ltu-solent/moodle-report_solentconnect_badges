@report @report_solentconnect_badges @javascript
Feature: Testing student_completes_badges in report_solentconnect_badges
  As a student
  When I complete a SolentConnect badge
  Then the badge should be listed for export to SolentConnect

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format | enablecompletion |
      | Course 1 | C1        | topics | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following report solentconnect badges config values are set:
      | courseids                           | C1     |
      | accreditationcode                   | ABC123 |
      | accreditationinstitutioncompanycode | ABC123 |
    And the following "activities" exist:
      | activity   | name             | intro     | content        | course | section | idnumber | completion | completionview |
      | page       | GDPR course      | GDPR      | GDPR           | C1     | 1       | GDPR     | 2          | 1              |
    # Type 2 = Course
    # Expire period 63072000 = 730 days (2 years) in seconds
    And the following "core_badges > Badge" exists:
      | name         | GDPR badge                   |
      | course       | C1                           |
      | description  | GDPR badge description       |
      | image        | badges/tests/behat/badge.png |
      | type         | 2                            |
      | status       | 0                            |
      | expireperiod | 63072000                     |
    And I am on the "Course 1" "course" page logged in as "teacher1"
    And I navigate to "Badges" in current page administration
    And I follow "GDPR badge"
    And I select "Criteria" from the "jump" singleselect
    And I set the field "type" to "Activity completion"
    And I set the field "GDPR course" to "1"
    And I press "Save"
    And I press "Enable access"
    And I click on "Enable" "button" in the "Confirm" "dialogue"
    And I log out

  Scenario: There are no issued badges
    Given I am on the "report_solentconnect_badges > report index" page logged in as admin
    And I should see "SolentConnect Badges"
    When I click on "Search" "button" in the "region-main" "region"
    Then I should see "Nothing to display"

  Scenario: A student completes a badge and is queued for export
    Given I am on the "Course 1" "course" page logged in as "student1"
    And I follow "GDPR course"
    Then the "View" completion condition of "GDPR course" is displayed as "done"
    And I log out
    When I am on the "report_solentconnect_badges > report index" page logged in as admin
    And I click on "Search" "button" in the "region-main" "region"
    Then the following should exist in the "report_solentconnect_badges_table" table:
    | First name | AccreditationIssuedDate | AccreditationExpiryDate | AccreditationYearIssued | AccreditationInstitutionCompanyCode | AccreditationComments | Time Exported |
    | Student 1  | ##today##%d/%m/%Y##     | ##+730 days##%d/%m/%Y## | ##today##%Y##           | ABC123                              | GDPR badge            | -             |

  Scenario: Badge awarded for non-watched course is not queued for export
    Given the following "courses" exist:
      | fullname | shortname | format | enablecompletion |
      | Course 2 | C2        | topics | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C2     | editingteacher |
      | student1 | C2     | student        |
    And the following "activities" exist:
      | activity   | name             | intro     | content        | course | section | idnumber | completion | completionview |
      | page       | Bribery course   | Bribery   | Bribery        | C2     | 1       | Bribery  | 2          | 1              |
    And the following "core_badges > Badge" exists:
      | name         | Bribery badge                |
      | course       | C2                           |
      | description  | Bribery badge description    |
      | image        | badges/tests/behat/badge.png |
      | type         | 2                            |
      | status       | 0                            |
      | expireperiod | 63072000                     |
    And I am on the "Course 2" "course" page logged in as "teacher1"
    And I navigate to "Badges" in current page administration
    And I follow "Bribery badge"
    And I select "Criteria" from the "jump" singleselect
    And I set the field "type" to "Activity completion"
    And I set the field "Bribery course" to "1"
    And I press "Save"
    And I press "Enable access"
    And I click on "Enable" "button" in the "Confirm" "dialogue"
    And I log out
    And I am on the "Course 2" "course" page logged in as "student1"
    And I follow "Bribery course"
    Then the "View" completion condition of "Bribery course" is displayed as "done"
    And I log out
    When I am on the "report_solentconnect_badges > report index" page logged in as admin
    And I click on "Search" "button" in the "region-main" "region"
    Then I should not see "Bribery badge"
    And I should not see "student1"
    And I should see "Nothing to display"

  Scenario: Mark an issued badge as Exported and Requeue
    Given I am on the "Course 1" "course" page logged in as "student1"
    And I follow "GDPR course"
    Then the "View" completion condition of "GDPR course" is displayed as "done"
    And I log out
    And I am on the "report_solentconnect_badges > report index" page logged in as admin
    And I click on "Search" "button" in the "region-main" "region"
    When I open the action menu in "Student 1" "table_row"
    And I follow "Mark Exported"
    And I press "Continue"
    Then I should see "Nothing to display"
    When I set the field "Show queued only" to "0"
    And I click on "Search" "button" in the "region-main" "region"
    Then the following should exist in the "report_solentconnect_badges_table" table:
    | First name | AccreditationIssuedDate | AccreditationExpiryDate | AccreditationYearIssued | AccreditationInstitutionCompanyCode | AccreditationComments | Time Exported       |
    | Student 1  | ##today##%d/%m/%Y##     | ##+730 days##%d/%m/%Y## | ##today##%Y##           | ABC123                              | GDPR badge            | ##today##%y-%m-%d## |
    When I open the action menu in "Student 1" "table_row"
    And I follow "Requeue"
    And I press "Continue"
    Then the following should exist in the "report_solentconnect_badges_table" table:
    | First name | AccreditationIssuedDate | AccreditationExpiryDate | AccreditationYearIssued | AccreditationInstitutionCompanyCode | AccreditationComments | Time Exported |
    | Student 1  | ##today##%d/%m/%Y##     | ##+730 days##%d/%m/%Y## | ##today##%Y##           | ABC123                              | GDPR badge            | -             |
