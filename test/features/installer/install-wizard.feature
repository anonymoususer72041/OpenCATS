@installer @javascript
Feature: Installation wizard
  The installation wizard should complete a fresh OpenCATS installation safely.

  Scenario: Complete a fresh empty database installation
    Given the installer is unlocked
    And the installer database is empty
    When I open the installation wizard
    Then the installation wizard should show the system check
    When I continue from the system check
    Then the installer should show database configuration
    When I configure and test the installer database connection
    Then the installer database connection should pass
    When I continue after database connectivity
    Then the installer should offer a new empty database installation
    When I choose the empty database installation path
    Then the installer should show resume indexing configuration
    When I skip resume indexing
    Then the installer should show mail settings
    When I configure no mail support
    Then the installer should show optional component configuration
    When I continue through optional component configuration
    Then the installer final maintenance should run
    And the installer should reach the installation complete screen
    And INSTALL_BLOCK should exist
    When I start OpenCATS from the installer
    Then the login page should be reachable
