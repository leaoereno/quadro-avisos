<?php

namespace Modules\NoticeBoardModule\Actions;

use CController;
use CControllerResponseData;
use CWebUser;

class CControllerNoticeBoardDashboard extends CController {

    protected function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        return true;
    }

    protected function checkPermissions(): bool {
        return $this->getUserType() >= USER_TYPE_ZABBIX_USER;
    }

    protected function doAction(): void {
        $userid    = (int) CWebUser::$data['userid'];
        $grpids    = $this->getUserGroupIds($userid);
        $notices   = [];

        if ($grpids) {
            $placeholders = implode(',', $grpids);
            $now          = zbx_dbstr(date('Y-m-d H:i:s'));

            $result = DBselect(
                'SELECT n.id, n.titulo, n.conteudo, n.tipo_borda, n.usrgrpid, n.para_todos,' .
                '       n.inicio, n.fim, n.criado_em, u.username AS usuario_nome' .
                ' FROM notice_board n' .
                ' LEFT JOIN users u ON u.userid = n.criado_por' .
                ' WHERE (n.usrgrpid IN (' . $placeholders . ') OR n.para_todos = 1)' .
                '   AND n.inicio <= ' . $now .
                '   AND n.fim    >= ' . $now .
                ' ORDER BY n.criado_em DESC'
            );

            while ($row = DBfetch($result)) {
                $notices[] = $row;
            }
        }

        $this->setResponse(new CControllerResponseData(['notices' => $notices]));
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
