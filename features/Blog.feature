Feature: The Blog

  Scenario: Home
    Given I am on the homepage
    When I follow "Browse application"
    Then I should be on "/en/blog/"
