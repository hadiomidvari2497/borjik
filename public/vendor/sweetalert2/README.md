# SweetAlert2 (local, no CDN)

Version **11.26.25**

## Generate the JS/CSS files

From the project root:

```bash
php scripts/install-sweetalert2.php
```

This expands the `.pack` files into:

- `sweetalert2.min.css`
- `sweetalert2.all.min.js`

## Or download once yourself and commit

```bash
mkdir -p public/vendor/sweetalert2
curl -L -o public/vendor/sweetalert2/sweetalert2.min.css https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25/dist/sweetalert2.min.css
curl -L -o public/vendor/sweetalert2/sweetalert2.all.min.js https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25/dist/sweetalert2.all.min.js
git add public/vendor/sweetalert2
git commit -m "vendor: SweetAlert2 11.26.25 local assets"
git push
```

Layout loads these files automatically when they exist.
