# Live server (Hostinger)

How the farm app runs on Hostinger and gets its updates.

## How an update flows

1. Code is pushed to `main` on GitHub (`KlokAds/matir-khamar`).
2. The **Hostinger deploy** workflow builds it (PHP packages without dev tools, the built
   frontend) and commits the result to the `hostinger-dist` branch.
3. On the site, the admin opens **Setup → App update** and presses **Update now** (or
   `php artisan khamar:update` over SSH; with `KHAMAR_AUTO_UPDATE=true` the scheduler does it
   every 10 minutes). The update takes a database backup, checks out the new build, runs the
   migrations and rebuilds the caches. `.env`, vouchers and backups are never touched.

## One-time setup

Code lives outside `public_html`; the subdomain's folder points at the app's `public/`.

```bash
cd ~
git clone --branch hostinger-dist https://github.com/KlokAds/matir-khamar.git klokads_khamar_app
cd klokads_khamar_app
cp .env.example .env            # then fill in APP_URL, DB_*, APP_ENV=production, APP_DEBUG=false
/opt/alt/php85/usr/bin/php artisan key:generate
/opt/alt/php85/usr/bin/php artisan migrate --force --seed
/opt/alt/php85/usr/bin/php artisan khamar:create-admin
/opt/alt/php85/usr/bin/php artisan storage:link
/opt/alt/php85/usr/bin/php artisan optimize
# point the subdomain at the app:
rm -rf ~/domains/<subdomain>/public_html && ln -s ~/klokads_khamar_app/public ~/domains/<subdomain>/public_html
```

The repository is private: store a GitHub login for the server user once
(`git config --global credential.helper store`, then one `git fetch` with a token as the password)
so the in-app update can fetch.

Scheduler (backups at 02:00, lease months, billing sync, auto-update): no hPanel cron. With
`KHAMAR_WEB_CRON=true` (the installer writes it) the first web request of each minute starts
`schedule:run` in the background. Add a free uptime monitor that pings `https://<site>/up` every
5 minutes so the nightly backup runs even when nobody opens the site.

**Never change `APP_KEY` on the live site**: the billing password and the backups are encrypted
with it. Keep a copy of it somewhere safe, apart from the backups.

Production `.env` must have `KHAMAR_REQUIRE_2FA=true`, `SESSION_SECURE_COOKIE=true` and an
`https://` `APP_URL` (offline mode needs https).
