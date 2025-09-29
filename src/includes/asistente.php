<?php
// Prevenir cualquier salida de errores de PHP que rompa el JSON
error_reporting(0);
ini_set('display_errors', 0);

// Asegurarse de que la sesión esté iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'auth.php';
require_once 'db.php';

// Configuración de la API de Gemini
define('GEMINI_API_KEY', 'AIzaSyBfxguuX1JmpmVRE4uaM6GRFfTjqnI9VZc');
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateContent');

// Configurar headers para JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

function getSystemPrompt() {
    return "Eres el asistente virtual del Sistema de Facturación. Tu nombre es 'FactuAssist'.

CONTEXTO DEL SISTEMA:
Este es un sistema completo de facturación que incluye:
- Gestión de productos e inventario
- Facturación con opciones de crédito
- Sistema de cuotas mensuales y bimensuales
- Control de pagos y estados de crédito
- Reportes de ventas e inventario
- Gestión de usuarios y roles

MÓDULOS PRINCIPALES:
1. Cajero:
   - Registrar clientes
   - Crear facturas
   - Gestionar ventas a crédito
   - Realizar cierre de caja

2. Administrador:
   - Gestión de créditos
   - Informe de inventario
   - Informe de ventas
   - Gestión de productos
   - Control de pagos

3. Global:
   - Gestión de usuarios
   - Configuración del sistema

INSTRUCCIONES:
- Responde de manera clara y precisa
- Proporciona instrucciones paso a paso
- Usa un tono profesional y servicial
- Si no conoces algo específico, indícalo
- Enfócate en las funcionalidades implementadas

EJEMPLOS DE USO COMÚN:
- '¿Cómo registro una nueva factura?'
- '¿Cómo agrego un nuevo cliente?'
- '¿Cómo gestiono los créditos?'
- '¿Cómo reviso el inventario?'
- '¿Cómo proceso pagos de cuotas?'
- '¿Cómo genero informes de ventas?'
- '¿Cómo administro los usuarios?'

CARACTERÍSTICAS ESPECIALES:
- Sistema de créditos con cuotas mensuales (2% interés) o bimensuales (1% interés)
- Plazos de crédito de 3 a 10 meses
- Control de stock con alertas de bajo inventario
- Seguimiento de pagos con estados y fechas de vencimiento
- Generación de PDFs para facturas e informes

Responde siempre basándote en las funcionalidades reales implementadas en el sistema.";
}

// Función para sanitizar entrada del usuario
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Función para validar rate limiting
function checkRateLimit($user_id) {
    global $conn;
    
    try {
        // Limpiar solicitudes antiguas
        $stmt = $conn->prepare("DELETE FROM asistente_logs WHERE user_id = ? AND timestamp < DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Contar solicitudes recientes
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM asistente_logs WHERE user_id = ? AND timestamp >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = $row['count'];
        
        if ($count >= 10) {
            return false;
        }
        
        // Registrar nueva solicitud
        $stmt = $conn->prepare("INSERT INTO asistente_logs (user_id, timestamp) VALUES (?, NOW())");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        return true;
    } catch (Exception $e) {
        error_log("Error en rate limiting: " . $e->getMessage());
        return true;
    }
}

// Función para generar respuesta con Gemini
function generateGeminiResponse($userMessage) {
    $prompt = getSystemPrompt() . "\n\nPregunta del usuario: " . $userMessage;
    
    $data = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => $prompt
                    ]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 1024
        ]
    ];
    
    $options = [
        'http' => [
            'header' => implode("\r\n", [
                "Content-Type: application/json",
                "x-goog-api-key: " . GEMINI_API_KEY
            ]),
            'method' => 'POST',
            'content' => json_encode($data),
            'timeout' => 30,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ];
    
    $context = stream_context_create($options);
    $url = GEMINI_API_URL . '?key=' . GEMINI_API_KEY;
    
    $result = file_get_contents($url, false, $context);
    
    if ($result === FALSE) {
        throw new Exception('Error al conectar con el servicio de IA: ' . error_get_last()['message']);
    }
    
    $response = json_decode($result, true);
    
    // Log de depuración
    error_log("Respuesta de Gemini: " . $result);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al decodificar respuesta: ' . json_last_error_msg());
    }
    
    // Verificar si hay errores en la respuesta
    if (isset($response['error'])) {
        throw new Exception('Error del servicio de IA: ' . $response['error']['message']);
    }
    
    // Verificar la estructura de la respuesta
    if (!isset($response['candidates']) || 
        !isset($response['candidates'][0]['content']) || 
        !isset($response['candidates'][0]['content']['parts']) || 
        !isset($response['candidates'][0]['content']['parts'][0]['text'])) {
        
        error_log("Respuesta inesperada: " . json_encode($response));
        throw new Exception('Estructura de respuesta inválida del servicio de IA');
    }
    
    return $response['candidates'][0]['content']['parts'][0]['text'];
}

// Función para registrar conversación
function logConversation($user_id, $message, $response) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("INSERT INTO asistente_conversaciones (user_id, mensaje_usuario, respuesta_ia, timestamp) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iss", $user_id, $message, $response);
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Error logging conversation: " . $e->getMessage());
    }
}

// Procesar solicitud
try {
    if (ob_get_length()) ob_clean();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    if (!isset($_SESSION['correo'])) {
        throw new Exception('Usuario no autenticado');
    }
    
    // Obtenemos el ID del usuario desde la base de datos usando el correo
    $stmt = $conn->prepare("SELECT id FROM usuario WHERE correo = ?");
    $stmt->bind_param("s", $_SESSION['correo']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Usuario no encontrado');
    }
    
    $row = $result->fetch_assoc();
    $user_id = $row['id'];
    
    if (!checkRateLimit($user_id)) {
        throw new Exception('Límite de solicitudes alcanzado. Espera un minuto.');
    }
    
    $rawInput = file_get_contents('php://input');
    if (!$rawInput) {
        throw new Exception('No se recibieron datos');
    }
    
    $input = json_decode($rawInput, true);
    if (!isset($input['message']) || empty(trim($input['message']))) {
        throw new Exception('Mensaje requerido');
    }
    
    $userMessage = sanitizeInput($input['message']);
    
    if (strlen($userMessage) > 500) {
        throw new Exception('Mensaje demasiado largo (máximo 500 caracteres)');
    }
    
    $aiResponse = generateGeminiResponse($userMessage);
    logConversation($user_id, $userMessage, $aiResponse);
    
    echo json_encode([
        'success' => true,
        'response' => $aiResponse
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}