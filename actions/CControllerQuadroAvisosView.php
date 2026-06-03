<?php

namespace Modules\QuadroAvisos\Actions;

use CController;
use CControllerResponseData;
use CWebUser;

class CControllerQuadroAvisosView extends CController {

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
        $userid    = (int) CWebUser::$data['userid'];
        $usrgrpids = $this->getUserGroupIds($userid);
        $avisos    = [];

        if ($usrgrpids) {
            $placeholders = implode(',', $usrgrpids);
            $result = DBselect(
                'SELECT a.id, a.titulo, a.conteudo, a.tipo_borda, a.usrgrpid,
                        a.inicio, a.fim, a.criado_em, u.alias AS usuario_nome
                 FROM quadro_avisos a
                 LEFT JOIN users u ON u.userid = a.criado_por
                 WHERE a.usrgrpid IN (' . $placeholders . ')
                 ORDER BY a.criado_em DESC'
            );
            while ($row = DBfetch($result)) {
                $avisos[] = $row;
            }
        }

        $grupos = [];
        $result = DBselect('SELECT usrgrpid, name FROM usrgrp ORDER BY name');
        while ($row = DBfetch($result)) {
            $grupos[] = $row;
        }

        $this->setResponse(new CControllerResponseData([
            'avisos'  => $avisos,
            'grupos'  => $grupos,
            'user_id' => $userid,
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
