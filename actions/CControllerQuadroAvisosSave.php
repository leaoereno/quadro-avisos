<?php

namespace Modules\QuadroAvisos\Actions;

use CController;
use CControllerResponseRedirect;
use CWebUser;

class CControllerQuadroAvisosSave extends CController {

    protected function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        return $this->validateInput([
            'id'         => 'int32',
            'titulo'     => 'required|string|not_empty',
            'conteudo'   => 'required|string|not_empty',
            'tipo_borda' => 'required|in info,success,warning,danger,mudanca,evento',
            'usrgrpid'   => 'required|int32',
            'inicio'     => 'required|string',
            'fim'        => 'required|string',
        ]);
    }

    protected function checkPermissions(): bool {
        return $this->getUserType() >= USER_TYPE_ZABBIX_ADMIN;
    }

    protected function doAction(): void {
        $id        = (int) $this->getInput('id', 0);
        $titulo    = zbx_dbstr($this->getInput('titulo'));
        $conteudo  = zbx_dbstr($this->getInput('conteudo'));
        $tipoBorda = zbx_dbstr($this->getInput('tipo_borda'));
        $usrgrpid  = (int) $this->getInput('usrgrpid');
        $inicio    = zbx_dbstr($this->getInput('inicio'));
        $fim       = zbx_dbstr($this->getInput('fim'));
        $userid    = (int) CWebUser::$data['userid'];

        if ($id === 0) {
            DBexecute(
                'INSERT INTO quadro_avisos (titulo, conteudo, tipo_borda, criado_por, usrgrpid, inicio, fim)' .
                ' VALUES (' . $titulo . ',' . $conteudo . ',' . $tipoBorda . ',' . $userid . ',' . $usrgrpid . ',' . $inicio . ',' . $fim . ')'
            );
        } else {
            DBexecute(
                'UPDATE quadro_avisos SET titulo=' . $titulo . ', conteudo=' . $conteudo .
                ', tipo_borda=' . $tipoBorda . ', usrgrpid=' . $usrgrpid .
                ', inicio=' . $inicio . ', fim=' . $fim .
                ' WHERE id=' . $id
            );
        }

        $this->setResponse(new CControllerResponseRedirect('zabbix.php?action=quadro_avisos.view'));
    }
}
