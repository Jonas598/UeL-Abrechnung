<?php

declare(strict_types=1);

use App\Mail\UserInviteMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Empfänger: per INVITE_TEST_TO überschreibbar, sonst MAIL_FROM_ADDRESS
$to = env('INVITE_TEST_TO') ?: (env('MAIL_FROM_ADDRESS') ?: 'test@example.com');

// --- Debug: welche Mail-Config wird wirklich verwendet? ---
$cfg = [
    'default' => config('mail.default'),
    'smtp.host' => config('mail.mailers.smtp.host'),
    'smtp.port' => config('mail.mailers.smtp.port'),
    'smtp.scheme' => config('mail.mailers.smtp.scheme'),
    'smtp.username' => config('mail.mailers.smtp.username'),
    'from.address' => config('mail.from.address'),
    'to' => $to,
];

fwrite(STDERR, "Mail config (effective):\n" . json_encode($cfg, JSON_PRETTY_PRINT) . "\n\n");

$user = User::first() ?: new User([
    'email' => env('MAIL_FROM_ADDRESS', 'test@example.com'),
    'vorname' => 'Test',
    'name' => 'User',
]);

$token = Password::createToken($user);

Mail::to($to)->send(new UserInviteMail($user, $token));

echo "Invite mail sent to {$to}\n";

