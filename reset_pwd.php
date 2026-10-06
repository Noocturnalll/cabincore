<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;

$user = User::where('nik', '000000')->first();
if ($user) {
    $user->password = Hash::make('password123');
    $user->save();
    echo "Password for 000000 reset to password123 successfully.\n";
} else {
    echo "User 000000 not found.\n";
}
