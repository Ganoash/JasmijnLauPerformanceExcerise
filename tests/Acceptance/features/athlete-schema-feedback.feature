Feature: Athlete views and updates a weekly schema

  Scenario: Athlete saves execution feedback for their own schema
    Given a logged-in athlete has a schema for week "2026-08-17"
    When the athlete opens their schema
    And enters "8.5" as the actual distance for Monday morning
    And enters "Ging goed" as uitvoering
    Then the field is saved
    And the running total updates immediately on the page

