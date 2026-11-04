<script>
/* آکاردئون کشویی سوالات متداول: روی <details> استاندارد سوار است و بدون JS یا با «کاهش حرکت» به‌صورت عادی کار می‌کند. */
(function () {
    var items = document.querySelectorAll('details.fq-item');
    if (!items.length || typeof Element.prototype.animate !== 'function') return;
    var reduce = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
    var EASE = 'cubic-bezier(.4,0,.2,1)';

    items.forEach(function (el) {
        var summary = el.querySelector('summary');
        var content = el.querySelector('.fq-a');
        if (!summary || !content) return;
        var anim = null, fade = null;

        function frame() { return el.offsetHeight - el.clientHeight; }
        function stopFade() { if (fade) { fade.cancel(); fade = null; } }
        function finish(open) {
            el.open = open;
            el.classList.remove('is-closing');
            el.style.height = '';
            anim = null;
            stopFade();
        }
        function expand() {
            el.classList.remove('is-closing');
            el.style.height = el.offsetHeight + 'px';
            el.open = true;
            window.requestAnimationFrame(function () {
                var from = el.offsetHeight;
                var to = summary.offsetHeight + content.offsetHeight + frame();
                if (anim) anim.cancel();
                stopFade();
                anim = el.animate({ height: [from + 'px', to + 'px'] }, { duration: 400, easing: EASE });
                fade = content.animate([{ opacity: 0, transform: 'translateY(-8px)' }, { opacity: 1, transform: 'none' }], { duration: 400, easing: EASE });
                anim.onfinish = function () { finish(true); };
            });
        }
        function shrink() {
            var from = el.offsetHeight;
            var to = summary.offsetHeight + frame();
            el.classList.add('is-closing');
            if (anim) anim.cancel();
            stopFade();
            el.style.height = from + 'px';
            anim = el.animate({ height: [from + 'px', to + 'px'] }, { duration: 360, easing: EASE });
            fade = content.animate([{ opacity: 1 }, { opacity: 0 }], { duration: 220, easing: EASE, fill: 'forwards' });
            anim.onfinish = function () { finish(false); };
        }

        summary.addEventListener('click', function (e) {
            if (reduce && reduce.matches) return; // رفتار پیش‌فرض مرورگر
            e.preventDefault();
            if (el.open && !el.classList.contains('is-closing')) shrink(); else expand();
        });
    });
})();
</script>
