# Test users

Three accounts, one per role, for clicking the rework through by hand. They are
built by a seeder rather than kept alive by hand, so they survive a re-port and
there is no question of what they hold.

```sh
php artisan db:seed --class=DevUsersSeeder
```

Idempotent — re-running resets the password and rebuilds the student's fixtures
rather than stacking a second set. The usual reasons to run it again are having
rebuilt the database from `port:*`, or having forgotten the password.

## The accounts

| Role | Email | Lands on |
|---|---|---|
| Student | `dev@viak.test` | `/de/student/profil` |
| Expert | `dev-expert@viak.test` | `/de/experte/profil` — **404, not built yet** |
| Admin | `dev-admin@viak.test` | `/dashboard` |

**Password, all three:**

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

## What is not covered

- **The expert portal does not exist.** `dev-expert@viak.test` signs in and the
  header points at `/de/experte/profil`, which 404s. That is the next chunk of
  `09-public-site.md`, not a broken account.
- **Admin user management is not built** (`08-accounts.md`), so the admin lands
  on the dashboard shell and finds the screens that chunks 02–06 built.
- **No multi-role account.** Four exist in the production data — three
  Admin + Expert + Student and one Admin + Student — and they are the reason the
  portals are two URL trees rather than one `/de/konto`. There is nothing to
  click on the second tree yet, so seeding one would only prove the header's
  precedence; add it when the expert portal lands.
- **Run My Accounts stays mocked.** Nothing these accounts do posts to the
  client's accounting; see `03-invoices.md`.

## Ported accounts

The 577 ported users are real people with scrubbed emails
(`user123@example.test`) and **no usable password**. Use the three above rather
than resetting one of theirs: a ported user's data is the record the port is
checked against, and fixtures written onto it stop it being that.

`php artisan db:scrub` is what replaces the addresses, in the legacy copy before
the port reads it; `00-foundation.md` has the rule under *Working data*.
