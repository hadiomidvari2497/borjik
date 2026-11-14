/* ابزارهای مشترک نقشه‌های بازار املاک (نقشه‌ی فهرست، جزئیات آگهی و انتخاب موقعیت).
   باید بعد از SDK نشان (maplibregl) بارگذاری شود. */
(function () {
    'use strict';
    var FA = '۰۱۲۳۴۵۶۷۸۹';
    var STYLE = 'https://static.neshan.org/sdk/maplibre/styles/light.json';
    var CENTER = [51.389, 35.6892];

    function fa(value) {
        return String(value).replace(/\d/g, function (d) { return FA[d]; });
    }

    function debounce(fn, wait) {
        var timer = null;
        return function () {
            var args = arguments, self = this;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(self, args); }, wait);
        };
    }

    /** چندضلعی تقریبی دایره‌ای به شعاع radiusM متر دور (lng, lat) برای نمایش محدوده‌ی تقریبی. */
    function circle(lng, lat, radiusM, steps) {
        steps = steps || 64;
        var coords = [], dLat = radiusM / 111320, dLng = radiusM / (111320 * Math.cos(lat * Math.PI / 180));
        for (var i = 0; i <= steps; i++) {
            var a = (i / steps) * 2 * Math.PI;
            coords.push([lng + dLng * Math.cos(a), lat + dLat * Math.sin(a)]);
        }
        return { type: 'Feature', properties: {}, geometry: { type: 'Polygon', coordinates: [coords] } };
    }

    /** نقشه را با کنترل‌های استاندارد (زوم، مقیاس و در صورت نیاز «موقعیت من») می‌سازد. */
    function create(opts) {
        var map = new maplibregl.Map({
            container: opts.container,
            style: STYLE,
            center: opts.center || CENTER,
            zoom: opts.zoom == null ? 11 : opts.zoom,
            apiKey: opts.apiKey,
            attributionControl: true
        });
        try {
            map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-left');
            if (opts.locate && maplibregl.GeolocateControl) {
                map.addControl(new maplibregl.GeolocateControl({ positionOptions: { enableHighAccuracy: true }, trackUserLocation: false }), 'top-left');
            }
            if (maplibregl.ScaleControl) {
                map.addControl(new maplibregl.ScaleControl({ unit: 'metric' }), 'bottom-left');
            }
        } catch (error) {
            console.warn('Map controls unavailable:', error);
        }
        return map;
    }

    /** نقشه تا نزدیک شدن به صفحه‌ی نمایش ساخته نمی‌شود (صرفه‌جویی در بارگذاری نقشه و سهمیه‌ی سرویس). */
    function whenVisible(el, callback) {
        if (!('IntersectionObserver' in window)) { callback(); return; }
        var observer = new IntersectionObserver(function (entries) {
            if (entries.some(function (e) { return e.isIntersecting; })) { observer.disconnect(); callback(); }
        }, { rootMargin: '250px' });
        observer.observe(el);
    }

    window.MarketMap = { STYLE: STYLE, CENTER: CENTER, fa: fa, debounce: debounce, circle: circle, create: create, whenVisible: whenVisible };
})();
