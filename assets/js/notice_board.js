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

    function renderContent(raw) {
        if (!raw) return '';
        var trimmed = raw.trim();
        return trimmed.charAt(0) === '<' ? trimmed : (window.marked ? window.marked.parse(trimmed) : trimmed);
    }

    // Render all .nb-rendered elements that have a data-raw attribute
    window.nbRenderAll = function () {
        loadMarked(function () {
            document.querySelectorAll('.nb-rendered[data-raw]').forEach(function (el) {
                el.innerHTML = renderContent(el.getAttribute('data-raw') || '');
                el.removeAttribute('data-raw');
            });
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        nbRenderAll();
    });

}());
