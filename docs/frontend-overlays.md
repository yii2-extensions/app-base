# Frontend overlays

`app-base` ships only the Yii2 backend and view templates that expect a layout to wrap
them.

## What an overlay contributes

`app-base` does **not** ship:

- `resources/views/layouts/main.php` content assumptions ; the layout is provided
  by the overlay.
- CSS, JavaScript, or asset bundles.
- Widgets or helpers (flash alerts, navbars, dark-mode toggles).
- Public assets such as `public/css/*`, `public/js/*`, or icons.

A _frontend overlay_ is a separate scaffold provider that contributes those files and
is declared **after** `app-base` in `allowed-packages` so its stubs win the merge on
any path both providers touch.

## Ordering rule

A consumer project that combines the base with an overlay looks like this in full:

```json
{
    "name": "my-company/my-app",
    "type": "project",
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

Scaffold applies providers top-to-bottom; the **last writer wins** for any shared
path. In practice:

- `app-base` ships `resources/views/site/about.php` (backend).
- `app-jquery` ships its own `resources/views/site/about.php` (Bootstrap markup).
- Because `app-jquery` is declared **after** `app-base`, the consumer receives the
  Bootstrap version. The backend view is never written to disk.

Reversing the order breaks the overlay: you would end up with the backend-only stub
and no layout. Always list the overlay last.

## Available overlays

| Overlay                                    | Stack                                                            | Notes                                                              |
| ------------------------------------------ | ---------------------------------------------------------------- | ------------------------------------------------------------------ |
| [`yii2-extensions/app-jquery`][app-jquery] | Bootstrap 5 + jQuery + `yiisoft/yii2-jquery` + `yii2-bootstrap5` | Default reference overlay. Ships navbar, footer, dark-mode toggle. |

## What an overlay is expected to ship

An overlay provider is responsible for:

| Path                        | Contents                                               |
| --------------------------- | ------------------------------------------------------ |
| `resources/views/layouts/`  | The real layout (`main.php`) and any partials.         |
| `resources/views/site/`     | Stylized homepage, contact, about, and error pages.    |
| `resources/views/user/`     | Login, signup, password-reset, and email-verify pages. |
| `src/assets/AppAsset.php`   | Asset bundle registering the overlay's CSS/JS.         |
| `src/widgets/`              | Layout widgets (`Alert`, navbar builders, etc.).       |
| `public/css/`, `public/js/` | Compiled styles and scripts.                           |

What an overlay must **not** touch:

- `src/controllers/`, `src/models/`, `src/migrations/`, `src/commands/` (owned by `app-base`).
- `config/` (owned by `app-base`).
- `rbac/` (owned by `app-base`).
- `resources/mail/` (owned by `app-base`).
- Server configuration (`.htaccess`, `nginx.conf`, `Caddyfile`) ; those live in
  dedicated `yii2-extensions/server-*` providers.

## Authoring a new overlay

See [`scaffold/docs/providers.md`][providers] for the scaffold-level contract. The
short checklist:

1. Start from a clean Yii2 app with the layout you want.
2. Add `scaffold.json` declaring the `copy` paths above.
3. Set `composer.json` `type: "yii2-scaffold"`.
4. Require `yii2-extensions/scaffold` and `yii2-extensions/app-base`.
5. Publish on Packagist and reference it from a consumer project **after** `app-base`
   in `allowed-packages`.

## Next steps

- 📚 [Installation Guide](installation.md)
- 📦 [Scaffold Workflow](scaffold.md)
- ⚙️ [Configuration Reference](configuration.md)
- 🧪 [Testing Guide](testing.md)

[app-jquery]: https://github.com/yii2-extensions/app-jquery
[providers]: https://github.com/yii2-extensions/scaffold/blob/main/docs/providers.md
