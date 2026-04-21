# Scaffold workflow

How the [`yii2-extensions/scaffold`][scaffold] plugin applies `app-base` to a consumer
project, and how to live with the scaffolded files over time.

## The provider manifest

Every file `app-base` contributes is declared in `scaffold.json` at the root of this
package:

```json
{
    "copy": [
        "src",
        "config",
        "rbac",
        "resources",
        "public",
        "yii",
        "runtime/.gitignore"
    ],
    "modes": {
        "config/*.php": "preserve",
        "rbac/*.php": "preserve",
        "public/assets/.gitkeep": "preserve",
        "public/images/**": "preserve",
        "runtime/.gitignore": "preserve"
    }
}
```

- `copy` lists every path (file or directory) the plugin copies into the consumer.
- `modes` overrides the default mode on a per-path glob basis.

Dev-only files (`tests/`, `composer.json`, `phpunit.xml.dist`, `phpstan.neon`, `.git*`,
`README.md`, `CHANGELOG.md`, `LICENSE`) are filtered by scaffold's default excludes and
stay inside the provider repo for its own CI. They never reach the consumer.

## File modes

`app-base` uses two of the four modes exposed by scaffold:

| Mode       | Behaviour                                                                                |
| ---------- | ---------------------------------------------------------------------------------------- |
| `replace`  | Default. Re-copies the stub on every install; overwrites user edits unless `--no-force`. |
| `preserve` | Written once. Subsequent installs leave the on-disk file untouched.                      |

Preserved paths (`config/*.php`, `rbac/*.php`, `public/assets/.gitkeep`,
`public/images/**`, `runtime/.gitignore`) are the files you are **expected** to edit:
db credentials, params, RBAC graph, runtime gitignores. Everything else
(`src/*`, `resources/*`, `public/index.php`, etc.) is replaced on install, so if you
edit those files scaffold will warn on the next install.

For the full list of available modes, see
[`scaffold/docs/modes.md`](https://github.com/yii2-extensions/scaffold/blob/main/docs/modes.md).

## The lockfile

After the first successful install, the root of your consumer project contains a
`scaffold-lock.json` that records the SHA-256 hash of every copied file.

- **Commit it** next to `composer.lock`. It lets CI and collaborators reproduce the
  exact scaffold state and detect drift.
- The `status` command compares on-disk hashes against the lockfile to classify each
  file as `synced`, `modified`, or `missing`.

## Inspecting the scaffold

The plugin ships a standalone Symfony Console CLI at `vendor/bin/scaffold`:

| Command                                                            | What it does                                                                      |
| ------------------------------------------------------------------ | --------------------------------------------------------------------------------- |
| `vendor/bin/scaffold status`                                       | Table of every tracked file: `synced` / `modified` / `missing`.                   |
| `vendor/bin/scaffold providers`                                    | Lists enabled providers and how many files each contributed.                      |
| `vendor/bin/scaffold diff <file>`                                  | Line-by-line diff between the provider stub and the current on-disk file.         |
| `vendor/bin/scaffold reapply [file] [--force] [--provider=<name>]` | Re-copies stubs from `vendor/`. User-modified files are skipped unless `--force`. |
| `vendor/bin/scaffold eject <file> [--yes]`                         | Drops a file entry from `scaffold-lock.json` without deleting the file from disk. |

## Common scenarios

### Accept an upstream change on a replaced file

```bash
vendor/bin/scaffold status
vendor/bin/scaffold diff src/controllers/UserController.php
vendor/bin/scaffold reapply src/controllers/UserController.php --force
```

### Keep a local override of a replaced file

```bash
vendor/bin/scaffold eject src/controllers/UserController.php --yes
```

After ejection, the file is no longer tracked. Future `composer install` runs will not
touch it.

### Re-seed a `preserve` file that was accidentally deleted

```bash
vendor/bin/scaffold reapply config/params.php --force
```

`preserve` only prevents **overwrites**; if the file is missing, reapply writes a
fresh copy.

### Uninstall the provider

Remove `yii2-extensions/app-base` from `require` and from `extra.scaffold.allowed-packages`,
then `composer update`. Scaffold removes the lockfile entries for the provider but
does not delete files from disk ; you own them now.

## Upstream reference

For the plugin itself (commands, modes, provider contract) see the
[scaffold documentation index](https://github.com/yii2-extensions/scaffold/tree/main/docs).

## Next steps

- 📚 [Installation Guide](installation.md)
- ⚙️ [Configuration Reference](configuration.md)
- 🎨 [Frontend Overlays](frontend-overlays.md)
- 🧪 [Testing Guide](testing.md)

[scaffold]: https://github.com/yii2-extensions/scaffold
