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

        // Visualizacao: submenu de Monitoramento — todos os usuarios
        $menu->findOrAdd(_('Monitoramento'))
            ->getSubMenu()
            ->add((new CMenuItem(_('Quadro de Avisos')))->setAction('quadro_avisos.dashboard'));

        if (!in_array(CWebUser::getType(), [USER_TYPE_ZABBIX_ADMIN, USER_TYPE_SUPER_ADMIN])) {
            return;
        }

        $qaAdminItem = (new CMenuItem(_('Quadro de Avisos')))
            ->setAction('quadro_avisos.view')
            ->setIcon('zi-support');

        if (CWebUser::getType() === USER_TYPE_SUPER_ADMIN) {
            // Super Admin: adiciona em Administracao
            foreach ($menu->getMenuItems() as $item) {
                if (bin2hex($item->getLabel()) === '41646d696e6973747261c3a7c3a36f') {
                    $item->getSubMenu()->add($qaAdminItem);
                    return;
                }
            }
        }

        // Admin tipo 2: adiciona ao final do menu principal
        $menu->add($qaAdminItem);
    }
}
