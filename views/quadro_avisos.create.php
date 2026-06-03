<?php
/**
 * @var array  $data['aviso']   Dados do aviso (novo ou existente)
 * @var array  $data['grupos']  Lista de grupos disponíveis
 * @var string $data['modo']    'create' | 'edit'
 */

$this->includeCSS('assets/css/quadro_avisos.css');
$this->includeJS('assets/js/quadro_avisos.js');

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

            <!-- Título -->
            <div class="qa-form-group">
                <label for="titulo"><?= _('Título') ?> <span class="required">*</span></label>
                <input type="text" id="titulo" name="titulo"
                       value="<?= htmlspecialchars($aviso['titulo']) ?>"
                       placeholder="Ex: Janela de manutenção - 12/06"
                       class="qa-input" required>
            </div>

            <!-- Linha 2: tipo e grupo -->
            <div class="qa-form-row">
                <div class="qa-form-group">
                    <label for="tipo_borda"><?= _('Tipo / Cor do Contorno') ?> <span class="required">*</span></label>
                    <select id="tipo_borda" name="tipo_borda" class="qa-input" onchange="qaUpdatePreview()">
                        <?php foreach ($tipos as $val => $label): ?>
                            <option value="<?= $val ?>"
                                <?= $aviso['tipo_borda'] === $val ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div class="qa-form-group">
                    <label for="usrgrpid"><?= _('Visível para o Grupo') ?> <span class="required">*</span></label>
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

            <!-- Agendamento -->
            <div class="qa-form-row">
                <div class="qa-form-group">
                    <label for="inicio"><?= _('Exibir a partir de') ?> <span class="required">*</span></label>
                    <input type="datetime-local" id="inicio" name="inicio"
                           value="<?= date('Y-m-d\TH:i', strtotime($aviso['inicio'])) ?>"
                           class="qa-input" required onchange="qaUpdatePreview()">
                </div>
                <div class="qa-form-group">
                    <label for="fim"><?= _('Exibir até') ?> <span class="required">*</span></label>
                    <input type="datetime-local" id="fim" name="fim"
                           value="<?= date('Y-m-d\TH:i', strtotime($aviso['fim'])) ?>"
                           class="qa-input" required onchange="qaUpdatePreview()">
                </div>
            </div>

            <!-- Editor Markdown/HTML -->
            <div class="qa-form-group">
                <label for="conteudo"><?= _('Conteúdo') ?> <span class="required">*</span>
                    <span class="qa-hint"><?= _('Suporta Markdown e HTML') ?></span>
                </label>
                <div class="qa-editor-tabs">
                    <button type="button" class="qa-tab active" onclick="qaSetTab('editor')"><?= _('Editor') ?></button>
                    <button type="button" class="qa-tab" onclick="qaSetTab('preview')"><?= _('Pré-visualização') ?></button>
                    <button type="button" class="qa-tab" onclick="qaSetTab('split')"><?= _('Dividido') ?></button>
                </div>
                <div class="qa-editor-wrap" id="qa-editor-wrap">
                    <textarea id="conteudo" name="conteudo"
                              class="qa-textarea" rows="12"
                              placeholder="<?= _('Descreva o aviso... Markdown e HTML são suportados.') ?>"
                              onkeyup="qaUpdatePreview()"
                              required><?= htmlspecialchars($aviso['conteudo']) ?></textarea>
                    <div id="qa-preview-pane" class="qa-rendered qa-preview-pane" style="display:none"></div>
                </div>
            </div>

            <!-- Preview do card -->
            <div class="qa-form-group">
                <label><?= _('Preview do Card') ?></label>
                <div id="qa-card-preview" class="qa-card qa-card--info qa-preview-card">
                    <div class="qa-card-header">
                        <div class="qa-card-meta">
                            <span class="qa-badge qa-badge--info" id="qa-prev-badge">ℹ️ Informativo</span>
                            <span class="qa-badge qa-badge--status qa-badge--agendado">◷ Agendado</span>
                        </div>
                    </div>
                    <h3 class="qa-card-title" id="qa-prev-title"><?= _('Título do aviso aparece aqui') ?></h3>
                    <div class="qa-card-body qa-rendered" id="qa-prev-body"></div>
                    <div class="qa-card-footer">
                        <span>🗓️ <span id="qa-prev-dates">--/--/---- → --/--/----</span></span>
                    </div>
                </div>
            </div>

            <!-- Botões -->
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
