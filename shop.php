<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// JSON na MESMA pasta do shop.php
$ITEMS_PATH = __DIR__ . '/items.json';

/** Helpers */
function read_json(string $path)
{
    if (!file_exists($path)) return null;
    $raw = file_get_contents($path);
    return $raw !== false ? json_decode($raw, true) : null;
}
function respond(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/** Params (GET) */
$code  = isset($_GET['code']) ? strtoupper(trim((string)$_GET['code'])) : null;
$qty   = isset($_GET['qty']) ? max(1, (int)$_GET['qty']) : 1;
$coins = isset($_GET['coins']) ? max(0, (int)$_GET['coins']) : null;

/** Validações */
if (!$code) respond(400, ['success' => false, 'error' => 'Parâmetro "code" é obrigatório.']);
if ($coins === null) respond(400, ['success' => false, 'error' => 'Parâmetro "coins" é obrigatório.']);

$items = read_json($ITEMS_PATH);
if (!is_array($items)) respond(500, ['success' => false, 'error' => 'Arquivo de itens inválido ou ausente.']);

/** Busca item */
$item = null;
foreach ($items as $it) {
    if (isset($it['code']) && strtoupper($it['code']) === $code) {
        $item = $it;
        break;
    }
}
if (!$item) respond(404, ['success' => false, 'error' => 'Item não encontrado.']);

/** Cálculo */
$priceEach = (int)($item['price'] ?? 0);
$total     = $priceEach * $qty;
if ($total <= 0) respond(400, ['success' => false, 'error' => 'Preço/quantidade inválidos.']);

if ($coins < $total) {
    respond(200, [
        'success'  => false,
        'error'    => 'Não há moedas suficientes.',
        'item'     => $item,
        'qty'      => $qty,
        'coins'    => $coins,
        'required' => $total,
        'missing'  => $total - $coins
    ]);
}

/** Sucesso: retorna os dados do item (sem confirmar/abater moedas) */
respond(200, [
    'success'    => true,
    'timestamp'  => date('c'),
    'item'       => $item,
    'qty'        => $qty,
    'price_each' => $priceEach,
    'total'      => $total,
    'coins'      => $coins
]);
