# Vendored skills

Third-party skills copied verbatim after a manual review (Markdown only, no scripts or hooks).
To update: re-clone, diff against these files, review, then copy.

| Skill | Source | Commit | License |
|---|---|---|---|
| caveman | https://github.com/JuliusBrussee/caveman (`skills/caveman/SKILL.md`) | aeb45e2f787c0757a8af383a291a280cb6aeb4c1 | Apache-2.0 |
| stop-slop | https://github.com/hardikpandya/stop-slop | 8da1f030185bdfe8471220585162991eaeb970e9 | MIT |
| absolute-mode | Local, written from the project owner's rules | n/a | n/a |

Only the core `caveman` skill is vendored. Its `/caveman ultra` and `/caveman wenyan` aliases point to the
`ultracave` and `megacave` skills, which are not installed here; and `/caveman status` reports `unknown`
because the caveman hook is not installed.
