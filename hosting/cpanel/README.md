# SkedBetter — cPanel Shared Hosting Deployment

## File Structure

```
/home/username/
├── skedbetter/              ← Full repo (uploaded via File Manager or FTP)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/
│   │   ├── build/           ← Pre-built frontend assets
│   │   └── setup.php
│   ├── resources/
│   ├── storage/
│   ├── vendor/              ← From 'composer install --no-dev'
│   └── ...
├── public_html/             ← Your web root (DO NOT delete)
│   ├── index.php            ← Shim (from this folder)
│   ├── .htaccess            ← Rewrite rules (from this folder)
│   ├── setup.php            ← Setup shim (from this folder)
│   ├── build/               ← COPY from skedbetter/public/build/
│   ├── favicon.ico          ← COPY from skedbetter/public/favicon.ico
│   └── robots.txt           ← COPY from skedbetter/public/robots.txt
```

## Step-by-Step Deployment

### On your local machine (WSL/Mac/Linux):

```bash
cd /var/www/html/skedbetter

# Install PHP dependencies (no dev packages)
composer install --no-dev --optimize-autoloader

# Build frontend assets
npm install
npm run build
```

### On cPanel (via File Manager or FTP):

1. **Create folder:** `/home/username/skedbetter`

2. **Upload the entire repo** to `/home/username/skedbetter/`
   - Include: `app/`, `bootstrap/`, `config/`, `database/`, `public/`, `resources/`, `routes/`, `storage/`, `vendor/`, `artisan`, `composer.json`
   - The `vendor/` folder is large — upload it or zip+upload+extract

3. **Copy to public_html:**
   - Copy `hosting/cpanel/index.php` → `/home/username/public_html/index.php`
   - Copy `hosting/cpanel/.htaccess` → `/home/username/public_html/.htaccess`
   - Copy `hosting/cpanel/setup.php` → `/home/username/public_html/setup.php`
   - Copy `public/build/` → `/home/username/public_html/build/`
   - Copy `public/favicon.ico` → `/home/username/public_html/favicon.ico`
   - Copy `public/robots.txt` → `/home/username/public_html/robots.txt`

4. **Set permissions** (via File Manager → Permissions):
   - `/home/username/skedbetter/storage/` → 775 (recursive)
   - `/home/username/skedbetter/bootstrap/cache/` → 775 (recursive)

5. **Set PHP version** (cPanel → MultiPHP Manager):
   - Set `skedbetter.com` to PHP 8.2 or 8.3

6. **Run setup:**
   - Visit `https://skedbetter.com/setup.php`
   - Enter database credentials (create DB first via cPanel → MySQL Databases)
   - Create superadmin account
   - Delete `public_html/setup.php` when done

## Updating

To update after code changes:

1. Run `composer install --no-dev` and `npm run build` locally
2. Upload changed files to `/home/username/skedbetter/`
3. Re-copy `public/build/` to `public_html/build/` if frontend changed
4. Visit `https://skedbetter.com` — migrations run automatically if needed

## Troubleshooting

- **500 error:** Check `skedbetter/storage/logs/laravel.log`
- **Blank page:** Enable PHP error display in cPanel → MultiPHP INI Editor
- **Permissions:** Storage and cache must be writable (775)
- **PHP version:** Must be 8.2+, check in MultiPHP Manager
