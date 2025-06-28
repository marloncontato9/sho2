<?php
header('Content-Type: application/json');

// Proteção com senha
$proxyPassword = getenv('PROXY_PASSWORD');
if (!isset($_GET['pass']) || $_GET['pass'] !== $proxyPassword) {
    echo json_encode(['error' => 'Acesso negado.']);
    exit;
}

// URL a ser encurtada
$url = $_GET['url'] ?? '';
if (!$url) {
    echo json_encode(['error' => 'URL não fornecida.']);
    exit;
}

// Dados da Shopee (AppId e Secret)
$credential = getenv('SHOPEE_API_ID');
$secretKey  = getenv('SHOPEE_API_SECRET');
$timestamp  = time();

// Montar o payload JSON conforme API GraphQL
$payload = [
    'query' => 'mutation {
        generateShortLink(input: {
            originUrl: "' . $url . '",
            subIds: ["auto"]
        }) {
            shortLink
        }
    }'
];

$payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);

// Montar string para assinatura: Credential + Timestamp + Payload + Secret
$stringToSign = $credential . $timestamp . $payloadJson . $secretKey;

// Calcular assinatura SHA256 hex lowercase
$signature = hash('sha256', $stringToSign);

// Montar header Authorization correto
$authorization = "SHA256 Credential=$credential, Timestamp=$timestamp, Signature=$signature";

// Configurar requisição HTTP POST para API Shopee
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
