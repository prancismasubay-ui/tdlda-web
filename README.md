# Kalinga Dialects Learning Dictionary — Web App

A PHP + MySQL dictionary application for the Kalinga Provincial Tourism
Office capstone project. Public visitors can search and browse for free —
no account needed — while admins manage content through a login-protected
panel. Built to run on **XAMPP** (Apache + MySQL).

## 1. Install XAMPP
Download and install XAMPP from https://www.apachefriends.org if you don't have it.

## 2. Copy the project into htdocs
Copy the whole `tinglayan_dictionary` folder into your XAMPP `htdocs` directory:

- Windows: `C:\xampp\htdocs\tinglayan_dictionary`
- macOS: `/Applications/XAMPP/htdocs/tinglayan_dictionary`
- Linux: `/opt/lampp/htdocs/tinglayan_dictionary`

## 3. Start Apache and MySQL
Open the XAMPP Control Panel and click **Start** next to both **Apache** and **MySQL**.

## 4. Create the database
1. Go to `http://localhost/phpmyadmin`
2. Click **Import** (top menu)
3. Choose the file `database.sql` from this project
4. Click **Go**

> ⚠️ **This version's `database.sql` drops and recreates the database from
> scratch** (schema changed to support multiple definitions per word, parts
> of speech, synonyms, etc.). If you already imported an earlier version and
> added real content, export/back it up first — re-importing will wipe it.

This creates the `kalinga_dialects_dictionary` database with all tables and
sample data, including Tinglayan pre-loaded as the first dialect.

## 5. Configure the database connection (if needed)
Open `config/database.php` — defaults match a standard XAMPP install
(`root` user, blank password). Only change these if your MySQL root user
has a password set.

## 6. Open the app
Visit: `http://localhost/tinglayan_dictionary/`

This opens the **public dictionary homepage** — free to search and browse,
no account required. Admins click **Login** (top-right) for a popup sign-in.

**Default login:** username `admin`, password `admin123`
> Change this after first login by adding a new admin user under **Admin
> Users** and removing the default one.

## Folder structure
```
tinglayan_dictionary/
├── config/database.php         # DB connection settings
├── includes/                   # header, sidebar, auth guard
├── assets/css/style.css        # all styling
├── uploads/audio/              # uploaded pronunciation audio files
├── process/                    # form-handling scripts (add/edit/delete/import/export)
├── api/                        # public REST API (see below)
├── database.sql                # import this into phpMyAdmin
├── manifest.json               # PWA manifest
├── service-worker.js           # offline caching for the public site
├── index.php                     # PUBLIC dictionary homepage
├── login.php                     # standalone login page (fallback)
├── dashboard.php                 # admin: stat cards + charts
├── dialects.php                  # admin: Dialects CRUD
├── words.php                     # admin: Dictionary Entries CRUD
├── words_import.php              # admin: bulk CSV/JSON import
├── parts_of_speech.php           # admin: Parts of Speech CRUD
├── categories.php                # admin: Categories CRUD
├── contributors.php              # admin: contributor summary
├── feedback.php                  # admin: survey responses
├── users.php                     # admin: user accounts
├── results.php                   # admin: analytics + failed searches
└── logout.php
```

## Feature overview

### Public site (no login)
- **Search & browse** by English or dialect term, with autocomplete-free
  live filtering via category chips and a **dialect switcher** dropdown
  (add more dialects later from the admin — they appear automatically).
- **Rich word cards**: part of speech, every definition (a word can have
  more than one), an example sentence pair per definition, synonyms, and
  etymology/notes.
- **Audio pronunciation** playback where uploaded.
- **Login popup** (top-right) — AJAX modal, no page reload, for admins.
- **Offline access** — a service worker caches the shell and previously
  viewed pages/audio so the dictionary keeps working without a connection
  once visited. Works on `localhost`; a real deployment needs HTTPS for
  service workers to register.
- **Failed-search logging** — any search with zero results is quietly
  logged so admins can see what people are looking for that isn't in the
  dictionary yet (see **Results** in the admin panel).

### Admin panel (login required)
- **Dictionary Entries** — each word can have multiple definitions (each
  with its own example sentence pair), a part of speech, etymology/notes,
  comma-separated synonyms, category, dialect, contributor, status, and an
  uploadable audio file.
- **Bulk Import** (`words_import.php`) — upload a CSV or JSON file to add
  many entries at once. Unknown dialects/categories are created
  automatically. Rows sharing the same dialect + term are merged as
  multiple definitions of one word. See the in-app format guide for columns.
- **Export** — CSV or JSON export buttons on the Dictionary Entries page
  (respects your current search/filter).
- **Dialects** — add/edit/delete the Kalinga dialects covered; mark them
  Active or Planned.
- **Parts of Speech** — manage the grammatical tag list.
- **Categories** — manage topical groupings, shared across dialects.
- **Results** — includes a **Failed Searches** table so you know what to
  add next.
- **Dashboard, Contributors, Feedback, Admin Users** — as before.

### REST API (`/api/`)
Read-only, JSON, CORS-enabled, no API key — for a future mobile app or any
other client. Visit `http://localhost/tinglayan_dictionary/api/` for full
documentation. Quick reference:

| Endpoint | Description |
|---|---|
| `GET /api/dialects.php` | List dialects (`?status=Active`) |
| `GET /api/categories.php` | List categories |
| `GET /api/parts_of_speech.php` | List POS tags |
| `GET /api/words.php` | Search/list published words (`?q=`, `?dialect=`, `?category=`, `?pos=`, `?limit=`, `?offset=`) |
| `GET /api/word.php?id=1` | Full detail for one word |

## Adding a new dialect
Either add it directly on the **Dialects** page, or just reference a new
dialect name in a bulk import file — it will be created automatically
(status "Active"). No schema changes needed either way.

## Notes
- Passwords are hashed with `password_hash()` / verified with
  `password_verify()`.
- All database queries use PDO prepared statements.
- Deleting a word cascades to its definitions and synonyms automatically.
- Deleting a dialect cascades to all of its words (confirmed in the UI
  before deletion).
