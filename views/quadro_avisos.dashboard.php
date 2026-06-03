<?php
/**
 * Visualização pública dos avisos ativos — acessível por TODOS os usuários.
 * Posicionada conceitualmente abaixo de "Dashboards" no fluxo de navegação.
 *
 * @var array $data['avisos']
 */

$this->includeCSS('assets/css/quadro_avisos.css');
$this->includeJS('assets/js/quadro_avisos.js');

$tipos_label = [
    'info'    => 'ℹ️ Informativo',
    'success' => '✅ Concluído',
    'warning' => '⚠️ Atenção',
    'danger'  => '🚨 Crítico',
    'mudanca' => '🔧 Req. Mudança',
    'evento'  => '📅 Evento',
];
?>

<div class="qa-dash-page">

    <div class="qa-dash-header">
        <h1 class="qa-dash-title">
            <span class="qa-dash-icon">📋</span>
            <?= _('Quadro de Avisos') ?>
        </h1>
        <p class="qa-dash-subtitle">
            <?= _('Comunicados, eventos e requisições de mudança da sua equipe.') ?>
        </p>
    </div>

    <?php if (empty($data['avisos'])): ?>
        <div class="qa-empty-state">
            <span>📭</span>
            <p><?= _('Nenhum aviso ativo para o seu grupo no momento.') ?></p>
        </div>
    <?php else: ?>
        <div class="qa-dash-grid" id="qa-grid">
            <?php foreach ($data['avisos'] as $aviso): ?>
                <div class="qa-card qa-card--<?= htmlspecialchars($aviso['tipo_borda']) ?>"
                     data-tipo="<?= htmlspecialchars($aviso['tipo_borda']) ?>">

                    <div class="qa-card-header">
                        <div class="qa-card-meta">
                            <span class="qa-badge qa-badge--<?= htmlspecialchars($aviso['tipo_borda']) ?>">
                                <?= $tipos_label[$aviso['tipo_borda']] ?? $aviso['tipo_borda'] ?>
                            </span>
                        </div>
                        <!-- Validade -->
                        <span class="qa-validity-chip">
                            até <?= (new DateTime($aviso['fim']))->format('d/m/Y H:i') ?>
                        </span>
                    </div>

                    <h3 class="qa-card-title"><?= htmlspecialchars($aviso['titulo']) ?></h3>

                    <div class="qa-card-body qa-rendered"
                         data-raw="<?= htmlspecialchars($aviso['conteudo']) ?>"></div>

                    <div class="qa-card-footer">
                        <div class="qa-card-info">
                            <span title="<?= _('Criado por') ?>">
                                👤 <?= htmlspecialchars($aviso['usuario_nome'] ?? 'N/A') ?>
                            </span>
                            <span title="<?= _('Data de criação') ?>">
                                🕐 <?= (new DateTime($aviso['criado_em']))->format('d/m/Y H:i') ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>

</div>
