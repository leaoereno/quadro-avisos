<?php
namespace Modules\NoticeBoardModule;

use Zabbix\Core\CModule;
use APP;
use CMenuItem;
use CWebUser;
use CView;

class Module extends CModule {

    public function init(): void {
        // Guard umbrella Informativos
        if (defined('ZBX_INFORMATIVOS_ACTIVE') && ZBX_INFORMATIVOS_ACTIVE === true) { return; }
        CView::registerDirectory(__DIR__ . '/views');

        try {
            $menu = APP::Component()->get('menu.main');
        } catch (\Throwable $e) {
            return;
        }

        // Submenu em Monitoramento: visível para todos os perfis
        $menu->findOrAdd(_('Monitoring'))
            ->getSubMenu()
            ->add((new CMenuItem(_('Notice Board')))->setAction('notice_board.dashboard'));

        if (!in_array(CWebUser::getType(), [USER_TYPE_ZABBIX_ADMIN, USER_TYPE_SUPER_ADMIN])) {
            return;
        }

        $adminItem = (new CMenuItem(_('Notice Board')))
            ->setAction('notice_board.view');

        if (CWebUser::getType() === USER_TYPE_SUPER_ADMIN) {
            // Super Admin: anexa dentro de "Administração" (sem ícone próprio, herda estrutura)
            foreach ($menu->getMenuItems() as $item) {
                if ($item->getLabel() === _('Administration') || $item->getLabel() === 'Administration') {
                    $item->getSubMenu()->add($adminItem);
                    return;
                }
            }
            // Fallback Super Admin
            $menu->add($adminItem);
            return;
        }

        // Admin (type=2): cria item raiz com ícone ZBX_ICON_BELL
        // O Admin não tem menu "Administração", então o item fica no nível raiz
        // e precisa de ícone explícito para aparecer corretamente na sidebar.
        $menu->add(
            (new CMenuItem(_('Notice Board')))
                ->setIcon(ZBX_ICON_BELL)
                ->setAction('notice_board.view')
        );
    }
}
