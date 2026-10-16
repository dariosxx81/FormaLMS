<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
        $demoAdmins = User::where('role', 'admin')->limit(2)->get();
        $demoStudents = User::where('role', 'student')->limit(4)->get();

        return view('auth.login', compact('settings', 'demoAdmins', 'demoStudents'));
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
                return back()->withErrors(['email' => 'Il tuo account è stato sospeso dalla segreteria didattica.']);
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
        $courses = Course::where('is_active', true)
            ->where('allow_self_enrollment', true)
            ->orderBy('title')
            ->get();
        $selectedCourseCode = $request->query('course');

        return view('auth.register', compact('settings', 'courses', 'selectedCourseCode'));
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
            'phone' => ['nullable', 'string', 'max:30'],
            'national_list_number' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'course_id' => ['nullable', 'exists:courses,id'],
        ]);

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
            $validated['course_id'] ?? null,
            null,
            ['status' => $user->status],
            $request
        );

        if (!empty($validated['course_id'])) {
            $enrollment = Enrollment::create([
                'user_id' => $user->id,
                'course_id' => (int) $validated['course_id'],
                'status' => $autoApprove ? 'active' : 'pending',
                'enrolled_at' => now(),
                'notes' => 'Auto-iscrizione in fase di registrazione',
            ]);
            $enrollment->recalculateProgress();

            ActivityLog::record(
                'course_enroll',
                "Iscrizione al corso ID #{$validated['course_id']} contestuale alla registrazione",
                $user->id,
                (int) $validated['course_id'],
                null,
                [],
                $request
            );
        }

        Auth::login($user);

        return redirect()->route('student.dashboard')
            ->with('success', 'Registrazione completata con successo! Benvenuto nella piattaforma FAD.');
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
