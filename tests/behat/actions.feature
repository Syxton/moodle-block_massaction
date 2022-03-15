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
    # I have to use this xpath_element because moodle behat misinterpretes the hide icon in the link as 'not visible'
    # and fails because of it
    And I click on "//i[@aria-label='Hide']" "xpath_element" in the "Mass Actions" "block"
    Then "Test Activity1" activity should be hidden
    And "Test Activity4" activity should be hidden
    When I click on "Test Activity1 Checkbox" "checkbox"
    And I click on "Test Activity4 Checkbox" "checkbox"
    And I click on "Show" "link" in the "Mass Actions" "block"
    Then "Test Activity1" activity should be visible
    And "Test Activity4" activity should be visible
    When I click on "Test Activity1 Checkbox" "checkbox"
    And I click on "Test Activity4 Checkbox" "checkbox"
    And I click on "Make available" "link" in the "Mass Actions" "block"
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
    And I set the field "Move to section" in the "Mass Actions" "block" to "Topic 3"
    And I click on "Move to section" "link" in the "Mass Actions" "block"
    Then I should see "Test Activity1" in the "Topic 3" "section"
    And I should see "Test Activity4" in the "Topic 3" "section"

  @javascript
  Scenario: Check if mass actions 'indent' and 'outdent' work
    When I click on "Test Activity2 Checkbox" "checkbox"
    And I click on "Test Activity5 Checkbox" "checkbox"
    And I click on "Indent (move right)" "link" in the "Mass Actions" "block"
    Then "#section-1 li.page div.mod-indent-1" "css_element" should exist
    Then "#section-4 li.assign div.mod-indent-1" "css_element" should exist
    When I click on "Test Activity2 Checkbox" "checkbox"
    And I click on "Test Activity5 Checkbox" "checkbox"
    And I click on "Outdent (move left)" "link" in the "Mass Actions" "block"
    Then "#section-1 li.page div.mod-indent-1" "css_element" should not exist
    Then "#section-4 li.assign div.mod-indent-1" "css_element" should not exist

  @javascript
  Scenario: Check if mass action 'delete' works
    When I click on "Test Activity1 Checkbox" "checkbox"
    And I click on "Test Activity4 Checkbox" "checkbox"
    # Again moodle behat has some issues for no understandable reason, so I have to use xpath again.
    And I click on "//i[@aria-label='Delete']" "xpath_element" in the "Mass Actions" "block"
    And I click on "Delete" "button"
    Then I should not see "Test Activity1"
    And I should not see "Test Activity4"

  @javascript
  Scenario: Check if mass action 'duplicate' works
    When I click on "Test Activity2 Checkbox" "checkbox"
    And I click on "Test Activity4 Checkbox" "checkbox"
    And I click on "Test Activity5 Checkbox" "checkbox"
    And I click on "Duplicate" "link" in the "Mass Actions" "block"
    Then I should see "Test Activity2 (copy)" in the "Topic 1" "section"
    And I should see "Test Activity4 (copy)" in the "Topic 4" "section"
    And I should see "Test Activity5 (copy)" in the "Topic 4" "section"

  @javascript
  Scenario: Check if mass action 'duplicate to section' works
    When I click on "Test Activity2 Checkbox" "checkbox"
    And I click on "Test Activity4 Checkbox" "checkbox"
    And I click on "Test Activity5 Checkbox" "checkbox"
    And I set the field "Duplicate to section" in the "Mass Actions" "block" to "Topic 3"
    And I click on "Duplicate to section" "link" in the "Mass Actions" "block"
    Then I should see "Test Activity2" in the "Topic 1" "section"
    And I should see "Test Activity4" in the "Topic 4" "section"
    And I should see "Test Activity5" in the "Topic 4" "section"
    And I should see "Test Activity2 (copy)" in the "Topic 3" "section"
    And I should see "Test Activity4 (copy)" in the "Topic 3" "section"
    And I should see "Test Activity5 (copy)" in the "Topic 3" "section"
