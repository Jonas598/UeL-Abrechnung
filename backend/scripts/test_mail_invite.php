<?php

/**
 * Testskript: Invite-Mail über Brevo API versenden (ohne SMTP).
 *
 * Voraussetzungen:
 * - backend/.env enthält:
 *   - BREVO_API_KEY
 *   - BREVO_FROM_EMAIL (muss bei Brevo verifiziert sein)
 *   - FRONTEND_URL
 * - DB ist erreichbar (weil Password::createToken in password_reset_tokens schreibt)
 *
 * Aufruf (PowerShell):
 *   cd C:\Github_Projekte\UeL-Abrechnung_Frontend\backend
 *   php scripts/test_mail_invite.php empfaenger@domain.de "Max" "Mustermann"
 */

use App\Mail\UserInviteMail;
use App\Models\User;
use App\Services\BrevoMailService;
use Illuminate\Support\Facades\Password;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$email = $argv[1] ?? null;
$vorname = $argv[2] ?? 'Test';
$name = $argv[3] ?? 'User';

// Optional: Absender überschreiben (nur für dieses Testskript)
$fromEmail = $argv[4] ?? null;
$fromName = $argv[5] ?? null;

if ($fromEmail) {
    config()->set('services.brevo.from.email', $fromEmail);
}
if ($fromName) {
    config()->set('services.brevo.from.name', $fromName);
}

if (!$email) {
    fwrite(STDERR, "Bitte Empfänger-Email angeben.\n");
    fwrite(STDERR, "Beispiel: php scripts/test_mail_invite.php empfaenger@domain.de \"Max\" \"Mustermann\"\n");
    exit(1);
}

// 1) User holen oder anlegen
$user = User::where('email', $email)->first();
if (!$user) {
    $user = User::create([
        'email' => $email,
        'vorname' => $vorname,
        'name' => $name,
        'password' => null,
        'isAdmin' => false,
        'isGeschaeftsstelle' => false,
    ]);
    echo "User angelegt: {$user->UserID} ({$user->email})\n";
} else {
    echo "User gefunden: {$user->UserID} ({$user->email})\n";
}

// 2) Token erstellen (landet in password_reset_tokens)
$token = Password::createToken($user);

// 3) Invite-Link ausgeben
$frontendBase = rtrim((string) config('app.frontend_url'), '/');
$inviteLink = $frontendBase . '/set-password?' . http_build_query([
    'token' => $token,
    'email' => $user->email,
]);

echo "\nInvite-Link (zum Kopieren):\n{$inviteLink}\n\n";

// 4) Mail rendern (Blade) und über Brevo senden
$mailable = new UserInviteMail($user, $token);
$html = $mailable->render();

$result = app(BrevoMailService::class)->sendTransactional(
    toEmail: $user->email,
    toName: trim(($user->vorname ?? '') . ' ' . ($user->name ?? '')),
    subject: $mailable->envelope()->subject ?? 'Willkommen! Bitte Passwort setzen',
    htmlContent: $html,
);

$messageId = $result['messageId'] ?? null;

echo "Invite-Mail wurde über Brevo gesendet an {$user->email}\n";
if ($messageId) {
    echo "Brevo messageId: {$messageId}\n";
}

echo "\nWenn keine Mail ankommt:\n";
echo "- In Brevo unter Transactional -> Logs nach der messageId bzw. Empfängeradresse suchen.\n";
echo "- Spam/Junk prüfen.\n";
echo "- Prüfen, ob die Empfänger-Domain evtl. blockiert ist (Brevo Suppressions/Blacklists).\n";
