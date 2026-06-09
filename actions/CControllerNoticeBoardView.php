<?php

namespace Modules\NoticeBoardModule\Actions;

use CController;
use CControllerResponseData;
use CWebUser;

class CControllerNoticeBoardView extends CController {

    protected function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        return true;
    }

    protected function checkPermissions(): bool {
        return $this->getUserType() >= USER_TYPE_ZABBIX_ADMIN;
    }

    protected function doAction(): void {
        $userid       = (int) CWebUser::$data['userid'];
        $isSuperAdmin = $this->getUserType() === USER_TYPE_SUPER_ADMIN;
        $grpids       = $this->getUserGroupIds($userid);
        $notices      = [];

        if ($grpids) {
            $placeholders = implode(',', $grpids);
            $result = DBselect(
                'SELECT n.id, n.titulo, n.conteudo, n.tipo_borda, n.usrgrpid, n.para_todos,' .
                ' n.inicio, n.fim, n.criado_em, n.criado_por, u.username AS usuario_nome' .
                ' FROM notice_board n' .
                ' LEFT JOIN users u ON u.userid = n.criado_por' .
                ' WHERE (n.usrgrpid IN (' . $placeholders . ') OR n.para_todos = 1)' .
                ' ORDER BY n.criado_em DESC'
            );
            while ($row = DBfetch($result)) {
                $notices[] = $row;
            }
        }

        $groups = [];
        $result = DBselect('SELECT usrgrpid, name FROM usrgrp ORDER BY name');
        while ($row = DBfetch($result)) {
            $groups[] = $row;
        }

        $this->setResponse(new CControllerResponseData([
            'notices'        => $notices,
            'groups'         => $groups,
            'user_id'        => $userid,
            'is_super_admin' => $isSuperAdmin,
        ]));
    }

    private function getUserGroupIds(int $userid): array {
        $ids = [];
        $result = DBselect('SELECT usrgrpid FROM users_groups WHERE userid=' . $userid);
        while ($row = DBfetch($result)) {
            $ids[] = (int) $row['usrgrpid'];
        }
        return $ids;
    }
}
