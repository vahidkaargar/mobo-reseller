# Workflow rules

## Plan before any change (hard rule)
Do not edit, create, delete, install, migrate, or commit anything until a plan has been shown
and the user has approved it. Reading and running read-only commands is always allowed.

A plan lists:
1. Goal and assumptions.
2. Files to touch, interfaces, build order.
3. Data impact: SQL migrations, MongoDB collections, wallet balances.
4. Verification: exact commands (`vendor/bin/pint --test`, `php artisan test --filter=...`).
5. Risks and security impact.

Ask blocking questions with the AskUserQuestion prompt (max 4 per call), not as free text.

## Delivery
- One concern per PR. Security fixes never ride along with tooling or feature work.
- Report: what changed, how it was verified (commands + results), risks, security impact.
- End a task with a short table of open questions, if any.

## Output modes (skills in .claude/skills/)
Active by default in this repo, in this order of precedence:
1. `caveman` (full intensity): chat voice. Wins over absolute-mode; never stack both grammars.
2. `absolute-mode`: no filler, no soft asks, no CTA tails. Applies where caveman is off.
3. `stop-slop`: applies to all prose that is persisted (docs, PR bodies, commit messages, comments).
Persisted artifacts (code, docs, commits, PRs) are written in normal prose, never caveman.
Turn off with "normal mode" / "stop caveman".
