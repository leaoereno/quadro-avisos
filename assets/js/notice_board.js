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

    // Renders raw HTML (which may include its own <style>) inside a sandboxed
    // iframe instead of innerHTML, so generic selectors in a notice's <style>
    // (e.g. .wrapper, header, .card) never leak out and override the host
    // page's own layout. Height auto-fits the content once it loads.
    function renderIsolatedHtml(container, html) {
        container.innerHTML = '';
        var frame = document.createElement('iframe');
        frame.setAttribute('sandbox', 'allow-same-origin');
        frame.style.cssText = 'width:100%;border:0;display:block;overflow:hidden;height:0;';
        container.appendChild(frame);
        frame.addEventListener('load', function () {
            try {
                var doc = frame.contentDocument;
                var h = Math.max(
                    doc.documentElement ? doc.documentElement.scrollHeight : 0,
                    doc.body ? doc.body.scrollHeight : 0
                );
                frame.style.height = h + 'px';
            } catch (e) {
                frame.style.height = '300px';
            }
        });
        frame.srcdoc = html;
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
                renderIsolatedHtml(el, raw);
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
