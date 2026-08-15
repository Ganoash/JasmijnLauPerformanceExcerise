Feature: Missing schema creation

  Scenario: Athlete opens a missing own week
    Given a logged-in athlete exists
    When the athlete opens their missing schema for week "2026-07-27"
    Then an empty schema with fourteen training slots is created

  Scenario: Coach opens a missing athlete week in admin
    Given an athlete exists
    And a coach is logged in
    When the coach opens the athlete schema editor for week "2026-09-14"
    Then an empty schema with fourteen training slots is created
