<?php
// Iniciar sesión para recordar el idioma
session_start();

// Cargar el archivo de traducciones
require_once 'translations.php';
require_once 'calculation.php';

// --- LÓGICA DE IDIOMA ---
// Si se pasa un idioma por GET, guardarlo en la sesión
if (isset($_GET['lang']) && in_array($_GET['lang'], ['es', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
    // Redirigir para limpiar el parámetro GET de la URL
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}
// Determinar el idioma a usar (de la sesión o por defecto 'es')
$lang = $_SESSION['lang'] ?? 'es';
$t = $translations[$lang] ?? $translations['es'];

// --- MANEJO DE LA SOLICITUD (REQUEST) ---

// Si la solicitud es POST, es para un cálculo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Obtener los datos JSON del cuerpo de la solicitud
    $json_data = file_get_contents('php://input');
    $inputs = json_decode($json_data, true);

    // Validar que los datos se decodificaron correctamente
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'error' => 'Invalid JSON data received.']);
        exit();
    }
    
    // Instanciar y ejecutar la calculadora
    $calculator = new PneumaticCalculator();
    $result = $calculator->run($inputs);

    // Devolver el resultado como JSON
    echo json_encode($result);
    exit();
}

// Si la solicitud es GET (o cualquier otra), mostrar la página principal
// El controlador incluye la vista, pasándole las variables necesarias ($t, $lang)
require_once 'index.php';








