<?php

namespace Modules\QuadroAvisos\Actions;

use CController;
use CControllerResponseRedirect;
use CControllerResponseFatal;
use DB;
use CWebUser;
use CMessageHelper;

class CControllerQuadroAvisosSave extends CController {

    protected function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        $fields = [
            'id'         => 'db quadro_avisos.id',
            'titulo'     => 'required|string|not_empty',
            'conteudo'   => 'required|string|not_empty',
            'tipo_borda' => 'required|in info,success,warning,danger,mudanca,evento',
            'usrgrpid'   => 'required|db usrgrp.usrgrpid',
            'inicio'     => 'required|string',
            'fim'        => 'required|string',
        ];
        return $this->validateInput($fields);
    }

    protected function checkPermissions(): bool {
        return in_array(CWebUser::getType(), [USER_TYPE_ZABBIX_ADMIN, USER_TYPE_SUPER_ADMIN]);
    }

    protected function doAction(): void {
        $id        = (int) $this->getInput('id', 0);
        $titulo    = $this->getInput('titulo');
        $conteudo  = $this->getInput('conteudo');
        $tipoBorda = $this->getInput('tipo_borda');
        $usrgrpid  = (int) $this->getInput('usrgrpid');
        $inicio    = $this->getInput('inicio');
        $fim       = $this->getInput('fim');
        $userid    = (int) CWebUser::$data['userid'];

        // Valida grupo do usuário (Super Admin pode qualquer grupo)
        if (CWebUser::getType() !== USER_TYPE_SUPER_ADMIN) {
            $usrgrpids = $this->getUserGroupIds($userid);
            if (!in_array($usrgrpid, $usrgrpids)) {
                CMessageHelper::setErrorTitle(_('Sem permissão para este grupo.'));
                $this->setResponse(new CControllerResponseRedirect('zabbix.php?action=quadro_avisos.view'));
                return;
            }
        }

        if ($id === 0) {
            // INSERT
            DB::execute(
                "INSERT INTO quadro_avisos (titulo, conteudo, tipo_borda, criado_por, usrgrpid, inicio, fim)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$titulo, $conteudo, $tipoBorda, $userid, $usrgrpid, $inicio, $fim]
            );
            CMessageHelper::setSuccessTitle(_('Aviso criado com sucesso.'));
        } else {
            // UPDATE - verifica ownership
            $aviso = DB::select_one("SELECT criado_por, usrgrpid FROM quadro_avisos WHERE id = ?", [$id]);
            if (!$aviso) {
                $this->setResponse(new CControllerResponseFatal());
                return;
            }

            DB::execute(
                "UPDATE quadro_avisos SET titulo=?, conteudo=?, tipo_borda=?, usrgrpid=?, inicio=?, fim=?
                 WHERE id=?",
                [$titulo, $conteudo, $tipoBorda, $usrgrpid, $inicio, $fim, $id]
            );
            CMessageHelper::setSuccessTitle(_('Aviso atualizado com sucesso.'));
        }

        $this->setResponse(new CControllerResponseRedirect('zabbix.php?action=quadro_avisos.view'));
    }

    private function getUserGroupIds(int $userid): array {
        $rows = DB::select_all("SELECT usrgrpid FROM users_groups WHERE userid = ?", [$userid]);
        return $rows ? array_column($rows, 'usrgrpid') : [];
    }
}
