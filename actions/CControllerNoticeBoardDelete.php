<?php

namespace Modules\NoticeBoardModule\Actions;

use CController;
use CControllerResponseRedirect;
use CWebUser;
use CUrl;

class CControllerNoticeBoardDelete extends CController {

    protected function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        return $this->validateInput(['id' => 'required|int32']);
    }

    protected function checkPermissions(): bool {
        return $this->getUserType() >= USER_TYPE_ZABBIX_ADMIN;
    }

    protected function doAction(): void {
        $id           = (int) $this->getInput('id');
        $isSuperAdmin = $this->getUserType() === USER_TYPE_SUPER_ADMIN;
        $userid       = (int) CWebUser::$data['userid'];

        $result = DBselect('SELECT id, criado_por FROM notice_board WHERE id=' . $id);
        $notice = DBfetch($result);

        if ($notice && ($isSuperAdmin || (int) $notice['criado_por'] === $userid)) {
            DBexecute('DELETE FROM notice_board WHERE id=' . $id);
        }

        $this->setResponse(new CControllerResponseRedirect(
            (new CUrl('zabbix.php'))->setArgument('action', 'notice_board.view')
        ));
    }
}
