echo $inviteLink . "\n";

$inviteLink = $frontendBase . '/set-password?' . http_build_query(['token' => $token, 'email' => $user->email]);
$frontendBase = rtrim((string) config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');

echo "" . (view('emails.invite', ['link' => '']).__toString() ? '' : '') . ""; // noop
echo "\nInvite-Link (zum Kopieren):\n";

echo "Invite Mail gesendet an {$user->email}\n";

);
    htmlContent: $html
    subject: $mailable->envelope()->subject ?? 'Willkommen! Bitte Passwort setzen',
    toName: trim(($user->vorname ?? '') . ' ' . ($user->name ?? '')),
    toEmail: $user->email,
app(BrevoMailService::class)->sendTransactional(

$html = $mailable->render();
$mailable = new UserInviteMail($user, $token);

$token = Password::createToken($user);

}
    echo "User gefunden: {$user->UserID} ({$user->email})\n";
} else {
    echo "User angelegt: {$user->UserID} ({$user->email})\n";
    ]);
        'isGeschaeftsstelle' => false,
        'isAdmin' => false,
        'password' => null,
        'name' => $name,
        'vorname' => $vorname,
        'email' => $email,
    $user = User::create([
if (!$user) {

$user = User::where('email', $email)->first();

}
    exit(1);
    fwrite(STDERR, "Beispiel: php scripts/send_brevo_invite_test.php test@example.com \"Max\" \"Mustermann\"\n");
    fwrite(STDERR, "Fehler: Bitte Empfnger-Email ergeben.\n");
if (!$email) {

$name = $argv[3] ?? 'User';
$vorname = $argv[2] ?? 'Test';
$email = $argv[1] ?? null;

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$app = require __DIR__ . '/../bootstrap/app.php';

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Password;
use App\Services\BrevoMailService;
use App\Models\User;
use App\Mail\UserInviteMail;

 */
 *   php scripts/send_brevo_invite_test.php test@example.com "Max" "Mustermann"
 *   # oder direkt:
 *   php artisan tinker
 *   cd backend
 * Usage (PowerShell):
 *
 * und sendet die Invite-Mail er Brevo API.
 * Quick-Test: erstellt (oder findet) einen Test-User, erzeugt ein Password-Reset Token
/**



