<?php
use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');

// Récupère la variable depuis $_ENV ou getenv()
$secret = $_ENV['DEPLOY_SECRET'] ?? getenv('DEPLOY_SECRET');

if (!isset($_GET['token']) || $_GET['token'] !== $secret) {
    http_response_code(403);
    echo 'Accès refusé.';
    exit;
}

chdir(__DIR__ . '/..');
$output = shell_exec('git pull 2>&1');

header('Content-Type: text/plain');
echo "=== DEPLOY OUTPUT ===\n\n";
echo $output;
