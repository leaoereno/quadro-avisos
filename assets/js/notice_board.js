/**
 * Notice Board — JavaScript
 *
 * Dependency: marked.js (loaded via CDN if not available globally)
 * Functions:
 *   nbRenderAll()    - renders all .nb-rendered[data-raw] elements
 *   nbLivePreview()  - updates live preview in the form (called from view)
 *   nbSetTab(tab)    - switches editor tabs
 */

(function () {
    'use strict';

    function loadMarked(callback) {
        if (typeof window.marked !== 'undefined') {
            callback();
            return;
        }
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/marked/marked.min.js';
        s.onload = callback;
        document.head.appendChild(s);
    }

    // Render all .nb-rendered elements that have a data-raw attribute.
    // HTML content (starts with "<") never needs marked.js, so it renders
    // immediately instead of waiting on the CDN script — if that script
    // fails to load (no internet egress, F5, CSP, etc.) HTML notices no
    // longer get stuck unrendered.
    window.nbRenderAll = function () {
        var pending = [];

        document.querySelectorAll('.nb-rendered[data-raw]').forEach(function (el) {
            var raw = (el.getAttribute('data-raw') || '').trim();
            if (!raw) {
                el.removeAttribute('data-raw');
                return;
            }
            if (raw.charAt(0) === '<') {
                el.innerHTML = raw;
                el.removeAttribute('data-raw');
            } else {
                pending.push(el);
            }
        });

        if (!pending.length) return;

        loadMarked(function () {
            pending.forEach(function (el) {
                el.innerHTML = window.marked.parse(el.getAttribute('data-raw') || '', {breaks: true, gfm: true});
                el.removeAttribute('data-raw');
            });
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        nbRenderAll();
    });

}());
