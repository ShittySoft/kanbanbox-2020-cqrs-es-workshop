Feature: Users can check in and out of buildings

  Scenario: Users can check into a building
    Given I registered a building
    When user "bob" checks into the building
    Then user "bob" should have been checked into the building

  Scenario: Users that check in twice into a building are detected as anomalies
    Given I registered a building
    And "bob" checked into the building
    When user "bob" checks into the building
    Then user "bob" should have been checked into the building
    Then a check-in anomaly should have been detected for "bob"
