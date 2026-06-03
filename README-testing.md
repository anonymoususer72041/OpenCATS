# CI/CD Testing Infrastructure Notes

## Overview of Databases
This project uses two distinct MariaDB instances during the testing phase to ensure data isolation:

### 1. opencatsdb (Port 3306)
* **Purpose:** Primary application database for functional testing.
* **Data:** Pre-seeded with `db/cats_schema.sql` and `test/data/securityTests.sql` via Docker's `initdb.d`.
* **Used By:** Manual development, Behat (Gherkin/Selenium) suites.

### 2. integrationtestdb (Port 3307)
* **Purpose:** A "Disposable Sandbox" for PHPUnit Integration Tests.
* **Behavior:** The `DatabaseTestCase.php` class drops and recreates this database for every test run to ensure a clean schema build from `db/cats_schema.sql`.

---

## Running Tests Locally
To mirror the CI environment on your machine:

1. **Prepare the environment:**
   ```bash
   cp test/config.php ./config.php
   touch ./INSTALL_BLOCK
   ```

2. **Start the containers:**
   ```bash
   cd docker/
   docker compose -f docker-compose-test.yml up -d --build
   ```

3. **Install PHP dependencies (Composer):**
   ```bash
   docker compose -f docker-compose-test.yml exec -T --workdir /var/www/public php composer install --no-interaction --prefer-dist
   ```

4. **Run the suites:**
   * **PHPUnit Unit Tests:** `docker compose -f docker-compose-test.yml exec php ./vendor/bin/phpunit --testsuite UnitTests`
   * **PHPUnit Integration Tests:** `docker compose -f docker-compose-test.yml exec php ./vendor/bin/phpunit --testsuite IntegrationTests`
   * **Behat:** `docker compose -f docker-compose-test.yml exec php ./vendor/bin/behat -c ./test/behat.yml`

### Installer Behat Suite
The installer suite is isolated from the normal Behat suites because the installer rewrites `config.php`, creates `INSTALL_BLOCK`, and changes database state. The installer context backs up and restores those files around each scenario and resets a dedicated installer database.

Run it from the `docker/` directory with Selenium and the test containers started:

```bash
docker compose -f docker-compose-test.yml exec php ./vendor/bin/behat -c ./test/behat.yml --suite=installer
```

By default the suite uses the `opencatsdb` test container and the database `cats_installer_test`. Override these settings only when needed:

* `OPENCATS_INSTALLER_DB_HOST` - installer database host.
* `OPENCATS_INSTALLER_DB_PORT` - installer database port.
* `OPENCATS_INSTALLER_DB_USER` - installer database user.
* `OPENCATS_INSTALLER_DB_PASSWORD` - installer database password. Falls back to `OPENCATS_INSTALLER_DB_PASS`.
* `OPENCATS_INSTALLER_DB_NAME` - installer database name. It must contain `installer`.
* `OPENCATS_INSTALLER_DEFAULT_PHONE_COUNTRY_CODE` - default phone country code selected during installation.
* `OPENCATS_INSTALLER_DB_ADMIN_HOST` - database host used for reset/setup. Defaults to `OPENCATS_INSTALLER_DB_HOST`.
* `OPENCATS_INSTALLER_DB_ADMIN_USER` - database user used for reset/setup.
* `OPENCATS_INSTALLER_DB_ADMIN_PASS` - database password used for reset/setup.
* `OPENCATS_INSTALLER_MAIL_FROM` - mail sender address used when disabling mail support.

The first installer scenario covers a fresh empty-database installation with resume indexing skipped and mail support disabled.

---

## Troubleshooting
* **Connection Errors:** If tests fail to connect, verify the `integrationtestdb` container is healthy. The CI uses `mysqladmin ping` to ensure the DB is ready before PHP attempts to connect.
* **Risky Tests:** PHPUnit may flag empty test methods (placeholders) as "Risky." These do not fail the build but should be implemented in future PRs.
