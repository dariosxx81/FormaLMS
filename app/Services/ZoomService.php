<?php

namespace App\Services;

use App\Mail\ModuleCompletedMail;
use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\ModuleProgress;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\WatchLog;
use App\Models\ZoomRegistrant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ZoomService
{
    public static function isModuleEnabled(): bool
    {
        $settings = PlatformSetting::getAllSettings();
        return ($settings['zoom_module_enabled'] ?? '0') === '1';
    }

    public static function hasApiCredentials(): bool
    {
        $settings = PlatformSetting::getAllSettings();
        return !empty($settings['zoom_account_id'])
            && !empty($settings['zoom_client_id'])
            && !empty($settings['zoom_client_secret']);
    }

    public static function getAccessToken(): ?string
    {
        if (!self::hasApiCredentials()) {
            return null;
        }

        return Cache::remember('zoom_s2s_access_token', 3300, function () {
            $settings = PlatformSetting::getAllSettings();
            $accountId = $settings['zoom_account_id'];
            $clientId = $settings['zoom_client_id'];
            $clientSecret = $settings['zoom_client_secret'];

            try {
                $response = Http::withBasicAuth($clientId, $clientSecret)
                    ->asForm()
                    ->post("https://zoom.us/oauth/token?grant_type=account_credentials&account_id={$accountId}");

                if ($response->successful()) {
                    return $response->json('access_token');
                }

                Log::warning('Zoom S2S OAuth failed: ' . $response->body());
            } catch (\Throwable $e) {
                Log::error('Zoom S2S OAuth exception: ' . $e->getMessage());
            }

            return null;
        });
    }

    /**
     * Registers or retrieves a participant's verified join URL for a Zoom module.
     * Works both with live Zoom Cloud API (if configured) and in Local / Direct Link mode.
     */
    public static function getOrRegisterParticipant(CourseModule $module, User $user): array
    {
        $cleanMeetingId = preg_replace('/[^0-9]/', '', (string) $module->zoom_meeting_id);

        $registrant = ZoomRegistrant::firstOrCreate(
            [
                'user_id' => $user->id,
                'course_module_id' => $module->id,
            ],
            [
                'course_id' => $module->course_id,
                'zoom_meeting_id' => $cleanMeetingId ?: 'LIVE-' . $module->id,
            ]
        );

        // If we already have a personal join URL from Zoom API, reuse it
        if (!empty($registrant->personal_join_url) && !empty($registrant->zoom_registrant_id)) {
            return [
                'join_url' => $registrant->personal_join_url,
                'mode' => 'api_registrant',
                'registrant' => $registrant,
            ];
        }

        $token = self::getAccessToken();

        // Try real Zoom Registrant API if credentials and numeric meeting ID exist
        if ($token && !empty($cleanMeetingId)) {
            try {
                $firstName = $user->first_name ?: explode(' ', $user->name)[0];
                $lastName = trim(($user->last_name ?: '') . ($user->fiscal_code ? " [{$user->fiscal_code}]" : ''));
                if (empty($lastName)) {
                    $lastName = 'Corsista';
                }

                $response = Http::withToken($token)
                    ->post("https://api.zoom.us/v2/meetings/{$cleanMeetingId}/registrants", [
                        'email' => $user->email,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'custom_questions' => [
                            [
                                'title' => 'Codice Fiscale',
                                'value' => $user->fiscal_code ?? 'N/D',
                            ],
                        ],
                    ]);

                if ($response->successful()) {
                    $joinUrl = $response->json('join_url');
                    $registrantId = $response->json('registrant_id');

                    $registrant->update([
                        'zoom_meeting_id' => $cleanMeetingId,
                        'zoom_registrant_id' => $registrantId,
                        'personal_join_url' => $joinUrl,
                    ]);

                    return [
                        'join_url' => $joinUrl,
                        'mode' => 'api_registrant',
                        'registrant' => $registrant,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('Zoom Registrant API fallback: ' . $e->getMessage());
            }
        }

        // Fallback: Direct Link with URL parameters for identity prefill
        $baseUrl = $module->zoom_join_url ?: $module->video_url;
        if (empty($baseUrl) && !empty($cleanMeetingId)) {
            $baseUrl = "https://zoom.us/j/{$cleanMeetingId}";
            if (!empty($module->zoom_passcode)) {
                $baseUrl .= '?pwd=' . urlencode($module->zoom_passcode);
            }
        }

        if (empty($baseUrl)) {
            $baseUrl = '#zoom-live-classroom';
        } else {
            $separator = str_contains($baseUrl, '?') ? '&' : '?';
            $uname = urlencode(trim($user->full_name . ($user->fiscal_code ? " ({$user->fiscal_code})" : '')));
            $baseUrl .= "{$separator}uname={$uname}&uemail=" . urlencode($user->email);
        }

        $registrant->update([
            'personal_join_url' => $baseUrl,
        ]);

        return [
            'join_url' => $baseUrl,
            'mode' => 'direct_tracked',
            'registrant' => $registrant,
        ];
    }

    /**
     * Records attendance seconds for a student in a Zoom module, updating ModuleProgress,
     * WatchLog, ZoomRegistrant, and recalculating Enrollment total sync + async hours.
     */
    public static function recordZoomAttendance(
        CourseModule $module,
        User $user,
        int $deltaSeconds,
        ?string $sessionToken = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        bool $replaceTotal = false
    ): ModuleProgress {
        $progress = ModuleProgress::firstOrCreate(
            [
                'user_id' => $user->id,
                'course_id' => $module->course_id,
                'course_module_id' => $module->id,
            ],
            [
                'watched_seconds' => 0,
                'max_position_seconds' => 0,
                'last_position_seconds' => 0,
                'completion_percent' => 0,
                'is_completed' => false,
                'started_at' => now(),
            ]
        );

        $duration = max(60, (int) $module->duration_seconds);
        $newSeconds = $replaceTotal
            ? max($progress->watched_seconds, $deltaSeconds)
            : ($progress->watched_seconds + max(0, $deltaSeconds));

        $cappedSeconds = min($duration, $newSeconds);
        $percent = min(100.0, round(($cappedSeconds / $duration) * 100, 1));
        $isCompleted = $percent >= 80.0 || $cappedSeconds >= ($duration - 30);

        $wasCompleted = $progress->is_completed;

        $progress->watched_seconds = $cappedSeconds;
        $progress->max_position_seconds = $cappedSeconds;
        $progress->last_position_seconds = $cappedSeconds;
        $progress->completion_percent = $percent;
        $progress->last_watched_at = now();

        if ($isCompleted && !$wasCompleted) {
            $progress->is_completed = true;
            $progress->completed_at = now();
        }

        $progress->save();

        // Update ZoomRegistrant record
        $cleanMeetingId = preg_replace('/[^0-9]/', '', (string) $module->zoom_meeting_id) ?: 'LIVE-' . $module->id;
        $registrant = ZoomRegistrant::firstOrCreate(
            [
                'user_id' => $user->id,
                'course_module_id' => $module->id,
            ],
            [
                'course_id' => $module->course_id,
                'zoom_meeting_id' => $cleanMeetingId,
                'first_joined_at' => now(),
            ]
        );

        $registrant->update([
            'attended_seconds' => $cappedSeconds,
            'first_joined_at' => $registrant->first_joined_at ?: now(),
            'last_left_at' => now(),
        ]);

        // Update WatchLog for Ministerial Dossier
        $token = $sessionToken ?: ('zoom_sync_' . $user->id . '_' . $module->id . '_' . date('Ymd'));
        $watchLog = WatchLog::firstOrCreate(
            [
                'user_id' => $user->id,
                'course_id' => $module->course_id,
                'course_module_id' => $module->id,
                'session_token' => substr($token, 0, 64),
            ],
            [
                'ip_address' => $ipAddress ?: request()->ip(),
                'user_agent' => $userAgent ?: ('Zoom Sincrono / ' . request()->userAgent()),
                'start_position_seconds' => 0,
                'end_position_seconds' => 0,
                'seconds_counted' => 0,
                'started_at' => now(),
                'last_heartbeat_at' => now(),
            ]
        );

        $watchLog->end_position_seconds = $cappedSeconds;
        $watchLog->seconds_counted = $replaceTotal
            ? $cappedSeconds
            : ($watchLog->seconds_counted + max(0, $deltaSeconds));
        $watchLog->last_heartbeat_at = now();
        $watchLog->save();

        // Recalculate Enrollment (Sync + Async unified logic)
        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $module->course_id)
            ->first();

        if ($enrollment) {
            $enrollment->recalculateProgress();

            if ($isCompleted && !$wasCompleted) {
                try {
                    $course = $module->course ?: Course::find($module->course_id);
                    if ($course) {
                        $settings = PlatformSetting::getAllSettings();
                        Mail::to($user->email)->send(new ModuleCompletedMail($user, $course, $module, $enrollment, $settings));
                        ActivityLog::record(
                            'module_completed_mail_sent',
                            "Inviata email di completamento Modulo Zoom #{$module->sort_order} ({$module->title}) a {$user->email}",
                            $user->id,
                            $course->id,
                            $module->id
                        );
                    }
                } catch (\Throwable $e) {
                    Log::warning("Impossibile inviare email completamento modulo Zoom a {$user->email}: " . $e->getMessage());
                }
            }
        }

        return $progress;
    }

    /**
     * Pulls official attendance report from Zoom Cloud API for a specific Zoom Module.
     */
    public static function syncMeetingParticipantsFromApi(CourseModule $module): array
    {
        $cleanMeetingId = preg_replace('/[^0-9]/', '', (string) $module->zoom_meeting_id);
        if (empty($cleanMeetingId)) {
            return [
                'success' => false,
                'message' => 'Meeting ID numerico non impostato per questo modulo.',
                'synced_count' => 0,
            ];
        }

        $token = self::getAccessToken();
        if (!$token) {
            return [
                'success' => false,
                'message' => 'Chiavi API Zoom S2S OAuth non configurate nelle Impostazioni (attualmente in modalità Tracciamento Diretto da Portale).',
                'synced_count' => 0,
            ];
        }

        try {
            $response = Http::withToken($token)
                ->get("https://api.zoom.us/v2/report/meetings/{$cleanMeetingId}/participants", [
                    'page_size' => 300,
                ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Risposta API Zoom: ' . ($response->json('message') ?: $response->status()),
                    'synced_count' => 0,
                ];
            }

            $participants = $response->json('participants', []);
            $aggregatedUsers = [];

            foreach ($participants as $p) {
                $registrantId = trim((string) ($p['registrant_id'] ?? $p['id'] ?? ''));
                $email = strtolower(trim((string) ($p['user_email'] ?? '')));
                $name = (string) ($p['name'] ?? '');
                $durationSec = (int) ($p['duration'] ?? 0);

                if ($durationSec <= 0) {
                    continue;
                }

                // Resolve User by: 1) zoom_registrant_id, 2) email, 3) [FISCAL_CODE] in name, 4) full_name match
                $user = null;
                if (!empty($registrantId)) {
                    $reg = ZoomRegistrant::where('zoom_registrant_id', $registrantId)
                        ->where('course_module_id', $module->id)
                        ->first();
                    if ($reg) {
                        $user = $reg->user;
                    }
                }
                if (!$user && !empty($email)) {
                    $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
                }
                if (!$user && preg_match('/[\[\(]([A-Z0-9]{11,16})[\]\)]/i', $name, $m)) {
                    $user = User::whereRaw('UPPER(fiscal_code) = ?', [strtoupper($m[1])])->first();
                }
                if (!$user && !empty($name)) {
                    $cleanName = strtolower(trim(preg_replace('/[\[\(].*?[\]\)]/', '', $name)));
                    if (strlen($cleanName) >= 4) {
                        $user = User::whereRaw('LOWER(name) = ?', [$cleanName])
                            ->orWhereRaw("LOWER( first_name || ' ' || last_name ) = ?", [$cleanName])
                            ->orWhereRaw("LOWER( last_name || ' ' || first_name ) = ?", [$cleanName])
                            ->first();
                    }
                }

                if ($user) {
                    if (!isset($aggregatedUsers[$user->id])) {
                        $aggregatedUsers[$user->id] = [
                            'user' => $user,
                            'seconds' => 0,
                        ];
                    }
                    $aggregatedUsers[$user->id]['seconds'] += $durationSec;
                }
            }

            $syncedCount = 0;
            foreach ($aggregatedUsers as $item) {
                self::recordZoomAttendance(
                    $module,
                    $item['user'],
                    $item['seconds'],
                    'zoom_api_report_' . $cleanMeetingId . '_' . $item['user']->id,
                    'Zoom Cloud Report API',
                    'Zoom Official API Sync',
                    true
                );
                $syncedCount++;
            }

            return [
                'success' => true,
                'message' => "Sincronizzati con successo {$syncedCount} corsisti dal report ufficiale Zoom Cloud.",
                'synced_count' => $syncedCount,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Errore comunicazione con Zoom API: ' . $e->getMessage(),
                'synced_count' => 0,
            ];
        }
    }

    /**
     * Lists upcoming and recurring meetings from Zoom Cloud API for the configured account.
     */
    public static function listUpcomingMeetings(): array
    {
        $token = self::getAccessToken();
        if (!$token) {
            return [];
        }

        try {
            $response = Http::withToken($token)
                ->get('https://api.zoom.us/v2/users/me/meetings', [
                    'type' => 'upcoming',
                    'page_size' => 100,
                ]);

            if ($response->successful()) {
                return $response->json('meetings') ?: [];
            }

            Log::warning('Zoom list meetings failed: ' . $response->body());
        } catch (\Throwable $e) {
            Log::error('Zoom list meetings exception: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Retrieves full meeting details from Zoom API (including occurrences for recurring meetings).
     */
    public static function getMeetingDetails(string $meetingId): ?array
    {
        $token = self::getAccessToken();
        if (!$token) {
            return null;
        }

        $cleanMeetingId = preg_replace('/[^0-9]/', '', $meetingId);
        if (empty($cleanMeetingId)) {
            return null;
        }

        try {
            $response = Http::withToken($token)
                ->get("https://api.zoom.us/v2/meetings/{$cleanMeetingId}");

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("Zoom get meeting {$cleanMeetingId} failed: " . $response->body());
        } catch (\Throwable $e) {
            Log::error("Zoom get meeting {$cleanMeetingId} exception: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Automatically imports or synchronizes a Zoom meeting (and its recurring occurrences)
     * into the course modules without manual intervention.
     *
     * Opzione 1: Per riunioni ricorrenti (stesso link Zoom ripetuto su più date),
     * viene creato/mantenuto UN SOLO Modulo Aula Virtuale Zoom con il totale delle ore
     * e l'elenco/calendario interattivo delle singole ripetizioni memorizzato in zoom_occurrences_json.
     */
    public static function syncCourseFromZoomMeeting(Course $course, string $meetingId): array
    {
        $cleanMeetingId = preg_replace('/[^0-9]/', '', $meetingId);
        if (empty($cleanMeetingId)) {
            return [
                'success' => false,
                'message' => 'ID Meeting Zoom non valido.',
            ];
        }

        $details = self::getMeetingDetails($cleanMeetingId);
        if (!$details) {
            return [
                'success' => false,
                'message' => "Impossibile recuperare i dettagli per il Meeting ID {$cleanMeetingId} da Zoom Cloud API. Verifica le credenziali Server-to-Server nelle impostazioni.",
            ];
        }

        $topic = trim((string) ($details['topic'] ?? "Lezione Live Zoom {$cleanMeetingId}"));
        $passcode = $details['password'] ?? null;
        $joinUrl = $details['join_url'] ?? "https://zoom.us/j/{$cleanMeetingId}";
        $occurrences = $details['occurrences'] ?? [];

        $moduleTitle = "Aula Virtuale Zoom — " . $topic;

        if (!empty($occurrences) && is_array($occurrences)) {
            // Sort occurrences chronologically
            usort($occurrences, function ($a, $b) {
                return strcmp($a['start_time'] ?? '', $b['start_time'] ?? '');
            });

            $totalDurationSec = 0;
            $occurrencesData = [];
            $firstStart = null;
            $lastEnd = null;
            $nextUpcomingStart = null;

            foreach ($occurrences as $occ) {
                // Ignore occurrences deleted or cancelled on Zoom
                if (!empty($occ['status']) && in_array(strtolower((string) $occ['status']), ['deleted', 'cancelled', 'canceled'])) {
                    continue;
                }

                $occStart = !empty($occ['start_time']) ? \Carbon\Carbon::parse(substr((string) $occ['start_time'], 0, 19), config('app.timezone', 'Europe/Rome')) : null;
                $durationMin = max(30, (int) ($occ['duration'] ?? 60));
                $durSec = $durationMin * 60;
                $occEnd = $occStart ? $occStart->copy()->addMinutes($durationMin) : null;
                $totalDurationSec += $durSec;

                if ($occStart) {
                    if (!$firstStart || $occStart->lt($firstStart)) {
                        $firstStart = $occStart;
                    }
                    if ($occEnd && (!$lastEnd || $occEnd->gt($lastEnd))) {
                        $lastEnd = $occEnd;
                    }
                    if ($occEnd->isFuture() && (!$nextUpcomingStart || $occStart->lt($nextUpcomingStart))) {
                        $nextUpcomingStart = $occStart;
                    }
                }

                $occurrencesData[] = [
                    'occurrence_id' => (string) ($occ['occurrence_id'] ?? ''),
                    'start_time' => $occ['start_time'] ?? null,
                    'duration_minutes' => $durationMin,
                    'status' => $occ['status'] ?? null,
                ];
            }

            if ($totalDurationSec <= 0) {
                $totalDurationSec = max(1800, ((int) ($details['duration'] ?? 60)) * 60);
            }

            // Opzione 1: EXACTLY 1 unified Zoom classroom module per course meeting!
            $existingModules = $course->modules()
                ->where('video_source_type', 'zoom')
                ->where('zoom_meeting_id', $cleanMeetingId)
                ->orderBy('id')
                ->get();

            $module = $existingModules->first();

            // Safely consolidate duplicates if any were previously created
            if ($existingModules->count() > 1) {
                $duplicates = $existingModules->slice(1);
                foreach ($duplicates as $dup) {
                    \App\Models\WatchLog::where('course_module_id', $dup->id)->update(['course_module_id' => $module->id]);
                    \App\Models\ZoomRegistrant::where('course_module_id', $dup->id)->update(['course_module_id' => $module->id]);
                    $dupProgresses = \App\Models\ModuleProgress::where('course_module_id', $dup->id)->get();
                    foreach ($dupProgresses as $dp) {
                        $mainProg = \App\Models\ModuleProgress::where('course_module_id', $module->id)->where('user_id', $dp->user_id)->first();
                        if ($mainProg) {
                            $mainProg->watched_seconds = min($totalDurationSec, $mainProg->watched_seconds + $dp->watched_seconds);
                            $mainProg->save();
                            $dp->delete();
                        } else {
                            $dp->update(['course_module_id' => $module->id]);
                        }
                    }
                    $dup->delete();
                }
            }

            $schedStart = $nextUpcomingStart ?: ($firstStart ?: null);
            $schedEnd = $lastEnd ?: ($schedStart ? $schedStart->copy()->addSeconds($totalDurationSec) : null);

            if ($module) {
                $module->update([
                    'title' => $moduleTitle,
                    'zoom_passcode' => $passcode ?: $module->zoom_passcode,
                    'zoom_scheduled_at' => $schedStart ?: $module->zoom_scheduled_at,
                    'zoom_scheduled_end_at' => $schedEnd ?: $module->zoom_scheduled_end_at,
                    'zoom_join_url' => $joinUrl ?: $module->zoom_join_url,
                    'zoom_occurrences_json' => $occurrencesData,
                    'duration_seconds' => $totalDurationSec,
                    'is_published' => true,
                ]);
            } else {
                $maxOrder = ((int) $course->modules()->max('sort_order') ?: 0) + 1;
                $module = $course->modules()->create([
                    'course_id' => $course->id,
                    'sort_order' => $maxOrder,
                    'title' => $moduleTitle,
                    'video_source_type' => 'zoom',
                    'zoom_meeting_id' => $cleanMeetingId,
                    'zoom_passcode' => $passcode,
                    'zoom_scheduled_at' => $schedStart,
                    'zoom_scheduled_end_at' => $schedEnd,
                    'zoom_join_url' => $joinUrl,
                    'zoom_occurrences_json' => $occurrencesData,
                    'duration_seconds' => $totalDurationSec,
                    'is_published' => true,
                ]);
            }

            $countOcc = count($occurrencesData);
            $totalHours = round($totalDurationSec / 3600, 1);
            $syncedMsg = "Configurata con successo l'Aula Virtuale Zoom unificata per \"{$topic}\" con {$countOcc} ripetizioni programmate (Totale {$totalHours} ore).";
            $syncedCount = 1;
        } else {
            // Single Meeting
            $start = !empty($details['start_time']) ? \Carbon\Carbon::parse(substr((string) $details['start_time'], 0, 19), config('app.timezone', 'Europe/Rome')) : null;
            $durationSec = max(1800, ((int) ($details['duration'] ?? 60)) * 60);
            $end = $start ? $start->copy()->addSeconds($durationSec) : null;

            $module = $course->modules()
                ->where('video_source_type', 'zoom')
                ->where('zoom_meeting_id', $cleanMeetingId)
                ->first();

            if ($module) {
                $module->update([
                    'title' => $moduleTitle,
                    'zoom_passcode' => $passcode ?: $module->zoom_passcode,
                    'zoom_scheduled_at' => $start ?: $module->zoom_scheduled_at,
                    'zoom_scheduled_end_at' => $end ?: $module->zoom_scheduled_end_at,
                    'zoom_join_url' => $joinUrl ?: $module->zoom_join_url,
                    'zoom_occurrences_json' => null,
                    'duration_seconds' => $durationSec,
                    'is_published' => true,
                ]);
            } else {
                $maxOrder = ((int) $course->modules()->max('sort_order') ?: 0) + 1;
                $module = $course->modules()->create([
                    'course_id' => $course->id,
                    'sort_order' => $maxOrder,
                    'title' => $moduleTitle,
                    'video_source_type' => 'zoom',
                    'zoom_meeting_id' => $cleanMeetingId,
                    'zoom_passcode' => $passcode,
                    'zoom_scheduled_at' => $start,
                    'zoom_scheduled_end_at' => $end,
                    'zoom_join_url' => $joinUrl,
                    'zoom_occurrences_json' => null,
                    'duration_seconds' => $durationSec,
                    'is_published' => true,
                ]);
            }

            $durationHours = round($durationSec / 3600, 1);
            $syncedMsg = "Sincronizzata con successo la sessione Zoom per \"{$topic}\" ({$durationHours} ore).";
            $syncedCount = 1;
        }

        $course->autoSyncDurationsFromModules();

        return [
            'success' => true,
            'message' => $syncedMsg,
            'synced_count' => $syncedCount,
        ];
    }
}
