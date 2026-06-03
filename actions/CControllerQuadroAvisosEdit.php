<?php

namespace Modules\QuadroAvisos\Actions;

use CController;
use CControllerResponseData;
use CControllerResponseFatal;
use DB;
use CWebUser;

class CControllerQuadroAvisosEdit extends CController {

    protected function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        $fields = [
            'id' => 'required|db quadro_avisos.id',
        ];
        $ret = $this->validateInput($fields);
        return $ret;
    }

    protected function checkPermissions(): bool {
        return in_array(CWebUser::getType(), [USER_TYPE_ZABBIX_ADMIN, USER_TYPE_SUPER_ADMIN]);
    }

    protected function doAction(): void {
        $id = (int) $this->getInput('id');
        $aviso = DB::select_one(
            "SELECT * FROM quadro_avisos WHERE id = ?",
            [$id]
        );

        if (!$aviso) {
            $this->setResponse(new CControllerResponseFatal());
            return;
        }

        // Verifica se pertence a grupo do usuário
        $usrgrpids = $this->getUserGroupIds(CWebUser::$data['userid']);
        if (!in_array($aviso['usrgrpid'], $usrgrpids) && CWebUser::getType() !== USER_TYPE_SUPER_ADMIN) {
            $this->setResponse(new CControllerResponseFatal());
            return;
        }

        $grupos = DB::select_all("SELECT usrgrpid, name FROM usrgrp ORDER BY name") ?? [];

        $this->setResponse(new CControllerResponseData([
            'aviso'  => $aviso,
            'grupos' => $grupos,
            'modo'   => 'edit',
        ]));
    }

    private function getUserGroupIds(int $userid): array {
        $rows = DB::select_all("SELECT usrgrpid FROM users_groups WHERE userid = ?", [$userid]);
        return $rows ? array_column($rows, 'usrgrpid') : [];
    }
}
