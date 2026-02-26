# Diary Release Run Sheet (One Page)

Use this for each release (example: v4.0.0).

## Release Info
- Version: 
- Date:
- Tester:
- Environment:
  - Moodle version:
  - PHP version:
  - DB engine/version:
  - Browser:
- Commit/branch/tag candidate:

## Stop Conditions (Immediate No-Go)
- [ ] Data loss or corrupted entry data
- [ ] Fatal error/exception in normal workflow
- [ ] Permission bypass (student can do teacher-only action)

## Pre-Checks (Fast)
- [ ] `mod/diary` syntax check passes (`php -l`)
- [ ] `mod/diary` PHPCS check passes
- [ ] Upgrade notes reviewed for deferred/known items

## Student Smoke (Core)
- [ ] Open Diary and create new entry
- [ ] Save succeeds and content persists after reload
- [ ] Edit entry (if allowed) and save succeeds
- [ ] No DB/debug error with open/close time unset or future date
- [ ] Prompt/status display is correct on view page

## Teacher Smoke (Core)
- [ ] Open report page and add feedback/rating
- [ ] Save feedback; values persist after reload
- [ ] Open single-user report page; edit feedback/rating
- [ ] Clear feedback path works and state updates correctly
- [ ] Delete-entry behavior respects permissions/settings

## Comms + Data Integrity
- [ ] Student submission triggers expected teacher notification (if enabled)
- [ ] Teacher feedback triggers expected student notification (if enabled)
- [ ] Backup/restore preserves prompts and entries
- [ ] PostgreSQL regression check: no bigint empty-id failure

## Prompt Editor/Prompt List Regression Checks
- [ ] Prompt list/status visibility behaves as expected
- [ ] Row/global collapse toggles behave correctly
- [ ] Sticky collapse state works across interactions
- [ ] Prompt editor page loads/saves without warnings

## Evidence / Notes
- Ticket refs tested:
  - 
- Screenshots/log snippets:
  - 
- Known non-blockers accepted for this release:
  - 

## Release Decision
- Decision: [ ] GO  [ ] NO-GO
- Blocking issues (if NO-GO):
  - 
- Follow-up actions:
  - 
- Sign-off:
  - Name:
  - Date:

---
Reminder: Link this completed run sheet in the release PR/notes before publishing.
