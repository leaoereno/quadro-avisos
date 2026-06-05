<?php
/**
 * Widget para o Dashboard do Zabbix
 * Exibe os avisos ativos do grupo do usuário logado.
 */

use CWebUser;

$userid    = (int) CWebUser::$data['userid'];
$usrgrpids = [];
$result    = DBselect('SELECT usrgrpid FROM users_groups WHERE userid=' . $userid);
while ($row = DBfetch($result)) {
    $usrgrpids[] = (int) $row['usrgrpid'];
}

$avisos = [];
if ($usrgrpids) {
    $placeholders = implode(',', $usrgrpids);
    $now_str      = date('Y-m-d H:i:s');
    // CORREÇÃO: usa para_todos=1 em vez de usrgrpid=0
    $result = DBselect(
        'SELECT a.id, a.titulo, a.conteudo, a.tipo_borda, a.inicio, a.fim, a.criado_em,' .
        '       u.username AS usuario_nome' .
        ' FROM quadro_avisos a' .
        ' LEFT JOIN users u ON u.userid = a.criado_por' .
        ' WHERE (a.usrgrpid IN (' . $placeholders . ') OR a.para_todos = 1)' .
        '   AND a.inicio <= ' . zbx_dbstr($now_str) .
        '   AND a.fim    >= ' . zbx_dbstr($now_str) .
        ' ORDER BY a.criado_em DESC' .
        ' LIMIT 20'
    );
    while ($row = DBfetch($result)) {
        $avisos[] = $row;
    }
}

$tipos_label = [
    'info'    => 'ℹ️ ' . _('Informational'),
    'success' => '✅ ' . _('Resolved'),
    'warning' => '⚠️ ' . _('Warning'),
    'danger'  => '🚨 ' . _('Critical / Urgent'),
    'mudanca' => '🔧 ' . _('Change Request'),
    'evento'  => '📅 ' . _('Event / Maintenance'),
];
?>

<div class="qa-widget-wrap">

    <?php if (empty($avisos)): ?>
        <div class="qa-widget-empty">
            <span>📭</span> <?= _('No active notices at this time.') ?>
        </div>
    <?php else: ?>
        <div class="qa-widget-list">
            <?php foreach ($avisos as $aviso): ?>
                <div class="qa-widget-card qa-card--<?= htmlspecialchars($aviso['tipo_borda']) ?>">
                    <div class="qa-widget-card-header">
                        <span class="qa-badge qa-badge--<?= htmlspecialchars($aviso['tipo_borda']) ?> qa-badge--sm">
                            <?= $tipos_label[$aviso['tipo_borda']] ?? $aviso['tipo_borda'] ?>
                        </span>
                        <span class="qa-widget-date">
                            <?= (new DateTime($aviso['criado_em']))->format('d/m/Y H:i') ?>
                            — <?= htmlspecialchars($aviso['usuario_nome'] ?? 'N/A') ?>
                        </span>
                    </div>
                    <div class="qa-widget-title"><?= htmlspecialchars($aviso['titulo']) ?></div>
                    <div class="qa-widget-body qa-rendered" data-raw="<?= htmlspecialchars($aviso['conteudo']) ?>"></div>
                    <div class="qa-widget-validity">
                        🗓️ <?= _('Until') ?> <?= (new DateTime($aviso['fim']))->format('d/m/Y H:i') ?>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof qaRenderAll === 'function') qaRenderAll();
});
</script>
