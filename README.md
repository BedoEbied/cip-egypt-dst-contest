# CIP Egypt Summer Time — AI Prompt Contest

Softxpert AI Expert Initiative wrap-up activity.

**Time box:** 30 minutes  
**Goal:** Use AI efficiently to diagnose and fix this bug — best correct fix, clear explanation, lowest useful token spend.

---

## The issue (brief)

CIP is a legacy time-tracking portal. Users in **Egypt** report:

> During **summer time**, when I clock in at **9:00 AM**, CIP shows **8:00 AM**.  
> Outside summer (regular / winter time) it looks correct.

The person timezone is `Africa/Cairo`, but the UI still shows **`(GMT+02:00) Cairo`**.

This repo contains an **isolated** copy of the clock-in → GMT store → local display path (not the full CIP app).

---

## What’s in this repo

| File | Purpose |
|------|---------|
| `isolated-bug.php` | Buggy capture / store / display helpers (edit this) |
| `reproduce.php` | Shows the 09:00 → 08:00 symptom |
| `README.md` | This brief |

### Run the reproduction

Requires PHP CLI:

```bash
php reproduce.php
```

You should see the stale path capture/display **08:00** while the true Cairo summer wall clock for that instant is **09:00**.

---

## What we need from you

1. **Root cause** — 2–6 sentences in your own words (not paste-only from the model).
2. **Minimal fix** — patch `isolated-bug.php` (and `reproduce.php` only if needed to prove the fix).
3. **Naive fix you avoided** — name one bad approach and why it fails (e.g. hardcoding +3 forever).
4. **AI log** — tool used, turns (max 5, aim ≤3), approximate tokens in+out per turn.

### Constraints

- Do **not** hardcode Egypt as `UTC+3` year-round (winter must stay correct).
- Do **not** “fix” by only renaming the label to `(GMT+03:00)`.
- Keep the change **minimal** — no full framework rewrite.
- Prefer keeping **GMT-at-rest** storage if you can make capture/display DST-safe.
- Egypt DST was restored in **2023+** (summer +3, winter +2). Legacy servers may still behave like fixed +2.

### Scoring (priority order)

1. Correct, production-safe fix  
2. Clear human explanation  
3. Token efficiency (lean prompts, fewer turns)

---

## How to work

1. Pull/clone this repo.
2. Read this README and skim `isolated-bug.php` **before** opening AI (~2 minutes).
3. Use **one** AI tool only.
4. Prefer a tight first prompt: symptom + constraints + which files — not “fix timezone” with a huge dump.
5. Freeze and submit at 30:00.

Good luck.
