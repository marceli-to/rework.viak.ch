<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>

# rework.viak.ch

A rebuild of the live site at **visualisierungs-akademie.ch** (the `viak.ch`
domain 301s to it). The legacy application is checked out beside this one at
`../viak.ch` and is the reference for both data and design.

## Read before working

- **`.rework/`** — one document per chunk, holding every decision with the
  measurement behind it. Read the chunk's doc before touching a chunk; code
  comments cross-reference them with `[[wiki-links]]`. `.rework/Open-Questions.md`
  is what is still unanswered and who owes it.
- **`resources/css/README.md`** — styling conventions for **both** the Blade site
  and the Vue dashboard. Three of the four differ from stock Tailwind, so a class
  copied from the docs will compile and be silently wrong.

## Two things that are easy to get wrong

**The public site is the current design rebuilt 1:1**, not a new one. A value in
`resources/views/site/` or in a Blade component it uses should trace back to
`../viak.ch/resources/sass/`, and the source file belongs in a comment. New pages
and new features come after parity. The Vue dashboard is not held to this — it
has no legacy design worth keeping.

**Run My Accounts stays mocked** outside production. Nothing posts to the
client's live accounting from a prototype; see `.rework/03-invoices.md`.
