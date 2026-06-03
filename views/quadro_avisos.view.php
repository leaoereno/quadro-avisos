<?php
/**
 * @var array $data
 * @var array $data['avisos']
 * @var array $data['grupos']
 * @var int   $data['user_id']
 */

use CWebUser;

$this->includeCSS('assets/css/quadro_avisos.css');
$this->includeJS('assets/js/quadro_avisos.js');

$page_title = _('Quadro de Avisos');
?>

<div class="qa-page-wrap">

    <!-- Cabeçalho -->
    <div class="qa-header">
        <div class="qa-header-title">
            <span class="qa-icon">📋</span>
            <h1><?= $page_title ?></h1>
        </div>
        <a href="zabbix.php?action=quadro_avisos.create" class="btn-action btn-create">
            + <?= _('Novo Aviso') ?>
        </a>
    </div>

    <!-- Mensagens de feedback -->
    <?php if (CMessageHelper::hasMessages()): ?>
        <?= CMessageHelper::getMessages() ?>
    <?php endif ?>

    <?php if (empty($data['avisos'])): ?>
        <div class="qa-empty-state">
            <span>📭</span>
            <p><?= _('Nenhum aviso cadastrado ainda.') ?></p>
        </div>
    <?php else: ?>

    <!-- Filtro rápido -->
    <div class="qa-filters">
        <input type="text" id="qa-search" placeholder="<?= _('Filtrar avisos...') ?>" class="qa-search-input">
        <select id="qa-filter-tipo" class="qa-filter-select">
            <option value=""><?= _('Todos os tipos') ?></option>
            <option value="info">ℹ️ Informativo</option>
            <option value="success">✅ Concluído</option>
            <option value="warning">⚠️ Atenção</option>
            <option value="danger">🚨 Crítico</option>
            <option value="mudanca">🔧 Req. de Mudança</option>
            <option value="evento">📅 Evento</option>
        </select>
        <select id="qa-filter-status" class="qa-filter-select">
            <option value=""><?= _('Todos os status') ?></option>
            <option value="ativo"><?= _('Ativo') ?></option>
            <option value="agendado"><?= _('Agendado') ?></option>
            <option value="expirado"><?= _('Expirado') ?></option>
        </select>
    </div>

    <!-- Grid de cards -->
    <div class="qa-grid" id="qa-grid">
        <?php foreach ($data['avisos'] as $aviso): ?>
            <?php
            $now    = new DateTime();
            $inicio = new DateTime($aviso['inicio']);
            $fim    = new DateTime($aviso['fim']);

            if ($now < $inicio)      $status = 'agendado';
            elseif ($now > $fim)     $status = 'expirado';
            else                     $status = 'ativo';

            $grpNome = '';
            foreach ($data['grupos'] as $g) {
                if ($g['usrgrpid'] == $aviso['usrgrpid']) {
                    $grpNome = $g['name'];
                    break;
                }
            }
            ?>
            <div class="qa-card qa-card--<?= htmlspecialchars($aviso['tipo_borda']) ?>"
                 data-tipo="<?= htmlspecialchars($aviso['tipo_borda']) ?>"
                 data-status="<?= $status ?>">

                <!-- Topo do card -->
                <div class="qa-card-header">
                    <div class="qa-card-meta">
                        <span class="qa-badge qa-badge--<?= htmlspecialchars($aviso['tipo_borda']) ?>">
                            <?= qa_tipo_label($aviso['tipo_borda']) ?>
                        </span>
                        <span class="qa-badge qa-badge--status qa-badge--<?= $status ?>">
                            <?= qa_status_label($status) ?>
                        </span>
                    </div>
                    <div class="qa-card-actions">
                        <a href="zabbix.php?action=quadro_avisos.edit&id=<?= (int)$aviso['id'] ?>"
                           class="qa-btn-icon" title="<?= _('Editar') ?>">✏️</a>
                        <a href="zabbix.php?action=quadro_avisos.delete&id=<?= (int)$aviso['id'] ?>"
                           class="qa-btn-icon qa-btn-delete"
                           title="<?= _('Excluir') ?>"
                           onclick="return confirm('<?= _('Confirma exclusão deste aviso?') ?>')">🗑️</a>
                    </div>
                </div>

                <!-- Título -->
                <h3 class="qa-card-title"><?= htmlspecialchars($aviso['titulo']) ?></h3>

                <!-- Conteúdo (renderiza Markdown/HTML) -->
                <div class="qa-card-body qa-rendered" data-raw="<?= htmlspecialchars($aviso['conteudo']) ?>"></div>

                <!-- Rodapé -->
                <div class="qa-card-footer">
                    <div class="qa-card-info">
                        <span title="<?= _('Criado por') ?>">👤 <?= htmlspecialchars($aviso['usuario_nome'] ?? 'N/A') ?></span>
                        <span title="<?= _('Grupo') ?>">👥 <?= htmlspecialchars($grpNome) ?></span>
                    </div>
                    <div class="qa-card-dates">
                        <span title="<?= _('Exibir de') ?>">🗓️ <?= (new DateTime($aviso['inicio']))->format('d/m/Y H:i') ?>
                            → <?= (new DateTime($aviso['fim']))->format('d/m/Y H:i') ?></span>
                    </div>
                    <div class="qa-card-created">
                        <span title="<?= _('Cadastrado em') ?>">🕐 <?= (new DateTime($aviso['criado_em']))->format('d/m/Y H:i') ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>
    <?php endif ?>
</div>

<?php
function qa_tipo_label(string $tipo): string {
    $map = [
        'info'    => 'ℹ️ Informativo',
        'success' => '✅ Concluído',
        'warning' => '⚠️ Atenção',
        'danger'  => '🚨 Crítico',
        'mudanca' => '🔧 Req. Mudança',
        'evento'  => '📅 Evento',
    ];
    return $map[$tipo] ?? $tipo;
}

function qa_status_label(string $status): string {
    $map = [
        'ativo'     => '● Ativo',
        'agendado'  => '◷ Agendado',
        'expirado'  => '○ Expirado',
    ];
    return $map[$status] ?? $status;
}
?>
