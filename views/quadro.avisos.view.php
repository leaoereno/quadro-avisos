<?php
/**
 * @var array  $data['avisos']
 * @var array  $data['grupos']
 * @var int    $data['user_id']
 * @var bool   $data['is_super_admin']
 */

// Tipos com msgids em inglês para o sistema de tradução
$tipos_label = [
    'info'    => 'ℹ️ ' . _('Informational'),
    'success' => '✅ ' . _('Resolved'),
    'warning' => '⚠️ ' . _('Warning'),
    'danger'  => '🚨 ' . _('Critical / Urgent'),
    'mudanca' => '🔧 ' . _('Change Request'),
    'evento'  => '📅 ' . _('Event / Maintenance'),
];

function qa_status(string $inicio, string $fim): string {
    $now = new DateTime();
    if ($now < new DateTime($inicio)) return 'scheduled';
    if ($now > new DateTime($fim))    return 'expired';
    return 'active';
}
function qa_status_label(string $s): string {
    return [
        'active'    => '● ' . _('Active'),
        'scheduled' => '◷ ' . _('Scheduled'),
        'expired'   => '○ ' . _('Expired'),
    ][$s] ?? $s;
}

$isSuperAdmin = $data['is_super_admin'];
$currentUser  = (int) $data['user_id'];
?>
<div class="qa-page-wrap">
    <div class="qa-header">
        <div class="qa-header-title">
            <span class="qa-icon">📋</span>
            <h1><?= _('Notice Board') ?></h1>
        </div>
        <a href="zabbix.php?action=quadro_avisos.create" class="btn-action btn-create">
            + <?= _('New Notice') ?>
        </a>
    </div>

    <?php if (empty($data['avisos'])): ?>
        <div class="qa-empty-state">
            <span>📭</span>
            <p><?= _('No notices registered yet.') ?></p>
        </div>
    <?php else: ?>
        <div class="qa-filters">
            <input type="text" id="qa-search" placeholder="<?= _('Filter notices...') ?>" class="qa-search-input">
            <select id="qa-filter-tipo" class="qa-filter-select">
                <option value=""><?= _('All types') ?></option>
                <?php foreach ($tipos_label as $val => $label): ?>
                    <option value="<?= $val ?>"><?= $label ?></option>
                <?php endforeach ?>
            </select>
            <select id="qa-filter-status" class="qa-filter-select">
                <option value=""><?= _('All statuses') ?></option>
                <option value="active"><?= _('Active') ?></option>
                <option value="scheduled"><?= _('Scheduled') ?></option>
                <option value="expired"><?= _('Expired') ?></option>
            </select>
        </div>

        <div class="qa-grid" id="qa-grid">
            <?php foreach ($data['avisos'] as $aviso):
                $status  = qa_status($aviso['inicio'], $aviso['fim']);
                $grpNome = '';
                foreach ($data['grupos'] as $g) {
                    if ($g['usrgrpid'] == $aviso['usrgrpid']) { $grpNome = $g['name']; break; }
                }
                // CORREÇÃO: usa para_todos em vez de usrgrpid=0
                if (!empty($aviso['para_todos'])) {
                    $grpNome = '🌐 ' . _('All groups');
                }
                $podeEditar = $isSuperAdmin || (int)$aviso['criado_por'] === $currentUser;
            ?>
                <div class="qa-card qa-card--<?= htmlspecialchars($aviso['tipo_borda']) ?>"
                     data-tipo="<?= htmlspecialchars($aviso['tipo_borda']) ?>"
                     data-status="<?= $status ?>">
                    <div class="qa-card-header">
                        <div class="qa-card-meta">
                            <span class="qa-badge qa-badge--<?= htmlspecialchars($aviso['tipo_borda']) ?>">
                                <?= $tipos_label[$aviso['tipo_borda']] ?? $aviso['tipo_borda'] ?>
                            </span>
                            <span class="qa-badge qa-badge--status qa-badge--<?= $status ?>">
                                <?= qa_status_label($status) ?>
                            </span>
                        </div>
                        <?php if ($podeEditar): ?>
                        <div class="qa-card-actions">
                            <a href="zabbix.php?action=quadro_avisos.edit&id=<?= (int)$aviso['id'] ?>"
                               class="qa-btn-icon" title="<?= _('Edit') ?>">✏️</a>
                            <a href="zabbix.php?action=quadro_avisos.delete&id=<?= (int)$aviso['id'] ?>"
                               class="qa-btn-icon qa-btn-delete" title="<?= _('Delete') ?>"
                               onclick="return confirm('<?= _('Confirm deletion?') ?>')">🗑️</a>
                        </div>
                        <?php endif ?>
                    </div>
                    <h3 class="qa-card-title"><?= htmlspecialchars($aviso['titulo']) ?></h3>
                    <div class="qa-card-body qa-rendered"
                         data-raw="<?= htmlspecialchars($aviso['conteudo']) ?>"></div>
                    <div class="qa-card-footer">
                        <div class="qa-card-info">
                            <span>👤 <?= htmlspecialchars($aviso['usuario_nome'] ?? 'N/A') ?></span>
                            <span>👥 <?= htmlspecialchars($grpNome) ?></span>
                        </div>
                        <div class="qa-card-dates">
                            🗓️ <?= (new DateTime($aviso['inicio']))->format('d/m/Y H:i') ?>
                            → <?= (new DateTime($aviso['fim']))->format('d/m/Y H:i') ?>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>

<script>
(function() {
    function renderRaw(el) {
        var raw = el.getAttribute('data-raw') || '';
        if (!raw) return;
        el.innerHTML = raw.trim().charAt(0) === '<'
            ? raw
            : (window.marked ? marked.parse(raw, {breaks: true, gfm: true}) : raw);
        el.removeAttribute('data-raw');
    }
    function loadMarkedAndRender() {
        if (window.marked) {
            document.querySelectorAll('.qa-rendered[data-raw]').forEach(renderRaw);
            return;
        }
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/marked/marked.min.js';
        s.onload = function() {
            document.querySelectorAll('.qa-rendered[data-raw]').forEach(renderRaw);
        };
        document.head.appendChild(s);
    }
    function applyFilters() {
        var search = (document.getElementById('qa-search') || {}).value || '';
        var tipo   = (document.getElementById('qa-filter-tipo') || {}).value || '';
        var status = (document.getElementById('qa-filter-status') || {}).value || '';
        document.querySelectorAll('#qa-grid .qa-card').forEach(function(card) {
            var ok = (!search || card.textContent.toLowerCase().includes(search.toLowerCase())) &&
                     (!tipo   || card.dataset.tipo   === tipo) &&
                     (!status || card.dataset.status === status);
            card.classList.toggle('qa-hidden', !ok);
        });
    }
    document.addEventListener('DOMContentLoaded', function() {
        loadMarkedAndRender();
        ['qa-search','qa-filter-tipo','qa-filter-status'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('input', applyFilters);
        });
    });
})();
</script>
