<?php

namespace Modules\QuadroAvisos\Actions;

use CController;
use CControllerResponseData;
use CControllerResponseFatal;
use CWebUser;

class CControllerQuadroAvisosEdit extends CController {

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
        $id     = (int) $this->getInput('id');
        $aviso  = null;
        $result = DBselect('SELECT * FROM quadro_avisos WHERE id=' . $id);
        if ($row = DBfetch($result)) {
            $aviso = $row;
        }

        if (!$aviso) {
            $this->setResponse(new CControllerResponseFatal());
            return;
        }

        $grupos = [];
        $result = DBselect('SELECT usrgrpid, name FROM usrgrp ORDER BY name');
        while ($row = DBfetch($result)) {
            $grupos[] = $row;
        }

        $this->setResponse(new CControllerResponseData([
            'aviso'  => $aviso,
            'grupos' => $grupos,
            'modo'   => 'edit',
        ]));
    }
}
