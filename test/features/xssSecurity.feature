@security @xss
Feature: Stored XSS payloads are safely rendered
  In order to prevent stored XSS in user-controlled fields
  Persisted values should be rendered as escaped text on detail and list pages

  Scenario: Candidate name script payload is escaped on details and list pages
    Given I am logged in with ADMIN access level
    And I set "first_name" on "candidate" where "candidate_id" is "20000" to:
      """
      xssCandidate<script>alert(101)</script>
      """
    When I do GET request "index.php?m=candidates&a=show&candidateID=20000"
    Then the response should not render raw html payload:
      """
      xssCandidate<script>alert(101)</script>
      """
    And the response should contain escaped html payload:
      """
      xssCandidate<script>alert(101)</script>
      """
    When I do GET request "index.php?m=candidates"
    Then the response should not render raw html payload:
      """
      xssCandidate<script>alert(101)</script>
      """

  Scenario: Company name image-onerror payload is escaped on details and list pages
    Given I am logged in with ADMIN access level
    And I set "name" on "company" where "company_id" is "20002" to:
      """
      xssCompany<img src=x onerror=alert(102)>
      """
    When I do GET request "index.php?m=companies&a=show&companyID=20002"
    Then the response should not render raw html payload:
      """
      xssCompany<img src=x onerror=alert(102)>
      """
    And the response should contain escaped html payload:
      """
      xssCompany<img src=x onerror=alert(102)>
      """
    When I do GET request "index.php?m=companies"
    Then the response should not render raw html payload:
      """
      xssCompany<img src=x onerror=alert(102)>
      """

  Scenario: Contact title javascript-link payload is escaped on details and list pages
    Given I am logged in with ADMIN access level
    And I set "title" on "contact" where "contact_id" is "30001" to:
      """
      xssContact<a href="javascript:alert(103)">profile</a>
      """
    When I do GET request "index.php?m=contacts&a=show&contactID=30001"
    Then the response should not render raw html payload:
      """
      xssContact<a href="javascript:alert(103)">profile</a>
      """
    And the response should contain escaped html payload:
      """
      xssContact<a href="javascript:alert(103)">profile</a>
      """
    When I do GET request "index.php?m=contacts"
    Then the response should not render raw html payload:
      """
      xssContact<a href="javascript:alert(103)">profile</a>
      """

  Scenario: Job order title attribute-breaking payload is escaped on details and list pages
    Given I am logged in with ADMIN access level
    And I set "title" on "joborder" where "joborder_id" is "40001" to:
      """
      xssJobOrder"><svg onload=alert(104)>
      """
    When I do GET request "index.php?m=joborders&a=show&jobOrderID=40001"
    Then the response should not render raw html payload:
      """
      xssJobOrder"><svg onload=alert(104)>
      """
    And the response should contain escaped html payload:
      """
      xssJobOrder"><svg onload=alert(104)>
      """
    When I do GET request "index.php?m=joborders"
    Then the response should not render raw html payload:
      """
      xssJobOrder"><svg onload=alert(104)>
      """

  Scenario: Attachment original filename payload is escaped on company details page
    Given I am logged in with ADMIN access level
    And I set "original_filename" on "attachment" where "attachment_id" is "80003" to:
      """
      xssAttachment<marquee onstart=alert(105)>.txt
      """
    When I do GET request "index.php?m=companies&a=show&companyID=20002"
    Then the response should not render raw html payload:
      """
      xssAttachment<marquee onstart=alert(105)>.txt
      """
    And the response should contain escaped html payload:
      """
      xssAttachment<marquee onstart=alert(105)>.txt
      """

  Scenario: Candidate activity notes script payload is escaped on candidate details page
    Given I am logged in with ADMIN access level
    And I set "notes" on "activity" where "activity_id" is "70001" to:
      """
      xssCandidateActivity<script>alert(106)</script>
      """
    When I do GET request "index.php?m=candidates&a=show&candidateID=20000"
    Then the response should not render raw html payload:
      """
      xssCandidateActivity<script>alert(106)</script>
      """
    And the response should contain escaped html payload:
      """
      xssCandidateActivity<script>alert(106)</script>
      """

  Scenario: Contact activity notes image-onerror payload is escaped on contact details page
    Given I am logged in with ADMIN access level
    And I set "notes" on "activity" where "activity_id" is "70002" to:
      """
      xssContactActivity<img src=x onerror=alert(107)>
      """
    When I do GET request "index.php?m=contacts&a=show&contactID=30001"
    Then the response should not render raw html payload:
      """
      xssContactActivity<img src=x onerror=alert(107)>
      """
    And the response should contain escaped html payload:
      """
      xssContactActivity<img src=x onerror=alert(107)>
      """
