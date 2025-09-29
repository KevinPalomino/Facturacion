<?php
// Prevenir cualquier salida de errores de PHP que rompa el JSON
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Función para obtener respuesta de la IA
function obtenerRespuestaIA($mensaje) {
    error_log("Iniciando consulta a la IA...");
    $url = "https://free.churchless.tech/v1/chat/completions";
    
    $data = [
        "model" => "gpt-3.5-turbo",
        "messages" => [
            [
                "role" => "system",
                "content" => "Eres un asistente virtual del sistema de facturación ContaFlow. Ayudas a los usuarios con preguntas sobre:
                - Creación y gestión de facturas
                - Registro de clientes
                - Gestión de inventario
                - Manejo de créditos y pagos
                - Generación de reportes
                - Cierre de caja
                Da respuestas cortas y precisas, enfocadas en los pasos prácticos."
            ],
            [
                "role" => "user",
                "content" => $mensaje
            ]
        ],
        "temperature" => 0.7,
        "max_tokens" => 150
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

    $context = stream_context_create($options);
    error_log("Enviando solicitud a la IA...");
    $result = @file_get_contents($url, false, $context);

    if ($result === FALSE) {
        error_log("Error al conectar con la IA: " . error_get_last()['message']);
        return obtenerRespuestaRespaldo($mensaje);
    }

    $response = json_decode($result, true);
    
    if (isset($response['choices'][0]['message']['content'])) {
        return $response['choices'][0]['message']['content'];
    }
    
    return obtenerRespuestaRespaldo($mensaje);
}

// Función de respaldo por si falla la IA
function obtenerRespuestaRespaldo($mensaje) {
    $respuestas = [
        'factura' => "Para crear una nueva factura:\n1. Ve al menú 'Nueva Factura'\n2. Selecciona el cliente\n3. Agrega los productos\n4. Elige el método de pago\n5. Guarda la factura",
        'cliente' => "Para registrar un cliente:\n1. Ve a 'Registrar Cliente'\n2. Completa los datos del cliente\n3. Guarda el registro",
        'credito' => "Para gestionar créditos:\n1. Ve a 'Gestión de Créditos'\n2. Busca el cliente\n3. Registra los pagos",
        'inventario' => "Para el inventario:\n1. Ve a 'Productos'\n2. Agrega o actualiza productos\n3. Verifica el stock",
        'reporte' => "Para reportes:\n1. Ve a 'Informes'\n2. Selecciona el tipo de reporte\n3. Genera el informe",
        'default' => "¿En qué puedo ayudarte? Puedes preguntarme sobre:\n- Facturas\n- Clientes\n- Créditos\n- Inventario\n- Reportes"
    ];

    $mensaje = strtolower($mensaje);
    foreach ($respuestas as $key => $respuesta) {
        if (strpos($mensaje, $key) !== false) {
            return $respuesta;
        }
    }
    return $respuestas['default'];
}

// Procesar la solicitud
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['message']) || empty(trim($input['message']))) {
        throw new Exception('Mensaje requerido');
    }

    $respuesta = obtenerRespuestaIA($input['message']);

    echo json_encode([
        'success' => true,
        'response' => $respuesta
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}