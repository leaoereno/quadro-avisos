<?php
/**
 * Widget view — rendered inside the Zabbix dashboard widget.
 * NOTE: SQL is executed here because the widget rendering pipeline
 * does not route through a dedicated controller in Zabbix 7.0 LTS.
 * Refactor to a proper widget class when Zabbix widget API allows it.
 */

use CWebUser;

$userid = (int) CWebUser::$data['userid'];
$grpids = [];
$result = DBselect('SELECT usrgrpid FROM users_groups WHERE userid=' . $userid);
while ($row = DBfetch($result)) {
    $grpids[] = (int) $row['usrgrpid'];
}

$notices = [];
if ($grpids) {
    $placeholders = implode(',', $grpids);
    $now          = zbx_dbstr(date('Y-m-d H:i:s'));
    $result = DBselect(
        'SELECT n.id, n.titulo, n.conteudo, n.tipo_borda, n.inicio, n.fim, n.criado_em,' .
        '       u.username AS usuario_nome' .
        ' FROM notice_board n' .
        ' LEFT JOIN users u ON u.userid = n.criado_por' .
        ' WHERE (n.usrgrpid IN (' . $placeholders . ') OR n.para_todos = 1)' .
        '   AND n.inicio <= ' . $now .
        '   AND n.fim    >= ' . $now .
        ' ORDER BY n.criado_em DESC' .
        ' LIMIT 20'
    );
    while ($row = DBfetch($result)) {
        $notices[] = $row;
    }
}

$type_labels = [
    'info'    => _('Informational'),
    'success' => _('Resolved'),
    'warning' => _('Warning'),
    'danger'  => _('Critical / Urgent'),
    'mudanca' => _('Change Request'),
    'evento'  => _('Event / Maintenance'),
];
?>

<div class="nb-widget-wrap">
    <?php if (empty($notices)): ?>
        <div class="nb-widget-empty">
            &#128237; <?= _('No active notices at this time.') ?>
        </div>
    <?php else: ?>
        <div class="nb-widget-list">
            <?php foreach ($notices as $notice): ?>
                <div class="nb-widget-card nb-card--<?= htmlspecialchars($notice['tipo_borda']) ?>">
                    <div class="nb-widget-card-header">
                        <span class="nb-badge nb-badge--<?= htmlspecialchars($notice['tipo_borda']) ?> nb-badge--sm">
                            <?= $type_labels[$notice['tipo_borda']] ?? $notice['tipo_borda'] ?>
                        </span>
                        <span class="nb-widget-date">
                            <?= (new DateTime($notice['criado_em']))->format('d/m/Y H:i') ?>
                            &mdash; <?= htmlspecialchars($notice['usuario_nome'] ?? 'N/A') ?>
                        </span>
                    </div>
                    <div class="nb-widget-title"><?= htmlspecialchars($notice['titulo']) ?></div>
                    <div class="nb-widget-body nb-rendered"
                         data-raw="<?= htmlspecialchars($notice['conteudo']) ?>"></div>
                    <div class="nb-widget-validity">
                        &#128197; <?= _('Until') ?> <?= (new DateTime($notice['fim']))->format('d/m/Y H:i') ?>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof nbRenderAll === 'function') nbRenderAll();
});
</script>
