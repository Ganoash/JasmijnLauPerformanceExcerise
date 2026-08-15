Feature: Coach edits weekly schema

  Scenario: Coach saves training descriptions without overwriting athlete feedback
    Given an athlete has filled in feedback for Monday morning
    And a coach opens the admin schema editor
    When the coach changes the Monday morning training description
    Then the new training description is saved
    And the athlete feedback remains unchanged

