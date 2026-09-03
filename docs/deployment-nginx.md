# Nginx configuration for VSP CRM

VSP CRM is a **Laravel + Inertia** application, not a standalone SPA. Every URL such as `/tasks/8`, `/tasks/projects/4`, or `/admin/employees/3/edit` must be routed to Laravel's front controller:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

Do **not** configure a separate SPA fallback to `index.html`. Laravel serves the HTML shell and Inertia handles client navigation after the first load.

## 502 Bad Gateway on page refresh

Production logs showed this error when refreshing task detail pages:

```text
upstream sent too big header while reading response header from upstream
request: "GET /tasks/5 HTTP/1.1"
```

### Root cause

- **Inertia client navigation** (`GET /tasks/8` with `X-Inertia: true`) returns a compact JSON response.
- **Browser refresh** (`GET /tasks/8` full document) renders the Blade shell, Ziggy routes, Vite tags, session cookies, and (previously) many `Link: rel=preload` headers.
- Nginx's default FastCGI header buffer (~4–8 KB) is too small for those combined response headers.
- Nginx rejects the upstream response and returns **502 Bad Gateway** before Laravel can send the page body.

This affects any heavy Inertia page on full document load, not only task detail:

- `/tasks/{id}`
- `/tasks/projects/{id}`
- `/tasks/logo-library/{company}`
- `/admin/employees/{id}/edit`

### Application fix (in repo)

`AddLinkHeadersForPreloadedAssets` was removed from the global web middleware in `bootstrap/app.php`. That middleware is an optional optimization and was the main source of oversized `Link` headers on document loads.

### Server fix (required on production)

Use the site template in [`deploy/nginx/app.vspcrm.in.conf`](../deploy/nginx/app.vspcrm.in.conf), especially:

```nginx
fastcgi_buffer_size 128k;
fastcgi_buffers 8 128k;
fastcgi_busy_buffers_size 256k;
fastcgi_read_timeout 120s;
```

Apply on the VPS:

```bash
sudo cp /var/www/vsp-task-management/deploy/nginx/app.vspcrm.in.conf /etc/nginx/sites-available/app.vspcrm.in
# Merge SSL lines from certbot if the site already has HTTPS configured.
sudo nginx -t
sudo systemctl reload nginx
```

Then run the verification script:

```bash
cd /var/www/vsp-task-management
bash deploy/verify-dynamic-pages.sh
```

### Verify after deployment

```bash
sudo tail -f /var/log/nginx/error.log
```

In the browser:

1. Open `/tasks`
2. Click a task → `/tasks/8`
3. Refresh `/tasks/8`
4. Open `/tasks/8` directly in a new tab
5. Hard refresh `/tasks/8`

You should see **200** responses and no new `too big header` lines in the Nginx error log.

## Related docs

- Upload limits: [`deployment-upload-limits.md`](deployment-upload-limits.md)
