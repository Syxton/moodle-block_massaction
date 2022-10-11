@block @block_massaction @block_massaction_actions
Feature: Check if all the different type of actions of the mass actions block work

  Background:
    Given the following config values are set as admin:
      | allowstealth | 1 |
    And the following "courses" exist:
      | fullname        | shortname | numsections | format  |
      | Test course     | TC        | 5           | topics  |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Mr        | Teacher  | teacher1@example.com |
      | student1 | Guy       | Student  | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | TC     | editingteacher |
      | student1 | TC     | student        |
    And the following "activities" exist:
      | activity | course | idnumber | name           | intro                 | section |
      | page     | TC     | 1        | Test Activity1 | Test page description | 0       |
      | page     | TC     | 2        | Test Activity2 | Test page description | 1       |
      | label    | TC     | 3        | Test Activity3 | Label text            | 2       |
      | page     | TC     | 4        | Test Activity4 | Test page description | 4       |
      | assign   | TC     | 5        | Test Activity5 | Test page description | 4       |
    When I log in as "teacher1"
    And I am on "Test course" course homepage with editing mode on
    And I add the "Mass Actions" block

  @javascript
  Scenario: Check if mass actions 'hide' and 'show' work
    When I click on "Test Activity1 Checkbox" "checkbox"
    And I click on "Test Activity4 Checkbox" "checkbox"
    And I click on "Hide" "button" in the "Mass Actions" "block"
    Then "Test Activity1" activity should be hidden
    And "Test Activity4" activity should be hidden
    When I click on "Test Activity1 Checkbox" "checkbox"
    And I click on "Test Activity4 Checkbox" "checkbox"
    And I click on "Show" "button" in the "Mass Actions" "block"
    Then "Test Activity1" activity should be visible
    And "Test Activity4" activity should be visible
    When I click on "Test Activity1 Checkbox" "checkbox"
    And I click on "Test Activity4 Checkbox" "checkbox"
    And I click on "Make available" "button" in the "Mass Actions" "block"
    And I open "Test Activity1" actions menu
    Then "Test Activity1" actions menu should have "Make unavailable" item
    When I open "Test Activity4" actions menu
    Then "Test Activity4" actions menu should have "Make unavailable" item
    And I log out
    When I log in as "student1"
    And I am on "Test course" course homepage
    Then I should not see "Test Activity1"
    And I should not see "Test Activity4"

  @javascript
  Scenario: Check if mass action 'move to section' works
    When I click on "Test Activity1 Checkbox" "checkbox"
    And I click on "Test Activity4 Checkbox" "checkbox"
    And I set the field "target_section_moving" in the "Mass Actions" "block" to "Topic 3"
    And I click on "move_to_section" "button" in the "Mass Actions" "block"
    Then I should see "Test Activity1" in the "Topic 3" "section"
    And I should see "Test Activity4" in the "Topic 3" "section"

  @javascript
  Scenario: Check if mass action 'delete' works
    When I click on "Test Activity1 Checkbox" "checkbox"
    And I click on "Test Activity4 Checkbox" "checkbox"
    And I click on "Delete" "button" in the "Mass Actions" "block"
    And I click on "Delete" "button"
    Then I should not see "Test Activity1"
    And I should not see "Test Activity4"

  @javascript
  Scenario: Check if mass action 'duplicate' works
    When I click on "Test Activity2 Checkbox" "checkbox"
    And I click on "Test Activity4 Checkbox" "checkbox"
    And I click on "Test Activity5 Checkbox" "checkbox"
    And I click on "Duplicate" "button" in the "Mass Actions" "block"
    Then I should see "Test Activity2 (copy)" in the "Topic 1" "section"
    And I should see "Test Activity4 (copy)" in the "Topic 4" "section"
    And I should see "Test Activity5 (copy)" in the "Topic 4" "section"

  @javascript
  Scenario: Check if mass action 'duplicate to course' works (keeping sections)
    Given the following "courses" exist:
      | fullname        | shortname | numsections | format  |
      | Test course 2   | TC2       | 2           | topics  |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | TC2    | editingteacher |
    When I click on "Test Activity2 Checkbox" "checkbox"
    And I click on "Test Activity4 Checkbox" "checkbox"
    And I click on "Test Activity5 Checkbox" "checkbox"
    And I click on "Duplicate to another course" "button" in the "Mass Actions" "block"
    And I open the autocomplete suggestions list
    And I click on "Test course 2" item in the autocomplete list
    And I click on "Choose course" "button"
    And I click on "Keep original section number" "radio"
    And I click on "Choose section" "button"
    And I am on "Test course 2" course homepage
    Then I should see "Test Activity2" in the "Topic 1" "section"
    And I should see "Test Activity4" in the "Topic 4" "section"
    And I should see "Test Activity5" in the "Topic 4" "section"

  @javascript
  Scenario: Check if mass action 'duplicate to course' works (target section)
    Given the following "courses" exist:
      | fullname        | shortname | numsections | format  |
      | Test course 2   | TC2       | 2           | topics  |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | TC2    | editingteacher |
    When I click on "Test Activity2 Checkbox" "checkbox"
    And I click on "Test Activity4 Checkbox" "checkbox"
    And I click on "Test Activity5 Checkbox" "checkbox"
    And I click on "Duplicate to another course" "button" in the "Mass Actions" "block"
    And I open the autocomplete suggestions list
    And I click on "Test course 2" item in the autocomplete list
    And I click on "Choose course" "button"
    And I click on "Section 2" "radio"
    And I click on "Choose section" "button"
    And I am on "Test course 2" course homepage
    Then I should see "Test Activity2" in the "Topic 2" "section"
    And I should see "Test Activity4" in the "Topic 2" "section"
    And I should see "Test Activity5" in the "Topic 2" "section"

  @javascript
  Scenario: Check if mass action 'duplicate to course' works (new section)
    Given the following "courses" exist:
      | fullname        | shortname | numsections | format  |
      | Test course 2   | TC2       | 2           | topics  |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | TC2    | editingteacher |
    When I click on "Test Activity2 Checkbox" "checkbox"
    And I click on "Test Activity4 Checkbox" "checkbox"
    And I click on "Test Activity5 Checkbox" "checkbox"
    And I click on "Duplicate to another course" "button" in the "Mass Actions" "block"
    And I open the autocomplete suggestions list
    And I click on "Test course 2" item in the autocomplete list
    And I click on "Choose course" "button"
    And I click on "New Section" "radio"
    And I click on "Choose section" "button"
    And I am on "Test course 2" course homepage
    Then I should see "Test Activity2" in the "Topic 3" "section"
    And I should see "Test Activity4" in the "Topic 3" "section"
    And I should see "Test Activity5" in the "Topic 3" "section"

  @javascript
  Scenario: Check if mass action 'duplicate to section' works
    When I click on "Test Activity2 Checkbox" "checkbox"
    And I click on "Test Activity4 Checkbox" "checkbox"
    And I click on "Test Activity5 Checkbox" "checkbox"
    And I set the field "target_section_duplicating" in the "Mass Actions" "block" to "Topic 3"
    And I click on "duplicate_to_section" "button" in the "Mass Actions" "block"
    Then I should see "Test Activity2" in the "Topic 1" "section"
    And I should see "Test Activity4" in the "Topic 4" "section"
    And I should see "Test Activity5" in the "Topic 4" "section"
    And I should see "Test Activity2 (copy)" in the "Topic 3" "section"
    And I should see "Test Activity4 (copy)" in the "Topic 3" "section"
    And I should see "Test Activity5 (copy)" in the "Topic 3" "section"
