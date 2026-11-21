# Kohana Documentation

These guides used to be served by the `userguide` module. They are plain Markdown now, readable on GitHub or in any editor. Each directory has a `menu.md` with its table of contents.

Upgrading an existing application? Start with the [upgrade guide](../UPGRADE.md).

| Guide | Covers |
|---|---|
| [Kohana](kohana/index.md) ([contents](kohana/menu.md)) | Installation, MVC, routing, requests, configuration, sessions, security, error handling |
| [Database](database/index.md) ([contents](database/menu.md)) | Connections, query builder, results, migrations, seeding |
| [ORM](orm/index.md) ([contents](orm/menu.md)) | Models, relationships, validation, filters |
| [Auth](auth/index.md) ([contents](auth/menu.md)) | User authentication and drivers |
| [Cache](cache/index.md) ([contents](cache/menu.md)) | APCu, File, Memcached and SQLite stores |
| [Image](image/index.md) ([contents](image/menu.md)) | Image manipulation with GD and Imagick |
| [Console](minion/index.md) ([contents](minion/menu.md)) | The `kohana` console, built-in commands, writing commands |
| [Testing](unittest/index.md) ([contents](unittest/menu.md)) | Pest and PHPUnit tests, test doubles |

Code examples omit `use` statements. Framework classes live in the `Kohana\` namespace (for example `Kohana\Http\Request`, `Kohana\Orm\Model`); see [`upgrade/class-map.php`](../upgrade/class-map.php) for every class's full name.
