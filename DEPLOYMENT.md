# Hostinger Deployment

## Before Upload

1. Export your latest project files.
2. Copy `.env.example` to `.env` and fill only production values.
3. Do not upload your local `.env` if it still contains localhost credentials or test values.
4. Keep Razorpay live keys only in the production `.env`.

## Upload To hPanel

1. Open Hostinger `hPanel`.
2. Open `File Manager`.
3. Upload the project into `public_html` or your target subfolder.
4. Keep the folder structure unchanged.

## PHP Version

Set the site to `PHP 8.2` or newer in hPanel before testing the app.

## Required PHP Extensions

Make sure these extensions are enabled in Hostinger:

- `mysqli`
- `curl`
- `mbstring`
- `fileinfo`
- `openssl`
- `json`

## Database Setup

1. Create a MySQL database in hPanel.
2. Update the production `.env` with the new DB credentials.
3. Import these SQL files into the database:
   - `config/auth_freemium_schema.sql`
   - `config/admin_schema.sql`
   - `config/typing_preference_schema.sql`
4. Create your first admin user with a securely hashed password.

Generate a password hash locally:

```powershell
C:\xampp\php\php.exe -r "echo password_hash('ChangeMeNow123', PASSWORD_DEFAULT), PHP_EOL;"
```

Then insert the admin in phpMyAdmin or Hostinger MySQL:

```sql
INSERT INTO admins (username, password)
VALUES ('admin', 'PASTE_GENERATED_HASH_HERE');
```

## Environment File

Create `.env` in the project root with values based on `.env.example`.

Example:

```env
APP_NAME=Ahilya Typing
APP_ENV=production
BASE_URL=https://your-domain.com/
DB_HOST=localhost
DB_USER=hostinger_db_user
DB_PASS=hostinger_db_password
DB_NAME=hostinger_db_name
RAZORPAY_KEY_ID=rzp_live_xxxxx
RAZORPAY_KEY_SECRET=xxxxx
```

## Existing Site Upgrade

If your old `test_attempts` table was created before guest tracking was added, run these SQL statements once:

```sql
-- or import config/upgrade_test_attempts_schema.sql
ALTER TABLE test_attempts
    ADD COLUMN guest_session_id VARCHAR(64) DEFAULT NULL AFTER student_id,
    ADD COLUMN access_type ENUM('guest','paid') NOT NULL DEFAULT 'paid' AFTER typed_words;

CREATE INDEX idx_attempts_guest ON test_attempts (guest_session_id);
```

If one of those columns already exists, remove that line and run the remaining statement manually.

## Production Checklist

1. Confirm `APP_ENV=production`.
2. Confirm `BASE_URL` matches the final domain exactly.
3. Confirm Razorpay keys are the correct live keys.
4. Confirm `logs/error.log` is writable by PHP on the server.
5. Confirm Apache `.htaccess` is uploaded.
6. Confirm HTTPS is enabled for the domain in Hostinger.
7. If you deploy into a subfolder instead of `public_html` root, review `.htaccess` error document paths.

## Retest After Deploy

1. Open home page.
2. Register a student account.
3. Run guest tests and confirm the count goes from 5 to 0.
4. Confirm the last guest test sends the user to account creation/payment flow.
5. Log in as student and verify dashboard data.
6. Create a Razorpay order and complete a payment in the correct environment.
7. Check admin student list, paragraph management, and manual 30-day activation.
