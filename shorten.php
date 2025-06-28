<?php
header('Content-Type: application/json');

// 🔒 Proteção com senha (parametro `pass`)
$proxyPassword = getenv('PROXY_PASSWORD');
if (!isset($_GET['pass']) || $_GET['pass'] !== $proxyPassword) {
    echo json_encode(['error' => 'Acesso negado.']);
    exit;
}

// 🔗 URL a ser encurtada
$url = $_GET['url'] ?? '';
if (!$url) {
    echo json_encode(['error' => 'URL não fornecida.']);
    exit;
}

// 🔐 Dados sensíveis da Shopee (via Render env)
$credential = getenv('SHOPEE_API_ID');
$secretKey  = getenv('SHOPEE_API_SECRET');
$timestamp  = time();

// 🧾 Montar string base para assinatura (adaptado)
$stringToSign = "Credential=$credential&Url=$url&Timestamp=$timestamp";
$signature = hash_hmac('sha256', $stringToSign, $secretKey);

// 🧾 Cabeçalho de autenticação
$authorization = "SHA256 Credential=$credential, Signature=$signature, Timestamp=$timestamp";

// 🔄 Monta a query GraphQL
$query = [
    'query' => 'mutation {
        generateShortLink(input: {
            originUrl: "' . $url . '",
            subIds: ["auto"]
        }) {
            shortLink
        }
    }'
];

// 🌐 Envio da requisição
$options = [
    'http' => [
        'method'  => 'POST',
        'header'  => [
            'Content-Type: application/json',
            'Authorization: ' . $authorization,
        ],
        'content' => json_encode($query),
        'ignore_errors' => true
    ]
];

$context = stream_context_create($options);
$response = file_get_contents('https://open-api.affiliate.shopee.com.br/graphql', false, $context);

// 🧩 Processar resposta
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
