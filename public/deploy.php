<?php

use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

// Charger la clé secrète de GitHub depuis .env
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');

$secret = $_ENV['DEPLOY_SECRET'] ?? getenv('DEPLOY_SECRET');

// Lire le contenu brut de la requête POST
$payload = file_get_contents('php://input');

// Vérifier que la signature est présente
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '';
$signature256 = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

// Calculer la signature
$hash = 'sha1=' . hash_hmac('sha1', $payload, $secret);
$hash256 = 'sha256=' . hash_hmac('sha256', $payload, $secret);

// Comparer les signatures (sécurisé)
$isValid = hash_equals($hash, $signature) || hash_equals($hash256, $signature256);

if (!$isValid) {
    http_response_code(403);
    echo "Signature invalide.";
    exit;
}

// Exécuter le déploiement
chdir(__DIR__ . '/..');
$output = shell_exec('git pull 2>&1');

header('Content-Type: text/plain');
echo "=== DEPLOY OK ===\n\n";
echo $output;
