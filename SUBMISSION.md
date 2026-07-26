# Submission — Egypt Summer-Time Clock-In Fix

Prepared by @softxpert.

## Root cause

CIP reduced `Africa/Cairo` to a timezone-less wall string and later reparsed it
through PHP's process-global timezone. A fixed `+02:00` default or stale tzdata
therefore omitted Egypt's restored summer `+03:00` rule and shifted the
displayed clock-in one hour early. The seeded `(GMT+02:00) Cairo` label and
numeric offsets are metadata, not date-aware conversion rules. Stored GMT
strings were also parsed implicitly instead of being declared as UTC.

## Minimal fix

- Capture the clock-in as an immutable instant and store it explicitly as UTC.
- Parse database values explicitly as UTC, then render through the person's
  IANA code.
- Replace fixed-offset calendar arithmetic with an instant-specific IANA
  conversion.
- Keep current OS/PHP tzdata as a deployment prerequisite.

## Naive fix avoided

I did not hardcode Cairo as `UTC+3` or rename the label to `GMT+03:00`. Egypt is
`UTC+2` in winter and `UTC+3` in summer, so either change would merely move the
bug to another part of the year.

## Verification

`php reproduce.php` checks winter, summer, both sides of both 2026 DST
transitions, a stale fixed-`+02:00` process default, invalid inputs, and the
IANA-aware calendar path. Result: **24 passed, 0 failed**.

## AI log and estimated cost

- Tool: OpenAI Codex (one AI tool)
- User turns through final publication authorization: **5**
- Token usage: **140,525 tokens**, Codex goal telemetry measured immediately
  before publication
- Approximate combined tokens per user turn: **28,105** (the telemetry does not
  expose an input/output or per-turn split)
- Estimated GPT-5 API-equivalent cost: **$0.42**, assuming 80% input and 20%
  output tokens
- Possible input/output-mix range: **$0.18–$1.41**
- Pricing: [GPT-5 standard API rates of $1.25/M input and $10/M output](https://developers.openai.com/api/docs/models/gpt-5),
  retrieved 2026-07-26

The cost is an API-equivalent estimate; actual Codex subscription billing may
differ. Telemetry includes repository, workflow, and tool context as well as
visible prompt/response tokens.

## Commit signature

New implementation and publication commits use:
`Abdelrahman Ebied <abdelrahman.ebied@softxpert.com>`.
