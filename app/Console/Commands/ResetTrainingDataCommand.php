<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\ModuleAttachment;
use App\Models\ModuleProgress;
use App\Models\User;
use App\Models\WatchLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetTrainingDataCommand extends Command
{
    protected $signature = 'fad:reset-data {--keep-courses : Elimina solo i corsisti e le relative iscrizioni, mantenendo i corsi}';

    protected $description = 'Azzera e ripulisce tutti i corsi, moduli, corsisti, iscrizioni, presenze e log di audit mantenendo intatti gli amministratori e le impostazioni';

    public function handle(): int
    {
        $keepCourses = $this->option('keep-courses');

        $this->warn('Avvio pulizia dati corsisti, frequenze e log di test...');

        DB::beginTransaction();

        try {
            // 1. Pulizia telemetria, log di visione e progressi
            WatchLog::query()->delete();
            ModuleProgress::query()->delete();
            Enrollment::query()->delete();

            // 2. Pulizia di tutti gli utenti corsisti (mantiene intatti gli admin)
            $deletedStudents = User::where('role', 'student')->delete();
            $this->info("Eliminati {$deletedStudents} account corsisti di test.");

            // 3. Se non richiesto di mantenere i corsi, elimina corsi, moduli e dispense
            if (!$keepCourses) {
                ModuleAttachment::query()->delete();
                CourseModule::query()->delete();
                $deletedCourses = Course::query()->delete();
                $this->info("Eliminati {$deletedCourses} corsi (e tutti i relativi moduli e dispense).");
            } else {
                $this->line('I corsi e i relativi moduli didattici sono stati preservati.');
            }

            // 4. Pulizia log di attività generali
            ActivityLog::query()->delete();

            DB::commit();

            $this->newLine();
            $this->info('Piattaforma azzerata con successo!');
            $this->line('- Il tuo account Amministratore (admin@formalms.it) e le impostazioni dell\'ente sono rimasti invariati.');
            $this->line('- Puoi ora creare i tuoi corsi da zero o invitare/registrare i nuovi corsisti per la tua prova.');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Errore durante la pulizia dei dati: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
