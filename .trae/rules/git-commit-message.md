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
- Keep proper nouns, brands, and acronyms capitalized correctly (e.g., Google, OAuth2, Windows, ORM)
- No dot (.) at the end
- Write in English


## Examples

- `feat(auth): add Google OAuth2 login provider`
- `fix(database): resolve memory leak in user ORM query`
- `docs(userguide): update installation steps for Windows setup`
- `style(system): reformat routing middleware according to linter`
- `test(unittest): add missing test cases for captcha verification`
- `chore: update dependencies and bump npm package version`
