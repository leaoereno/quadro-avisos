<?php

namespace Modules\QuadroAvisos\Actions;

use CController;
use CControllerResponseRedirect;
use CControllerResponseFatal;
use DB;
use CWebUser;
use CMessageHelper;

class CControllerQuadroAvisosDelete extends CController {

    protected function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        $fields = ['id' => 'required|db quadro_avisos.id'];
        return $this->validateInput($fields);
    }

    protected function checkPermissions(): bool {
        return in_array(CWebUser::getType(), [USER_TYPE_ZABBIX_ADMIN, USER_TYPE_SUPER_ADMIN]);
    }

    protected function doAction(): void {
        $id     = (int) $this->getInput('id');
        $userid = (int) CWebUser::$data['userid'];

        $aviso = DB::select_one("SELECT criado_por, usrgrpid FROM quadro_avisos WHERE id = ?", [$id]);
        if (!$aviso) {
            $this->setResponse(new CControllerResponseFatal());
            return;
        }

        // Super Admin pode deletar qualquer aviso; Admin apenas do seu grupo
        if (CWebUser::getType() !== USER_TYPE_SUPER_ADMIN) {
            $usrgrpids = $this->getUserGroupIds($userid);
            if (!in_array($aviso['usrgrpid'], $usrgrpids)) {
                CMessageHelper::setErrorTitle(_('Sem permissão para excluir este aviso.'));
                $this->setResponse(new CControllerResponseRedirect('zabbix.php?action=quadro_avisos.view'));
                return;
            }
        }

        DB::execute("DELETE FROM quadro_avisos WHERE id = ?", [$id]);
        CMessageHelper::setSuccessTitle(_('Aviso excluído com sucesso.'));
        $this->setResponse(new CControllerResponseRedirect('zabbix.php?action=quadro_avisos.view'));
    }

    private function getUserGroupIds(int $userid): array {
        $rows = DB::select_all("SELECT usrgrpid FROM users_groups WHERE userid = ?", [$userid]);
        return $rows ? array_column($rows, 'usrgrpid') : [];
    }
}
