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
      | activity | course | idnumber | name           | intro                  | section |
      | page     | TC     | 1        | Test Activity1 | Test page description1 | 0       |
      | page     | TC     | 2        | Test Activity2 | Test page description2 | 1       |
      | label    | TC     | 3        | Test Activity3 | Test Activity3         | 2       |
      | page     | TC     | 4        | Test Activity4 | Test page description4 | 4       |
      | assign   | TC     | 5        | Test Activity5 | Test page description5 | 4       |
    And the following "blocks" exist:
      | blockname       | contextlevel | reference | pagetypepattern | defaultregion |
      | recent_activity | Course       | TC        | course-view-*   | side-pre      |
    When I log in as "teacher1"
    And I am on "Test course" course homepage with editing mode on
    And I add the "Mass Actions" block
    And I click on "Enable bulk editing" "button"

  @javascript
  Scenario: Check if mass actions 'hide' and 'show' work
    When I click on "Test Activity1" "checkbox"
    And I click on "Test Activity4" "checkbox"
    And I click on "Hide" "button" in the "Mass Actions" "block"
    Then "Test Activity1" activity should be hidden
    And "Test Activity4" activity should be hidden
    When I click on "Enable bulk editing" "button"
    And I click on "Test Activity1" "checkbox"
    And I click on "Test Activity4" "checkbox"
    And I click on "Show" "button" in the "Mass Actions" "block"
    Then "Test Activity1" activity should be visible
    And "Test Activity4" activity should be visible
    When I click on "Enable bulk editing" "button"
    And I click on "Test Activity1" "checkbox"
    And I click on "Make available" "button" in the "Mass Actions" "block"
    Then I should see "Available but not shown on course page" in the "Test Activity1" "activity"
    When I hide section "4"
    And I click on "Enable bulk editing" "button"
    And I click on "Test Activity4" "checkbox"
    And I click on "Test Activity5" "checkbox"
    And I click on "Make available" "button" in the "Mass Actions" "block"
    And I should see "Available but not shown on course page" in the "Test Activity4" "activity"
    And I should see "Available but not shown on course page" in the "Test Activity5" "activity"
    When I click on "Enable bulk editing" "button"
    And I click on "Test Activity5" "checkbox"
    And I click on "Hide" "button" in the "Mass Actions" "block"
    And "Test Activity5" activity should be hidden
    And I log out
    When I log in as "student1"
    And I am on "Test course" course homepage
    Then "Test Activity1" activity should be hidden
    And I should see "Test Activity1" in the "Recent activity" "block"
    When I click on "Test Activity1" "link" in the "Recent activity" "block"
    And I should see "Test Activity1"
    When I click on "TC" "link"
    And "Test Activity4" activity should be hidden
    And I should see "Test Activity4" in the "Recent activity" "block"
    When I click on "Test Activity4" "link" in the "Recent activity" "block"
    And I should see "Test Activity4"
    When I click on "TC" "link"
    And "Test Activity5" activity should be hidden
    And I should not see "Test Activity5" in the "Recent activity" "block"

  @javascript
  Scenario: Check if mass action 'move to section' works
    When I click on "Test Activity1" "checkbox"
    And I click on "Test Activity4" "checkbox"
    And I set the field "target_section_moving" in the "Mass Actions" "block" to "3"
    And I click on "move_to_section" "button" in the "Mass Actions" "block"
    Then I should see "Test Activity1" in the "#section-3" "css_element"
    And I should see "Test Activity4" in the "#section-3" "css_element"

  @javascript
  Scenario: Check if mass action 'delete' works
    When I click on "Test Activity1" "checkbox"
    And I click on "Test Activity4" "checkbox"
    And I click on "Delete" "button" in the "Mass Actions" "block"
    And I click on "Delete" "button"
    Then I should not see "Test Activity1"
    And I should not see "Test Activity4"

  @javascript
  Scenario: Check if mass actions 'showdescription' and 'hidedescription' work
    When I am on "Test course" course homepage
    Then I should not see "Test page description1"
    And I should not see "Test page description4"
    When I click on "Enable bulk editing" "button"
    And I click on "Test Activity1" "checkbox"
    And I click on "Test Activity4" "checkbox"
    And I click on "Show description" "button" in the "Mass Actions" "block"
    Then I should see "Test page description1"
    And I should see "Test page description4"
    When I click on "Enable bulk editing" "button"
    And I click on "Test Activity1" "checkbox"
    And I click on "Test Activity4" "checkbox"
    And I click on "Hide description" "button" in the "Mass Actions" "block"
    Then I should see "Test Activity1"
    And I should see "Test Activity4"
    And I should not see "Test page description1"
    And I should not see "Test page description4"

  @javascript
  Scenario: Check if mass action 'duplicate' works
    When I click on "Test Activity2" "checkbox"
    And I click on "Test Activity4" "checkbox"
    And I click on "Test Activity5" "checkbox"
    And I click on "Duplicate" "button" in the "Mass Actions" "block"
    Then I should see "Test Activity2 (copy)" in the "#section-1" "css_element"
    And I should see "Test Activity4 (copy)" in the "#section-4" "css_element"
    And I should see "Test Activity5 (copy)" in the "#section-4" "css_element"
  @javascript
  Scenario: Check if mass action 'duplicate to course' works (keeping sections)
    Given the following "courses" exist:
      | fullname        | shortname | numsections | format  |
      | Test course 2   | TC2       | 2           | topics  |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | TC2    | editingteacher |
    When I click on "Test Activity2" "checkbox"
    And I click on "Test Activity4" "checkbox"
    And I click on "Test Activity5" "checkbox"
    And I click on "Duplicate to another course" "button" in the "Mass Actions" "block"
    And I open the autocomplete suggestions list
    And I click on "Test course 2" item in the autocomplete list
    And I click on "Choose course" "button"
    And I click on "Keep original section number" "radio"
    And I click on "Choose section" "button"
    And I am on "Test course 2" course homepage
    Then I should see "Test Activity2" in the "#section-1" "css_element"
    And I should see "Test Activity4" in the "#section-4" "css_element"
    And I should see "Test Activity5" in the "#section-4" "css_element"

  @javascript
  Scenario: Check if mass action 'duplicate to course' works (target section)
    Given the following "courses" exist:
      | fullname        | shortname | numsections | format  |
      | Test course 2   | TC2       | 2           | topics  |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | TC2    | editingteacher |
    When I click on "Test Activity2" "checkbox"
    And I click on "Test Activity4" "checkbox"
    And I click on "Test Activity5" "checkbox"
    And I click on "Duplicate to another course" "button" in the "Mass Actions" "block"
    And I open the autocomplete suggestions list
    And I click on "Test course 2" item in the autocomplete list
    And I click on "Choose course" "button"
    And I click on "Section 2" "radio"
    And I click on "Choose section" "button"
    And I am on "Test course 2" course homepage
    Then I should see "Test Activity2" in the "#section-2" "css_element"
    And I should see "Test Activity4" in the "#section-2" "css_element"
    And I should see "Test Activity5" in the "#section-2" "css_element"

  @javascript
  Scenario: Check if mass action 'duplicate to course' works (new section)
    Given the following "courses" exist:
      | fullname        | shortname | numsections | format  |
      | Test course 2   | TC2       | 2           | topics  |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | TC2    | editingteacher |
    When I click on "Test Activity2" "checkbox"
    And I click on "Test Activity4" "checkbox"
    And I click on "Test Activity5" "checkbox"
    And I click on "Duplicate to another course" "button" in the "Mass Actions" "block"
    And I open the autocomplete suggestions list
    And I click on "Test course 2" item in the autocomplete list
    And I click on "Choose course" "button"
    And I click on "New Section" "radio"
    And I click on "Choose section" "button"
    And I am on "Test course 2" course homepage
    Then I should see "Test Activity2" in the "#section-3" "css_element"
    And I should see "Test Activity4" in the "#section-3" "css_element"
    And I should see "Test Activity5" in the "#section-3" "css_element"

  @javascript
  Scenario: Check if mass action 'duplicate to section' works
    When I click on "Test Activity2" "checkbox"
    And I click on "Test Activity4" "checkbox"
    And I click on "Test Activity5" "checkbox"
    And I set the field "target_section_duplicating" in the "Mass Actions" "block" to "3"
    And I click on "duplicate_to_section" "button" in the "Mass Actions" "block"
    Then I should see "Test Activity2" in the "#section-1" "css_element"
    And I should see "Test Activity4" in the "#section-4" "css_element"
    And I should see "Test Activity5" in the "#section-4" "css_element"
    And I should see "Test Activity2 (copy)" in the "#section-3" "css_element"
    And I should see "Test Activity4 (copy)" in the "#section-3" "css_element"
    And I should see "Test Activity5 (copy)" in the "#section-3" "css_element"

  @javascript
  Scenario: Check if mass actions 'indent' and 'outdent' work
    When I click on "Test Activity2" "checkbox"
    And I click on "Test Activity5" "checkbox"
    And I click on "Indent (move right)" "button" in the "Mass Actions" "block"
    Then "#section-1 li.modtype_page.indented" "css_element" should exist
    Then "#section-4 li.modtype_assign.indented" "css_element" should exist
    When I click on "Enable bulk editing" "button"
    And I click on "Test Activity2" "checkbox"
    And I click on "Test Activity5" "checkbox"
    And I click on "Outdent (move left)" "button" in the "Mass Actions" "block"
    Then "#section-1 li.modtype_page.indented" "css_element" should not exist
    Then "#section-4 li.modtype_assign.indented" "css_element" should not exist
