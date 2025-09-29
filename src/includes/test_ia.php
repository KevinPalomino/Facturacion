<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Probando conexión con la IA</h1>";

$url = "https://free.churchless.tech/v1/chat/completions";
    
$data = [
    "model" => "gpt-3.5-turbo",
    "messages" => [
        [
            "role" => "system",
            "content" => "Eres un asistente de prueba."
        ],
        [
            "role" => "user",
            "content" => "Di hola"
        ]
    ],
    "temperature" => 0.7,
    "max_tokens" => 50
];

$options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'POST',
        'content' => json_encode($data),
        'timeout' => 15
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
];

echo "<h2>Enviando solicitud...</h2>";
echo "<pre>";
print_r($data);
echo "</pre>";

$context = stream_context_create($options);
$result = @file_get_contents($url, false, $context);

if ($result === FALSE) {
    echo "<h2>Error en la conexión:</h2>";
    echo "<pre>";
    print_r(error_get_last());
    echo "</pre>";
} else {
    echo "<h2>Respuesta recibida:</h2>";
    echo "<pre>";
    print_r(json_decode($result, true));
    echo "</pre>";
}