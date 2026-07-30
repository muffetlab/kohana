---
alwaysApply: true
scene: git_message
---

# Git Conventions Guidelines 🔄

## Commit Message Format

All commit messages MUST follow the [Conventional Commits](mdc:https:/www.conventionalcommits.org) specification:

```
<type>[optional scope]: <description>
```

### Types

- `build`: Changes that affect the build system or external dependencies
- `chore`: Changes to the build process or auxiliary tools
- `ci`: Changes to CI configuration files and scripts
- `docs`: Documentation only changes
- `feat`: A new feature
- `fix`: A bug fix
- `perf`: A code change that improves performance
- `refactor`: A code change that neither fixes a bug nor adds a feature
- `style`: Changes that do not affect the meaning of the code (formatting, etc.)
- `test`: Adding missing tests or correcting existing tests

### Scope

The scope MUST be enclosed in parentheses and specify the name of the affected root directory or a subdirectory under "modules" (as perceived by the person reading the changelog). If the change affects multiple modules or is global, the scope should be omitted.

- `application`
- `auth`
- `cache`
- `codebench`
- `database`
- `image`
- `minion`
- `orm`
- `unittest`
- `userguide`
- `public`
- `system`

### Description

- Use the imperative, present tense: "change" not "changed" nor "changes"
- Don't capitalize the first word of the description
- Keep proper nouns and brands capitalized correctly (e.g., Google, OAuth2, Windows)
- Use the correct letter case for acronyms (e.g., URL, ORM, OAuth2)
- No dot (.) at the end
- Write in English

### Breaking Changes

Changes that break backward compatibility MUST be marked as BREAKING CHANGE:

- **Renaming public properties or methods**: If you rename a public property or method (e.g., `Shortcode::parseAttrs()`), this is a BREAKING CHANGE because external code may depend on the original name
- **Renaming protected properties or methods**: If the class is widely inherited, this is also a BREAKING CHANGE
- **Private members**: Renaming private properties or methods is NOT a BREAKING CHANGE

To mark a BREAKING CHANGE:
1. Add a `!` after the type/scope: `refactor(application)!: rename parse_attrs to parseAttrs`
2. Or add `BREAKING CHANGE:` in the commit message footer with details

## Examples

- `feat(auth): add Google OAuth2 login provider`
- `fix(database): resolve memory leak in user ORM query`
- `docs(userguide): update installation steps for Windows setup`
- `style(system): reformat routing middleware according to linter`
- `test(unittest): add missing test cases for captcha verification`
- `chore: update dependencies and bump npm package version`
- `refactor(application)!: rename parse_attrs to parseAttrs`
- `refactor(application): rename $xmlrpc to $xmlRpc` (NOT a breaking change, internal variable only)

Breaking change using footer:

```
refactor(application): rename parse_attrs to parseAttrs

BREAKING CHANGE: the parse_attrs method has been renamed to parseAttrs
```
