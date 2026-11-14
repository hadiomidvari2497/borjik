(function () {
    'use strict';

    var toastTimer = null;

    function persianNumber(value) {
        return String(value).replace(/\d/g, function (digit) {
            return '۰۱۲۳۴۵۶۷۸۹'[digit];
        });
    }

    function showToast(message, isError) {
        var toast = document.querySelector('[data-favorite-toast]');
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'market-favorite-toast';
            toast.setAttribute('data-favorite-toast', '');
            toast.setAttribute('role', 'status');
            toast.setAttribute('aria-live', 'polite');
            document.body.appendChild(toast);
        }

        toast.textContent = message || 'وضعیت نشان آگهی به‌روزرسانی شد.';
        toast.style.borderColor = isError ? '#f0caca' : '';
        toast.style.color = isError ? '#9b3434' : '';
        toast.classList.add('is-visible');
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(function () {
            toast.classList.remove('is-visible');
        }, 2600);
    }

    function updateButton(form, saved) {
        var button = form.querySelector('button[type="submit"]');
        if (!button) return;

        button.classList.toggle('is-saved', saved);
        button.setAttribute('aria-pressed', saved ? 'true' : 'false');
        button.setAttribute('aria-label', saved ? 'حذف از نشان‌شده‌ها' : 'نشان کردن آگهی');
        button.setAttribute('title', saved ? 'نشان‌شده' : 'نشان کردن');

        var label = button.querySelector('span');
        if (label) label.textContent = saved ? 'نشان‌شده' : 'نشان کردن';
    }

    function renderEmptySavedList(grid) {
        if (!grid || grid.querySelector('[data-favorite-card]')) return;

        grid.innerHTML = '<div class="empty" style="grid-column:1/-1">' +
            '<div class="empty-ico"><svg class="icon" aria-hidden="true"><use href="#i-bookmark"/></svg></div>' +
            '<h3>هنوز آگهی‌ای نشان نکرده‌ای</h3>' +
            '<p class="muted">وقتی در میان آگهی‌ها موردی توجهت را جلب کرد، نشانش کن تا اینجا نگه داشته شود.</p>' +
            '<div class="actions" style="justify-content:center"><a class="btn lg" href="/properties">رفتن به بازار</a></div>' +
            '</div>';

        var pagination = document.querySelector('.pagination');
        if (pagination) pagination.remove();
    }

    function updateSavedListAfterRemoval(card) {
        var grid = document.getElementById('saved-listings-grid');
        if (!grid || !card) return;

        card.classList.add('is-removing');
        window.setTimeout(function () {
            card.remove();

            var count = document.querySelector('[data-favorites-count]');
            if (count) {
                var next = Math.max(0, Number(count.getAttribute('data-count') || 0) - 1);
                count.setAttribute('data-count', String(next));
                count.textContent = persianNumber(next) + ' آگهی ذخیره‌شده';
            }

            renderEmptySavedList(grid);
        }, 210);
    }

    document.addEventListener('submit', function (event) {
        var form = event.target.closest('form[data-favorite-form]');
        if (!form) return;

        event.preventDefault();

        var button = form.querySelector('button[type="submit"]');
        if (!button || button.disabled) return;

        var wasSaved = button.classList.contains('is-saved');
        button.disabled = true;
        button.classList.add('is-saving');

        fetch(form.action, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new FormData(form)
        }).then(function (response) {
            return response.json().then(function (data) {
                if (!response.ok) {
                    throw new Error(data.message || 'ذخیره آگهی انجام نشد. دوباره تلاش کن.');
                }
                return data;
            });
        }).then(function (data) {
            updateButton(form, Boolean(data.saved));

            var card = form.closest('[data-favorite-card]');
            if (card && !data.saved && document.getElementById('saved-listings-grid')) {
                updateSavedListAfterRemoval(card);
            }

            showToast(data.message, false);
        }).catch(function (error) {
            showToast(error.message || 'ارتباط با سرور برقرار نشد. دوباره تلاش کن.', true);
        }).finally(function () {
            button.disabled = false;
            button.classList.remove('is-saving');
        });
    });
})();
