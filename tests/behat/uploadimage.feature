@format @format_grid
Feature: Image upload
  As a teacher I need to upload an image to a section.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email          |
      | daisy    | Daisy     | Grid     | daisy@grid.com |
    And the following "courses" exist:
      | fullname | shortname | format  | numsections |
      | Grid     | GD        | grid    | 5           |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | daisy    | GD     | editingteacher |
    And I am on the "GD" "Course" page logged in as "daisy"

  @_file_upload @javascript
  Scenario: Upload an image to section 2
    When I turn editing mode on
    And I edit the section "2"
    And I upload "course/format/grid/tests/fixtures/Ducking.jpg" file to "Section image" filemanager
    And I press "Save changes"
    And I turn editing mode off
    Then "//img[contains(@src, 'Ducking.jpg')]" "xpath_element" should exist in the "#grid-section-2 .grid-image" "css_element"
