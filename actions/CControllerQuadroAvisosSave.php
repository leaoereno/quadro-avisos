<?php

namespace Modules\QuadroAvisos\Actions;

use CController;
use CControllerResponseRedirect;
use CWebUser;
use CUrl;

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
            'usrgrpid'   => 'required|array',
            'inicio'     => 'required|string',
            'fim'        => 'required|string',
        ]);
    }

    protected function checkPermissions(): bool {
        return $this->getUserType() >= USER_TYPE_ZABBIX_ADMIN;
    }

    protected function doAction(): void {
        $id           = (int) $this->getInput('id', 0);
        $titulo       = zbx_dbstr($this->getInput('titulo'));
        $conteudo     = zbx_dbstr($this->getInput('conteudo'));
        $tipoBorda    = zbx_dbstr($this->getInput('tipo_borda'));
        $userid       = (int) CWebUser::$data['userid'];
        $isSuperAdmin = $this->getUserType() === USER_TYPE_SUPER_ADMIN;

        $inicio = zbx_dbstr(str_replace('T', ' ', $this->getInput('inicio')) . ':00');
        $fim    = zbx_dbstr(str_replace('T', ' ', $this->getInput('fim'))    . ':00');

        // Grupos selecionados
        $grpids = array_map('intval', (array) $this->getInput('usrgrpid', [0]));
        $grpids = array_filter($grpids, function($v){ return $v >= 0; });
        if (!$grpids) $grpids = [0];

        // Se "Todos" (0) está selecionado, salva apenas 0
        if (in_array(0, $grpids)) $grpids = [0];

        // Admin não pode usar usrgrpid=0
        if (!$isSuperAdmin) {
            $grpids = array_filter($grpids, function($v){ return $v > 0; });
            if (!$grpids) $grpids = [array_values($grpids)[0] ?? 0];
        }

        if ($id === 0) {
            // Cria um aviso para cada grupo selecionado
            foreach ($grpids as $grpid) {
                DBexecute(
                    'INSERT INTO quadro_avisos (titulo, conteudo, tipo_borda, criado_por, usrgrpid, inicio, fim)' .
                    ' VALUES (' . $titulo . ',' . $conteudo . ',' . $tipoBorda . ',' .
                    $userid . ',' . $grpid . ',' . $inicio . ',' . $fim . ')'
                );
            }
        } else {
            // Edição: atualiza o grupo para o primeiro selecionado
            $grpid = reset($grpids);
            DBexecute(
                'UPDATE quadro_avisos SET titulo=' . $titulo .
                ', conteudo=' . $conteudo .
                ', tipo_borda=' . $tipoBorda .
                ', usrgrpid=' . $grpid .
                ', inicio=' . $inicio .
                ', fim=' . $fim .
                ' WHERE id=' . $id
            );
        }

        $this->setResponse(new CControllerResponseRedirect(
            (new CUrl('zabbix.php'))->setArgument('action', 'quadro_avisos.view')
        ));
    }
}
