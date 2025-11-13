<?php
// Iniciar sesión para recordar el idioma
session_start();

// Cargar archivos necesarios
require_once 'translations.php';
require_once 'calculation.php';
// require_once 'db.php'; // Eliminado

// --- LÓGICA DE IDIOMA ---
$lang = $_SESSION['lang'] ?? 'es';
if (isset($_GET['lang']) && in_array($_GET['lang'], ['es', 'en'])) {
    $lang = $_GET['lang'];
    $_SESSION['lang'] = $lang;
    // Redirigir para limpiar el parámetro GET de la URL
    $redirect_url = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: $redirect_url");
    exit();
}
$t = $translations[$lang] ?? $translations['es'];

// --- MANEJO DE LA SOLICITUD (REQUEST) ---

$action = $_GET['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    header('Content-Type: application/json');
    $json_data = file_get_contents('php://input');
    $inputs = json_decode($json_data, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON data received.']);
        exit();
    }

    $calculator = new PneumaticCalculator(); // Ya no se pasa la conexión PDO

    if ($action === 'calculate') {
        // --- Solicitud de Cálculo ---
        $result = $calculator->run($inputs);
        echo json_encode($result);

    } else if ($action === 'suggest_parameters') {
        // --- Solicitud de Sugerencia de Parámetros ---
        $result = $calculator->suggestParameters($inputs, $t); // Pasar $t para los mensajes de error
        echo json_encode($result);
    }
    
    exit();

} else {
    // --- Solicitud GET normal (Cargar la página) ---
    // El controlador incluye la vista, pasándole las variables necesarias ($t, $lang)
    require_once 'view.php';
    exit();
}



