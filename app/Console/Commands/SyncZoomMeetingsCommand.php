<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Services\ZoomService;
use Illuminate\Console\Command;

class SyncZoomMeetingsCommand extends Command
{
    protected $signature = 'zoom:sync {--course= : ID o codice del corso da sincronizzare} {--meeting= : ID Meeting Zoom da sincronizzare}';
    protected $description = 'Sincronizza automaticamente eventi, date e presenze da Zoom Cloud API per i corsi';

    public function handle(): int
    {
        $this->info("=== Sincronizzazione Automatica Zoom Cloud API ===");

        if (!ZoomService::hasApiCredentials()) {
            $this->warn("Credenziali Zoom API (Server-to-Server OAuth) non configurate nelle impostazioni piattaforma.");
            return self::FAILURE;
        }

        $courseIdOrCode = $this->option('course');
        $meetingId = $this->option('meeting');

        $coursesQuery = Course::query();
        if ($courseIdOrCode) {
            $coursesQuery->where(function ($q) use ($courseIdOrCode) {
                $q->where('id', $courseIdOrCode)->orWhere('code', $courseIdOrCode);
            });
        }

        $courses = $coursesQuery->get();

        if ($courses->isEmpty()) {
            $this->error("Nessun corso trovato.");
            return self::FAILURE;
        }

        $this->info("Trovati {$courses->count()} corsi da verificare.");

        foreach ($courses as $course) {
            $this->line("-> Elaborazione Corso [{$course->code}] {$course->title}...");

            $zoomModules = $course->modules()->where('video_source_type', 'zoom')->get();

            // If specific meetingId passed or modules have zoom_meeting_id
            $meetingIds = [];
            if ($meetingId) {
                $meetingIds[] = $meetingId;
            } else {
                foreach ($zoomModules as $zm) {
                    if (!empty($zm->zoom_meeting_id)) {
                        $clean = preg_replace('/[^0-9]/', '', $zm->zoom_meeting_id);
                        if (!empty($clean) && !in_array($clean, $meetingIds)) {
                            $meetingIds[] = $clean;
                        }
                    }
                }
            }

            if (empty($meetingIds)) {
                $this->comment("   Nessun Meeting ID associato a questo corso. Salto.");
                continue;
            }

            foreach ($meetingIds as $mid) {
                $this->info("   Sincronizzazione date e occorrenze per Meeting ID {$mid}...");
                $res = ZoomService::syncCourseFromZoomMeeting($course, $mid);
                if ($res['success']) {
                    $this->info("   ✓ " . $res['message']);
                } else {
                    $this->warn("   ! " . $res['message']);
                }

                // Also sync participants report for completed sessions
                foreach ($course->modules()->where('video_source_type', 'zoom')->where('zoom_meeting_id', $mid)->get() as $zmMod) {
                    $partRes = ZoomService::syncMeetingParticipantsFromApi($zmMod);
                    if ($partRes['success'] && ($partRes['synced_count'] ?? 0) > 0) {
                        $this->info("     ✓ Presenze Modulo #{$zmMod->sort_order}: " . $partRes['message']);
                    }
                }
            }
        }

        $this->info("Sincronizzazione completata con successo!");
        return self::SUCCESS;
    }
}
