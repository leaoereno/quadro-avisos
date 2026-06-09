<?php

namespace Modules\NoticeBoardModule\Actions;

use CController;
use CControllerResponseData;
use CControllerResponseRedirect;
use CWebUser;
use CUrl;

class CControllerNoticeBoardEdit extends CController {

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

        $result = DBselect('SELECT * FROM notice_board WHERE id=' . $id);
        $notice = DBfetch($result) ?: null;

        if (!$notice || (!$isSuperAdmin && (int) $notice['criado_por'] !== $userid)) {
            $this->setResponse(new CControllerResponseRedirect(
                (new CUrl('zabbix.php'))->setArgument('action', 'notice_board.view')
            ));
            return;
        }

        if ($isSuperAdmin) {
            $groups = [];
            $result = DBselect('SELECT usrgrpid, name FROM usrgrp ORDER BY name');
            while ($row = DBfetch($result)) {
                $groups[] = $row;
            }
        } else {
            $groups = [];
            $result = DBselect(
                'SELECT g.usrgrpid, g.name FROM usrgrp g' .
                ' INNER JOIN users_groups ug ON ug.usrgrpid = g.usrgrpid' .
                ' WHERE ug.userid=' . $userid . ' ORDER BY g.name'
            );
            while ($row = DBfetch($result)) {
                $groups[] = $row;
            }
        }

        $this->setResponse(new CControllerResponseData([
            'notice'         => $notice,
            'groups'         => $groups,
            'mode'           => 'edit',
            'is_super_admin' => $isSuperAdmin,
        ]));
    }
}
