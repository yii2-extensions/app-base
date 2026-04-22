# Configuration reference

Every file listed here is shipped with `mode: preserve` (see [scaffold.md](scaffold.md)), so edits are safe: scaffold
will not overwrite them on subsequent `composer install` runs.

## `config/db.php`

Defaults to SQLite at `runtime/db.sqlite`:

```php
return [
    'class' => \yii\db\Connection::class,
    'dsn' => 'sqlite:' . dirname(__DIR__) . '/runtime/db.sqlite',
];
```

Switch to MySQL, PostgreSQL, or any other driver supported by Yii2 by editing the `dsn`, `username`, and `password`
keys. The same connection is reused by `config/console.php`, so migrations automatically target the configured database.

## `config/test_db.php`

Test-only connection, defaults to a separate SQLite file at `tests/support/data/test.sqlite`:

```php
return [
    'class' => \yii\db\Connection::class,
    'dsn' => 'sqlite:' . dirname(__DIR__) . '/tests/support/data/test.sqlite',
];
```

## `config/params.php`

Application parameters are read from `params.php` by both `web.php` and `console.php`.

| Key                                    | Default               | Purpose                                                                 |
| -------------------------------------- | --------------------- | ----------------------------------------------------------------------- |
| `admin.email`                          | `admin@example.com`   | Seed admin email (used by `CreateAdminUser` migration).                 |
| `admin.password`                       | `admin`               | Seed admin password. **Change before going to production.**             |
| `admin.username`                       | `admin`               | Seed admin username.                                                    |
| `adminEmail`                           | `admin@example.com`   | Contact form recipient.                                                 |
| `senderEmail`                          | `noreply@example.com` | `From:` address on outgoing mail (verify, reset).                       |
| `senderName`                           | `Example.com mailer`  | `From:` name on outgoing mail.                                          |
| `supportEmail`                         | `support@example.com` | Rendered in the footer and on error pages.                              |
| `turnstile.secretKey`                  | Cloudflare test key   | Cloudflare Turnstile server-side secret.                                |
| `turnstile.siteKey`                    | Cloudflare test key   | Cloudflare Turnstile client-side site key.                              |
| `user.emailVerificationTokenExpire`    | `86400`               | Verification token lifetime in seconds.                                 |
| `user.passwordMinLength`               | `8`                   | Minimum password length enforced by `SignupForm` / `ResetPasswordForm`. |
| `user.passwordResetTokenExpire`        | `3600`                | Reset token lifetime in seconds.                                        |
| `user.resendVerificationEmailCooldown` | `60`                  | Cooldown between "resend verification" requests.                        |

## `config/web.php`

Wires the web application:

- `authManager` ; `yii\rbac\PhpManager`, fed by `rbac/items.php`, `rbac/rules.php`, `rbac/assignments.php`.
- `user.identityClass` ; points at `app\models\User` from `src/models/User.php`.
- `user.loginUrl` ; `['user/login']`.
- `mailer` ; `MailerInterface` with `useFileTransport: true` and view path set to `@app/resources/mail`. **Disable
  `useFileTransport` for production** and configure an SMTP transport instead.
- `errorHandler.errorAction` ; `site/error` (rendered by `SiteController::actionError`).
- `urlManager.enablePrettyUrl` and `showScriptName: false`.
- `request.cookieValidationKey` ; **placeholder `''`**; set to a 32-byte secret before deployment.

## `config/console.php`

Console application configuration. Highlights:

- `controllerMap.migrate` ; `MigrateController` with `migrationNamespaces: ['app\\migrations']`.
- `controllerMap.serve` ; `ServeController` with `docroot: '@app/public'` (for `./yii serve`).
- `aliases.@app/migrations`, `@npm`, `@tests`.

## `config/test.php`

Test-time configuration overrides:

- Uses `config/test_db.php` instead of `db.php`.
- Disables CSRF.
- Loads `app\tests\support\MailerBootstrap` to capture outgoing messages in tests.
- Blanks `turnstile.secretKey` so contact-form tests do not hit Cloudflare.

## `rbac/`

Three preserve-mode files implementing Yii2's `PhpManager` RBAC:

- `rbac/items.php` ; roles and permissions.
- `rbac/rules.php` ; rule classes (e.g. "owner can update own post").
- `rbac/assignments.php` ; user-to-role assignments.

Edit these directly or replace the backend with a different `authManager` in `config/web.php` / `config/console.php`.

## Mail templates

Preserve mode does **not** cover `resources/mail/*` (those are under `replace`).
Rebrand the templates in place if you want to keep them tracked by scaffold, or `scaffold eject` individual templates to
take full ownership.

Files:

- `resources/mail/emailVerify-html.php`
- `resources/mail/emailVerify-text.php`
- `resources/mail/passwordResetToken-html.php`
- `resources/mail/passwordResetToken-text.php`
- `resources/mail/layouts/html.php`
- `resources/mail/layouts/text.php`

## Production checklist

- [ ] Replace `request.cookieValidationKey` in `config/web.php` with a 32-byte secret.
- [ ] Change `admin.password` in `config/params.php` before running migrations ; or
      delete the seeded admin and create your own.
- [ ] Disable `useFileTransport` in `config/web.php` and configure an SMTP transport.
- [ ] Set real `adminEmail`, `senderEmail`, `senderName`, `supportEmail`.
- [ ] Replace the Cloudflare Turnstile test keys with your production site keys.
- [ ] Point `config/db.php` at a real database.
- [ ] Run migrations (`./yii migrate`).
- [ ] Clear `runtime/` on deploy and ensure it is writable by the web server.

## Next steps

- 📚 [Installation Guide](installation.md)
- 📦 [Scaffold Workflow](scaffold.md)
- 🎨 [Frontend Overlays](frontend-overlays.md)
- 🧪 [Testing Guide](testing.md)
