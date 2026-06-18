<?php
/**
 * @var array  $data['notices']
 * @var array  $data['groups']
 * @var int    $data['user_id']
 * @var bool   $data['is_super_admin']
 */

$type_labels = [
    'info'    => 'i ' . _('Informational'),
    'success' => 'v ' . _('Resolved'),
    'warning' => '! ' . _('Warning'),
    'danger'  => 'x ' . _('Critical / Urgent'),
    'mudanca' => 'c ' . _('Change Request'),
    'evento'  => 'e ' . _('Event / Maintenance'),
];

function nb_status(string $inicio, string $fim): string {
    $now = new DateTime();
    if ($now < new DateTime($inicio)) return 'scheduled';
    if ($now > new DateTime($fim))    return 'expired';
    return 'active';
}

function nb_status_label(string $s): string {
    return [
        'active'    => '&#9679; ' . _('Active'),
        'scheduled' => '&#9711; ' . _('Scheduled'),
        'expired'   => '&#9675; ' . _('Expired'),
    ][$s] ?? $s;
}

$isSuperAdmin = $data['is_super_admin'];
$currentUser  = (int) $data['user_id'];
?>
<div class="nb-page-wrap">
    <div class="nb-header">
        <div class="nb-header-title">
            <span class="nb-icon">&#128203;</span>
            <h1><?= _('Notice Board') ?></h1>
        </div>
        <a href="zabbix.php?action=notice_board.create" class="btn-action btn-create">
            + <?= _('New Notice') ?>
        </a>
    </div>

    <?php if (empty($data['notices'])): ?>
        <div class="nb-empty-state">
            <span>&#128237;</span>
            <p><?= _('No notices registered yet.') ?></p>
        </div>
    <?php else: ?>
        <div class="nb-filters">
            <input type="text" id="nb-search"
                   placeholder="<?= _('Filter notices...') ?>"
                   class="nb-search-input">
            <select id="nb-filter-tipo" class="nb-filter-select">
                <option value=""><?= _('All types') ?></option>
                <?php foreach ($type_labels as $val => $label): ?>
                    <option value="<?= $val ?>"><?= $label ?></option>
                <?php endforeach ?>
            </select>
            <select id="nb-filter-status" class="nb-filter-select">
                <option value=""><?= _('All statuses') ?></option>
                <option value="active"><?= _('Active') ?></option>
                <option value="scheduled"><?= _('Scheduled') ?></option>
                <option value="expired"><?= _('Expired') ?></option>
            </select>
        </div>

        <div class="nb-grid" id="nb-grid">
            <?php foreach ($data['notices'] as $notice):
                $status   = nb_status($notice['inicio'], $notice['fim']);
                $grpName  = '';
                foreach ($data['groups'] as $g) {
                    if ($g['usrgrpid'] == $notice['usrgrpid']) {
                        $grpName = $g['name'];
                        break;
                    }
                }
                if (!empty($notice['para_todos'])) {
                    $grpName = '&#127760; ' . _('All groups');
                }
                $canEdit = $isSuperAdmin || (int) $notice['criado_por'] === $currentUser;
            ?>
                <div class="nb-card nb-card--<?= htmlspecialchars($notice['tipo_borda']) ?>"
                     data-tipo="<?= htmlspecialchars($notice['tipo_borda']) ?>"
                     data-status="<?= $status ?>">
                    <div class="nb-card-header">
                        <div class="nb-card-meta">
                            <span class="nb-badge nb-badge--<?= htmlspecialchars($notice['tipo_borda']) ?>">
                                <?= $type_labels[$notice['tipo_borda']] ?? $notice['tipo_borda'] ?>
                            </span>
                            <span class="nb-badge nb-badge--status nb-badge--<?= $status ?>">
                                <?= nb_status_label($status) ?>
                            </span>
                        </div>
                        <?php if ($canEdit): ?>
                        <div class="nb-card-actions">
                            <a href="zabbix.php?action=notice_board.edit&id=<?= (int) $notice['id'] ?>"
                               class="nb-btn-icon" title="<?= _('Edit') ?>">&#9998;</a>
                            <a href="zabbix.php?action=notice_board.delete&id=<?= (int) $notice['id'] ?>"
                               class="nb-btn-icon nb-btn-delete" title="<?= _('Delete') ?>"
                               onclick="return confirm('<?= _('Confirm deletion?') ?>')">&#128465;</a>
                        </div>
                        <?php endif ?>
                    </div>
                    <h3 class="nb-card-title"><?= htmlspecialchars($notice['titulo']) ?></h3>
                    <div class="nb-card-body nb-rendered"
                         data-raw="<?= htmlspecialchars($notice['conteudo']) ?>"></div>
                    <div class="nb-card-footer">
                        <div class="nb-card-info">
                            <span>&#128100; <?= htmlspecialchars($notice['usuario_nome'] ?? 'N/A') ?></span>
                            <span>&#128101; <?= htmlspecialchars($grpName) ?></span>
                        </div>
                        <div class="nb-card-dates">
                            &#128197;
                            <?= (new DateTime($notice['inicio']))->format('d/m/Y H:i') ?>
                            &rarr;
                            <?= (new DateTime($notice['fim']))->format('d/m/Y H:i') ?>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>

<script>
(function () {
    // Renders raw HTML (which may include its own <style>) inside a sandboxed
    // iframe instead of innerHTML, so generic selectors in a notice's <style>
    // (e.g. .wrapper, header, .card) never leak out and override the host
    // page's own layout. Height auto-fits the content once it loads. A hidden
    // text mirror is kept so applyFilters() can still search inside it.
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

                var mirror = document.createElement('span');
                mirror.className = 'nb-search-mirror';
                mirror.style.display = 'none';
                mirror.textContent = doc.body ? doc.body.textContent : '';
                container.appendChild(mirror);
            } catch (e) {
                frame.style.height = '300px';
            }
        });
        frame.srcdoc = html;
    }
    function renderRaw(el) {
        var raw = (el.getAttribute('data-raw') || '').trim();
        if (!raw) { el.removeAttribute('data-raw'); return true; }
        if (raw.charAt(0) === '<') {
            renderIsolatedHtml(el, raw);
            el.removeAttribute('data-raw');
            return true;
        }
        if (window.marked) {
            el.innerHTML = marked.parse(raw, {breaks: true, gfm: true});
            el.removeAttribute('data-raw');
            return true;
        }
        return false; // needs marked.js, still pending
    }
    function loadMarkedAndRender() {
        var pending = [];
        document.querySelectorAll('.nb-rendered[data-raw]').forEach(function (el) {
            if (!renderRaw(el)) pending.push(el);
        });
        if (!pending.length) return;

        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/marked/marked.min.js';
        s.onload = function () {
            pending.forEach(renderRaw);
        };
        document.head.appendChild(s);
    }
    function applyFilters() {
        var search = (document.getElementById('nb-search') || {}).value || '';
        var tipo   = (document.getElementById('nb-filter-tipo') || {}).value || '';
        var status = (document.getElementById('nb-filter-status') || {}).value || '';
        document.querySelectorAll('#nb-grid .nb-card').forEach(function (card) {
            var ok = (!search || card.textContent.toLowerCase().includes(search.toLowerCase())) &&
                     (!tipo   || card.dataset.tipo   === tipo) &&
                     (!status || card.dataset.status === status);
            card.classList.toggle('nb-hidden', !ok);
        });
    }
    document.addEventListener('DOMContentLoaded', function () {
        loadMarkedAndRender();
        ['nb-search', 'nb-filter-tipo', 'nb-filter-status'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('input', applyFilters);
        });
    });
}());
</script>
