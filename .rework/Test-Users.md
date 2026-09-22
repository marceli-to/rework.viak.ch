# Test users

Four accounts — one per role, plus one holding all three — for clicking the
rework through by hand. They are built by a seeder rather than kept alive by
hand, so they survive a re-port and there is no question of what they hold.

```sh
php artisan db:seed --class=DevUsersSeeder
```

Idempotent — re-running resets the passwords and rebuilds the fixtures rather
than stacking a second set. The usual reasons to run it again are having rebuilt
the database from `port:*`, or having forgotten the password.

**It logs you out**, which is not a bug and is worth expecting: `updateOrCreate`
re-hashes the password every run, and Laravel's session guard remembers the hash
it authenticated against. Sign in again after seeding.

## The accounts

| Role | Email | Lands on |
|---|---|---|
| Student | `dev@viak.test` | `/de/student/profil` |
| Expert | `dev-expert@viak.test` | `/de/experte/profil` |
| Admin | `dev-admin@viak.test` | `/dashboard` |
| All three | `dev-all@viak.test` | `/de/student/profil` — **and both portals are real** |

Plus eight throwaway students, `dev-teilnehmer-{event}-{0..3}@viak.test`, who
exist to be names on the two experts' participant lists. They hold the same
password and nothing else worth looking at.

**Password, all of them:**

```
FLAW-GLEE-CENT-BOSS-GAVE-HOOK-FUSE
```

From `genpass`, first candidate, unedited.

**It is written down on purpose.** These accounts live in a local database that
anyone holding this repository can rebuild with one command, so the password
guards nothing — hiding it would only mean nobody could use them. The same
sentence is not true of anything in production, and `DevUsersSeeder` throws
unless `APP_ENV=local` so this set cannot follow the code there. Do not soften
that guard.

The "Landing on" column is `SiteUrl::profileFor()`, which is what the header's
*Profil* icon uses: student first for an account holding more than one role,
then expert, then the dashboard for an admin-only account. A guest gets
`/login`.

`dev-all@viak.test` is what that precedence is for: it holds all three roles,
lands on the student portal, and reaches the other two by typing the URL —
legacy adds a role-picker screen after login that the rework does not have
(`09-public-site.md`).

## What the student holds

Enough that every branch of the portal draws something. Hung off **real ported
events**, so the rows read like the site rather than like a factory.

| | |
|---|---|
| *Merkliste* | 1 event — the filled heart and a *Buchen* |
| *Gebuchte Kurse* | 3 seats: a plain one, one **holding a laptop**, one whose event **offers** a laptop |
| *Absolvierte Kurse* | 1 seat on an event that has already run |
| *Dokumente* | 1 invoice (number, total, `(bezahlt)`) and 1 participation confirmation |
| *Rechnungsadressen* | 2, one with a company and one without |

Both event states appear where the data allows it — *Kurs findet statt* in green
and *Kurs offen, wird bestätigt* in orange — because the state line is the one
thing on a row that says whether the course is actually happening. The snapshot
has 36 upcoming events and only **3 confirmed**, so the seeder takes what it can
get rather than insisting.

**The document files are not there.** Those are rows, not PDFs, so *Download*
answers 404. Producing a real one means running the invoice pipeline, which is a
different thing to be testing; `03-invoices.md` covers it.

## What each expert holds

`dev-expert@viak.test` and `dev-all@viak.test` teach **different** courses, so
the two sets of fixtures do not collide — pointing both at one event made the
second run of the seeder add a second message and a second document to a course
that already had one.

| | |
|---|---|
| *Bevorstehende Kurse* | 1 course, with **3 live seats and a cancelled fourth** — so the count beside the row and the list disagree with the raw booking total, which is the thing to be able to look at |
| *Vergangene Kurse* | 1 course, so the second list is not empty |
| *Nachrichten* | 1 note, with its recipient rows written |
| *Kurs-Dokumente* | 1 document — the row, **not the file**, so *Download* answers 404 for the same reason the student's do |

One participant carries a firm on their profile, so the third column has
something in it.

## What is not covered

- **Admin user management is not built** (`08-accounts.md`), so the admin lands
  on the dashboard shell and finds the screens that chunks 02–06 built.
- **The participant list has no PDF.** The expert's course screen draws the
  list; legacy's *Teilnehmerliste (PDF)* link is deferred with its policy
  already in place (`09-public-site.md`).
- **Nothing is mailed.** Posting a note to a course records who it reaches and
  sends nothing — there is no Mailable in the rework yet, on this path or the
  checkout's.
- **Run My Accounts stays mocked.** Nothing these accounts do posts to the
  client's accounting; see `03-invoices.md`.

## Ported accounts

The 577 ported users are real people with scrubbed emails
(`user123@example.test`) and **no usable password**. Use the four above rather
than resetting one of theirs: a ported user's data is the record the port is
checked against, and fixtures written onto it stop it being that.

`php artisan db:scrub` is what replaces the addresses, in the legacy copy before
the port reads it; `00-foundation.md` has the rule under *Working data*.
