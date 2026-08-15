Feature: Inactive training types

  Scenario: Inactive training types remain visible in existing schemas
    Given a training schema uses an exercise that is now inactive
    When the athlete opens the schema
    Then the inactive exercise is still visible
    And the inactive exercise is not offered for new schema selections

