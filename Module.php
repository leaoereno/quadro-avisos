<?php

namespace Modules\QuadroAvisos;

use Zabbix\Core\CModule;
use APP;
use CMenuItem;
use CWebUser;
use CView;

class Module extends CModule {

    public function init(): void {
        CView::registerDirectory(__DIR__ . '/views');

        try {
            $menu = APP::Component()->get('menu.main');
        } catch (\Throwable $e) {
            return;
        }

        // ── 1. Visualização: submenu de "Monitoramento" — todos os usuários
        $menu->findOrAdd(_('Monitoramento'))
            ->getSubMenu()
            ->add((new CMenuItem(_('Quadro de Avisos')))->setAction('quadro_avisos.dashboard'));

        // ── 2. Administração: submenu de "Administração" — Admin e Super Admin
        if (!in_array(CWebUser::getType(), [USER_TYPE_ZABBIX_ADMIN, USER_TYPE_SUPER_ADMIN])) {
            return;
        }

        $menu->findOrAdd(_('Administração'))
            ->getSubMenu()
            ->add((new CMenuItem(_('Quadro de Avisos')))->setAction('quadro_avisos.view'));
    }
}
