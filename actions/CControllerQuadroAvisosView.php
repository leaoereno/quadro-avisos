<?php

namespace Modules\QuadroAvisos\Actions;

use CController;
use CControllerResponseData;
use CControllerResponseFatal;
use APP;
use DB;
use CWebUser;

class CControllerQuadroAvisosView extends CController {

    protected function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        return true;
    }

    protected function checkPermissions(): bool {
        // Menu visível apenas para Admin e Super Admin
        return in_array(CWebUser::getType(), [USER_TYPE_ZABBIX_ADMIN, USER_TYPE_SUPER_ADMIN]);
    }

    protected function doAction(): void {
        // Obtém grupos do usuário logado
        $usrgrpids = $this->getUserGroupIds(CWebUser::$data['userid']);

        // Busca todos os avisos dos grupos do usuário (sem filtro de data para admin)
        $avisos = [];
        if ($usrgrpids) {
            $placeholders = implode(',', array_fill(0, count($usrgrpids), '?'));
            $sql = "SELECT a.*, u.alias AS usuario_nome
                    FROM quadro_avisos a
                    LEFT JOIN users u ON u.userid = a.criado_por
                    WHERE a.usrgrpid IN ($placeholders)
                    ORDER BY a.criado_em DESC";
            $avisos = DB::select_all($sql, $usrgrpids) ?? [];
        }

        // Grupos disponíveis para o filtro/cadastro
        $grupos = DB::select_all(
            "SELECT usrgrpid, name FROM usrgrp ORDER BY name"
        ) ?? [];

        $this->setResponse(new CControllerResponseData([
            'avisos'  => $avisos,
            'grupos'  => $grupos,
            'user_id' => CWebUser::$data['userid'],
        ]));
    }

    private function getUserGroupIds(int $userid): array {
        $rows = DB::select_all(
            "SELECT usrgrpid FROM users_groups WHERE userid = ?",
            [$userid]
        );
        return $rows ? array_column($rows, 'usrgrpid') : [];
    }
}
