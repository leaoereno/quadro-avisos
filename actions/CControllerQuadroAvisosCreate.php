<?php

namespace Modules\QuadroAvisos\Actions;

use CController;
use CControllerResponseData;
use CWebUser;

class CControllerQuadroAvisosCreate extends CController {

    protected function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        return true;
    }

    protected function checkPermissions(): bool {
        return $this->getUserType() >= USER_TYPE_ZABBIX_ADMIN;
    }

    protected function doAction(): void {
        $isSuperAdmin = $this->getUserType() === USER_TYPE_SUPER_ADMIN;
        $userid       = (int) CWebUser::$data['userid'];

        $aviso = [
            'id'         => 0,
            'titulo'     => '',
            'conteudo'   => '',
            'tipo_borda' => 'info',
            'usrgrpid'   => 0,
            'inicio'     => date('Y-m-d H:i:s'),
            'fim'        => date('Y-m-d H:i:s', strtotime('+7 days')),
        ];

        // Admin só vê seus próprios grupos
        if ($isSuperAdmin) {
            $grupos = [];
            $result = DBselect('SELECT usrgrpid, name FROM usrgrp ORDER BY name');
            while ($row = DBfetch($result)) { $grupos[] = $row; }
        } else {
            $grupos = [];
            $result = DBselect(
                'SELECT g.usrgrpid, g.name FROM usrgrp g'.
                ' INNER JOIN users_groups ug ON ug.usrgrpid = g.usrgrpid'.
                ' WHERE ug.userid=' . $userid .
                ' ORDER BY g.name'
            );
            while ($row = DBfetch($result)) { $grupos[] = $row; }
        }

        $this->setResponse(new CControllerResponseData([
            'aviso'           => $aviso,
            'grupos'          => $grupos,
            'modo'            => 'create',
            'is_super_admin'  => $isSuperAdmin,
        ]));
    }
}
