Feature: Harvest

  @api
  Scenario: As an administrator I should be able to add a harvest source.

    Given users:
    | name               | mail                     | status | roles             |
    | Administrator      | admin@fakeemail.com      | 1      | administrator     |

    And I am logged in as "Administrator"
    And I am on "node/add/harvest-source"
    Then I should see the text "Create Harvest Source"
    And I fill in "Title" with "Source 1"
    And I fill in "Source URI" with "https://data.mo.gov/data.json"
    And I select "datajson_v1_1_json" from "Type"
    And I press "Save"
    Then I should see the success message "Harvest Source Source 1 has been created."

  @api
  Scenario Outline: As a user I should not be able to add a harvest source.

    Given pages:
    | name                  | url                     |
    | Create Harvest Source | node/add/harvest-source |

    And I am logged in as a "<role>"
    And I should not be able to access "Create Harvest Source"

    Examples:
    | role               |
    | anonymous user     |
    | authenticated user |

  @api
  Scenario: As an administrator I should see only the published harvest sources listed on the harvest dashboard.

    Given users:
    | name               | mail                     | status | roles             |
    | Administrator      | admin@fakeemail.com      | 1      | administrator     |
    And sources:
    | title          | author           | published  |
    | Source one     | Administrator    | Yes        |
    | Source two     | Administrator    | No         |
    And pages:
    | title              | url                            |
    | Harvest Dashboard  | /admin/dkan/harvest/dashboard  |

    And I am logged in as "Administrator"
    And I am on the "Harvest Dashboard" page
    Then I should see the text "Source one"
    And I should not see the text "Source two"
