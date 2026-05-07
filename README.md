# Summer Discipline

A personal daily discipline journal built with Laravel, Blade, Alpine.js, and Tailwind CSS.

The app is designed for a single owner: anyone can view calendar data, but creating and editing records requires login.

## Tech Stack

- PHP `^8.3`
- Laravel `^13.7`
- Blade templates + Alpine.js
- Tailwind CSS (CDN)
- File-based JSON record storage
- Optional Cloudflare R2 (S3-compatible) for image files

## Features

- Monthly calendar heatmap with multiple color modes (overall, health, study)
- Day detail modal with notes and image preview
- Date-range query with quick filters (this week / this month / recent 10 days)
- One record per date (`YYYY-MM-DD`)
- Calendar short note + long-form ramblings
- Health and study modules with optional ratings (`1-5`)
- Owner-only write access (create, update, upload image)

## Project Structure

```text
app/
  Http/Controllers/
    CalendarController.php
    RecordController.php
    RecordImageController.php
    LoginController.php
  Repositories/
    RecordFileRepository.php
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
storage/app/records/          # Daily JSON records
database/                     # SQLite file (users/sessions, if used)
```

## Data Storage

### 1) Daily record content (JSON, local filesystem)

Records are stored as one file per day:

- `storage/app/records/YYYY/YYYY-MM-DD.json`

These JSON files store:

- `calendar_note`
- `ramblings`
- `health` section
- `study` section
- `images` metadata (URL/path/caption/time), not binary image bytes

### 2) Authentication/session data (database)

Owner authentication uses Laravel `users` table (default SQLite in local setup).

### 3) Images

Image files are stored through the Laravel filesystem disk selected by `IMAGE_DISK`:

- `IMAGE_DISK=public` -> local storage
- `IMAGE_DISK=s3` -> Cloudflare R2 (recommended for smaller local disk usage)

## Access Model

- Public (read): calendar page and record query APIs
- Authenticated owner (write): create record, update record, upload image

Current route behavior:

- `GET /calendar` (public)
- `GET /records/range` (public)
- `GET /records/{date}` (public)
- `GET /records/create` (auth)
- `POST /records` (auth)
- `PUT /records/{date}` (auth)
- `POST /records/{date}/images` (auth)

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

3. Run migrations and seed owner user:

```bash
php artisan migrate
php artisan db:seed
```

4. Start development server:

```bash
php artisan serve
```

5. Open:

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
  - ensure persistent storage for `storage/app/records` (and `database/database.sqlite` if using SQLite)
  - rotate any leaked secrets before production release

## Security Notes

- Never commit `.env` to Git.
- Use a strong owner password in production.
- Prefer HTTPS when exposed to the internet.
