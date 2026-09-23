<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Env;
use App\Repositories\UserRepository;

$envFile = $argv[1] ?? '.env';
Env::load(dirname(__DIR__, 2), $envFile);

$phone = '+998900000000';
$password = bin2hex(random_bytes(6));

$existing = (new UserRepository())->findByPhone($phone);

if ($existing !== null) {
    echo "Admin already exists (phone {$phone}).\n";
    exit(0);
}

(new UserRepository())->create('Bosh administrator', $phone, Auth::hashPassword($password), 'admin');

echo "Admin created. Login: {$phone}  Password: {$password}\n";
