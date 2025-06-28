<?php
// proxy.php

// Configurar domínio permitido para CORS - altere para seu domínio real
$allowedOrigin = '*';

header("Access-Control-Allow-Origin: $allowedOrigin");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Proteção simples com senha
if (!isset($_GET['pass']) || $_GET['pass'] !== getenv('PROXY_PASSWORD')) {
    http_response_code(403);
    echo json_encode(["error" => "Acesso negado."]);
    exit;
}

$appId = getenv('SHOPEE_API_ID');
$secret = getenv('SHOPEE_API_SECRET');

$keyword = $_GET['keyword'] ?? 'moto';
$sortType = $_GET['sortType'] ?? 2;
$limit = $_GET['limit'] ?? 5;
$page = $_GET['page'] ?? 1;  // NOVO: aceitar o parâmetro page
$timestamp = time();

$query = <<<GQL
{
  productOfferV2(keyword: "$keyword", sortType: $sortType, limit: $limit, page: $page) {
    nodes {
      itemId
      productName
      priceMin
      priceMax
      commissionRate
      offerLink
      imageUrl
      shopName
      ratingStar
    }
    pageInfo {
      page
      limit
      hasNextPage
    }
  }
}
GQL;

$payload = json_encode(["query" => $query]);

$signatureBase = $appId . $timestamp . $payload . $secret;
$signature = hash('sha256', $signatureBase);

$headers = [
    "Content-Type: application/json",
    "Authorization: SHA256 Credential=$appId, Timestamp=$timestamp, Signature=$signature"
];

$ch = curl_init('https://open-api.affiliate.shopee.com.br/graphql');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);

if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(["error" => "Erro cURL: " . curl_error($ch)]);
} else {
    echo $response;
}
curl_close($ch);
exit;
