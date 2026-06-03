<?php

namespace Modules\QuadroAvisos\Actions;

use CController;
use CControllerResponseRedirect;
use CControllerResponseFatal;

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
        $id = (int) $this->getInput('id');
        DBexecute('DELETE FROM quadro_avisos WHERE id=' . $id);
        $this->setResponse(new CControllerResponseRedirect('zabbix.php?action=quadro_avisos.view'));
    }
}
