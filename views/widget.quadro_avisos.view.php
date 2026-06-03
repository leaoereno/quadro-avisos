<?php
/**
 * Widget para o Dashboard do Zabbix
 * Exibe os avisos ativos do grupo do usuário logado.
 *
 * Coloque este arquivo em:
 * modules/quadro_avisos/views/widget.quadro_avisos.view.php
 *
 * E registre o widget em manifest.json (veja README).
 */

use CWebUser;

/**
 * Helper para buscar avisos ativos do usuário no contexto do widget.
 * Em widgets, não há controller; fazemos a query direto na view.
 */
$userid = (int) CWebUser::$data['userid'];

// Grupos do usuário logado
$usrgrpids_rows = DB::select_all(
    "SELECT usrgrpid FROM users_groups WHERE userid = ?",
    [$userid]
) ?? [];
$usrgrpids = array_column($usrgrpids_rows, 'usrgrpid');

$avisos = [];
if ($usrgrpids) {
    $placeholders = implode(',', array_fill(0, count($usrgrpids), '?'));
    $params = array_merge($usrgrpids, [date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
    $avisos = DB::select_all(
        "SELECT a.*, u.alias AS usuario_nome
         FROM quadro_avisos a
         LEFT JOIN users u ON u.userid = a.criado_por
         WHERE a.usrgrpid IN ($placeholders)
           AND a.inicio <= ?
           AND a.fim    >= ?
         ORDER BY a.criado_em DESC
         LIMIT 20",
        $params
    ) ?? [];
}

$tipos_label = [
    'info'    => 'ℹ️ Informativo',
    'success' => '✅ Concluído',
    'warning' => '⚠️ Atenção',
    'danger'  => '🚨 Crítico',
    'mudanca' => '🔧 Req. Mudança',
    'evento'  => '📅 Evento',
];
?>

<div class="qa-widget-wrap">

    <?php if (empty($avisos)): ?>
        <div class="qa-widget-empty">
            <span>📭</span> <?= _('Nenhum aviso ativo no momento.') ?>
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
                        🗓️ Até <?= (new DateTime($aviso['fim']))->format('d/m/Y H:i') ?>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>

</div>

<script>
// Renderiza Markdown/HTML nos cards do widget após carregamento
document.addEventListener('DOMContentLoaded', function() {
    if (typeof qaRenderAll === 'function') qaRenderAll();
});
</script>
