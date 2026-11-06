<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CourseModule;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\ZoomRegistrant;
use App\Services\ZoomService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZoomWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();
        $event = (string) ($payload['event'] ?? '');

        // 1. Zoom Endpoint URL Validation (CRC challenge-response)
        if ($event === 'endpoint.url_validation') {
            $plainToken = (string) ($payload['payload']['plainToken'] ?? '');
            $settings = PlatformSetting::getAllSettings();
            $secret = (string) ($settings['zoom_webhook_secret_token'] ?? '');

            $encryptedToken = hash_hmac('sha256', $plainToken, $secret);

            return response()->json([
                'plainToken' => $plainToken,
                'encryptedToken' => $encryptedToken,
            ], 200);
        }

        // 2. Participant Left -> Record session duration
        if ($event === 'meeting.participant_left') {
            $obj = $payload['payload']['object'] ?? [];
            $meetingId = preg_replace('/[^0-9]/', '', (string) ($obj['id'] ?? ''));
            $participant = $obj['participant'] ?? [];

            $email = strtolower(trim((string) ($participant['email'] ?? '')));
            $userName = (string) ($participant['user_name'] ?? '');
            $registrantId = (string) ($participant['registrant_id'] ?? $participant['id'] ?? '');

            $joinTime = !empty($participant['join_time']) ? strtotime($participant['join_time']) : null;
            $leaveTime = !empty($participant['leave_time']) ? strtotime($participant['leave_time']) : time();
            $durationSec = ($joinTime && $leaveTime && $leaveTime > $joinTime)
                ? ($leaveTime - $joinTime)
                : (int) ($participant['duration'] ?? 60);

            $module = CourseModule::where('video_source_type', 'zoom')
                ->where(function ($q) use ($meetingId) {
                    $q->where('zoom_meeting_id', $meetingId)
                      ->orWhereRaw("REPLACE(zoom_meeting_id, ' ', '') = ?", [$meetingId]);
                })
                ->first();

            if ($module) {
                $user = null;
                if (!empty($registrantId)) {
                    $reg = ZoomRegistrant::where('zoom_registrant_id', $registrantId)->first();
                    if ($reg) {
                        $user = $reg->user;
                    }
                }
                if (!$user && !empty($email)) {
                    $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
                }
                if (!$user && preg_match('/[\[\(]([A-Z0-9]{11,16})[\]\)]/i', $userName, $m)) {
                    $user = User::whereRaw('UPPER(fiscal_code) = ?', [strtoupper($m[1])])->first();
                }
                if (!$user && !empty($userName)) {
                    $cleanName = strtolower(trim(preg_replace('/[\[\(].*?[\]\)]/', '', $userName)));
                    if (strlen($cleanName) >= 4) {
                        $user = User::whereRaw('LOWER(name) = ?', [$cleanName])
                            ->orWhereRaw("LOWER( first_name || ' ' || last_name ) = ?", [$cleanName])
                            ->orWhereRaw("LOWER( last_name || ' ' || first_name ) = ?", [$cleanName])
                            ->first();
                    }
                }

                if ($user && $durationSec > 0) {
                    ZoomService::recordZoomAttendance(
                        $module,
                        $user,
                        $durationSec,
                        'zoom_webhook_' . $meetingId . '_' . $user->id,
                        'Zoom Webhook',
                        'Zoom Event: meeting.participant_left',
                        false
                    );

                    ActivityLog::record(
                        'zoom_webhook_attendance',
                        "Ricevuto evento uscita Zoom per {$user->full_name} (+{$durationSec}s su Modulo #{$module->sort_order})",
                        $user->id,
                        $module->course_id,
                        $module->id
                    );
                }
            }
        }

        // 3. Meeting Ended -> Full report sync
        if ($event === 'meeting.ended') {
            $obj = $payload['payload']['object'] ?? [];
            $meetingId = preg_replace('/[^0-9]/', '', (string) ($obj['id'] ?? ''));

            $modules = CourseModule::where('video_source_type', 'zoom')
                ->where(function ($q) use ($meetingId) {
                    $q->where('zoom_meeting_id', $meetingId)
                      ->orWhereRaw("REPLACE(zoom_meeting_id, ' ', '') = ?", [$meetingId]);
                })
                ->get();

            foreach ($modules as $module) {
                ZoomService::syncMeetingParticipantsFromApi($module);
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
