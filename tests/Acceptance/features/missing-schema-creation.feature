Feature: Missing schema creation

  Scenario: Frontend missing schema URLs resolve to the schema page
    When the frontend schema URL "http://localhost/training-schema/1/2026-08-10/" is resolved
    Then WordPress routes it to user "1" and week "2026-08-10"

  Scenario: Athlete opens a missing own week
    Given a logged-in athlete exists
    When the athlete opens their missing schema for week "2026-07-27"
    Then an empty schema with fourteen training slots is created

  Scenario: Coach opens a missing athlete week in admin
    Given an athlete exists
    And a coach is logged in
    When the coach opens the athlete schema editor for week "2026-09-14"
    Then an empty schema with fourteen training slots is created
