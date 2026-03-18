# Unsaid Thoughts Production Deployment Checklist

## 1) Server Requirements
- PHP 8.1+ with `mysqli`, `openssl`, and `mbstring` enabled
- Apache with `.htaccess` overrides enabled (`AllowOverride All`)
- MySQL or MariaDB
- HTTPS enabled (recommended before go-live)

## 2) Upload Application
1. Upload all project files to your web root.
2. Confirm `.htaccess` is present in the project root.
3. Ensure file permissions allow PHP to read application files.

## 3) Create Production Database and User
Run in MySQL (example):

```sql
CREATE DATABASE unsaid_thoughts CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'unsaid_app'@'localhost' IDENTIFIED BY 'REPLACE_WITH_STRONG_PASSWORD';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, INDEX, ALTER ON unsaid_thoughts.* TO 'unsaid_app'@'localhost';
FLUSH PRIVILEGES;
```

Then import schema:

```bash
mysql -u unsaid_app -p unsaid_thoughts < schema.sql
```

## 4) Configure Environment Variables (Required)
This app now reads DB config from environment variables via `db_config.php`.

Set these variables in your host panel / Apache / server environment:
- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASSWORD`

Recommended values:
- `DB_HOST=localhost`
- `DB_PORT=3306`
- `DB_NAME=unsaid_thoughts`
- `DB_USER=unsaid_app`
- `DB_PASSWORD=<strong-random-password>`

## 5) First Admin Bootstrap (One-Time)
1. Open `admin/admin_config.php`.
2. Set:

```php
define('ADMIN_BOOTSTRAP_KEY', 'put-a-long-random-secret-here');
```

3. Open in browser:
- `/admin/admin_register.php?setup_key=put-a-long-random-secret-here`

4. Create your admin account.
5. Immediately clear bootstrap key:

```php
define('ADMIN_BOOTSTRAP_KEY', '');
```

## 6) Security Checks Before Go-Live
- Confirm admin login works only with DB admin account.
- Confirm repeated failed admin logins are rate-limited.
- Confirm direct access to utility scripts is blocked externally by `.htaccess`.
- Confirm `setup.php` returns `403` from non-local requests.
- Confirm site is served over HTTPS.

## 7) Functional Smoke Test
1. Public pages load:
- `home.php`
- `explore.php`
- `create.php`

2. Admin flow:
- Access `/admin/dashboard-admin.php` while logged out -> should redirect to login.
- Login with admin account -> should enter dashboard.
- Logout -> dashboard should be protected again.

3. Data flow:
- Create thought, view on explore/home, test delete/soft-delete flows in admin.

## 8) Post-Deployment Hardening (Recommended)
- Add daily DB backups.
- Add fail2ban / WAF / Cloudflare rate limiting.
- Restrict `/admin` by IP if possible.
- Disable directory listing in Apache (`Options -Indexes`).
- Monitor PHP and Apache error logs.

## 9) Rollback Plan
If deployment fails:
1. Restore previous app files.
2. Restore previous DB backup.
3. Revert environment variables.
4. Re-test admin login and public pages.

## 10) Quick Go-Live Signoff
- [ ] DB created and imported
- [ ] Env vars set correctly
- [ ] Admin account created
- [ ] ADMIN_BOOTSTRAP_KEY cleared
- [ ] HTTPS enabled
- [ ] Utility routes blocked
- [ ] Admin routes protected
- [ ] Smoke test passed
