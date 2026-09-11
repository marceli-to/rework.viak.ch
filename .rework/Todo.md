# Todo

Open items that are deliberately *not* being solved yet. Each one records enough
detail to act on without redoing the investigation.

---

## Restore the 14 events lost to two-digit years

**Decide during chunk 02 of the real build. Marcel to confirm the reconstruction.**

### What happened

`EventStoreRequest` validated `dates.*` with `required|min:1` — a string-*length*
check, not a date check. A day typed as `25.09.25` is parsed by
`Carbon::createFromFormat('d.m.Y', …)` as **year 25**, giving a large negative
timestamp. That value wins the `min()` in `EventController::store()` that derives
`events.date`, while the row's own `event_dates` entry falls back to the form
default — which is why so many date rows read the day the event was created.

An event stored in year 25 or 26 fails every upcoming-events comparison, so it
was published and then never appeared on the site. None of the 14 took a single
booking. Entered in three sittings: 2025-06-03 (11), 2025-11-13 (1), 2025-11-21 (2).

Fixed forward on the legacy branch `fix/invoice-due-at-auto-update` (commit
`d4974ff`): `date_format:d.m.Y|after:01.01.2000`. That stops new ones. The 14
existing rows are untouched.

### Reconstruction

`events.date + 2000 years` is always **the day that was mistyped**. Any
`event_dates` row that is not the creation date is a correctly-saved other day
of the same course. The affected courses run **Thursday + Friday** (Blender,
Godot) or a **single day** (SketchUp, Twinmotion).

The first four rows are where a real date row survived, and in every one of them
it is exactly the Thursday before / Friday after the mistyped day — which is what
validates the rule for the rest.

| # | Course | Mistyped day | Wkday | Real schedule | Basis |
|---|---|---|---|---|---|
| 202 | Blender Animation | 25.09.2025 | Thu | 25.–26.09. | both rows survived |
| 207 | Blender Rendering | 27.11.2025 | Thu | 27.–28.11. | both rows survived |
| 208 | Blender Animation | 12.12.2025 | Fri | 11.–12.12. | 11.12. survived |
| 211 | Godot Einführung | 05.12.2025 | Fri | 04.–05.12. | 04.12. survived |
| 203 | Blender Modeling | 09.10.2025 | Thu | 09.–10.10. | Thu+Fri pattern |
| 204 | Godot Einführung | 18.09.2025 | Thu | 18.–19.09. | Thu+Fri pattern |
| 205 | Godot Vertiefung | 06.11.2025 | Thu | 06.–07.11. | Thu+Fri pattern |
| 209 | Blender Modeling | 18.12.2025 | Thu | 18.–19.12. | Thu+Fri pattern |
| 210 | Godot Einführung | 04.12.2025 | Thu | 04.–05.12. | Thu+Fri pattern |
| 288 | Twinmotion | 12.03.2026 | Thu | 12.03. (1 day) | course is always 1 day |
| 294 | SketchUp | 26.01.2026 | Mon | 26.01. (1 day) | course is always 1 day |

### Three that need a human, not a rule

- **210 / 211 / 212** all resolve to *Godot Einführungskurs, 4.–5. Dezember 2025*.
  Almost certainly one course entered three times while the form misbehaved,
  not three courses.
- **294 / 295** are two identical SketchUp events on 26.01.2026. Same story.
- **213** lands on a **Saturday**; Blender Modeling has only ever run Thu+Fri.
  Here the day itself looks mistyped, not just the year. Do not restore from
  the data — ask what it was meant to be, or drop it.

Best estimate: ~10 real lost course dates, ~4 duplicate attempts.

### When it gets solved

`port:courses` currently **skips** all 14 and reports them. Options at build time:

1. Drop them. They are historical, unbooked, and invisible. Cheapest, loses nothing
   a customer ever saw.
2. Restore from the table above, so the course history is complete for reporting.

Either way the port should stop skipping silently once the decision is made.

---

## Other open questions

Carried from the chunk docs so they are in one place:

- **VAT on software licences** — rate, and how it posts to Run My Accounts.
  Blocks `03-invoices.md`. Needs the client's bookkeeper.
- **Licence fulfilment** — manual dispatch or reseller API? Blocks chunk 05 scoping.
- **Historical invoice due dates** — recoverable from Run My Accounts? See
  `03-invoices.md`. Only matters if dunning or the accounting export needs them.
- ~~Roles as a single enum column~~ — **resolved 2026-09-11**: reverted to a pivot.
  The hierarchy would have dropped the two top-listed public experts, who are
  Admin + Expert. See `02-courses-events.md`.
- ~~Poppins vs Effra~~ — **resolved 2026-09-11**: staying with Effra (Typekit
  `kcs4ept`). Legacy also loads a second kit, `bmx5jih`; confirm it is dead.
- **Production PHP version** — the rework is pinned to 8.3. Raise it if production
  runs 8.4.
- **English on the public site** — currently admin-only behind `role:admin`.
  Does the rework ship EN publicly?
