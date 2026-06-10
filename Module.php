<?php
namespace Modules\NoticeBoardModule;
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
        // Monitoring menu: visible to all users
        $menu->findOrAdd(_('Monitoring'))
            ->getSubMenu()
            ->add((new CMenuItem(_('Notice Board')))->setAction('notice_board.dashboard'));
        if (!in_array(CWebUser::getType(), [USER_TYPE_ZABBIX_ADMIN, USER_TYPE_SUPER_ADMIN])) {
            return;
        }
        $adminItem = (new CMenuItem(_('Notice Board')))
            ->setAction('notice_board.view');
        if (CWebUser::getType() === USER_TYPE_SUPER_ADMIN) {
            foreach ($menu->getMenuItems() as $item) {
                if ($item->getLabel() === _('Administration') || $item->getLabel() === 'Administration') {
                    $item->getSubMenu()->add($adminItem);
                    return;
                }
            }
        }
        // Admin type 2: append to main menu
        $menu->add($adminItem);
    }
}
