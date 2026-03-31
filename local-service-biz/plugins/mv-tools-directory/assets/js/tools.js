/**
 * MV Tools Directory — Frontend JS
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        /* ── Category filtering ── */
        var filterBtns = document.querySelectorAll('.mvtd-filter-btn');
        var cards      = document.querySelectorAll('.mvtd-card');
        var emptyState = document.getElementById('mvtd-empty');

        filterBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var cat = this.dataset.category;
                filterBtns.forEach(function (b) { b.classList.remove('active'); });
                this.classList.add('active');
                var shown = 0;
                cards.forEach(function (c) {
                    var match = cat === 'all' || c.dataset.category === cat;
                    c.style.display = match ? '' : 'none';
                    if (match) shown++;
                });
                if (emptyState) emptyState.style.display = shown === 0 ? 'block' : 'none';
            });
        });

        /* ── Card expand / collapse ── */
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.mvtd-expand-btn');
            if (!btn) return;
            var id      = btn.dataset.id;
            var details = document.getElementById('mvtd-details-' + id);
            if (!details) return;
            var isOpen = details.classList.contains('open');
            // Close all
            document.querySelectorAll('.mvtd-card-details.open').forEach(function (d) { d.classList.remove('open'); });
            document.querySelectorAll('.mvtd-expand-btn').forEach(function (b) {
                b.setAttribute('aria-expanded', 'false');
                b.querySelector('.mvtd-expand-label') && (b.querySelector('.mvtd-expand-label').textContent = 'Learn more');
            });
            // Open clicked
            if (!isOpen) {
                details.classList.add('open');
                btn.setAttribute('aria-expanded', 'true');
                var label = btn.querySelector('.mvtd-expand-label');
                if (label) label.textContent = 'Show less';
            }
        });
    });
}());
