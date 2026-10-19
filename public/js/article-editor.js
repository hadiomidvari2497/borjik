(() => {
    const boot = () => {
        const editor = document.getElementById('article-editor-canvas');
        const source = document.getElementById('body');
        const form = editor?.closest('form');
        if (!editor || !source || !form || editor.dataset.ready === '1') return;
        editor.dataset.ready = '1';
        editor.innerHTML = source.value || '';
        const counter = document.getElementById('article-editor-count');
        const number = new Intl.NumberFormat('fa-IR');

        const sync = () => {
            source.value = editor.innerHTML.trim();
            if (counter) counter.textContent = number.format((editor.innerText || '').trim().length) + ' نویسه';
        };
        const run = (command, value = null) => {
            editor.focus();
            document.execCommand(command, false, value);
            sync();
        };

        document.querySelectorAll('#article-editor .article-editor-toolbar button').forEach((button) => {
            button.addEventListener('mousedown', (event) => event.preventDefault());
            button.addEventListener('click', () => {
                if (button.dataset.command) {
                    run(button.dataset.command);
                } else if (button.dataset.block) {
                    run('formatBlock', button.dataset.block);
                } else if (button.dataset.action === 'link') {
                    const ask = window.Borjik && typeof window.Borjik.prompt === 'function'
                        ? window.Borjik.prompt({ title: 'افزودن لینک', text: 'نشانی لینک را وارد کنید', placeholder: 'https://example.com', maxlength: 1000 })
                        : Promise.resolve(null);
                    ask.then((url) => {
                        if (!url) return;
                        if (/^(https?:\/\/|mailto:|\/|#)/i.test(url.trim())) {
                            run('createLink', url.trim());
                        } else if (window.Borjik?.toastError) {
                            window.Borjik.toastError('نشانی لینک معتبر نیست.');
                        }
                    });
                } else if (button.dataset.action === 'clear') {
                    run('removeFormat');
                    run('unlink');
                }
            });
        });

        editor.addEventListener('input', sync);
        editor.addEventListener('paste', (event) => {
            event.preventDefault();
            const text = (event.clipboardData || window.clipboardData).getData('text/plain');
            document.execCommand('insertText', false, text);
            sync();
        });
        form.addEventListener('submit', sync);
        sync();

        const input = document.getElementById('cover_image');
        const preview = document.getElementById('article-cover-preview');
        if (input && preview) {
            input.addEventListener('change', () => {
                const file = input.files?.[0];
                if (!file) return;
                if (!file.type.startsWith('image/')) return;
                let image = preview.querySelector('img');
                if (!image) {
                    image = document.createElement('img');
                    image.alt = 'پیش‌نمایش تصویر جلد';
                    preview.prepend(image);
                }
                image.src = URL.createObjectURL(file);
            });
        }
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();
