Feature: Schema privacy

  Scenario: Athlete cannot view another user's schema
    Given athlete A has a schema for week "2026-08-17"
    And athlete B is logged in
    When athlete B opens athlete A's schema URL
    Then access is denied

