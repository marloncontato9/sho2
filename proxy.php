<?php
// proxy.php

// Proteção com senha opcional
if (!isset($_GET['pass']) || $_GET['pass'] !== getenv('PROXY_PASSWORD')) {
    http_response_code(403);
    echo json_encode(["error" => "Acesso negado."]);
    exit;
}

// Variáveis de ambiente do Render
$appId = getenv('SHOPEE_API_ID');
$secret = getenv('SHOPEE_API_SECRET');

$keyword = $_GET['keyword'] ?? 'moto';
$sortType = $_GET['sortType'] ?? 2; // 2 = ITEM_SOLD_DESC
$limit = $_GET['limit'] ?? 5;
$timestamp = time();

// Payload (GraphQL)
$query = <<<GQL
{
  productOfferV2(keyword: "$keyword", sortType: $sortType, limit: $limit) {
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

// Assinatura
$signatureBase = $appId . $timestamp . $payload . $secret;
$signature = hash('sha256', $signatureBase);

// Cabeçalhos
$headers = [
    "Content-Type: application/json",
    "Authorization: SHA256 Credential=$appId, Timestamp=$timestamp, Signature=$signature"
];

// Requisição para API da Shopee
$ch = curl_init('https://open-api.affiliate.shopee.com.br/graphql');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);

// Resultado
if (curl_errno($ch)) {
    echo json_encode(["error" => "Erro cURL: " . curl_error($ch)]);
} else {
    echo $response;
}
curl_close($ch);
