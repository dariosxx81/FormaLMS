@extends('layouts.app')

@section('title', 'Imposta Nuova Password — ' . ($settings['platform_name'] ?? 'FormaFAD LMS'))

@section('content')
<div class="min-h-screen flex items-center justify-center p-4 sm:p-8 bg-slate-50/70">

    <div class="max-w-lg w-full bg-white rounded-3xl shadow-xl border border-slate-200/90 overflow-hidden">

        <div class="p-6 sm:p-8 bg-slate-900 text-white relative">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 border border-white/15 text-[11px] font-bold uppercase tracking-wider text-indigo-200 mb-3">
                <span>🔗 Link di Recupero Verificato</span>
            </span>
            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Imposta la tua Nuova Password</h1>
            <p class="text-xs text-slate-300 mt-1.5 leading-relaxed">
                Scegli una nuova password sicura per l'account <strong>{{ $email }}</strong>.
            </p>
        </div>

        <div class="p-6 sm:p-8">
            @if($errors->any())
                <div class="mb-5 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-xs font-semibold space-y-1">
                    @foreach($errors->all() as $err)
                        <div class="flex items-center gap-2">
                            <span class="text-red-500">•</span>
                            <span>{{ $err }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Email Registrata *</label>
                    <input type="email" name="email" value="{{ old('email', $email) }}" required readonly
                           class="w-full rounded-2xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm text-slate-700 font-semibold cursor-not-allowed">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                    <div>
                        <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Nuova Password *</label>
                        <input type="password" name="password" required minlength="6" autofocus
                               placeholder="Minimo 6 caratteri"
                               class="w-full rounded-2xl border border-slate-300 bg-slate-50/50 hover:bg-white focus:bg-white px-4 py-3 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Conferma Password *</label>
                        <input type="password" name="password_confirmation" required minlength="6"
                               placeholder="Ripeti nuova password"
                               class="w-full rounded-2xl border border-slate-300 bg-slate-50/50 hover:bg-white focus:bg-white px-4 py-3 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-600 transition">
                    </div>
                </div>

                <button type="submit"
                        class="w-full mt-2 py-3.5 px-6 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-black text-sm shadow-lg shadow-indigo-600/25 transition cursor-pointer flex items-center justify-center gap-2">
                    <span>Salva Nuova Password e Accedi</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </form>
        </div>

    </div>
</div>
@endsection
