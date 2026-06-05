<?php
$aviso        = $data['aviso'];
$modo         = $data['modo'];
$isSuperAdmin = $data['is_super_admin'];
$title        = $modo === 'edit' ? _('Edit Notice') : _('New Notice');

$tipos = [
    'info'    => 'ℹ️ ' . _('Informational'),
    'success' => '✅ ' . _('Resolved'),
    'warning' => '⚠️ ' . _('Warning'),
    'danger'  => '🚨 ' . _('Critical / Urgent'),
    'mudanca' => '🔧 ' . _('Change Request'),
    'evento'  => '📅 ' . _('Event / Maintenance'),
];
?>
<div class="qa-page-wrap">
    <div class="qa-header">
        <div class="qa-header-title">
            <a href="zabbix.php?action=quadro_avisos.view" class="qa-back-btn">← <?= _('Back') ?></a>
            <h1><?= $title ?></h1>
        </div>
    </div>
    <div class="qa-form-wrap">
        <form method="post" action="zabbix.php?action=quadro_avisos.save" id="qa-form">
            <input type="hidden" name="id" value="<?= (int)$aviso['id'] ?>">

            <div class="qa-form-group">
                <label for="titulo"><?= _('Title') ?> <span class="required">*</span></label>
                <input type="text" id="titulo" name="titulo"
                       value="<?= htmlspecialchars($aviso['titulo']) ?>"
                       class="qa-input" required>
            </div>

            <div class="qa-form-row">
                <div class="qa-form-group">
                    <label for="tipo_borda"><?= _('Type / Border Color') ?></label>
                    <select id="tipo_borda" name="tipo_borda" class="qa-input">
                        <?php foreach ($tipos as $val => $label): ?>
                            <option value="<?= $val ?>"
                                <?= $aviso['tipo_borda'] === $val ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="qa-form-group">
                    <label for="usrgrpid">
                        <?= _('Visible to') ?>
                        <?php if ($isSuperAdmin): ?>
                            <span class="qa-hint"><?= _('Super Admin can select multiple groups') ?></span>
                        <?php endif ?>
                    </label>

                    <?php if ($isSuperAdmin): ?>
                        <!-- Super Admin: multiselect + opção Todos -->
                        <select id="usrgrpid" name="usrgrpid[]" class="qa-input qa-multiselect"
                                multiple size="6">
                            <option value="0"
                                <?= !empty($aviso['para_todos']) ? 'selected' : '' ?>>
                                🌐 <?= _('All user groups') ?>
                            </option>
                            <?php foreach ($data['grupos'] as $g): ?>
                                <option value="<?= (int)$g['usrgrpid'] ?>"
                                    <?= (!empty($aviso['usrgrpid']) && (int)$aviso['usrgrpid'] === (int)$g['usrgrpid']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($g['name']) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                        <span class="qa-hint" style="margin-top:4px">
                            <?= _('Ctrl+click to select multiple. Selecting "All" ignores others.') ?>
                        </span>
                    <?php else: ?>
                        <!-- Admin: select simples apenas com seus grupos -->
                        <select id="usrgrpid" name="usrgrpid[]" class="qa-input" required>
                            <option value=""><?= _('Select...') ?></option>
                            <?php foreach ($data['grupos'] as $g): ?>
                                <option value="<?= (int)$g['usrgrpid'] ?>"
                                    <?= (!empty($aviso['usrgrpid']) && (int)$aviso['usrgrpid'] === (int)$g['usrgrpid']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($g['name']) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    <?php endif ?>
                </div>
            </div>

            <div class="qa-form-row">
                <div class="qa-form-group">
                    <label for="inicio"><?= _('Display from') ?></label>
                    <input type="datetime-local" id="inicio" name="inicio"
                           value="<?= date('Y-m-d\TH:i', strtotime($aviso['inicio'])) ?>"
                           class="qa-input" required>
                </div>
                <div class="qa-form-group">
                    <label for="fim"><?= _('Display until') ?></label>
                    <input type="datetime-local" id="fim" name="fim"
                           value="<?= date('Y-m-d\TH:i', strtotime($aviso['fim'])) ?>"
                           class="qa-input" required>
                </div>
            </div>

            <div class="qa-form-group">
                <label><?= _('Content') ?>
                    <span class="qa-hint"><?= _('Supports Markdown and HTML') ?></span>
                </label>
                <div class="qa-editor-tabs">
                    <button type="button" class="qa-tab active" id="tab-editor"
                            onclick="qaSetTab('editor')"><?= _('Editor') ?></button>
                    <button type="button" class="qa-tab" id="tab-preview"
                            onclick="qaSetTab('preview')"><?= _('Preview') ?></button>
                    <button type="button" class="qa-tab" id="tab-split"
                            onclick="qaSetTab('split')"><?= _('Split') ?></button>
                </div>
                <div class="qa-editor-wrap" id="qa-editor-wrap">
                    <textarea id="conteudo" name="conteudo" class="qa-textarea" rows="14"
                              oninput="qaLivePreview()"
                              required><?= htmlspecialchars($aviso['conteudo']) ?></textarea>
                    <div id="qa-preview-pane" class="qa-preview-pane" style="display:none"></div>
                </div>
            </div>

            <div class="qa-form-actions">
                <a href="zabbix.php?action=quadro_avisos.view" class="btn-action btn-cancel">
                    <?= _('Cancel') ?>
                </a>
                <button type="submit" class="btn-action btn-save">
                    <?= $modo === 'edit' ? _('Save Changes') : _('Create Notice') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.qa-multiselect { min-height: 140px; padding: 4px; }
.qa-multiselect option { padding: 5px 8px; border-radius: 3px; margin-bottom: 2px; }
.qa-multiselect option:checked {
    background: var(--color-action-primary-bg, #1362b8);
    color: #fff;
}
.qa-editor-wrap { display: flex; }
.qa-editor-wrap .qa-textarea { flex: 1; }
.qa-preview-pane {
    flex: 1; padding: 12px 16px;
    border: 1px solid var(--color-border, #ccc);
    border-left: none;
    background: var(--color-bg-surface, #fff);
    overflow-y: auto; min-height: 280px; max-height: 480px;
    font-size: 13px; line-height: 1.6;
    color: var(--color-text-main, #333);
}
.qa-preview-pane h1,.qa-preview-pane h2,.qa-preview-pane h3{margin:8px 0 4px;font-weight:600}
.qa-preview-pane p{margin:0 0 8px}
.qa-preview-pane ul,.qa-preview-pane ol{margin:4px 0 8px 20px}
.qa-preview-pane table{width:100%;border-collapse:collapse;font-size:12px}
.qa-preview-pane th,.qa-preview-pane td{border:1px solid var(--color-border,#ddd);padding:6px 10px}
.qa-preview-pane th{background:var(--color-bg-secondary,#f5f5f5)}
.qa-preview-pane a{color:var(--color-link,#1362b8)}
.qa-preview-pane code{background:var(--color-bg-secondary,#f0f0f0);padding:1px 5px;border-radius:3px;font-size:12px}
</style>

<script>
(function() {
    var currentTab = 'editor';
    function loadMarked(cb) {
        if (window.marked) { cb(); return; }
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/marked/marked.min.js';
        s.onload = cb; document.head.appendChild(s);
    }
    function renderContent(raw) {
        if (!raw) return '';
        return raw.trim().charAt(0) === '<' ? raw : marked.parse(raw, {breaks:true, gfm:true});
    }
    window.qaLivePreview = function() {
        if (currentTab === 'editor') return;
        loadMarked(function() {
            document.getElementById('qa-preview-pane').innerHTML =
                renderContent(document.getElementById('conteudo').value || '');
        });
    };
    window.qaSetTab = function(tab) {
        currentTab = tab;
        var textarea = document.getElementById('conteudo');
        var pane     = document.getElementById('qa-preview-pane');
        document.querySelectorAll('.qa-tab').forEach(function(t){t.classList.remove('active');});
        document.getElementById('tab-'+tab).classList.add('active');
        if (tab === 'editor') {
            textarea.style.display = ''; pane.style.display = 'none';
        } else if (tab === 'preview') {
            textarea.style.display = 'none'; pane.style.display = '';
        } else {
            textarea.style.display = ''; pane.style.display = '';
        }
        if (tab !== 'editor') {
            loadMarked(function() {
                pane.innerHTML = renderContent(document.getElementById('conteudo').value || '');
            });
        }
    };

    // Se "Todos" for selecionado no multiselect, desmarca os outros
    var sel = document.getElementById('usrgrpid');
    if (sel && sel.multiple) {
        sel.addEventListener('change', function() {
            var opts = Array.from(sel.options);
            var todosOpt = opts.find(function(o){ return o.value === '0'; });
            if (todosOpt && todosOpt.selected) {
                opts.forEach(function(o){ if (o.value !== '0') o.selected = false; });
            }
        });
    }
})();
</script>
