<?php

namespace Modules\QuadroAvisos\Actions;

use CController;
use CControllerResponseData;
use DB;
use CWebUser;

/**
 * Controller público — exibe apenas avisos ATIVOS do grupo do usuário.
 * Acessível por todos os tipos de usuário (User, Admin, Super Admin).
 */
class CControllerQuadroAvisosDashboard extends CController {

    protected function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        return true;
    }

    protected function checkPermissions(): bool {
        // Qualquer usuário autenticado pode ver
        return CWebUser::isLoggedIn();
    }

    protected function doAction(): void {
        $userid    = (int) CWebUser::$data['userid'];
        $usrgrpids = $this->getUserGroupIds($userid);

        $avisos = [];
        if ($usrgrpids) {
            $placeholders = implode(',', array_fill(0, count($usrgrpids), '?'));
            $now    = date('Y-m-d H:i:s');
            $params = array_merge($usrgrpids, [$now, $now]);

            $avisos = DB::select_all(
                "SELECT a.*, u.alias AS usuario_nome
                 FROM quadro_avisos a
                 LEFT JOIN users u ON u.userid = a.criado_por
                 WHERE a.usrgrpid IN ($placeholders)
                   AND a.inicio <= ?
                   AND a.fim    >= ?
                 ORDER BY a.criado_em DESC",
                $params
            ) ?? [];
        }

        $this->setResponse(new CControllerResponseData([
            'avisos' => $avisos,
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
