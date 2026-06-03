<?php

namespace Modules\QuadroAvisos\Actions;

use CController;
use CControllerResponseData;
use APP;
use DB;
use CWebUser;

class CControllerQuadroAvisosCreate extends CController {

    protected function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        return true;
    }

    protected function checkPermissions(): bool {
        return in_array(CWebUser::getType(), [USER_TYPE_ZABBIX_ADMIN, USER_TYPE_SUPER_ADMIN]);
    }

    protected function doAction(): void {
        $aviso = [
            'id'         => 0,
            'titulo'     => '',
            'conteudo'   => '',
            'tipo_borda' => 'info',
            'usrgrpid'   => 0,
            'inicio'     => date('Y-m-d H:i:s'),
            'fim'        => date('Y-m-d H:i:s', strtotime('+7 days')),
        ];

        $grupos = DB::select_all(
            "SELECT usrgrpid, name FROM usrgrp ORDER BY name"
        ) ?? [];

        $this->setResponse(new CControllerResponseData([
            'aviso'  => $aviso,
            'grupos' => $grupos,
            'modo'   => 'create',
        ]));
    }
}
