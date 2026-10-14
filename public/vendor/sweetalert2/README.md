# Vendor assets (SweetAlert2)

Packed as base64+gzip to keep the repository lean.

```bash
php scripts/expand-vendor.php
```

Also runs automatically after `composer install` / `composer update`.

Produces:
- `sweetalert2.min.css`
- `sweetalert2.all.min.js`

Version: SweetAlert2 **11.26.x** (local files, not CDN).
