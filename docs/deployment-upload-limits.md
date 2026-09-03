# Production upload configuration

Apply these settings on the VPS hosting **https://app.vspcrm.in** before relying on Task Management uploads.

The application validates uploads in Laravel, but PHP and the web server can still block files first. **The lowest limit across PHP, Laravel, Spatie Media Library, and the web server becomes the effective upload limit.**

## Application limits (enforced in code)

| Upload area | Per-file limit |
|---|---|
| Task working files | **600 MB** |
| Creative review / proof files | **600 MB** |
| Company logo library | **600 MB** |
| Operations documents | **600 MB** |
| Content calendar attachments | **600 MB** |
| Task attachments on create | **600 MB** |
| Notification sounds | **600 MB** |

The user-facing rejection message is:

`File size cannot exceed 600 MB.`

Multi-file uploads are also limited by the total HTTP request size. The application validates against a documented **600 MB** combined request limit.

**Important:** `post_max_size` is the total request body size. With `post_max_size = 600M`, only one 600 MB file can be uploaded per request.

Task working files intentionally **do not** accept video uploads. Reels/videos belong in Creative review proofs.

## Required PHP settings

Edit the PHP configuration used by PHP-FPM or Apache on the VPS:

```ini
upload_max_filesize = 600M
post_max_size = 600M
max_file_uploads = 20
```

Restart PHP-FPM or Apache after changing PHP settings.

## Required Nginx settings

Inside the `server` block (or `http` block if you prefer a global default):

```nginx
client_max_body_size 600M;
```

The site template in [`deploy/nginx/app.vspcrm.in.conf`](../deploy/nginx/app.vspcrm.in.conf) includes this value.

Reload or restart Nginx after changing the configuration:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

## Apache note

If the VPS uses Apache with `mod_php` or PHP-FPM behind Apache, still set the PHP values above. Apache itself does not need a separate body-size directive for normal Laravel uploads once PHP and any reverse proxy are configured correctly.

## Spatie Media Library

`config/media-library.php` sets a global Spatie maximum of **600 MB**. Laravel validation enforces the same limit per upload area.

## Restart checklist

After deployment or configuration changes, restart the services that cache PHP settings:

1. PHP-FPM **or** Apache (whichever executes PHP)
2. Nginx (if used as the web server or reverse proxy)

Verify with `phpinfo()` or `php -i` on the **web SAPI**, not only the CLI binary, because CLI and FPM/Apache can use different `php.ini` files.

## Effective limit example

| Layer | Limit |
|---|---|
| Laravel upload validation | 600 MB |
| Spatie Media Library | 600 MB |
| PHP `upload_max_filesize` | Must be **≥ 600M** |
| PHP `post_max_size` | Must be **≥ 600M** |
| Nginx `client_max_body_size` | Must be **≥ 600M** |

If PHP is left at `upload_max_filesize = 2M`, the real limit is **2 MB** even though the application allows 600 MB uploads.

## Scheduler requirement

Automatic cleanup of temporary Working Files and Creative Review files runs daily via:

```bash
php artisan files:cleanup
```

Ensure cron is configured:

```bash
* * * * * cd /var/www/vsp-task-management && php artisan schedule:run >> /dev/null 2>&1
```
