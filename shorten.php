<?php
// shorten.php

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Autenticação (use a mesma do proxy.php)
if (!isset($_GET['pass']) || $_GET['pass'] !== getenv('PROXY_PASSWORD')) {
    http_response_code(403);
    echo json_encode(["error" => "Acesso negado."]);
    exit;
}

// Configurações da API Shopee
$appId = getenv('SHOPEE_API_ID');
$secret = getenv('SHOPEE_API_SECRET');
$timestamp = time();

// Recebe os parâmetros
$originUrl = $_GET['originUrl'] ?? '';
$subIds = isset($_GET['subIds']) ? json_decode($_GET['subIds']) : [];

// Validação
if (empty($originUrl)) {
    http_response_code(400);
    echo json_encode(["error" => "originUrl é obrigatório"]);
    exit;
}

// Mutation GraphQL
$mutation = <<<GQL
mutation {
    generateShortLink(input: {
        originUrl: "$originUrl",
        subIds: ["s1", "s2", "s3", "s4", "s5"]  // Pode ser dinâmico com implode(', ', $subIds)
    }) {
        shortLink
    }
}
GQL;

// Assinatura da requisição
$payload = json_encode(["query" => $mutation]);
$signatureBase = $appId . $timestamp . $payload . $secret;
$signature = hash('sha256', $signatureBase);

// Headers
$headers = [
    "Content-Type: application/json",
    "Authorization: SHA256 Credential=$appId, Timestamp=$timestamp, Signature=$signature"
];

// Request para a API Shopee
$ch = curl_init('https://open-api.affiliate.shopee.com.br/graphql');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => $headers
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(["error" => "Erro cURL: " . curl_error($ch)]);
} else {
    echo $response;
}

curl_close($ch);
