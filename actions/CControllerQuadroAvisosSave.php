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

        // Grupos selecionados (apenas inteiros positivos)
        $grpids = array_filter(
            array_map('intval', (array) $this->getInput('usrgrpid', [])),
            function ($v) { return $v >= 0; }
        );
        if (!$grpids) {
            $grpids = [0];
        }

        /*
         * CORREÇÃO BUG FK:
         * usrgrpid=0 antes violava a FK com usrgrp.usrgrpid.
         * Agora usamos a coluna `para_todos=1` com usrgrpid=NULL
         * para representar "visível para todos os grupos".
         * Apenas Super Admin pode criar avisos para todos.
         */
        $paraTodos = 0;
        if (in_array(0, $grpids)) {
            if ($isSuperAdmin) {
                $paraTodos = 1;
                $grpids    = [null]; // usrgrpid será NULL no banco
            } else {
                // Admin não pode usar "todos" — remove o 0 e usa seus grupos
                $grpids = array_filter($grpids, function ($v) { return $v > 0; });
                if (!$grpids) {
                    $this->setResponse(new CControllerResponseRedirect(
                        (new CUrl('zabbix.php'))->setArgument('action', 'quadro_avisos.view')
                    ));
                    return;
                }
            }
        }

        if ($id === 0) {
            // Criação — um registro por grupo selecionado
            foreach ($grpids as $grpid) {
                $grpSql = ($paraTodos || $grpid === null) ? 'NULL' : (int) $grpid;
                DBexecute(
                    'INSERT INTO quadro_avisos' .
                    ' (titulo, conteudo, tipo_borda, criado_por, usrgrpid, para_todos, inicio, fim)' .
                    ' VALUES (' .
                        $titulo    . ',' .
                        $conteudo  . ',' .
                        $tipoBorda . ',' .
                        $userid    . ',' .
                        $grpSql    . ',' .
                        (int) $paraTodos . ',' .
                        $inicio    . ',' .
                        $fim       .
                    ')'
                );
            }
        } else {
            // Edição — atualiza o primeiro grupo selecionado
            $grpid  = reset($grpids);
            $grpSql = ($paraTodos || $grpid === null) ? 'NULL' : (int) $grpid;
            DBexecute(
                'UPDATE quadro_avisos SET' .
                '  titulo='     . $titulo    .
                ', conteudo='   . $conteudo  .
                ', tipo_borda=' . $tipoBorda .
                ', usrgrpid='   . $grpSql    .
                ', para_todos=' . (int) $paraTodos .
                ', inicio='     . $inicio    .
                ', fim='        . $fim       .
                ' WHERE id='    . $id
            );
        }

        $this->setResponse(new CControllerResponseRedirect(
            (new CUrl('zabbix.php'))->setArgument('action', 'quadro_avisos.view')
        ));
    }
}
