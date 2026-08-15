Feature: Coach uses the shared frontend schema page

  Scenario: Coach saves athlete feedback from the frontend schema page
    Given an athlete has a schema for week "2026-08-17"
    And a coach is logged in
    When the coach opens the athlete schema URL
    And saves "Pijnvrij" as the injury comment for Monday morning
    Then access is allowed
    And the injury comment is saved
