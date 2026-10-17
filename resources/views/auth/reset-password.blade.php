@extends('layouts.app')

@section('title', 'Imposta Nuova Password — ' . ($settings['platform_name'] ?? 'FormaFAD LMS'))

@section('content')
<div class="min-h-[calc(100vh-4rem)] flex items-center justify-center p-4 sm:p-8"
     style="background: radial-gradient(circle at top right, var(--brand-primary-soft), transparent 45%), radial-gradient(circle at bottom left, var(--brand-accent-soft), transparent 45%);">

    <div class="max-w-lg w-full bg-white rounded-3xl shadow-xl border border-slate-200/90 overflow-hidden">

        <div class="p-6 sm:p-8 text-white"
             style="background: linear-gradient(135deg, var(--brand-secondary) 0%, var(--brand-secondary-dark) 100%);">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 border border-white/15 text-[11px] font-bold uppercase tracking-wider mb-3">
                <span>🔗 Link di Recupero Verificato</span>
            </span>
            <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight">Imposta la tua Nuova Password</h1>
            <p class="text-xs opacity-80 mt-1">
                Scegli una nuova password sicura per l'account <strong>{{ $email }}</strong>.
            </p>
        </div>

        <div class="p-6 sm:p-8">
            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Email Registrata *</label>
                    <input type="email" name="email" value="{{ old('email', $email) }}" required readonly
                           class="w-full rounded-xl border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-700 font-semibold">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nuova Password *</label>
                        <input type="password" name="password" required minlength="6" autofocus
                               placeholder="Minimo 6 caratteri"
                               class="w-full rounded-xl border border-slate-300 bg-slate-50/60 focus:bg-white px-4 py-3 text-sm text-slate-900 focus:outline-none focus:ring-2 transition"
                               style="--tw-ring-color: var(--brand-primary);">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Conferma Password *</label>
                        <input type="password" name="password_confirmation" required minlength="6"
                               placeholder="Ripeti nuova password"
                               class="w-full rounded-xl border border-slate-300 bg-slate-50/60 focus:bg-white px-4 py-3 text-sm text-slate-900 focus:outline-none focus:ring-2 transition"
                               style="--tw-ring-color: var(--brand-primary);">
                    </div>
                </div>

                <button type="submit"
                        class="w-full py-3.5 px-6 rounded-xl font-extrabold text-sm shadow-lg transition transform active:scale-[0.99] cursor-pointer mt-2"
                        style="background-color: var(--brand-primary); color: var(--brand-primary-contrast);">
                    Salva Nuova Password e Vai al Login &rarr;
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
