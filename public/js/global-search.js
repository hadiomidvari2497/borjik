(function () {
  'use strict';
  function init() {
    var input = document.getElementById('global-search-input');
    var panel = document.getElementById('global-search-results');
    if (!input || !panel) return;

    var timer = null;
    var controller = null;
    var activeIndex = -1;
    var links = [];
    var endpoint = input.getAttribute('data-endpoint');

    function close() {
      panel.hidden = true;
      input.setAttribute('aria-expanded', 'false');
      activeIndex = -1;
    }
    function setActive(index) {
      if (!links.length) return;
      activeIndex = (index + links.length) % links.length;
      links.forEach(function (link, i) {
        link.classList.toggle('is-active', i === activeIndex);
        link.setAttribute('aria-selected', i === activeIndex ? 'true' : 'false');
      });
      links[activeIndex].scrollIntoView({block:'nearest'});
    }
    function render(items, query) {
      panel.replaceChildren();
      links = [];
      activeIndex = -1;
      if (!items.length) {
        var empty = document.createElement('div');
        empty.className = 'global-search-empty';
        empty.textContent = 'نتیجه‌ای برای «' + query + '» پیدا نشد.';
        panel.appendChild(empty);
      } else {
        var lastGroup = '';
        items.forEach(function (item) {
          if (item.group !== lastGroup) {
            var heading = document.createElement('div');
            heading.className = 'global-search-group';
            heading.textContent = item.group;
            panel.appendChild(heading);
            lastGroup = item.group;
          }
          var link = document.createElement('a');
          link.className = 'global-search-result';
          link.href = item.url;
          link.setAttribute('role', 'option');
          link.setAttribute('aria-selected', 'false');
          var icon = document.createElement('span');
          icon.className = 'global-search-result-icon';
          icon.setAttribute('aria-hidden', 'true');
          icon.textContent = ({unit:'⌂',user:'♙',request:'↗',contract:'▤',invoice:'﷼',document:'▧'})[item.icon] || '⌕';
          var copy = document.createElement('span');
          copy.className = 'global-search-result-copy';
          var title = document.createElement('strong');
          title.textContent = item.title;
          var subtitle = document.createElement('small');
          subtitle.textContent = item.subtitle || '';
          copy.append(title, subtitle);
          link.append(icon, copy);
          panel.appendChild(link);
          links.push(link);
        });
      }
      panel.hidden = false;
      input.setAttribute('aria-expanded', 'true');
    }
    function search() {
      var query = input.value.trim();
      if (query.length < 2) { close(); return; }
      if (controller) controller.abort();
      controller = new AbortController();
      panel.replaceChildren();
      var loading = document.createElement('div');
      loading.className = 'global-search-empty';
      loading.textContent = 'در حال جست‌وجو…';
      panel.appendChild(loading);
      panel.hidden = false;
      input.setAttribute('aria-expanded', 'true');
      fetch(endpoint + '?q=' + encodeURIComponent(query), {
        credentials:'same-origin',
        headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},
        signal:controller.signal
      }).then(function (response) {
        if (!response.ok) throw new Error('search_failed');
        return response.json();
      }).then(function (data) {
        if (input.value.trim() === query) render(data.results || [], query);
      }).catch(function (error) {
        if (error.name === 'AbortError') return;
        panel.replaceChildren();
        var failed = document.createElement('div');
        failed.className = 'global-search-empty';
        failed.textContent = 'جست‌وجو انجام نشد؛ دوباره تلاش کن.';
        panel.appendChild(failed);
      });
    }

    input.addEventListener('input', function () {
      window.clearTimeout(timer);
      timer = window.setTimeout(search, 260);
    });
    input.addEventListener('focus', function () {
      if (input.value.trim().length >= 2) search();
    });
    input.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') { close(); input.blur(); return; }
      if (event.key === 'ArrowDown' && !panel.hidden) { event.preventDefault(); setActive(activeIndex + 1); }
      if (event.key === 'ArrowUp' && !panel.hidden) { event.preventDefault(); setActive(activeIndex < 0 ? links.length - 1 : activeIndex - 1); }
      if (event.key === 'Enter' && activeIndex >= 0 && links[activeIndex]) {
        event.preventDefault();
        window.location.assign(links[activeIndex].href);
      }
    });
    document.addEventListener('click', function (event) {
      if (!event.target.closest('.global-search')) close();
    });
    document.addEventListener('keydown', function (event) {
      if (event.key !== '/' || event.ctrlKey || event.metaKey || event.altKey) return;
      var target = event.target;
      if (target && (/INPUT|TEXTAREA|SELECT/.test(target.tagName) || target.isContentEditable)) return;
      event.preventDefault();
      input.focus();
    });
    panel.addEventListener('click', function (event) {
      var link = event.target.closest('a[href]');
      if (link) close();
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
