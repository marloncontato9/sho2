<?php
header('Content-Type: application/json');

$proxyPassword = getenv('PROXY_PASSWORD');
if (!isset($_GET['pass']) || $_GET['pass'] !== $proxyPassword) {
    echo json_encode(['error' => 'Acesso negado.']);
    exit;
}

$url = $_GET['url'] ?? '';
if (!$url) {
    echo json_encode(['error' => 'URL não fornecida.']);
    exit;
}

$credential = getenv('SHOPEE_API_ID');
$secretKey  = getenv('SHOPEE_API_SECRET');
$timestamp  = time();

// Montar query com \n explicitamente
$queryStr = <<<GQL
mutation {
    generateShortLink(input: {
        originUrl: "$url",
        subIds: ["auto"]
    }) {
        shortLink
    }
}
GQL;

$queryStr = str_replace("\n", "\\n", $queryStr);

$payload = ['query' => $queryStr];
$payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);

// String para assinatura
$stringToSign = $credential . $timestamp . $payloadJson . $secretKey;

// Debug (remova ou comente depois)
error_log("StringToSign: $stringToSign");

$signature = hash('sha256', $stringToSign);

$authorization = "SHA256 Credential=$credential, Timestamp=$timestamp, Signature=$signature";

$options = [
    'http' => [
        'method'  => 'POST',
        'header'  => [
            'Content-Type: application/json',
            'Authorization: ' . $authorization,
        ],
        'content' => $payloadJson,
        'ignore_errors' => true
    ]
];

$context = stream_context_create($options);
$response = file_get_contents('https://open-api.affiliate.shopee.com.br/graphql', false, $context);

if ($response === false) {
    echo json_encode(['error' => 'Erro na conexão com a API.']);
    exit;
}

$data = json_decode($response, true);

if (isset($data['data']['generateShortLink']['shortLink'])) {
    echo json_encode(['short' => $data['data']['generateShortLink']['shortLink']]);
} else {
    echo json_encode([
        'error' => 'Erro ao encurtar link.',
        'debug' => $data
    ]);
}
