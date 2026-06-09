<?php
/**
 * Quadro de Avisos — REST API
 * Version: 1.0.0
 *
 * Endpoints:
 *   GET  /api/avisos              — Lista avisos (com filtros opcionais)
 *   GET  /api/avisos/{id}         — Busca aviso por ID
 *   POST /api/avisos              — Cria novo aviso
 *
 * Autenticação: Bearer Token (Zabbix API token ou token externo configurado)
 * Respostas:    JSON, UTF-8
 */

declare(strict_types=1);

// ── Bootstrap Zabbix ─────────────────────────────────────────────────────────
// Ajuste o caminho conforme sua instalação do Zabbix
$zabbixRoot = dirname(__DIR__, 3); // /usr/share/zabbix (3 níveis acima de modules/notice_board/api)

if (!file_exists($zabbixRoot . '/include/defines.inc.php')) {
    // Fallback: tenta caminhos comuns
    foreach (['/usr/share/zabbix', '/var/www/html', '/usr/share/nginx/html'] as $path) {
        if (file_exists($path . '/include/defines.inc.php')) {
            $zabbixRoot = $path;
            break;
        }
    }
}

// Impede acesso direto sem bootstrap do Zabbix em produção
if (!file_exists($zabbixRoot . '/include/defines.inc.php')) {
    ApiResponse::error(503, 'ZABBIX_NOT_FOUND', 'Zabbix bootstrap not found. Adjust $zabbixRoot in api/index.php.');
}

define('ZBX_SESSION_ACTIVE', 0);
require_once $zabbixRoot . '/include/defines.inc.php';
require_once $zabbixRoot . '/include/db.inc.php';
require_once $zabbixRoot . '/include/func.inc.php';

// ── Configuração da API ───────────────────────────────────────────────────────
/**
 * Tokens de acesso permitidos para fontes externas.
 * Configure tokens seguros em produção — use variáveis de ambiente ou
 * um arquivo de configuração fora do webroot.
 *
 * Exemplo com variável de ambiente:
 *   $API_TOKENS = array_filter(explode(',', getenv('QA_API_TOKENS') ?: ''));
 */
$API_TOKENS = [
    // 'seu-token-secreto-aqui',
    // 'token-grafana',
    // 'token-servicenow',
];

// Se nenhum token configurado, permite requisições (modo desenvolvimento)
// ATENÇÃO: defina tokens em produção!
$AUTH_REQUIRED = !empty($API_TOKENS);

// ── Headers CORS & Content-Type ───────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Api-Token');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Roteamento ────────────────────────────────────────────────────────────────
$method     = $_SERVER['REQUEST_METHOD'];
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Normaliza: remove prefixo até /api/
if (preg_match('#/api/avisos(?:/(\d+))?#', $requestUri, $m)) {
    $resourceId = isset($m[1]) ? (int)$m[1] : null;
} else {
    ApiResponse::error(404, 'NOT_FOUND', 'Endpoint not found. Available: GET /api/avisos, POST /api/avisos, GET /api/avisos/{id}');
}

// ── Autenticação ──────────────────────────────────────────────────────────────
if ($AUTH_REQUIRED) {
    $token = '';
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_API_TOKEN'] ?? '';

    if (str_starts_with($authHeader, 'Bearer ')) {
        $token = substr($authHeader, 7);
    } elseif (!empty($_SERVER['HTTP_X_API_TOKEN'])) {
        $token = $_SERVER['HTTP_X_API_TOKEN'];
    }

    if (!in_array($token, $API_TOKENS, true)) {
        ApiResponse::error(401, 'UNAUTHORIZED', 'Invalid or missing API token. Provide via Authorization: Bearer <token> or X-Api-Token header.');
    }
}

// ── Dispatch ──────────────────────────────────────────────────────────────────
try {
    if ($method === 'GET' && $resourceId !== null) {
        handleGetById((int)$resourceId);
    } elseif ($method === 'GET') {
        handleGetList();
    } elseif ($method === 'POST' && $resourceId === null) {
        handlePost();
    } else {
        ApiResponse::error(405, 'METHOD_NOT_ALLOWED', "Method $method not allowed on this endpoint.");
    }
} catch (Throwable $e) {
    ApiResponse::error(500, 'INTERNAL_ERROR', 'Internal server error: ' . $e->getMessage());
}

// ══════════════════════════════════════════════════════════════════════════════
// HANDLERS
// ══════════════════════════════════════════════════════════════════════════════

/**
 * GET /api/avisos
 *
 * Query params:
 *   tipo_borda  string   Filtra por tipo (info|success|warning|danger|mudanca|evento)
 *   status      string   Filtra por status temporal (active|scheduled|expired)
 *   usrgrpid    int      Filtra por grupo de usuários
 *   para_todos  bool     Se true, retorna apenas avisos visíveis para todos
 *   limit       int      Máx de registros (padrão: 50, máx: 200)
 *   offset      int      Deslocamento para paginação (padrão: 0)
 *   order       string   Ordenação: criado_em_desc|criado_em_asc|inicio_asc|fim_asc
 */
function handleGetList(): void
{
    $tipos_validos = ['info', 'success', 'warning', 'danger', 'mudanca', 'evento'];

    // Parâmetros de entrada com sanitização
    $tipoBorda = $_GET['tipo_borda'] ?? '';
    $status    = $_GET['status']     ?? '';
    $usrgrpid  = isset($_GET['usrgrpid']) ? (int)$_GET['usrgrpid'] : null;
    $paraTodos = isset($_GET['para_todos']) ? filter_var($_GET['para_todos'], FILTER_VALIDATE_BOOLEAN) : null;
    $limit     = min((int)($_GET['limit']  ?? 50), 200);
    $offset    = max((int)($_GET['offset'] ?? 0), 0);
    $order     = $_GET['order'] ?? 'criado_em_desc';

    if ($limit <= 0) $limit = 50;

    // Validações
    if ($tipoBorda && !in_array($tipoBorda, $tipos_validos, true)) {
        ApiResponse::error(400, 'INVALID_PARAM', "tipo_borda must be one of: " . implode(', ', $tipos_validos));
    }
    if ($status && !in_array($status, ['active', 'scheduled', 'expired'], true)) {
        ApiResponse::error(400, 'INVALID_PARAM', 'status must be: active, scheduled or expired');
    }

    $orderMap = [
        'criado_em_desc' => 'a.criado_em DESC',
        'criado_em_asc'  => 'a.criado_em ASC',
        'inicio_asc'     => 'a.inicio ASC',
        'fim_asc'        => 'a.fim ASC',
    ];
    $orderSql = $orderMap[$order] ?? 'a.criado_em DESC';

    // Construção da query com filtros
    $nowStr = date('Y-m-d H:i:s');
    $where  = ['1=1'];

    if ($tipoBorda) {
        $where[] = 'a.tipo_borda = ' . zbx_dbstr($tipoBorda);
    }

    if ($paraTodos === true) {
        $where[] = 'a.para_todos = 1';
    } elseif ($paraTodos === false) {
        $where[] = 'a.para_todos = 0';
    }

    if ($usrgrpid !== null) {
        $where[] = '(a.usrgrpid = ' . $usrgrpid . ' OR a.para_todos = 1)';
    }

    // Filtro de status temporal
    if ($status === 'active') {
        $where[] = 'a.inicio <= ' . zbx_dbstr($nowStr);
        $where[] = 'a.fim    >= ' . zbx_dbstr($nowStr);
    } elseif ($status === 'scheduled') {
        $where[] = 'a.inicio > ' . zbx_dbstr($nowStr);
    } elseif ($status === 'expired') {
        $where[] = 'a.fim < ' . zbx_dbstr($nowStr);
    }

    $whereSql = implode(' AND ', $where);

    // Contagem total para paginação
    $countResult = DBselect("SELECT COUNT(*) AS total FROM notice_board a WHERE $whereSql");
    $totalRow    = DBfetch($countResult);
    $total       = (int)($totalRow['total'] ?? 0);

    // Busca paginada
    $result = DBselect(
        'SELECT a.id, a.titulo, a.conteudo, a.tipo_borda, a.usrgrpid, a.para_todos,' .
        '       a.inicio, a.fim, a.criado_em, a.atualizado_em,' .
        '       u.username AS criado_por_nome,' .
        '       g.name AS grupo_nome' .
        ' FROM notice_board a' .
        ' LEFT JOIN users   u ON u.userid   = a.criado_por' .
        ' LEFT JOIN usrgrp  g ON g.usrgrpid = a.usrgrpid' .
        " WHERE $whereSql" .
        " ORDER BY $orderSql",
        $limit,
        $offset
    );

    $avisos = [];
    while ($row = DBfetch($result)) {
        $avisos[] = formatAviso($row, $nowStr);
    }

    ApiResponse::success([
        'data'       => $avisos,
        'pagination' => [
            'total'   => $total,
            'limit'   => $limit,
            'offset'  => $offset,
            'pages'   => (int)ceil($total / $limit),
        ],
        'filters_applied' => array_filter([
            'tipo_borda' => $tipoBorda ?: null,
            'status'     => $status    ?: null,
            'usrgrpid'   => $usrgrpid,
            'para_todos' => $paraTodos,
        ], fn($v) => $v !== null),
    ]);
}

/**
 * GET /api/avisos/{id}
 */
function handleGetById(int $id): void
{
    $nowStr = date('Y-m-d H:i:s');
    $result = DBselect(
        'SELECT a.id, a.titulo, a.conteudo, a.tipo_borda, a.usrgrpid, a.para_todos,' .
        '       a.inicio, a.fim, a.criado_em, a.atualizado_em,' .
        '       u.username AS criado_por_nome,' .
        '       g.name AS grupo_nome' .
        ' FROM notice_board a' .
        ' LEFT JOIN users   u ON u.userid   = a.criado_por' .
        ' LEFT JOIN usrgrp  g ON g.usrgrpid = a.usrgrpid' .
        ' WHERE a.id = ' . $id
    );

    $row = DBfetch($result);
    if (!$row) {
        ApiResponse::error(404, 'NOT_FOUND', "Notice with id=$id not found.");
    }

    ApiResponse::success(['data' => formatAviso($row, $nowStr)]);
}

/**
 * POST /api/avisos
 *
 * Body JSON:
 *   titulo      string  (obrigatório)  Título do aviso
 *   conteudo    string  (obrigatório)  Conteúdo em Markdown ou HTML
 *   tipo_borda  string  (obrigatório)  info|success|warning|danger|mudanca|evento
 *   usrgrpid    int     (condicional)  ID do grupo. Null se para_todos=true
 *   para_todos  bool    (opcional)     true = visível para todos os grupos
 *   inicio      string  (obrigatório)  ISO 8601: 2025-06-01T08:00:00
 *   fim         string  (obrigatório)  ISO 8601: 2025-06-30T18:00:00
 *   criado_por  int     (obrigatório)  userid do Zabbix (usuário criador)
 *   source      string  (opcional)     Identificador da fonte remota (ex: "grafana", "servicenow")
 */
function handlePost(): void
{
    $tipos_validos = ['info', 'success', 'warning', 'danger', 'mudanca', 'evento'];

    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        ApiResponse::error(400, 'INVALID_JSON', 'Request body must be valid JSON. Error: ' . json_last_error_msg());
    }

    // ── Validação dos campos obrigatórios ─────────────────────────────────────
    $errors = [];

    $titulo    = trim($body['titulo']    ?? '');
    $conteudo  = trim($body['conteudo']  ?? '');
    $tipoBorda = trim($body['tipo_borda'] ?? '');
    $inicio    = trim($body['inicio']    ?? '');
    $fim       = trim($body['fim']       ?? '');
    $criadoPor = isset($body['criado_por']) ? (int)$body['criado_por'] : 0;
    $usrgrpid  = isset($body['usrgrpid']) ? (int)$body['usrgrpid'] : null;
    $paraTodos = isset($body['para_todos']) ? (bool)$body['para_todos'] : false;
    $source    = substr(trim($body['source'] ?? ''), 0, 64); // fonte remota opcional

    if ($titulo === '') {
        $errors[] = ['field' => 'titulo', 'message' => 'Required field.'];
    } elseif (mb_strlen($titulo) > 255) {
        $errors[] = ['field' => 'titulo', 'message' => 'Max 255 characters.'];
    }

    if ($conteudo === '') {
        $errors[] = ['field' => 'conteudo', 'message' => 'Required field.'];
    }

    if ($tipoBorda === '') {
        $errors[] = ['field' => 'tipo_borda', 'message' => 'Required field.'];
    } elseif (!in_array($tipoBorda, $tipos_validos, true)) {
        $errors[] = ['field' => 'tipo_borda', 'message' => 'Must be one of: ' . implode(', ', $tipos_validos)];
    }

    // Validação e normalização de datas (aceita ISO 8601 com T ou espaço)
    $inicioTs = $fimTs = null;
    if ($inicio === '') {
        $errors[] = ['field' => 'inicio', 'message' => 'Required field. Format: 2025-06-01T08:00:00'];
    } else {
        $inicioNorm = str_replace('T', ' ', $inicio);
        // Remove timezone offset se presente
        $inicioNorm = preg_replace('/[+-]\d{2}:\d{2}$|Z$/', '', $inicioNorm);
        $inicioTs   = strtotime($inicioNorm);
        if ($inicioTs === false) {
            $errors[] = ['field' => 'inicio', 'message' => 'Invalid date format. Use ISO 8601: 2025-06-01T08:00:00'];
        }
    }

    if ($fim === '') {
        $errors[] = ['field' => 'fim', 'message' => 'Required field. Format: 2025-06-30T18:00:00'];
    } else {
        $fimNorm = str_replace('T', ' ', $fim);
        $fimNorm = preg_replace('/[+-]\d{2}:\d{2}$|Z$/', '', $fimNorm);
        $fimTs   = strtotime($fimNorm);
        if ($fimTs === false) {
            $errors[] = ['field' => 'fim', 'message' => 'Invalid date format. Use ISO 8601: 2025-06-30T18:00:00'];
        }
    }

    if ($inicioTs && $fimTs && $fimTs <= $inicioTs) {
        $errors[] = ['field' => 'fim', 'message' => 'fim must be after inicio.'];
    }

    if ($criadoPor <= 0) {
        $errors[] = ['field' => 'criado_por', 'message' => 'Required field. Must be a valid Zabbix userid (integer > 0).'];
    }

    if (!$paraTodos && $usrgrpid === null) {
        $errors[] = ['field' => 'usrgrpid', 'message' => 'Required when para_todos is false. Provide a valid Zabbix usrgrpid.'];
    }

    if (!empty($errors)) {
        ApiResponse::error(422, 'VALIDATION_ERROR', 'Request validation failed.', ['errors' => $errors]);
    }

    // ── Verifica existência do usuário criador ────────────────────────────────
    $userCheck = DBfetch(DBselect('SELECT userid FROM users WHERE userid=' . $criadoPor));
    if (!$userCheck) {
        ApiResponse::error(422, 'VALIDATION_ERROR', "User criado_por=$criadoPor not found in Zabbix.", [
            'errors' => [['field' => 'criado_por', 'message' => "userid=$criadoPor does not exist."]]
        ]);
    }

    // ── Verifica existência do grupo (se fornecido) ───────────────────────────
    if (!$paraTodos && $usrgrpid > 0) {
        $grpCheck = DBfetch(DBselect('SELECT usrgrpid FROM usrgrp WHERE usrgrpid=' . $usrgrpid));
        if (!$grpCheck) {
            ApiResponse::error(422, 'VALIDATION_ERROR', "User group usrgrpid=$usrgrpid not found in Zabbix.", [
                'errors' => [['field' => 'usrgrpid', 'message' => "usrgrpid=$usrgrpid does not exist."]]
            ]);
        }
    }

    // ── Formata datas para MySQL ───────────────────────────────────────────────
    $inicioDb = date('Y-m-d H:i:s', $inicioTs);
    $fimDb    = date('Y-m-d H:i:s', $fimTs);
    $grpSql   = ($paraTodos || $usrgrpid === null) ? 'NULL' : (int)$usrgrpid;

    // ── INSERT ────────────────────────────────────────────────────────────────
    $sourceSql = $source ? zbx_dbstr($source) : 'NULL';
    DBexecute(
        'INSERT INTO notice_board' .
        ' (titulo, conteudo, tipo_borda, criado_por, usrgrpid, para_todos, inicio, fim, source)' .
        ' VALUES (' .
            zbx_dbstr($titulo)    . ',' .
            zbx_dbstr($conteudo)  . ',' .
            zbx_dbstr($tipoBorda) . ',' .
            (int)$criadoPor       . ',' .
            $grpSql               . ',' .
            (int)$paraTodos       . ',' .
            zbx_dbstr($inicioDb)  . ',' .
            zbx_dbstr($fimDb)     . ',' .
            $sourceSql            .
        ')'
    );

    $newId = DBinsert_id();

    // Retorna o aviso criado
    $nowStr = date('Y-m-d H:i:s');
    $result = DBselect(
        'SELECT a.id, a.titulo, a.conteudo, a.tipo_borda, a.usrgrpid, a.para_todos,' .
        '       a.inicio, a.fim, a.criado_em, a.atualizado_em,' .
        '       u.username AS criado_por_nome,' .
        '       g.name AS grupo_nome' .
        ' FROM notice_board a' .
        ' LEFT JOIN users   u ON u.userid   = a.criado_por' .
        ' LEFT JOIN usrgrp  g ON g.usrgrpid = a.usrgrpid' .
        ' WHERE a.id = ' . (int)$newId
    );

    $row = DBfetch($result);

    http_response_code(201);
    ApiResponse::success(['data' => formatAviso($row, $nowStr)], 201);
}

// ══════════════════════════════════════════════════════════════════════════════
// HELPERS
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Formata uma linha do banco para o formato de resposta da API.
 */
function formatAviso(array $row, string $nowStr): array
{
    $inicio = new DateTime($row['inicio']);
    $fim    = new DateTime($row['fim']);
    $now    = new DateTime($nowStr);

    if ($now < $inicio) {
        $status = 'scheduled';
    } elseif ($now > $fim) {
        $status = 'expired';
    } else {
        $status = 'active';
    }

    return [
        'id'            => (int)$row['id'],
        'titulo'        => $row['titulo'],
        'conteudo'      => $row['conteudo'],
        'tipo_borda'    => $row['tipo_borda'],
        'status'        => $status,
        'usrgrpid'      => $row['usrgrpid'] !== null ? (int)$row['usrgrpid'] : null,
        'grupo_nome'    => $row['para_todos'] ? 'All groups' : ($row['grupo_nome'] ?? null),
        'para_todos'    => (bool)$row['para_todos'],
        'criado_por'    => [
            'username' => $row['criado_por_nome'] ?? 'N/A',
        ],
        'inicio'        => $inicio->format(DateTime::ATOM),
        'fim'           => $fim->format(DateTime::ATOM),
        'criado_em'     => (new DateTime($row['criado_em']))->format(DateTime::ATOM),
        'atualizado_em' => (new DateTime($row['atualizado_em']))->format(DateTime::ATOM),
    ];
}

/**
 * Classe de resposta padronizada
 */
class ApiResponse
{
    public static function success(array $payload, int $code = 200): never
    {
        http_response_code($code);
        echo json_encode(array_merge(
            ['success' => true, 'timestamp' => date(DateTime::ATOM)],
            $payload
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public static function error(int $code, string $errorCode, string $message, array $extra = []): never
    {
        http_response_code($code);
        echo json_encode(array_merge([
            'success'   => false,
            'timestamp' => date(DateTime::ATOM),
            'error'     => [
                'code'    => $errorCode,
                'message' => $message,
            ],
        ], $extra), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
