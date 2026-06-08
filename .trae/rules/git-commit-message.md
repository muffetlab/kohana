---
alwaysApply: true
scene: git_message
---

Use Conventional Commits for all commit messages.

Format: `<type>(<scope>): <short summary>`

- **Commit Type**: `build|chore|ci|docs|feat|fix|perf|refactor|style|test`
- **Commit Scope**: `application|auth|cache|codebench|database|image|minion|orm|unittest|userguide|public|system`
- **Short Summary**:
    - Use present tense (e.g., "add" instead of "added").
    - Do not capitalize the first letter.
    - Do not end with a period.

Example: `fix(system): fix version return type`
