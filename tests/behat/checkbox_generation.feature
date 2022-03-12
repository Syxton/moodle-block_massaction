@block @block_massaction @block_massaction_checkbox_generation
Feature: Check if block generates all necessary checkboxes in all the supported course formats and properly disables
  the currently not active sections (or sections not containing any modules)

  @javascript
  Scenario: Check if checkboxes are created properly for onetopic format
    Given onetopic_course_format_is_installed
    And the following "courses" exist:
      | fullname        | shortname | numsections | format   |
      | Test course     | TC        | 5           | onetopic |
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
      | page     | TC     | 5        | Test Activity5 | Test page description | 4       |
    When I log in as "teacher1"
    And I am on "Test course" course homepage with editing mode on
    And I add the "Mass Actions" block
    When I follow "General"
    And I click on "Test Activity1 Checkbox" "checkbox"
    Then the field "Test Activity1 Checkbox" matches value "1"
    Then the "disabled" attribute of "#block-massaction-control-section-list-select-option-0" "css_element" should not be set
    Then the "disabled" attribute of "#block-massaction-control-section-list-select-option-1" "css_element" should be set
    Then the "disabled" attribute of "#block-massaction-control-section-list-select-option-2" "css_element" should be set
    Then the "disabled" attribute of "#block-massaction-control-section-list-select-option-3" "css_element" should be set
    Then the "disabled" attribute of "#block-massaction-control-section-list-select-option-4" "css_element" should be set
    When I follow "Topic 4"
    And I click on "Test Activity4 Checkbox" "checkbox"
    Then the field "Test Activity4 Checkbox" matches value "1"
    When I follow "Topic 2"
    And I click on "Label text Checkbox" "checkbox"
    Then the field "Label text Checkbox" matches value "1"
    Then the "disabled" attribute of "#block-massaction-control-section-list-select-option-0" "css_element" should be set
    Then the "disabled" attribute of "#block-massaction-control-section-list-select-option-1" "css_element" should be set
    Then the "disabled" attribute of "#block-massaction-control-section-list-select-option-2" "css_element" should not be set
    Then the "disabled" attribute of "#block-massaction-control-section-list-select-option-3" "css_element" should be set
    Then the "disabled" attribute of "#block-massaction-control-section-list-select-option-4" "css_element" should be set
    When I follow "Topic 3"
    Then the "disabled" attribute of "#block-massaction-control-section-list-select-option-0" "css_element" should be set
    Then the "disabled" attribute of "#block-massaction-control-section-list-select-option-1" "css_element" should be set
    Then the "disabled" attribute of "#block-massaction-control-section-list-select-option-2" "css_element" should be set
    Then the "disabled" attribute of "#block-massaction-control-section-list-select-option-3" "css_element" should be set
    Then the "disabled" attribute of "#block-massaction-control-section-list-select-option-4" "css_element" should be set

  @javascript
  Scenario Outline: Check if checkboxes are created properly for week, topic formats
    Given the following "courses" exist:
      | fullname        | shortname | numsections | format         |
      | Test course     | TC        | 5           | <courseformat> |
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
      | page     | TC     | 5        | Test Activity5 | Test page description | 4       |
    And the following config values are set as admin:
      | config                 | value | plugin       |
      | assumedatastoreconsent | 1     | format_tiles |
    When I log in as "teacher1"
    And I am on "Test course" course homepage with editing mode on
    And I add the "Mass Actions" block
    And I click on "Test Activity1 Checkbox" "checkbox"
    And I click on "Test Activity4 Checkbox" "checkbox"
    Then the field "Test Activity1 Checkbox" matches value "1"
    Then the field "Test Activity2 Checkbox" matches value ""
    Then the field "Label text Checkbox" matches value ""
    Then the field "Test Activity4 Checkbox" matches value "1"
    Then the field "Test Activity5 Checkbox" matches value ""
    Then the "disabled" attribute of "#block-massaction-control-section-list-select-option-0" "css_element" should not be set
    Then the "disabled" attribute of "#block-massaction-control-section-list-select-option-1" "css_element" should not be set
    Then the "disabled" attribute of "#block-massaction-control-section-list-select-option-2" "css_element" should not be set
    Then the "disabled" attribute of "#block-massaction-control-section-list-select-option-3" "css_element" should be set
    Then the "disabled" attribute of "#block-massaction-control-section-list-select-option-4" "css_element" should not be set
    Examples:
      | courseformat |
      | weeks        |
      | topics       |

  @javascript
  Scenario Outline: Check if checkboxes are created properly for topcoll and tiles format
    Given tiles_course_format_is_installed
    And topcoll_course_format_is_installed
    And the following "courses" exist:
      | fullname        | shortname | numsections | format         |
      | Test course     | TC        | 5           | <courseformat> |
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
      | page     | TC     | 5        | Test Activity5 | Test page description | 4       |
    And the following config values are set as admin:
      | config                 | value | plugin       |
      | assumedatastoreconsent | 1     | format_tiles |
    When I log in as "teacher1"
    And I am on "Test course" course homepage with editing mode on
    And I click on <expandsections>
    And I add the "Mass Actions" block
    And I click on "Test Activity1 Checkbox" "checkbox"
    And I click on "Test Activity4 Checkbox" "checkbox"
    Then the field "Test Activity1 Checkbox" matches value "1"
    Then the field "Test Activity2 Checkbox" matches value ""
    Then the field "Label text Checkbox" matches value ""
    Then the field "Test Activity4 Checkbox" matches value "1"
    Then the field "Test Activity5 Checkbox" matches value ""
    Then the "disabled" attribute of "#block-massaction-control-section-list-select-option-0" "css_element" should not be set
    Then the "disabled" attribute of "#block-massaction-control-section-list-select-option-1" "css_element" should not be set
    Then the "disabled" attribute of "#block-massaction-control-section-list-select-option-2" "css_element" should not be set
    Then the "disabled" attribute of "#block-massaction-control-section-list-select-option-3" "css_element" should be set
    Then the "disabled" attribute of "#block-massaction-control-section-list-select-option-4" "css_element" should not be set
    Examples:
      | courseformat | expandsections |
      | topcoll      | "Open all" "text"    |
      | tiles        | "Expand all" "link"  |

  @javascript
  Scenario: Check if checkboxes are selected properly by choosing the section in the section selector and using the links
    "Select all" and "Deselect all"
    Given the following "courses" exist:
      | fullname        | shortname | numsections | format |
      | Test course     | TC        | 5           | topics |
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
      | page     | TC     | 1        | Test Activity1 | Test page description | 1       |
      | page     | TC     | 2        | Test Activity2 | Test page description | 1       |
      | page     | TC     | 3        | Test Activity3 | Test page description | 1       |
      | page     | TC     | 4        | Test Activity4 | Test page description | 4       |
      | page     | TC     | 5        | Test Activity5 | Test page description | 4       |
    When I log in as "teacher1"
    And I am on "Test course" course homepage with editing mode on
    And I add the "Mass Actions" block
    # Dropdown list is being generated by JS, so we need to wait a bit until this has been loaded.
    And I wait "3" seconds
    And I set the field "Select all in section" to "Topic 1"
    Then the field "Test Activity1 Checkbox" matches value "1"
    Then the field "Test Activity2 Checkbox" matches value "1"
    Then the field "Test Activity3 Checkbox" matches value "1"
    Then the field "Test Activity4 Checkbox" matches value ""
    Then the field "Test Activity5 Checkbox" matches value ""
    When I click on "Select all" "link" in the "Mass Actions" "block"
    Then the field "Test Activity1 Checkbox" matches value "1"
    Then the field "Test Activity2 Checkbox" matches value "1"
    Then the field "Test Activity3 Checkbox" matches value "1"
    Then the field "Test Activity4 Checkbox" matches value "1"
    Then the field "Test Activity5 Checkbox" matches value "1"
    When I click on "Deselect all" "link" in the "Mass Actions" "block"
    Then the field "Test Activity1 Checkbox" matches value ""
    Then the field "Test Activity2 Checkbox" matches value ""
    Then the field "Test Activity3 Checkbox" matches value ""
    Then the field "Test Activity4 Checkbox" matches value ""
    Then the field "Test Activity5 Checkbox" matches value ""
