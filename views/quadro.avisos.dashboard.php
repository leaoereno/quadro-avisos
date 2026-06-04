<?php
/**
 * @var array $data['avisos']
 */
$tipos_label = [
    'info'    => 'ℹ️ Informativo',
    'success' => '✅ Concluído',
    'warning' => '⚠️ Atenção',
    'danger'  => '🚨 Crítico',
    'mudanca' => '🔧 Req. Mudança',
    'evento'  => '📅 Evento',
];
?>
<div class="qa-dash-page">
    <div class="qa-dash-header">
        <h1 class="qa-dash-title">
            <span class="qa-dash-icon">📋</span>
            <?= _('Quadro de Avisos') ?>
        </h1>
        <p class="qa-dash-subtitle">
            <?= _('Comunicados, eventos e requisições de mudança da sua equipe.') ?>
        </p>
    </div>

    <?php if (empty($data['avisos'])): ?>
        <div class="qa-empty-state">
            <span>📭</span>
            <p><?= _('Nenhum aviso ativo para o seu grupo no momento.') ?></p>
        </div>
    <?php else: ?>
        <div class="qa-dash-grid" id="qa-grid">
            <?php foreach ($data['avisos'] as $aviso): ?>
                <div class="qa-card qa-card--<?= htmlspecialchars($aviso['tipo_borda']) ?> qa-card-clickable"
                     data-tipo="<?= htmlspecialchars($aviso['tipo_borda']) ?>"
                     data-titulo="<?= htmlspecialchars($aviso['titulo']) ?>"
                     data-conteudo="<?= htmlspecialchars($aviso['conteudo']) ?>"
                     data-badge="<?= htmlspecialchars($tipos_label[$aviso['tipo_borda']] ?? $aviso['tipo_borda']) ?>"
                     data-usuario="<?= htmlspecialchars($aviso['usuario_nome'] ?? 'N/A') ?>"
                     data-criado="<?= (new DateTime($aviso['criado_em']))->format('d/m/Y H:i') ?>"
                     data-fim="até <?= (new DateTime($aviso['fim']))->format('d/m/Y H:i') ?>"
                     onclick="qaOpenModal(this)">
                    <div class="qa-card-header">
                        <div class="qa-card-meta">
                            <span class="qa-badge qa-badge--<?= htmlspecialchars($aviso['tipo_borda']) ?>">
                                <?= $tipos_label[$aviso['tipo_borda']] ?? $aviso['tipo_borda'] ?>
                            </span>
                        </div>
                        <span class="qa-validity-chip">
                            até <?= (new DateTime($aviso['fim']))->format('d/m/Y H:i') ?>
                        </span>
                    </div>
                    <h3 class="qa-card-title"><?= htmlspecialchars($aviso['titulo']) ?></h3>
                    <div class="qa-card-body qa-rendered" data-raw="<?= htmlspecialchars($aviso['conteudo']) ?>"></div>
                    <div class="qa-card-footer">
                        <div class="qa-card-info">
                            <span>👤 <?= htmlspecialchars($aviso['usuario_nome'] ?? 'N/A') ?></span>
                            <span>🕐 <?= (new DateTime($aviso['criado_em']))->format('d/m/Y H:i') ?></span>
                        </div>
                    </div>
                    <div class="qa-card-expand-hint">Clique para expandir ↗</div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>

<!-- Modal -->
<div id="qa-modal-overlay" class="qa-modal-overlay" onclick="qaCloseModal(event)">
    <div class="qa-modal" id="qa-modal">
        <div class="qa-modal-header" id="qa-modal-header">
            <div class="qa-modal-meta">
                <span class="qa-badge" id="qa-modal-badge"></span>
                <span class="qa-validity-chip" id="qa-modal-fim"></span>
            </div>
            <button class="qa-modal-close" onclick="qaCloseModal(null)" title="Fechar">✕</button>
        </div>
        <h2 class="qa-modal-title" id="qa-modal-title"></h2>
        <div class="qa-modal-body qa-rendered" id="qa-modal-body"></div>
        <div class="qa-modal-footer">
            <span id="qa-modal-usuario"></span>
            <span id="qa-modal-criado"></span>
        </div>
    </div>
</div>

<style>
/* Cards clicáveis */
.qa-card-clickable {
    cursor: pointer;
    transition: box-shadow .2s, transform .15s;
}
.qa-card-clickable:hover {
    box-shadow: 0 6px 20px rgba(0,0,0,.18);
    transform: translateY(-2px);
}
.qa-card-expand-hint {
    font-size: 11px;
    color: var(--color-text-secondary, #aaa);
    text-align: right;
    margin-top: 4px;
    opacity: 0;
    transition: opacity .2s;
}
.qa-card-clickable:hover .qa-card-expand-hint { opacity: 1; }

/* Overlay */
.qa-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.55);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
    backdrop-filter: blur(2px);
}
.qa-modal-overlay.qa-open {
    display: flex;
    animation: qaFadeIn .15s ease;
}
@keyframes qaFadeIn {
    from { opacity: 0; }
    to   { opacity: 1; }
}

/* Modal */
.qa-modal {
    background: var(--color-bg-surface, #fff);
    border-radius: 8px;
    box-shadow: 0 8px 40px rgba(0,0,0,.3);
    max-width: 780px;
    width: 100%;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    animation: qaSlideUp .18s ease;
    border-top: 5px solid var(--qa-modal-color, #1a7abf);
}
@keyframes qaSlideUp {
    from { transform: translateY(20px); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
}

.qa-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px 0;
    gap: 10px;
    flex-wrap: wrap;
}
.qa-modal-meta { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }

.qa-modal-close {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: var(--color-text-secondary, #888);
    padding: 4px 8px;
    border-radius: 4px;
    line-height: 1;
    transition: background .15s, color .15s;
    margin-left: auto;
}
.qa-modal-close:hover {
    background: rgba(0,0,0,.08);
    color: var(--color-text-main, #333);
}

.qa-modal-title {
    font-size: 20px;
    font-weight: 600;
    margin: 12px 20px 0;
    color: var(--color-text-main, #222);
    line-height: 1.3;
}

.qa-modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 14px 20px;
    font-size: 14px;
    line-height: 1.6;
    color: var(--color-text-main, #333);
}

.qa-modal-footer {
    display: flex;
    gap: 16px;
    padding: 12px 20px;
    border-top: 1px solid var(--color-border, #e8e8e8);
    font-size: 12px;
    color: var(--color-text-secondary, #888);
    flex-wrap: wrap;
}

/* Cores por tipo no modal */
.qa-modal--info    { --qa-modal-color: #1a7abf; }
.qa-modal--success { --qa-modal-color: #3a8a3a; }
.qa-modal--warning { --qa-modal-color: #c89200; }
.qa-modal--danger  { --qa-modal-color: #c0392b; }
.qa-modal--mudanca { --qa-modal-color: #7b5ea7; }
.qa-modal--evento  { --qa-modal-color: #0097a7; }
</style>

<script>
// Carrega marked.js e renderiza HTML/Markdown
(function() {
    function loadMarked(cb) {
        if (window.marked) { cb(); return; }
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/marked/marked.min.js';
        s.onload = cb;
        document.head.appendChild(s);
    }

    function renderElements() {
        loadMarked(function() {
            document.querySelectorAll('.qa-rendered').forEach(function(el) {
                if (el.dataset.rendered) return;
                el.innerHTML = marked.parse(el.textContent, {breaks: true, gfm: true} || '');
                el.dataset.rendered = '1';
            });
        });
    }

    window.qaOpenModal = function(card) {
        loadMarked(function() {
            var tipo     = card.dataset.tipo || 'info';
            var titulo   = card.dataset.titulo || '';
            var conteudo = card.dataset.conteudo || '';
            var badge    = card.dataset.badge || '';
            var usuario  = card.dataset.usuario || '';
            var criado   = card.dataset.criado || '';
            var fim      = card.dataset.fim || '';

            var modal = document.getElementById('qa-modal');
            modal.className = 'qa-modal qa-modal--' + tipo;

            document.getElementById('qa-modal-badge').textContent  = badge;
            document.getElementById('qa-modal-badge').className     = 'qa-badge qa-badge--' + tipo;
            document.getElementById('qa-modal-fim').textContent     = fim;
            document.getElementById('qa-modal-title').textContent   = titulo;
            document.getElementById('qa-modal-body').innerHTML = conteudo.trim().charAt(0) === '<' ? conteudo : marked.parse(conteudo, {breaks: true, gfm: true});
            document.getElementById('qa-modal-usuario').textContent = '👤 ' + usuario;
            document.getElementById('qa-modal-criado').textContent  = '🕐 ' + criado;

            document.getElementById('qa-modal-overlay').classList.add('qa-open');
            document.body.style.overflow = 'hidden';
        });
    };

    window.qaCloseModal = function(event) {
        if (event && event.target !== document.getElementById('qa-modal-overlay')) return;
        document.getElementById('qa-modal-overlay').classList.remove('qa-open');
        document.body.style.overflow = '';
    };

    // ESC fecha o modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') qaCloseModal(null);
    });

    document.addEventListener("DOMContentLoaded", function() {
            renderElements();
            // Renderiza cards via data-raw
            document.querySelectorAll(".qa-rendered[data-raw]").forEach(function(el) {
                loadMarked(function() {
                    var raw = el.getAttribute("data-raw") || "";
                    el.innerHTML = raw.trim().charAt(0) === "<" ? raw : marked.parse(raw, {breaks: true, gfm: true});
                });
            });
        });
})();
</script>
