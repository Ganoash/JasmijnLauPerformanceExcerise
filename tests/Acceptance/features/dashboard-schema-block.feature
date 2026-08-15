Feature: Dashboard schema block

  Scenario: Athlete opens current and nearby weeks from the dashboard block
    Given a logged-in athlete exists
    When the dashboard schema block is rendered
    Then the block links to last, current, next, and two-weeks-ahead schemas

  Scenario: Anonymous visitors do not see dashboard schema links
    Given nobody is logged in
    When the dashboard schema block is rendered
    Then no dashboard schema links are shown
