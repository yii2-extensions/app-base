# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog, and this project adheres to Semantic Versioning.

## 0.1.0 Under development

- feat: initial `yii2-extensions/app-base` package structure.
- fix: improve formatting and clarity in `README.md`.
- refactor: non-final concrete `SiteController`/`UserController` (no abstract base); `site/error` uses `yii\web\ErrorAction`; authed users redirected home from guest-only actions.
- chore: migrate to `yii2-extensions/scaffold` consumer model with `php-forge/baseline` and `php-forge/coding-standard ^0.3@dev`.
- feat!: rebuild web layer on Yii2 `22.0` standalone actions (Vertical Slice under `app\usecases`); drop `SiteController`/`UserController`.
- feat: enable `yii2-extensions/debug` toolbar/module in `config/web.php` under `YII_ENV`.
