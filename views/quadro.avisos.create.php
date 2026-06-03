<?php
/**
 * @var array  $data['aviso']
 * @var array  $data['grupos']
 * @var string $data['modo']
 */
$aviso = $data['aviso'];
$modo  = $data['modo'];
$title = $modo === 'edit' ? _('Editar Aviso') : _('Novo Aviso');
$tipos = [
    'info'    => 'ℹ️ Informativo',
    'success' => '✅ Concluído / Resolvido',
    'warning' => '⚠️ Atenção',
    'danger'  => '🚨 Crítico / Urgente',
    'mudanca' => '🔧 Requisição de Mudança',
    'evento'  => '📅 Evento / Manutenção',
];
?>
<div class="qa-page-wrap">
    <div class="qa-header">
        <div class="qa-header-title">
            <a href="zabbix.php?action=quadro_avisos.view" class="qa-back-btn">← <?= _('Voltar') ?></a>
            <h1><?= $title ?></h1>
        </div>
    </div>
    <div class="qa-form-wrap">
        <form method="post" action="zabbix.php?action=quadro_avisos.save" id="qa-form">
            <input type="hidden" name="id" value="<?= (int)$aviso['id'] ?>">

            <div class="qa-form-group">
                <label for="titulo"><?= _('Título') ?> <span class="required">*</span></label>
                <input type="text" id="titulo" name="titulo"
                       value="<?= htmlspecialchars($aviso['titulo']) ?>"
                       class="qa-input" required>
            </div>

            <div class="qa-form-row">
                <div class="qa-form-group">
                    <label for="tipo_borda"><?= _('Tipo / Cor do Contorno') ?></label>
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
                    <label for="usrgrpid"><?= _('Visível para o Grupo') ?></label>
                    <select id="usrgrpid" name="usrgrpid" class="qa-input" required>
                        <option value=""><?= _('Selecione...') ?></option>
                        <?php foreach ($data['grupos'] as $g): ?>
                            <option value="<?= (int)$g['usrgrpid'] ?>"
                                <?= (int)$aviso['usrgrpid'] === (int)$g['usrgrpid'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($g['name']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>

            <div class="qa-form-row">
                <div class="qa-form-group">
                    <label for="inicio"><?= _('Exibir a partir de') ?></label>
                    <input type="datetime-local" id="inicio" name="inicio"
                           value="<?= date('Y-m-d\TH:i', strtotime($aviso['inicio'])) ?>"
                           class="qa-input" required>
                </div>
                <div class="qa-form-group">
                    <label for="fim"><?= _('Exibir até') ?></label>
                    <input type="datetime-local" id="fim" name="fim"
                           value="<?= date('Y-m-d\TH:i', strtotime($aviso['fim'])) ?>"
                           class="qa-input" required>
                </div>
            </div>

            <div class="qa-form-group">
                <label for="conteudo"><?= _('Conteúdo') ?>
                    <span class="qa-hint"><?= _('Suporta Markdown e HTML') ?></span>
                </label>
                <div class="qa-editor-tabs">
                    <button type="button" class="qa-tab active" onclick="qaSetTab('editor', event)"><?= _('Editor') ?></button>
                    <button type="button" class="qa-tab" onclick="qaSetTab('preview', event)"><?= _('Preview') ?></button>
                    <button type="button" class="qa-tab" onclick="qaSetTab('split', event)"><?= _('Dividido') ?></button>
                </div>
                <div class="qa-editor-wrap">
                    <textarea id="conteudo" name="conteudo" class="qa-textarea" rows="12"
                              onkeyup="qaUpdatePreview()"
                              required><?= htmlspecialchars($aviso['conteudo']) ?></textarea>
                    <div id="qa-preview-pane" class="qa-rendered qa-preview-pane" style="display:none"></div>
                </div>
            </div>

            <div class="qa-form-actions">
                <a href="zabbix.php?action=quadro_avisos.view" class="btn-action btn-cancel">
                    <?= _('Cancelar') ?>
                </a>
                <button type="submit" class="btn-action btn-save">
                    <?= $modo === 'edit' ? _('Salvar Alterações') : _('Criar Aviso') ?>
                </button>
            </div>
        </form>
    </div>
</div>
<script src="modules/quadro-avisos/assets/js/quadro_avisos.js"></script>
