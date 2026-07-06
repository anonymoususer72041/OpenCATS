# Legacy Testing Infrastructure Notes

The legacy OpenCATS tests still live in their original folders and are run
through the Laravel Sail stack. There are two groups of tests:

* **Laravel suite** – `tests/Unit`, `tests/Feature` (root `phpunit.xml`), run
  with `sail artisan test`.
* **Legacy suites** – kept under `legacy/`, driven by their own
  `legacy/phpunit.xml.dist` and `legacy/test/behat.yml`:
  * `UnitTests` – pure unit tests, no infrastructure required.
  * `IntegrationTests` – need a MariaDB sandbox (`integrationtestdb`).
  * `Behat` (`default` / `security`) – drive the legacy app (served through the
    Laravel wrapper) against `opencatsdb`, optionally via Selenium.

## CI-only services

The legacy databases and browser are recreated for CI by `compose.ci.yml`,
which is merged with the base Sail stack (`compose.yaml`) via the
`COMPOSE_FILE` environment variable:

| Service             | Purpose                                             |
| ------------------- | --------------------------------------------------- |
| `opencatsdb`        | Functional DB for Behat. Seeded from `legacy/db/cats_schema.sql` + `legacy/test/data/securityTests.sql`. |
| `integrationtestdb` | Disposable sandbox; `DatabaseTestCase` drops/recreates `cats_integrationtest` each run. |
| `selenium`          | Standalone Chrome for `@javascript` Behat scenarios. |

Hostnames and credentials (`dev`/`dev`) mirror `legacy/test/config.php`.

## Running the suites locally

```bash
# 1. Bring up the full stack (base Sail + legacy CI services)
export COMPOSE_FILE=compose.yaml:compose.ci.yml
cp legacy/test/config.php legacy/config.php
./vendor/bin/sail up -d --wait

# 2. Laravel suite
./vendor/bin/sail artisan test

# 3. Legacy suites (run from the legacy/ working directory inside the container)
./vendor/bin/sail exec laravel.test bash -c "cd legacy && ../vendor/bin/phpunit -c phpunit.xml.dist --testsuite UnitTests"
./vendor/bin/sail exec laravel.test bash -c "cd legacy && ../vendor/bin/phpunit -c phpunit.xml.dist --testsuite IntegrationTests"
./vendor/bin/sail exec laravel.test bash -c "cd legacy && ../vendor/bin/behat -c test/behat.yml --suite=default"
./vendor/bin/sail exec laravel.test bash -c "cd legacy && ../vendor/bin/behat -c test/behat.yml --suite=security"
```

## Troubleshooting

* **DB not ready:** `sail up -d --wait` blocks on the healthchecks, so the
  seeded databases are ready before tests run. If a suite still can't connect,
  check `sail logs opencatsdb integrationtestdb`.
* **Behat can't reach the app:** the suites target `http://laravel.test` (the
  Sail app container serving the legacy app through the wrapper). Ensure the
  `laravel.test` service is up.
* **Risky tests:** PHPUnit may flag empty placeholder methods as "Risky". These
  do not fail the build.
