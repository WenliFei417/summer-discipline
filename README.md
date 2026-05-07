# Summer Discipline

A personal daily discipline journal built with Laravel, Blade, Alpine.js, and Tailwind CSS.

The app is designed for a single owner: anyone can view calendar data, but creating, editing, and deleting records requires login.

## Tech Stack

- PHP `^8.3`
- Laravel `^13.7`
- Blade templates + Alpine.js
- Tailwind CSS (CDN)
- Database-backed daily record storage
- Optional Cloudflare R2 (S3-compatible) for image files

## Features

- Monthly calendar heatmap with multiple color modes (overall, health, study)
- Day detail modal with in-page image preview overlay
- Search popover with optional keyword, optional date range, and section filters (`health`, `study`, `ramblings`, `note`)
- Quick filters for `This Week` and `This Month`
- One record per date (`YYYY-MM-DD`)
- Manual daily level (`0-5`) for calendar color; default is `0` (no color)
- Calendar short note + long-form ramblings
- Health and study modules with optional ratings (`1-5`)
- Owner-only write access (create, update, delete, upload image)

## Project Structure

```text
app/
  Http/Controllers/
    CalendarController.php
    RecordController.php
    RecordImageController.php
    LoginController.php
  Repositories/
    RecordRepository.php
  Services/
    ImageStorageService.php
  Support/
    DateRecord.php

resources/views/
  calendar/index.blade.php
  records/form.blade.php
  auth/login.blade.php
  layouts/app.blade.php

routes/web.php
database/                     # SQLite file for records/auth locally
```

## Data Storage

### 1) Daily record content (database)

Records are stored in `records` and `record_images` tables.

Stored fields include:

- `level` (manual `0-5`, default `0`)
- `calendar_note`
- `ramblings`
- `health` section
- `study` section
- `images` metadata (URL/path/caption/time), not binary image bytes

### 2) Authentication/session data (database)

Owner authentication uses Laravel `users` table in the same database.

### 3) Images

Image files are stored through the Laravel filesystem disk selected by `IMAGE_DISK`:

- `IMAGE_DISK=public` -> local storage
- `IMAGE_DISK=s3` -> Cloudflare R2 (recommended for smaller local disk usage)

## Access Model

- Public (read): calendar page and record query APIs
- Authenticated owner (write): create record, update record, delete record, upload image

Current route behavior:

- `GET /calendar` (public)
- `GET /records/range` (public)
- `GET /records/search` (public)
- `GET /records/{date}` (public)
- `GET /records/create` (auth)
- `POST /records` (auth)
- `PUT /records/{date}` (auth)
- `DELETE /records/{date}` (auth)
- `POST /records/{date}/images` (auth)

Delete behavior:

- Deleting a record removes the database row and its related `record_images` rows.
- The app also attempts to delete related image objects from the configured `IMAGE_DISK` (for example Cloudflare R2 when `IMAGE_DISK=s3`).

Search behavior:

- Keyword is optional.
- Date range is optional. If no date range is set, search runs across all dates.
- Section filters are multi-select and optional; if none are selected, all sections are searched.

## Local Setup

1. Install dependencies:

```bash
composer install
```

2. Create env file and app key:

```bash
cp .env.example .env
php artisan key:generate
```

3. Run migrations:

```bash
php artisan migrate
```

4. (Optional, recommended) seed owner user:

```bash
php artisan db:seed
```

5. Start development server:

```bash
php artisan serve
```

6. Open:

- [http://127.0.0.1:8000/calendar](http://127.0.0.1:8000/calendar)

## Required Environment Variables

Minimum useful values:

```env
APP_NAME=SummerDiscipline
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=America/New_York

DB_CONNECTION=sqlite
SESSION_DRIVER=file
CACHE_STORE=file

OWNER_NAME=YourName
OWNER_EMAIL=you@example.com
OWNER_PASSWORD=your_password

IMAGE_DISK=public
```

## Cloudflare R2 (Optional)

To store images in R2:

```env
IMAGE_DISK=s3
FILESYSTEM_DISK=public

AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=auto
AWS_BUCKET=your_bucket_name
AWS_ENDPOINT=https://<account_id>.r2.cloudflarestorage.com
AWS_URL=https://pub-<hash>.r2.dev
AWS_USE_PATH_STYLE_ENDPOINT=false
```

## Deployment Notes

- For personal local-only usage, deployment is optional.
- If deploying:
  - set `APP_ENV=production`, `APP_DEBUG=false`, and real `APP_URL`
  - ensure persistent storage for `database/database.sqlite` if using SQLite
  - rotate any leaked secrets before production release

## Security Notes

- Never commit `.env` to Git.
- Use a strong owner password in production.
- Prefer HTTPS when exposed to the internet.
