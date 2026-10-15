# کتابخانه‌های لوکال (بدون CDN)

| کتابخانه | نسخه | فایل‌ها |
|---|---|---|
| Bootstrap (فقط Toast) | 5.3.8 | `bootstrap/bootstrap.bundle.min.js` (رسمی، بدون تغییر)، `bootstrap/bootstrap-toast.rtl.min.css` |
| SweetAlert2 | 11.26.25 | `sweetalert2/sweetalert2.min.js`، `sweetalert2/sweetalert2.min.css` (رسمی، بدون تغییر) |

`bootstrap-toast.rtl.min.css` فقط شامل `.toast`، `.btn-close` و transitionهاست و از SCSS رسمی Bootstrap کامپایل و RTL شده؛
پس استایل بقیه‌ی اپ (reboot و ...) تغییر نمی‌کند.

## به‌روزرسانی (روی سیستم توسعه، با npm)

```bash
npm pack sweetalert2@11 bootstrap@5.3 && for f in *.tgz; do mkdir -p "x/${f%.tgz}" && tar xzf "$f" -C "x/${f%.tgz}"; done
# SweetAlert2: dist/sweetalert2.min.js و dist/sweetalert2.min.css را کپی کنید
# Bootstrap JS: dist/js/bootstrap.bundle.min.js را کپی کنید
# Bootstrap CSS (فقط toast):
npm i sass rtlcss
cat > toast.scss <<'SCSS'
@import "functions"; @import "variables"; @import "variables-dark"; @import "maps"; @import "mixins";
@import "root"; @import "transitions"; @import "close"; @import "toasts";
SCSS
npx sass --no-source-map --style=compressed --load-path=x/bootstrap-*/package/scss toast.scss toast.css
npx rtlcss toast.css bootstrap-toast.rtl.min.css
```

لایسنس هر دو MIT است (فایل `LICENSE` کنار هرکدام).
