<?php
/**
 * @var array  $data['notice']
 * @var array  $data['groups']
 * @var string $data['mode']      create|edit
 * @var bool   $data['is_super_admin']
 */
$notice       = $data['notice'];
$mode         = $data['mode'];
$isSuperAdmin = $data['is_super_admin'];
$title        = $mode === 'edit' ? _('Edit Notice') : _('New Notice');

$types = [
    'info'    => _('Informational'),
    'success' => _('Resolved'),
    'warning' => _('Warning'),
    'danger'  => _('Critical / Urgent'),
    'mudanca' => _('Change Request'),
    'evento'  => _('Event / Maintenance'),
];
?>
<div class="nb-page-wrap">
    <div class="nb-header">
        <div class="nb-header-title">
            <a href="zabbix.php?action=notice_board.view" class="nb-back-btn">
                &larr; <?= _('Back') ?>
            </a>
            <h1><?= $title ?></h1>
        </div>
    </div>
    <div class="nb-form-wrap">
        <form method="post" action="zabbix.php?action=notice_board.save" id="nb-form">
            <input type="hidden" name="id" value="<?= (int) $notice['id'] ?>">

            <div class="nb-form-group">
                <label for="titulo"><?= _('Title') ?> <span class="required">*</span></label>
                <input type="text" id="titulo" name="titulo"
                       value="<?= htmlspecialchars($notice['titulo']) ?>"
                       class="nb-input" required>
            </div>

            <div class="nb-form-row">
                <div class="nb-form-group">
                    <label for="tipo_borda"><?= _('Type / Border Color') ?></label>
                    <select id="tipo_borda" name="tipo_borda" class="nb-input" onchange="nbLivePreview()">
                        <?php foreach ($types as $val => $label): ?>
                            <option value="<?= $val ?>"
                                <?= $notice['tipo_borda'] === $val ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="nb-form-group">
                    <label for="usrgrpid">
                        <?= _('Visible to') ?>
                        <?php if ($isSuperAdmin): ?>
                            <span class="nb-hint"><?= _('Super Admin can select multiple groups') ?></span>
                        <?php endif ?>
                    </label>

                    <?php if ($isSuperAdmin): ?>
                        <select id="usrgrpid" name="usrgrpid[]"
                                class="nb-input nb-multiselect" multiple size="5">
                            <option value="0"
                                <?= !empty($notice['para_todos']) ? 'selected' : '' ?>>
                                &#127760; <?= _('All user groups') ?>
                            </option>
                            <?php foreach ($data['groups'] as $g): ?>
                                <option value="<?= (int) $g['usrgrpid'] ?>"
                                    <?= (!empty($notice['usrgrpid']) && (int) $notice['usrgrpid'] === (int) $g['usrgrpid']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($g['name']) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                        <span class="nb-hint" style="margin-top:4px">
                            <?= _('Ctrl+click to select multiple. Selecting "All" ignores others.') ?>
                        </span>
                    <?php else: ?>
                        <select id="usrgrpid" name="usrgrpid[]" class="nb-input" required>
                            <option value=""><?= _('Select...') ?></option>
                            <?php foreach ($data['groups'] as $g): ?>
                                <option value="<?= (int) $g['usrgrpid'] ?>"
                                    <?= (!empty($notice['usrgrpid']) && (int) $notice['usrgrpid'] === (int) $g['usrgrpid']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($g['name']) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    <?php endif ?>
                </div>
            </div>

            <div class="nb-form-row">
                <div class="nb-form-group">
                    <label for="inicio"><?= _('Display from') ?></label>
                    <input type="datetime-local" id="inicio" name="inicio"
                           value="<?= date('Y-m-d\TH:i', strtotime($notice['inicio'])) ?>"
                           class="nb-input" required onchange="nbLivePreview()">
                </div>
                <div class="nb-form-group">
                    <label for="fim"><?= _('Display until') ?></label>
                    <input type="datetime-local" id="fim" name="fim"
                           value="<?= date('Y-m-d\TH:i', strtotime($notice['fim'])) ?>"
                           class="nb-input" required onchange="nbLivePreview()">
                </div>
            </div>

            <div class="nb-form-group">
                <label>
                    <?= _('Content') ?>
                    <span class="nb-hint"><?= _('Supports Markdown and HTML') ?></span>
                </label>
                <div class="nb-editor-tabs">
                    <button type="button" class="nb-tab active" id="tab-editor"
                            onclick="nbSetTab('editor')"><?= _('Editor') ?></button>
                    <button type="button" class="nb-tab" id="tab-preview"
                            onclick="nbSetTab('preview')"><?= _('Preview') ?></button>
                    <button type="button" class="nb-tab" id="tab-split"
                            onclick="nbSetTab('split')"><?= _('Split') ?></button>
                </div>
                <div class="nb-editor-wrap" id="nb-editor-wrap">
                    <textarea id="conteudo" name="conteudo" class="nb-textarea" rows="14"
                              oninput="nbLivePreview()"
                              required><?= htmlspecialchars($notice['conteudo']) ?></textarea>
                    <div id="nb-preview-pane" class="nb-preview-pane" style="display:none"></div>
                </div>
            </div>

            <div class="nb-form-actions">
                <a href="zabbix.php?action=notice_board.view" class="btn-action btn-cancel">
                    <?= _('Cancel') ?>
                </a>
                <button type="submit" class="btn-action btn-save">
                    <?= $mode === 'edit' ? _('Save Changes') : _('Create Notice') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var currentTab = 'editor';

    function loadMarked(cb) {
        if (window.marked) { cb(); return; }
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/marked/marked.min.js';
        s.onload = cb;
        document.head.appendChild(s);
    }

    function renderContent(raw) {
        if (!raw) return '';
        return raw.trim().charAt(0) === '<' ? raw : marked.parse(raw, {breaks: true, gfm: true});
    }

    window.nbLivePreview = function () {
        if (currentTab === 'editor') return;
        loadMarked(function () {
            var pane = document.getElementById('nb-preview-pane');
            var ta   = document.getElementById('conteudo');
            if (pane && ta) pane.innerHTML = renderContent(ta.value || '');
        });
    };

    window.nbSetTab = function (tab) {
        currentTab = tab;
        var ta   = document.getElementById('conteudo');
        var pane = document.getElementById('nb-preview-pane');
        document.querySelectorAll('.nb-tab').forEach(function (t) { t.classList.remove('active'); });
        document.getElementById('tab-' + tab).classList.add('active');
        if (tab === 'editor') {
            ta.style.display = '';
            pane.style.display = 'none';
        } else if (tab === 'preview') {
            ta.style.display = 'none';
            pane.style.display = '';
        } else {
            ta.style.display = '';
            pane.style.display = '';
        }
        if (tab !== 'editor') {
            loadMarked(function () {
                pane.innerHTML = renderContent(ta.value || '');
            });
        }
    };

    // Super Admin multiselect: selecting "All" deselects others
    var sel = document.getElementById('usrgrpid');
    if (sel && sel.multiple) {
        sel.addEventListener('change', function () {
            var opts     = Array.from(sel.options);
            var allOpt   = opts.find(function (o) { return o.value === '0'; });
            if (allOpt && allOpt.selected) {
                opts.forEach(function (o) { if (o.value !== '0') o.selected = false; });
            }
        });
    }

    document.getElementById('titulo')
        .addEventListener('input', nbLivePreview);
}());
</script>
