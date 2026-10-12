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
      | student2 | Student   | 2        | student2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "activity" exists:
      | activity                            | assign                  |
      | course                              | C1                      |
      | section                             | 1                       |
      | name                                | Test assignment name    |
      | intro                               | Submit your online text |
      | completion                          | 1                       |
      | assignsubmission_onlinetext_enabled | 1                       |
    And the following "core_badges > Badge" exists:
      | name        | Course Badge                 |
      | course      | C1                           |
      | description | Course badge description     |
      | image       | badges/tests/behat/badge.png |
      | status      | 0                            |
      | type        | 2                            |
    And the following "core_badges > Criteria" exists:
      | badge        | Course Badge   |
      | criteria     | 1              |
      | role         | editingteacher |
    And the following config values are set as admin:
      | config                              | value  | plugin                      |
      | courseids                           | C1     | report_solentconnect_badges |
      | accreditationcode                   | ABC123 | report_solentconnect_badges |
      | accreditationinstitutioncompanycode | ABC123 | report_solentconnect_badges |
    And the following report solentconnect badges config values are set:
      | courseids                           | C1     |
      | accreditationcode                   | ABC123 |
      | accreditationinstitutioncompanycode | ABC123 |

    Scenario: There are no issued badges
      Given I am on the "report_solentconnect_badges > report index" page logged in as admin
      And I should see "SolentConnect Badges"
      When I click on "Search" "button" in the "region-main" "region"
      Then I should see "Nothing to display"

    Scenario: A student completes a badge
      Given the following "core_badges > Issued badge" exists:
      | badge        | Course Badge   |
      | user         | student1      |
      | dateissued   | 2026-01-01    |
      | dateexpire   | 2026-12-31    |
      And I am on the "report_solentconnect_badges > report index" page logged in as admin
      When I click on "Search" "button" in the "region-main" "region"
      Then I should see "Course Badge"
      And I should see "student1"
      And I should see "2026-01-01"
      And I should see "2026-12-31"
      

