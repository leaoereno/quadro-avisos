<?php

namespace Modules\QuadroAvisos\Actions;

use CController;
use CControllerResponseRedirect;
use CWebUser;
use CUrl;

class CControllerQuadroAvisosDelete extends CController {

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
        $result = DBselect('SELECT id, criado_por FROM quadro_avisos WHERE id=' . $id);
        if ($row = DBfetch($result)) {
            $aviso = $row;
        }

        // Só deleta se existir e for o criador (ou Super Admin)
        if ($aviso && ($isSuperAdmin || (int)$aviso['criado_por'] === $userid)) {
            DBexecute('DELETE FROM quadro_avisos WHERE id=' . $id);
        }

        $this->setResponse(new CControllerResponseRedirect(
            (new CUrl('zabbix.php'))->setArgument('action', 'quadro_avisos.view')
        ));
    }
}
