# Testing guide

`app-base` ships a Codeception suite exercising every controller, model, form, and migration it contributes.

## Quick run

```bash
composer install
composer tests               # codecept run
composer static              # phpstan --memory-limit=-1
composer check-dependencies  # composer-require-checker
composer ecs                 # coding standard
```

Or directly:

```bash
vendor/bin/codecept run
vendor/bin/codecept run unit
vendor/bin/codecept run unit tests/unit/models/LoginFormTest.php
```

## Suite layout

```text
tests/
├── _bootstrap.php              # runs migrations on the test DB once, before the suite
├── _phpstan_bootstrap.php      # PHPStan-only bootstrap
├── codeception.yml             # suite config (coverage enabled for /src/*)
├── functional.suite.yml
├── unit.suite.yml
├── unit/
│   ├── HelloControllerTest.php
│   ├── LoginTest.php
│   ├── LogoutTest.php
│   ├── SiteControllerTest.php
│   ├── UserControllerTest.php
│   ├── migrations/
│   │   ├── CreateAdminUserTest.php
│   │   └── CreateUserTableTest.php
│   └── models/
│       ├── ContactFormTest.php
│       ├── ContactFormValidateTurnstileTest.php
│       ├── LoginFormTest.php
│       ├── PasswordResetRequestFormTest.php
│       ├── ResendVerificationEmailFormTest.php
│       ├── ResetPasswordFormTest.php
│       ├── SignupFormTest.php
│       ├── UserSearchTest.php
│       ├── UserTest.php
│       └── VerifyEmailFormTest.php
└── support/
    ├── AcceptanceTester.php
    ├── FunctionalTester.php
    ├── MailerBootstrap.php     # captures outgoing mail in tests
    ├── UnitTester.php
    ├── data/
    │   ├── login_data.php
    │   ├── test.sqlite
    │   └── user.php
    └── fixtures/
        └── UserFixture.php
```

## Test database

`tests/_bootstrap.php` boots a temporary Yii console application, wires the `MigrateController` against
`config/test_db.php`, and runs every migration once before the first test executes. The connection is SQLite at
`tests/support/data/test.sqlite`.

The migration is not reset between tests; fixtures are rolled back via the `Yii2` Codeception module rather than by
re-migrating.

## Mail capture

Outgoing mail in tests is routed through `app\tests\support\MailerBootstrap`, which configures `yii\symfonymailer\Mailer`
with `useFileTransport: true`. Tests assert on the produced files in `runtime/mail/` via the Codeception `Filesystem`
module.

## Turnstile

`ContactFormValidateTurnstileTest` exercises the Cloudflare Turnstile integration.
`config/test.php` sets `turnstile.secretKey` to the empty string so the full validator is bypassed in the rest of the
suite; the dedicated test reinstates the key and stubs the HTTP response.

## Coverage

Codeception's coverage is enabled for `/src/*` only:

```yaml
coverage:
    enabled: true
    include:
        - /src/*
```

Reports are written under `tests/support/output/coverage/` (HTML) and `tests/support/output/coverage.txt` (text).

### Why coverage is not 100 %

Controllers that end in `render(...)` cannot be fully exercised by `app-base` alone; the views in `resources/views/` are
designed to be replaced by a [frontend overlay](frontend-overlays.md) that ships the real layout, widgets, and asset
bundle. Running the suite against `app-base` in isolation leaves those render paths uncovered.

End-to-end coverage of `app-base`'s controllers and views is the overlay's responsibility. Each overlay ships its own
Codeception suite that instantiates the full stack; those suites report 100 % for the rendered paths. For example,
[`yii2-extensions/app-jquery`][app-jquery] reports 100 % once both packages run together.

Treat each provider's coverage as a **slice**; the union across `app-base` plus the chosen overlay is what represents a
real consumer app.

## Static analysis

PHPStan runs at `level: max` with strict advanced checks (`checkImplicitMixed`, `checkBenevolentUnionTypes`,
`checkUninitializedProperties`, ...) and the bleeding edge ruleset. Configuration lives in `phpstan.neon`; the Yii2
extension is configured through `tests/support/phpstan-config.php`.

## Next steps

- 📚 [Installation Guide](installation.md)
- 📦 [Scaffold Workflow](scaffold.md)
- ⚙️ [Configuration Reference](configuration.md)
- 🎨 [Frontend Overlays](frontend-overlays.md)

[app-jquery]: https://github.com/yii2-extensions/app-jquery
