<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetMail;
use App\Mail\WelcomeRegistrationMail;
use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return Auth::user()->isAdmin()
                ? redirect()->route('admin.dashboard')
                : redirect()->route('student.dashboard');
        }

        $settings = PlatformSetting::getAllSettings();
        return view('auth.login', compact('settings'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            /** @var User $user */
            $user = Auth::user();

            if ($user->status === 'suspended') {
                Auth::logout();
                return back()->withErrors(['email' => 'Il tuo account è stato sospeso o disattivato dalla segreteria didattica.']);
            }

            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);

            ActivityLog::record(
                'login',
                "Accesso eseguito da {$user->full_name} ({$user->email})",
                $user->id,
                null,
                null,
                ['role' => $user->role],
                $request
            );

            return $user->isAdmin()
                ? redirect()->route('admin.dashboard')
                : redirect()->route('student.dashboard');
        }

        return back()->withErrors([
            'email' => 'Credenziali non valide. Verifica email e password.',
        ])->onlyInput('email');
    }

    public function showRegister(Request $request)
    {
        $settings = PlatformSetting::getAllSettings();
        $prefilledCode = $request->query('code', $request->query('course', ''));

        return view('auth.register', compact('settings', 'prefilledCode'));
    }

    public function register(Request $request)
    {
        $settings = PlatformSetting::getAllSettings();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'fiscal_code' => ['required', 'string', 'min:11', 'max:20'],
            'birth_date' => ['required', 'date'],
            'birth_city' => ['required', 'string', 'max:120'],
            'birth_province' => ['required', 'string', 'max:10'],
            'birth_country' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30'],
            'national_list_number' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'invite_code' => ['nullable', 'string', 'max:50'],
        ], [
            'email.unique' => 'Questa email risulta già registrata in piattaforma. Accedi o usa il recupero password.',
            'phone.required' => 'Il numero di telefono cellulare è obbligatorio per le comunicazioni didattiche del corso.',
        ]);

        // Resolve course either from invite_code (secret code given by Admin) or course_id
        $targetCourse = null;
        $rawInviteCode = strtoupper(trim((string) ($validated['invite_code'] ?? '')));

        if ($rawInviteCode !== '') {
            $targetCourse = Course::where('is_active', true)
                ->where(function ($q) use ($rawInviteCode) {
                    $q->whereRaw('UPPER(invite_code) = ?', [$rawInviteCode])
                      ->orWhereRaw('UPPER(code) = ?', [$rawInviteCode]);
                })
                ->first();

            if (!$targetCourse) {
                return back()
                    ->withErrors(['invite_code' => 'Il Codice Corso inserito non è valido oppure il corso non è attivo.'])
                    ->withInput();
            }
        } elseif (!empty($validated['course_id'])) {
            $targetCourse = Course::find($validated['course_id']);
        }

        $autoApprove = ($settings['auto_approve_registration'] ?? '1') === '1';

        $user = User::create([
            'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'fiscal_code' => strtoupper(trim($validated['fiscal_code'])),
            'birth_date' => $validated['birth_date'],
            'birth_city' => $validated['birth_city'],
            'birth_province' => strtoupper(trim($validated['birth_province'])),
            'birth_country' => $validated['birth_country'],
            'phone' => $validated['phone'] ?? null,
            'national_list_number' => $validated['national_list_number'] ?? null,
            'email' => strtolower(trim($validated['email'])),
            'role' => 'student',
            'status' => $autoApprove ? 'active' : 'pending',
            'password' => Hash::make($validated['password']),
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        ActivityLog::record(
            'user_register',
            "Nuova auto-registrazione corsista: {$user->full_name} (CF: {$user->fiscal_code})",
            $user->id,
            $targetCourse?->id,
            null,
            ['status' => $user->status],
            $request
        );

        if ($targetCourse) {
            $enrollment = Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $targetCourse->id,
                'status' => $autoApprove ? 'active' : 'pending',
                'enrolled_at' => now(),
                'notes' => $rawInviteCode !== '' ? "Iscritto con Codice Corso ({$rawInviteCode})" : 'Auto-iscrizione in fase di registrazione',
            ]);
            $enrollment->recalculateProgress();

            ActivityLog::record(
                'course_enroll',
                "Iscrizione al corso \"{$targetCourse->title}\" ({$targetCourse->code}) contestuale alla registrazione",
                $user->id,
                $targetCourse->id,
                null,
                [],
                $request
            );
        }

        if (PlatformSetting::isMailActive('welcome')) {
            try {
                Mail::to($user->email)->send(new WelcomeRegistrationMail($user, $targetCourse, $settings));
                ActivityLog::record(
                    'welcome_mail_sent',
                    "Inviata email di benvenuto e conferma registrazione a {$user->email}",
                    $user->id,
                    $targetCourse?->id,
                    null,
                    [],
                    $request
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Impossibile inviare email di registrazione a {$user->email}: " . $e->getMessage());
            }
        } else {
            ActivityLog::record(
                'welcome_mail_skipped',
                "Invio email di benvenuto a {$user->email} saltato (notifica disattivata da pannello admin)",
                $user->id,
                $targetCourse?->id
            );
        }

        Auth::login($user);

        return redirect()->route('student.dashboard')
            ->with('success', $targetCourse
                ? "Registrazione completata e corso \"{$targetCourse->title}\" abilitato!"
                : 'Registrazione anagrafica completata! Puoi sbloccare un corso inserendo il Codice Corso fornito dalla Segreteria.');
    }

    public function showForgotPassword()
    {
        $settings = PlatformSetting::getAllSettings();
        return view('auth.forgot-password', compact('settings'));
    }

    public function sendPasswordResetLink(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower(trim($validated['email']));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (!$user) {
            return back()
                ->withErrors(['email' => 'Nessun account registrato con questo indirizzo email.'])
                ->onlyInput('email');
        }

        if ($user->status === 'suspended') {
            return back()
                ->withErrors(['email' => 'Questo account risulta sospeso dalla segreteria didattica.'])
                ->onlyInput('email');
        }

        $plainToken = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($plainToken),
                'created_at' => now(),
            ]
        );

        $settings = PlatformSetting::getAllSettings();
        $resetUrl = route('password.reset.token', [
            'token' => $plainToken,
            'email' => $user->email,
        ]);

        Mail::to($user->email)->send(new PasswordResetMail($user, $resetUrl, $settings, 'link'));

        ActivityLog::record(
            'password_reset_request',
            "Inviata email con link di recupero password a {$user->email}",
            $user->id,
            null,
            null,
            [],
            $request
        );

        return back()
            ->with('success', "Abbiamo inviato un'email con il link di recupero password all'indirizzo {$user->email}. Controlla la tua casella di posta!");
    }

    public function showResetPasswordForm(Request $request, string $token)
    {
        $settings = PlatformSetting::getAllSettings();
        $email = (string) $request->query('email', '');

        return view('auth.reset-password', compact('settings', 'token', 'email'));
    }

    public function resetPasswordWithToken(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $email = strtolower(trim($validated['email']));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Account non trovato per questo indirizzo email.']);
        }

        if ($user->status === 'suspended') {
            return back()->withErrors(['email' => 'Questo account risulta sospeso dalla segreteria didattica.']);
        }

        $record = DB::table('password_reset_tokens')->where('email', $user->email)->first();

        if (!$record || !Hash::check($validated['token'], $record->token)) {
            return back()->withErrors(['email' => 'Il link di recupero password non è valido o è scaduto. Richiedine uno nuovo.']);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        $settings = PlatformSetting::getAllSettings();
        Mail::to($user->email)->send(new PasswordResetMail($user, null, $settings, 'changed'));

        ActivityLog::record(
            'password_reset',
            "Password reimpostata tramite link email per {$user->full_name} ({$user->email})",
            $user->id,
            null,
            null,
            [],
            $request
        );

        return redirect()->route('login')
            ->with('success', 'Password reimpostata con successo tramite link email! Ora puoi accedere con la tua nuova password.');
    }

    public function resetPasswordWithIdentity(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'fiscal_code' => ['required', 'string', 'min:11', 'max:20'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $email = strtolower(trim($validated['email']));
        $fiscalCode = strtoupper(trim($validated['fiscal_code']));

        $user = User::whereRaw('LOWER(email) = ?', [$email])
            ->whereRaw('UPPER(fiscal_code) = ?', [$fiscalCode])
            ->first();

        if (!$user) {
            return back()
                ->withErrors(['email' => 'Nessun account trovato con questa combinazione di Email e Codice Fiscale. Verifica i dati o contatta la segreteria.'])
                ->onlyInput('email', 'fiscal_code');
        }

        if ($user->status === 'suspended') {
            return back()
                ->withErrors(['email' => 'Questo account risulta sospeso dalla segreteria didattica.'])
                ->onlyInput('email', 'fiscal_code');
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        $settings = PlatformSetting::getAllSettings();
        Mail::to($user->email)->send(new PasswordResetMail($user, null, $settings, 'changed'));

        ActivityLog::record(
            'password_reset',
            "Reimpostazione password eseguita tramite verifica Email + Codice Fiscale per {$user->full_name}",
            $user->id,
            null,
            null,
            [],
            $request
        );

        return redirect()->route('login')
            ->with('success', 'Password reimpostata con successo! Ora puoi accedere con la tua nuova password.');
    }

    public function demoSwitch(Request $request, User $user)
    {
        Auth::login($user);
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        ActivityLog::record(
            'demo_switch',
            "Cambio profilo rapido Demo verso {$user->full_name} ({$user->role})",
            $user->id,
            null,
            null,
            ['role' => $user->role],
            $request
        );

        return $user->isAdmin()
            ? redirect()->route('admin.dashboard')->with('success', "Accesso rapido eseguito come Admin: {$user->full_name}")
            : redirect()->route('student.dashboard')->with('success', "Accesso rapido eseguito come Corsista: {$user->full_name}");
    }

    public function showProfile()
    {
        $user = Auth::user();
        $settings = PlatformSetting::getAllSettings();
        return view('auth.profile', compact('user', 'settings'));
    }

    public function updateProfile(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'fiscal_code' => ['required', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date'],
            'birth_city' => ['nullable', 'string', 'max:120'],
            'birth_province' => ['nullable', 'string', 'max:10'],
            'birth_country' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'national_list_number' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:190', \Illuminate\Validation\Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update([
            'name' => trim($data['first_name'] . ' ' . $data['last_name']),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'fiscal_code' => strtoupper(trim($data['fiscal_code'])),
            'birth_date' => $data['birth_date'] ?: $user->birth_date,
            'birth_city' => $data['birth_city'] ?? $user->birth_city,
            'birth_province' => strtoupper($data['birth_province'] ?? ($user->birth_province ?: 'RM')),
            'birth_country' => $data['birth_country'] ?? ($user->birth_country ?: 'Italia'),
            'phone' => $data['phone'] ?? null,
            'national_list_number' => $data['national_list_number'] ?? null,
            'email' => strtolower(trim($data['email'])),
        ]);

        ActivityLog::record(
            'profile_update',
            "Dati anagrafici e email aggiornati da {$user->full_name} ({$user->email})",
            $user->id,
            null,
            null,
            [],
            $request
        );

        return back()->with('success', 'Dati del profilo aggiornati con successo.');
    }

    public function updatePassword(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'current_password.current_password' => 'La password attuale inserita non è corretta.',
            'password.confirmed' => 'La conferma della nuova password non coincide.',
            'password.min' => 'La nuova password deve contenere almeno :min caratteri.',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        ActivityLog::record(
            'password_update',
            "Password account aggiornata con successo per {$user->full_name}",
            $user->id,
            null,
            null,
            [],
            $request
        );

        return back()->with('success', 'Password modificata con successo.');
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            ActivityLog::record(
                'logout',
                'Disconnessione utente eseguita correttamente',
                Auth::id(),
                null,
                null,
                [],
                $request
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
