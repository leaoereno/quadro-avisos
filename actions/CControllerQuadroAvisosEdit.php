<?php

namespace Modules\QuadroAvisos\Actions;

use CController;
use CControllerResponseData;
use CControllerResponseRedirect;
use CWebUser;
use CUrl;

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
        $id           = (int) $this->getInput('id');
        $isSuperAdmin = $this->getUserType() === USER_TYPE_SUPER_ADMIN;
        $userid       = (int) CWebUser::$data['userid'];

        $aviso  = null;
        $result = DBselect('SELECT * FROM quadro_avisos WHERE id=' . $id);
        if ($row = DBfetch($result)) {
            $aviso = $row;
        }

        // Redireciona se nao encontrou ou nao tem permissao
        if (!$aviso || (!$isSuperAdmin && (int)$aviso['criado_por'] !== $userid)) {
            $this->setResponse(new CControllerResponseRedirect(
                (new CUrl('zabbix.php'))->setArgument('action', 'quadro_avisos.view')
            ));
            return;
        }

        if ($isSuperAdmin) {
            $grupos = [];
            $result = DBselect('SELECT usrgrpid, name FROM usrgrp ORDER BY name');
            while ($row = DBfetch($result)) { $grupos[] = $row; }
        } else {
            $grupos = [];
            $result = DBselect(
                'SELECT g.usrgrpid, g.name FROM usrgrp g' .
                ' INNER JOIN users_groups ug ON ug.usrgrpid = g.usrgrpid' .
                ' WHERE ug.userid=' . $userid . ' ORDER BY g.name'
            );
            while ($row = DBfetch($result)) { $grupos[] = $row; }
        }

        $this->setResponse(new CControllerResponseData([
            'aviso'          => $aviso,
            'grupos'         => $grupos,
            'modo'           => 'edit',
            'is_super_admin' => $isSuperAdmin,
        ]));
    }
}
