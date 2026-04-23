# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog, and this project adheres to Semantic Versioning.

## 0.1.0 Under development

- feat: initial `yii2-extensions/app-base` package structure.
- fix: improve formatting and clarity in `README.md`.
- refactor: extract `AbstractSiteController` and `AbstractUserController` under `src/controllers/Base/` so overlays can override `render*()` only.
- refactor: drop `Base/Abstract*Controller`; `SiteController` and `UserController` are now non-final concrete classes.
