/**
 * Quadro de Avisos — JavaScript
 *
 * Dependência: marked.js (carregado via CDN abaixo se não disponível globalmente)
 * Funções principais:
 *   qaRenderAll()      — renderiza todos os .qa-rendered com data-raw
 *   qaUpdatePreview()  — atualiza preview em tempo real no formulário
 *   qaSetTab(tab)      — alterna tabs do editor
 *   qaInitFilters()    — filtro de busca e tipo/status nos cards
 */

(function () {
    'use strict';

    // ── Carrega marked.js dinamicamente se necessário ────────────────────────
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

    // ── Renderiza Markdown ou HTML em todos os elementos .qa-rendered ────────
    window.qaRenderAll = function () {
        loadMarked(function () {
            document.querySelectorAll('.qa-rendered[data-raw]').forEach(function (el) {
                var raw = el.getAttribute('data-raw') || '';
                el.innerHTML = window.marked ? window.marked.parse(raw) : raw;
                el.removeAttribute('data-raw'); // evita re-render duplo
            });
        });
    };

    // ── Preview em tempo real no formulário ─────────────────────────────────
    window.qaUpdatePreview = function () {
        loadMarked(function () {
            var textarea = document.getElementById('conteudo');
            var titleEl  = document.getElementById('titulo');
            if (!textarea) return;

            var raw = textarea.value || '';
            var rendered = window.marked ? window.marked.parse(raw) : raw;

            // Painel de preview (split/preview tab)
            var pane = document.getElementById('qa-preview-pane');
            if (pane) pane.innerHTML = rendered;

            // Preview do card
            var prevBody  = document.getElementById('qa-prev-body');
            var prevTitle = document.getElementById('qa-prev-title');
            if (prevBody)  prevBody.innerHTML = rendered;
            if (prevTitle && titleEl) {
                prevTitle.textContent = titleEl.value || 'Título do aviso aparece aqui';
            }

            // Tipo/borda no card preview
            var tipoEl = document.getElementById('tipo_borda');
            var cardPrev = document.getElementById('qa-card-preview');
            var prevBadge = document.getElementById('qa-prev-badge');
            if (tipoEl && cardPrev) {
                var tipoVal = tipoEl.value;
                // Remove classes anteriores
                cardPrev.className = cardPrev.className.replace(/qa-card--\S+/g, '');
                cardPrev.classList.add('qa-card--' + tipoVal);
                if (prevBadge) {
                    prevBadge.className = prevBadge.className.replace(/qa-badge--\S+/g, '');
                    prevBadge.classList.add('qa-badge', 'qa-badge--' + tipoVal);
                    var labels = {
                        info: 'ℹ️ Informativo', success: '✅ Concluído',
                        warning: '⚠️ Atenção', danger: '🚨 Crítico',
                        mudanca: '🔧 Req. Mudança', evento: '📅 Evento'
                    };
                    prevBadge.textContent = labels[tipoVal] || tipoVal;
                }
            }

            // Datas no card preview
            var inicioEl = document.getElementById('inicio');
            var fimEl    = document.getElementById('fim');
            var prevDates = document.getElementById('qa-prev-dates');
            if (prevDates && inicioEl && fimEl) {
                var fmt = function(v) {
                    if (!v) return '--/--/----';
                    var d = new Date(v);
                    return d.toLocaleDateString('pt-BR') + ' ' +
                           d.toLocaleTimeString('pt-BR', {hour:'2-digit',minute:'2-digit'});
                };
                prevDates.textContent = fmt(inicioEl.value) + ' → ' + fmt(fimEl.value);
            }
        });
    };

    // ── Abas do editor ───────────────────────────────────────────────────────
    window.qaSetTab = function (tab) {
        var textarea = document.getElementById('conteudo');
        var pane     = document.getElementById('qa-preview-pane');
        if (!textarea || !pane) return;

        document.querySelectorAll('.qa-tab').forEach(function (t) {
            t.classList.remove('active');
        });
        event.target.classList.add('active');

        if (tab === 'editor') {
            textarea.style.display = '';
            pane.style.display = 'none';
        } else if (tab === 'preview') {
            textarea.style.display = 'none';
            pane.style.display = '';
            qaUpdatePreview();
        } else { // split
            textarea.style.display = '';
            pane.style.display = '';
            qaUpdatePreview();
        }
    };

    // ── Filtros na listagem ──────────────────────────────────────────────────
    function qaInitFilters() {
        var searchEl  = document.getElementById('qa-search');
        var tipoEl    = document.getElementById('qa-filter-tipo');
        var statusEl  = document.getElementById('qa-filter-status');
        if (!searchEl && !tipoEl && !statusEl) return;

        function applyFilters() {
            var search = searchEl  ? searchEl.value.toLowerCase()  : '';
            var tipo   = tipoEl    ? tipoEl.value                   : '';
            var status = statusEl  ? statusEl.value                 : '';

            document.querySelectorAll('#qa-grid .qa-card').forEach(function (card) {
                var text     = card.textContent.toLowerCase();
                var cardTipo = card.getAttribute('data-tipo') || '';
                var cardStat = card.getAttribute('data-status') || '';

                var ok = (!search || text.includes(search)) &&
                         (!tipo   || cardTipo === tipo) &&
                         (!status || cardStat === status);

                card.classList.toggle('qa-hidden', !ok);
            });
        }

        [searchEl, tipoEl, statusEl].forEach(function (el) {
            if (el) el.addEventListener('input', applyFilters);
        });
    }

    // ── Init ─────────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        qaRenderAll();
        qaInitFilters();

        // Atualiza preview ao digitar título
        var titleEl = document.getElementById('titulo');
        if (titleEl) {
            titleEl.addEventListener('input', qaUpdatePreview);
        }

        // Trigger inicial do preview no formulário
        if (document.getElementById('qa-form')) {
            qaUpdatePreview();
        }
    });

})();
