# Production upload configuration

Apply these settings on the VPS hosting **https://app.vspcrm.in** before relying on the new Task Management upload limits.

The application validates uploads in Laravel, but PHP and the web server can still block files first. **The lowest limit across PHP, Laravel, Spatie Media Library, and the web server becomes the effective upload limit.**

## Application limits (enforced in code)

| Upload area | Allowed types | Per-file limit |
|---|---|---|
| Task working files | jpg, jpeg, png, gif, webp, pdf, doc, docx, xls, xlsx, csv, ppt, pptx, zip, txt, rtf | **50 MB** |
| Creative proof images | jpg, jpeg, png, gif, webp | **20 MB** |
| Creative proof videos | mp4, mov, webm | **100 MB** |
| Creative proof documents / design | pdf, doc, docx, ai, psd | **50 MB** |
| Notification sounds | mp3, wav, ogg | **5 MB** |

Multi-file uploads are also limited by the total HTTP request size. The application validates against a documented **150 MB** combined request limit.

**Important:** `post_max_size` is the total request body size. Allowing 100 MB per file does **not** mean multiple 100 MB files can be uploaded in one request when `post_max_size` is only 150 MB.

Task working files intentionally **do not** accept video uploads. Reels/videos belong in Creative review proofs.

## Required PHP settings

Edit the PHP configuration used by PHP-FPM or Apache on the VPS:

```ini
upload_max_filesize = 100M
post_max_size = 150M
max_file_uploads = 20
```

Restart PHP-FPM or Apache after changing PHP settings.

## Required Nginx settings

Inside the `server` block (or `http` block if you prefer a global default):

```nginx
client_max_body_size 100M;
```

Reload or restart Nginx after changing the configuration:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

## Apache note

If the VPS uses Apache with `mod_php` or PHP-FPM behind Apache, still set the PHP values above. Apache itself does not need a separate body-size directive for normal Laravel uploads once PHP and any reverse proxy are configured correctly.

## Spatie Media Library

`config/media-library.php` sets a global Spatie maximum of **100 MB**. Laravel validation keeps notification sounds at **5 MB** even though Spatie allows more.

## Restart checklist

After deployment or configuration changes, restart the services that cache PHP settings:

1. PHP-FPM **or** Apache (whichever executes PHP)
2. Nginx (if used as the web server or reverse proxy)

Verify with `phpinfo()` or `php -i` on the **web SAPI**, not only the CLI binary, because CLI and FPM/Apache can use different `php.ini` files.

## Effective limit example

| Layer | Limit |
|---|---|
| Laravel creative proof video rule | 100 MB |
| Spatie Media Library | 100 MB |
| PHP `upload_max_filesize` | Must be **≥ 100M** or videos above PHP’s value will fail |
| PHP `post_max_size` | Must be **≥ 150M** for documented multi-file behaviour |
| Nginx `client_max_body_size` | Must be **≥ 100M** |

If PHP is left at `upload_max_filesize = 2M`, the real limit is **2 MB** even though the application allows 100 MB videos.
