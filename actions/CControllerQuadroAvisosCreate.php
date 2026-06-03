<?php

namespace Modules\QuadroAvisos\Actions;

use CController;
use CControllerResponseData;

class CControllerQuadroAvisosCreate extends CController {

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
        $aviso = [
            'id'         => 0,
            'titulo'     => '',
            'conteudo'   => '',
            'tipo_borda' => 'info',
            'usrgrpid'   => 0,
            'inicio'     => date('Y-m-d H:i:s'),
            'fim'        => date('Y-m-d H:i:s', strtotime('+7 days')),
        ];

        $grupos = [];
        $result = DBselect('SELECT usrgrpid, name FROM usrgrp ORDER BY name');
        while ($row = DBfetch($result)) {
            $grupos[] = $row;
        }

        $this->setResponse(new CControllerResponseData([
            'aviso'  => $aviso,
            'grupos' => $grupos,
            'modo'   => 'create',
        ]));
    }
}
