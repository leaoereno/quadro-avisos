<?php

namespace Modules\QuadroAvisos\Actions;

use CController;
use CControllerResponseData;
use CWebUser;

class CControllerQuadroAvisosDashboard extends CController {

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
        $usrgrpids = $this->getUserGroupIds($userid);
        $avisos    = [];

        if ($usrgrpids) {
            $placeholders = implode(',', $usrgrpids);
            $now_str      = date('Y-m-d H:i:s');

            $result = DBselect(
                'SELECT a.id, a.titulo, a.conteudo, a.tipo_borda, a.usrgrpid, a.para_todos,' .
                '       a.inicio, a.fim, a.criado_em, u.username AS usuario_nome' .
                ' FROM quadro_avisos a' .
                ' LEFT JOIN users u ON u.userid = a.criado_por' .
                // CORREÇÃO: usa para_todos=1 em vez de usrgrpid=0
                ' WHERE (a.usrgrpid IN (' . $placeholders . ') OR a.para_todos = 1)' .
                '   AND a.inicio <= ' . zbx_dbstr($now_str) .
                '   AND a.fim    >= ' . zbx_dbstr($now_str) .
                ' ORDER BY a.criado_em DESC'
            );

            while ($row = DBfetch($result)) {
                $avisos[] = $row;
            }
        }

        $this->setResponse(new CControllerResponseData(['avisos' => $avisos]));
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
