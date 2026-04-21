# Testing

This package contains a PHP test suite and supporting tooling for static analysis, dependency hygiene, and coding
standards.

## Automated refactoring and coding standards

Run Rector:

```bash
composer rector
```

## Codeception

Run the Codeception suites (unit + functional + acceptance):

```bash
composer tests
```

### Suite layout

```text
tests/
├── _bootstrap.php              # boots a console app and runs migrations on the test DB once
├── _phpstan_bootstrap.php      # PHPStan-only bootstrap
├── codeception.yml             # global suite config (coverage enabled for /src/*)
├── acceptance.suite.yml
├── functional.suite.yml
├── unit.suite.yml
├── acceptance/
│   └── UserAccessCest.php      # exercises AccessControl + VerbFilter via the full request pipeline
├── unit/
│   ├── HelloControllerTest.php
│   ├── LoginTest.php
│   ├── LogoutTest.php
│   ├── SiteControllerTest.php
│   ├── UserControllerTest.php
│   ├── migrations/
│   │   ├── CreateAdminUserTest.php
│   │   └── CreateUserTableTest.php
│   └── models/                 # one test class per model/form
└── support/
    ├── AcceptanceTester.php
    ├── FunctionalTester.php
    ├── MailerBootstrap.php     # captures outgoing mail in tests
    ├── UnitTester.php
    ├── data/
    │   ├── login_data.php
    │   ├── test.sqlite
    │   └── user.php            # UserFixture dataset
    └── fixtures/
        └── UserFixture.php
```

### Suite responsibilities

| Suite        | Purpose                                                                                                                      |
| ------------ | ---------------------------------------------------------------------------------------------------------------------------- |
| `unit`       | Tests single classes in isolation (controllers, models, forms, migrations) by instantiating objects directly.                |
| `functional` | Reserved for integration-style tests that emulate a request (currently empty; left wired for future additions).              |
| `acceptance` | Dispatches through the full Yii2 request pipeline so controller `behaviors()` (`AccessControl`, `VerbFilter`) actually fire. |

### Test database

`tests/_bootstrap.php` boots a temporary Yii console application, wires the `MigrateController` against
`config/test_db.php`, and runs every migration once before the first test executes. The connection is SQLite at
`tests/support/data/test.sqlite`. Migrations are not reset between tests; fixtures roll back via the `Yii2` Codeception
module.

### Mail capture

Outgoing mail is routed through `app\tests\support\MailerBootstrap`, which configures `yii\symfonymailer\Mailer` with
`useFileTransport: true`. Tests assert on the produced files in `runtime/mail/` via the Codeception `Filesystem`
module.

### Turnstile

`ContactFormValidateTurnstileTest` exercises the Cloudflare Turnstile integration. `config/test.php` sets
`turnstile.secretKey` to the empty string so the full validator is bypassed in the rest of the suite; the dedicated
test reinstates the key and stubs the HTTP response.

### Coverage

Codeception's coverage is enabled for `/src/*`. Reports are written under `tests/support/output/coverage/` (HTML) and
`tests/support/output/coverage.txt` (text). The suite holds **100 % line and method coverage** across controllers,
models, forms, and migrations.

## Coding standards

Run Easy Coding Standard with fixes (PER-3 + PSR-12, configured via `ecs.php` which extends `php-forge/coding-standard`):

```bash
composer ecs
```

## Dependency definition check

Verify that every symbol referenced from `src/` is declared by a runtime dependency. The whitelist for runtime-defined
Yii constants (`YII_DEBUG`, `YII_ENV_TEST`) lives in `composer-require-checker.json`:

```bash
composer check-dependencies
```

## Static analysis

Run PHPStan at `level: max` with the strict-rules extension and bleeding-edge ruleset enabled (`checkImplicitMixed`,
`checkBenevolentUnionTypes`, `checkUninitializedProperties`, ...). Configuration lives in `phpstan.neon`; the Yii2
extension picks up its app config from `tests/support/phpstan-config.php`:

```bash
composer static
```

## Passing extra arguments

Composer scripts support forwarding additional arguments using `--`.

Examples:

```bash
composer tests -- run unit                                          # only the unit suite
composer tests -- run unit tests/unit/models/LoginFormTest.php      # a single test file
composer tests -- run acceptance                                    # only the acceptance suite
composer static -- --memory-limit=512M                              # raise the PHPStan memory limit
composer ecs -- --no-progress-bar                                   # quieter ECS output
```

## Next steps

- 📚 [Installation Guide](installation.md)
- 📦 [Scaffold Workflow](scaffold.md)
- ⚙️ [Configuration Reference](configuration.md)
- 🎨 [Frontend Overlays](frontend-overlays.md)
