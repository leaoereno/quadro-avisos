<?php

namespace Modules\NoticeBoardModule\Actions;

use CController;
use CControllerResponseRedirect;
use CWebUser;
use CUrl;

class CControllerNoticeBoardSave extends CController {

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

        // Parse datetime-local: input arrives as "YYYY-MM-DDTHH:MM" (no seconds)
        $inicioRaw = str_replace('T', ' ', $this->getInput('inicio'));
        $fimRaw    = str_replace('T', ' ', $this->getInput('fim'));

        // Append seconds only if not already present
        $inicio = zbx_dbstr(preg_match('/\d{2}:\d{2}:\d{2}$/', $inicioRaw) ? $inicioRaw : $inicioRaw . ':00');
        $fim    = zbx_dbstr(preg_match('/\d{2}:\d{2}:\d{2}$/', $fimRaw)    ? $fimRaw    : $fimRaw    . ':00');

        $grpids = array_filter(
            array_map('intval', (array) $this->getInput('usrgrpid', [])),
            fn($v) => $v >= 0
        );
        if (!$grpids) {
            $grpids = [0];
        }

        $paraTodos = 0;
        if (in_array(0, $grpids)) {
            if ($isSuperAdmin) {
                $paraTodos = 1;
                $grpids    = [null];
            } else {
                $grpids = array_filter($grpids, fn($v) => $v > 0);
                if (!$grpids) {
                    $this->setResponse(new CControllerResponseRedirect(
                        (new CUrl('zabbix.php'))->setArgument('action', 'notice_board.view')
                    ));
                    return;
                }
            }
        }

        if ($id === 0) {
            foreach ($grpids as $grpid) {
                $grpSql = ($paraTodos || $grpid === null) ? 'NULL' : (int) $grpid;
                DBexecute(
                    'INSERT INTO notice_board' .
                    ' (titulo, conteudo, tipo_borda, criado_por, usrgrpid, para_todos, inicio, fim)' .
                    ' VALUES (' .
                        $titulo              . ',' .
                        $conteudo            . ',' .
                        $tipoBorda           . ',' .
                        $userid              . ',' .
                        $grpSql              . ',' .
                        (int) $paraTodos     . ',' .
                        $inicio              . ',' .
                        $fim                 .
                    ')'
                );
            }
        } else {
            $grpid  = reset($grpids);
            $grpSql = ($paraTodos || $grpid === null) ? 'NULL' : (int) $grpid;
            DBexecute(
                'UPDATE notice_board SET' .
                '  titulo='     . $titulo        .
                ', conteudo='   . $conteudo       .
                ', tipo_borda=' . $tipoBorda      .
                ', usrgrpid='   . $grpSql         .
                ', para_todos=' . (int) $paraTodos .
                ', inicio='     . $inicio         .
                ', fim='        . $fim            .
                ' WHERE id='    . $id
            );
        }

        $this->setResponse(new CControllerResponseRedirect(
            (new CUrl('zabbix.php'))->setArgument('action', 'notice_board.view')
        ));
    }
}
