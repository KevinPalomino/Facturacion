<?php
// Prevenir cualquier salida de errores de PHP que rompa el JSON
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Función para obtener respuesta de la IA
function obtenerRespuestaIA($mensaje) {
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
        "max_tokens" => 200
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
    $result = @file_get_contents($url, false, $context);

    if ($result === FALSE) {
        // Si falla, usar respuesta de respaldo
        return obtenerRespuestaRespaldo($mensaje);
    }

    $response = json_decode($result, true);
    
    if (isset($response['choices'][0]['message']['content'])) {
        return $response['choices'][0]['message']['content'];
    } else {
        return obtenerRespuestaRespaldo($mensaje);
    }
}

// Función de respaldo por si falla la IA
function obtenerRespuestaRespaldo($mensaje) {
    $respuestas = [
    'factura' => [
        'pregunta' => ['crear', 'nueva', 'factura', 'generar'],
        'respuesta' => "Para crear una nueva factura:
1. Ve al menú 'Nueva Factura'
2. Selecciona o registra el cliente
3. Agrega los productos
4. Elige el método de pago (efectivo o crédito)
5. Guarda la factura"
    ],
    'cliente' => [
        'pregunta' => ['cliente', 'agregar', 'registrar', 'nuevo'],
        'respuesta' => "Para registrar un nuevo cliente:
1. Ve a 'Registrar Cliente'
2. Completa los datos básicos (nombre, documento, teléfono)
3. Agrega la dirección
4. Guarda el registro"
    ],
    'credito' => [
        'pregunta' => ['credito', 'cuota', 'pago', 'abonar'],
        'respuesta' => "Para gestionar créditos:
1. Ve a 'Gestión de Créditos'
2. Busca el cliente
3. Verifica el estado de cuenta
4. Registra los pagos de cuotas
5. Imprime el comprobante"
    ],
    'inventario' => [
        'pregunta' => ['inventario', 'stock', 'producto', 'productos'],
        'respuesta' => "Para gestionar el inventario:
1. Ve a 'Productos' en el menú
2. Puedes agregar nuevos productos
3. Actualizar existencias
4. Ver el informe de inventario
5. Configurar alertas de stock bajo"
    ],
    'reporte' => [
        'pregunta' => ['reporte', 'informe', 'ventas', 'reportes'],
        'respuesta' => "Para generar reportes:
1. Ve a 'Informe de Ventas'
2. Selecciona el rango de fechas
3. Elige el tipo de reporte
4. Genera el informe
5. Puedes exportar a PDF"
    ],
    'caja' => [
        'pregunta' => ['caja', 'cierre', 'cerrar'],
        'respuesta' => "Para hacer el cierre de caja:
1. Ve a 'Cierre Caja'
2. Verifica todas las ventas del día
3. Cuenta el dinero en efectivo
4. Registra el monto final
5. Genera el reporte de cierre"
    ],
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
    global $respuestas;
    $mensaje = mb_strtolower(trim($mensaje));
    $mejorCoincidencia = ['puntos' => 0, 'respuesta' => $respuestas['default']];

    foreach ($respuestas as $key => $value) {
        if ($key === 'default') continue;
        
        $puntos = 0;
        foreach ($value['pregunta'] as $palabra) {
            if (strpos($mensaje, $palabra) !== false) {
                $puntos++;
            }
        }
        
        if ($puntos > $mejorCoincidencia['puntos']) {
            $mejorCoincidencia = [
                'puntos' => $puntos,
                'respuesta' => $value['respuesta']
            ];
        }
    }

    return $mejorCoincidencia['respuesta'];
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

    $respuesta = encontrarRespuesta($input['message']);

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