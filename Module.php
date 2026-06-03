<?php

namespace Modules\QuadroAvisos;

use Zabbix\Core\CModule;
use APP;
use CMenu;
use CMenuItem;
use CWebUser;

class Module extends CModule {

    public function init(): void {
        try {
            $menu = APP::Component()->get('menu.main');
        } catch (\Throwable $e) {
            return;
        }

        // ── 1. Link de visualização abaixo de "Dashboards" ──────────────────
        // Visível para TODOS os usuários autenticados.
        try {
            $menu->insertAfter(_('Dashboards'),
                (new CMenuItem(_('Quadro de Avisos')))->setAction('quadro_avisos.dashboard')
            );
        } catch (\Throwable $e) {
            // Se insertAfter falhar (label não encontrado), não bloqueia o resto
        }

        // ── 2. Menu de administração — item de PRIMEIRO NÍVEL ───────────────
        // Posicionado entre "Dados coletados" e "Alertas".
        // Visível apenas para Admin (tipo 2) e Super Admin (tipo 3).
        if (!in_array(CWebUser::getType(), [USER_TYPE_ZABBIX_ADMIN, USER_TYPE_SUPER_ADMIN])) {
            return;
        }

        $adminItem = (new CMenuItem(_('Quadro de Avisos — Admin')))
            ->setAction('quadro_avisos.view');

        try {
            $menu->insertAfter(_('Dados coletados'), $adminItem);
        } catch (\Throwable $e) {
            try {
                $menu->insertAfter(_('Relatórios'), $adminItem);
            } catch (\Throwable $e2) {
                $menu->add($adminItem);
            }
        }
    }

    /**
     * SQL de instalação da tabela (execute manualmente no banco do Zabbix).
     */
    public static function getSqlInstall(): string {
        return <<<SQL
CREATE TABLE IF NOT EXISTS `quadro_avisos` (
  `id`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `titulo`        VARCHAR(255)     NOT NULL,
  `conteudo`      MEDIUMTEXT       NOT NULL,
  `tipo_borda`    VARCHAR(32)      NOT NULL DEFAULT 'info',
  `criado_por`    INT UNSIGNED     NOT NULL,
  `usrgrpid`      INT UNSIGNED     NOT NULL,
  `inicio`        DATETIME         NOT NULL,
  `fim`           DATETIME         NOT NULL,
  `criado_em`     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inicio_fim`  (`inicio`, `fim`),
  KEY `idx_usrgrpid`    (`usrgrpid`),
  KEY `idx_criado_por`  (`criado_por`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
    }
}
