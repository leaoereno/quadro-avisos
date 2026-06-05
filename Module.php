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

        /*
         * Multilinguagem: usamos _('Notice Board') como msgid.
         * O Zabbix carrega automaticamente o .mo do idioma do usuário a partir
         * de locale/{lang}/LC_MESSAGES/module.mo — se não encontrar, exibe o msgid.
         * O msgid em inglês garante que usuários em en_US vejam "Notice Board"
         * e usuários em pt_BR vejam "Quadro de Avisos" (via module.po compilado).
         */

        // Item de visualização: aparece em Monitoramento para todos os usuários
        $menu->findOrAdd(_('Monitoring'))
            ->getSubMenu()
            ->add((new CMenuItem(_('Notice Board')))->setAction('quadro_avisos.dashboard'));

        if (!in_array(CWebUser::getType(), [USER_TYPE_ZABBIX_ADMIN, USER_TYPE_SUPER_ADMIN])) {
            return;
        }

        $qaAdminItem = (new CMenuItem(_('Notice Board')))
            ->setAction('quadro_avisos.view')
            ->setIcon('zi-support');

        if (CWebUser::getType() === USER_TYPE_SUPER_ADMIN) {
            // Super Admin: adiciona no menu Administração (hex = "Administração")
            foreach ($menu->getMenuItems() as $item) {
                if (bin2hex($item->getLabel()) === '41646d696e6973747261c3a7c3a36f'
                    || $item->getLabel() === 'Administration') {
                    $item->getSubMenu()->add($qaAdminItem);
                    return;
                }
            }
        }

        // Admin tipo 2: adiciona ao final do menu principal
        $menu->add($qaAdminItem);
    }
}
