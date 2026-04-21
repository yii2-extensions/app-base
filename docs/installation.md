# Installation guide

Step-by-step walkthrough for starting a Yii2 project with `yii2-extensions/app-base`.

## System requirements

- [PHP](https://www.php.net/downloads) `8.3` or higher.
- [Composer](https://getcomposer.org/download/) `2.9` or higher.

`app-base` is a scaffold **provider**, not a runtime library. It contributes files;
it does not ship a class to extend. See [scaffold.md](scaffold.md) for the underlying
mechanism.

## 1. Create the project's `composer.json`

Make a fresh directory and save the following as `composer.json` in its root. This is
the full manifest a consumer project needs ; nothing is omitted.

```json
{
    "name": "my-company/my-app",
    "type": "project",
    "description": "My Yii2 application based on yii2-extensions/app-base.",
    "license": "proprietary",
    "minimum-stability": "dev",
    "prefer-stable": true,
    "require": {
        "php": ">=8.3",
        "yii2-extensions/app-base": "^22.0@dev",
        "yii2-extensions/app-jquery": "^22.0@dev"
    },
    "require-dev": {
        "yii2-extensions/scaffold": "^0.1@dev"
    },
    "extra": {
        "scaffold": {
            "allowed-packages": [
                "yii2-extensions/app-base",
                "yii2-extensions/app-jquery"
            ]
        }
    },
    "config": {
        "allow-plugins": {
            "yii2-extensions/scaffold": true,
            "yiisoft/yii2-composer": true
        }
    }
}
```

### Why each key matters

| Key                                             | Purpose                                                                                               |
| ----------------------------------------------- | ----------------------------------------------------------------------------------------------------- |
| `minimum-stability: dev`, `prefer-stable`       | Required because `^22.0@dev` and `^0.1@dev` resolve to dev branches until a stable release exists.    |
| `require.yii2-extensions/app-base`              | The backend provider (this package).                                                                  |
| `require.yii2-extensions/app-jquery`            | A frontend overlay. Swap for any other overlay ; see [frontend-overlays.md](frontend-overlays.md).    |
| `require-dev.yii2-extensions/scaffold`          | The Composer plugin that copies provider files into the project tree.                                 |
| `extra.scaffold.allowed-packages`               | Scaffold ignores any provider not listed here. Order matters ; overlays must come **after** the base. |
| `config.allow-plugins.yii2-extensions/scaffold` | Composer refuses to run plugins that are not explicitly authorized.                                   |
| `config.allow-plugins.yiisoft/yii2-composer`    | Yii2's own Composer plugin (handles autoload extras such as `@app` alias).                            |

## 2. Run `composer install`

```bash
composer install
```

The scaffold plugin runs automatically at the end of install and copies the provider
trees into the project root. You should now see `src/`, `config/`, `rbac/`,
`resources/`, `public/`, `yii`, and `scaffold-lock.json`.

Verify the result:

```bash
vendor/bin/scaffold providers   # lists both providers and their file counts
vendor/bin/scaffold status      # every file reported as "synced"
```

Commit `scaffold-lock.json` alongside `composer.lock`:

```bash
git add scaffold-lock.json composer.lock
git commit -m "chore: scaffold yii2-extensions/app-base"
```

## 3. Make `runtime/` and `public/assets/` writable

The provider ships only placeholders for these directories. Yii2 writes caches, logs,
the SQLite database, and compiled assets into them at runtime:

```bash
chmod -R u+w runtime/ public/assets/
```

## 4. Run the migrations

`app-base` ships two migrations: `CreateUserTable` and `CreateAdminUser` (seeds the
default admin account from `config/params.php`).

```bash
./yii migrate
```

The database defaults to SQLite at `runtime/db.sqlite`. Change `config/db.php` if you
need MySQL, PostgreSQL, or another driver ; see [configuration.md](configuration.md).

## 5. Start the dev server

```bash
php -S localhost:8080 -t public public/router.php
```

Open `http://localhost:8080`. The frontend overlay renders the layout; `app-base`
handles `site/*` and `user/*` actions. Log in with the seeded admin credentials from
`config/params.php` (defaults: username `admin`, password `admin`).

## Next steps

- 📦 [Scaffold Workflow](scaffold.md)
- ⚙️ [Configuration Reference](configuration.md)
- 🎨 [Frontend Overlays](frontend-overlays.md)
- 🧪 [Testing Guide](testing.md)
