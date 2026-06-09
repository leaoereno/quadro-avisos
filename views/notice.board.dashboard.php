<?php
/**
 * @var array $data['notices']
 */
$type_labels = [
    'info'    => _('Informational'),
    'success' => _('Resolved'),
    'warning' => _('Warning'),
    'danger'  => _('Critical / Urgent'),
    'mudanca' => _('Change Request'),
    'evento'  => _('Event / Maintenance'),
];
?>
<div class="nb-dash-page">
    <div class="nb-dash-header">
        <h1 class="nb-dash-title">
            <span class="nb-dash-icon">&#128203;</span>
            <?= _('Notice Board') ?>
        </h1>
        <p class="nb-dash-subtitle">
            <?= _('Notices, events and change requests for your team.') ?>
        </p>
    </div>

    <?php if (empty($data['notices'])): ?>
        <div class="nb-empty-state">
            <span>&#128237;</span>
            <p><?= _('No active notices for your group at this time.') ?></p>
        </div>
    <?php else: ?>
        <div class="nb-dash-grid" id="nb-grid">
            <?php foreach ($data['notices'] as $notice): ?>
                <div class="nb-card nb-card--<?= htmlspecialchars($notice['tipo_borda']) ?> nb-card-clickable"
                     data-tipo="<?= htmlspecialchars($notice['tipo_borda']) ?>"
                     data-titulo="<?= htmlspecialchars($notice['titulo']) ?>"
                     data-conteudo="<?= htmlspecialchars($notice['conteudo']) ?>"
                     data-badge="<?= htmlspecialchars($type_labels[$notice['tipo_borda']] ?? $notice['tipo_borda']) ?>"
                     data-usuario="<?= htmlspecialchars($notice['usuario_nome'] ?? 'N/A') ?>"
                     data-criado="<?= (new DateTime($notice['criado_em']))->format('d/m/Y H:i') ?>"
                     data-fim="<?= _('Until') ?> <?= (new DateTime($notice['fim']))->format('d/m/Y H:i') ?>"
                     onclick="nbOpenModal(this)">
                    <div class="nb-card-header">
                        <div class="nb-card-meta">
                            <span class="nb-badge nb-badge--<?= htmlspecialchars($notice['tipo_borda']) ?>">
                                <?= $type_labels[$notice['tipo_borda']] ?? $notice['tipo_borda'] ?>
                            </span>
                        </div>
                        <span class="nb-validity-chip">
                            <?= _('Until') ?> <?= (new DateTime($notice['fim']))->format('d/m/Y H:i') ?>
                        </span>
                    </div>
                    <h3 class="nb-card-title"><?= htmlspecialchars($notice['titulo']) ?></h3>
                    <div class="nb-card-body nb-rendered"
                         data-raw="<?= htmlspecialchars($notice['conteudo']) ?>"></div>
                    <div class="nb-card-footer">
                        <div class="nb-card-info">
                            <span>&#128100; <?= htmlspecialchars($notice['usuario_nome'] ?? 'N/A') ?></span>
                            <span>&#128336; <?= (new DateTime($notice['criado_em']))->format('d/m/Y H:i') ?></span>
                        </div>
                    </div>
                    <div class="nb-card-expand-hint"><?= _('Click to expand') ?> &#8599;</div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>

<!-- Modal -->
<div id="nb-modal-overlay" class="nb-modal-overlay" onclick="nbCloseModal(event)">
    <div class="nb-modal" id="nb-modal">
        <div class="nb-modal-header" id="nb-modal-header">
            <div class="nb-modal-meta">
                <span class="nb-badge" id="nb-modal-badge"></span>
                <span class="nb-validity-chip" id="nb-modal-fim"></span>
            </div>
            <button class="nb-modal-close" onclick="nbCloseModal(null)"
                    title="<?= _('Close') ?>">&#10005;</button>
        </div>
        <h2 class="nb-modal-title" id="nb-modal-title"></h2>
        <div class="nb-modal-body nb-rendered" id="nb-modal-body"></div>
        <div class="nb-modal-footer">
            <span id="nb-modal-usuario"></span>
            <span id="nb-modal-criado"></span>
        </div>
    </div>
</div>

<script>
(function () {
    function loadMarked(cb) {
        if (window.marked) { cb(); return; }
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/marked/marked.min.js';
        s.onload = cb;
        document.head.appendChild(s);
    }

    function renderRaw(el) {
        var raw = el.getAttribute('data-raw') || '';
        if (!raw) return;
        el.innerHTML = raw.trim().charAt(0) === '<' ? raw : marked.parse(raw, {breaks: true, gfm: true});
        el.removeAttribute('data-raw');
    }

    window.nbOpenModal = function (card) {
        loadMarked(function () {
            var tipo    = card.dataset.tipo || 'info';
            var content = card.dataset.conteudo || '';
            var modal   = document.getElementById('nb-modal');
            modal.className = 'nb-modal nb-modal--' + tipo;
            document.getElementById('nb-modal-badge').textContent  = card.dataset.badge || '';
            document.getElementById('nb-modal-badge').className    = 'nb-badge nb-badge--' + tipo;
            document.getElementById('nb-modal-fim').textContent    = card.dataset.fim || '';
            document.getElementById('nb-modal-title').textContent  = card.dataset.titulo || '';
            document.getElementById('nb-modal-body').innerHTML     = content.trim().charAt(0) === '<'
                ? content
                : marked.parse(content, {breaks: true, gfm: true});
            document.getElementById('nb-modal-usuario').textContent = '&#128100; ' + (card.dataset.usuario || '');
            document.getElementById('nb-modal-criado').textContent  = '&#128336; ' + (card.dataset.criado || '');
            document.getElementById('nb-modal-overlay').classList.add('nb-open');
            document.body.style.overflow = 'hidden';
        });
    };

    window.nbCloseModal = function (event) {
        if (event && event.target !== document.getElementById('nb-modal-overlay')) return;
        document.getElementById('nb-modal-overlay').classList.remove('nb-open');
        document.body.style.overflow = '';
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') nbCloseModal(null);
    });

    document.addEventListener('DOMContentLoaded', function () {
        loadMarked(function () {
            document.querySelectorAll('.nb-rendered[data-raw]').forEach(renderRaw);
        });
    });
}());
</script>
