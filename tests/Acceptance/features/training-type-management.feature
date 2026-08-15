Feature: Training type management

  Scenario: Coach creates an active exercise
    Given a coach is logged in
    When the coach creates active exercise "Heuveltraining" in category "running" with unit "kilometers"
    Then the exercise is offered for new schema selections
