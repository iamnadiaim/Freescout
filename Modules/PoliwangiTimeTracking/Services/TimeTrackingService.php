<?php

namespace Modules\PoliwangiTimeTracking\Services;

use App\Conversation;
use Modules\PoliwangiTimeTracking\Models\TimeTrackingLog;
use Modules\PoliwangiTimeTracking\Models\TimeTrackingSession;

class TimeTrackingService
{
    public static function startFromReply(Conversation $conversation, $userId, $threadId = null)
    {
        // Cari session existing untuk conversation + user
        $session = TimeTrackingSession::where('conversation_id', $conversation->id)
            ->where('user_id', $userId)
            ->first();

        // Kalau belum ada, buat baru
        if (!$session) {
            return TimeTrackingSession::create([
                'conversation_id' => $conversation->id,
                'mailbox_id' => $conversation->mailbox_id,
                'user_id' => $userId,
                'started_at' => now(),
                'elapsed_seconds' => 0,
                'status' => 'running',
                'source' => 'reply',
                'thread_id' => $threadId,
            ]);
        }

        // Kalau sudah running, jangan reset waktu
        if ($session->status === 'running') {
            return $session;
        }

        // Kalau paused/stopped, lanjutkan lagi dari waktu sekarang
        $session->update([
            'started_at' => now(),
            'status' => 'running',
            'source' => 'reply',
            'thread_id' => $threadId ?: $session->thread_id,
        ]);

        return $session;
    }

    public static function pause(Conversation $conversation, $userId)
    {
        $session = TimeTrackingSession::where('conversation_id', $conversation->id)
            ->where('user_id', $userId)
            ->first();

        if (!$session || $session->status !== 'running') {
            return $session;
        }

        $elapsed = (int) $session->elapsed_seconds;

        if ($session->started_at) {
            $elapsed += now()->diffInSeconds($session->started_at);
        }

        $session->update([
            'elapsed_seconds' => $elapsed,
            'started_at' => null,
            'status' => 'paused',
        ]);

        return $session;
    }

    public static function stopAndSave(Conversation $conversation, $userId, $note = null, $source = 'timer', $forcedSeconds = null)
    {
        $session = TimeTrackingSession::where('conversation_id', $conversation->id)
            ->where('user_id', $userId)
            ->first();

        /*
     * Ambil log yang sudah ada.
     * Kita pakai 1 log per conversation + user + source.
     */
        $log = null;

        if ($source !== 'closed') {
            $log = TimeTrackingLog::where('conversation_id', $conversation->id)
                ->where('user_id', $userId)
                ->where('source', $source)
                ->first();
        }

        $loggedSeconds = $log ? (int) $log->seconds : 0;

        /*
     * Kalau frontend mengirim seconds, berarti itu total angka yang tampil di UI.
     * Jadi langsung pakai itu supaya UI dan database sama.
     */
        if ($forcedSeconds !== null && (int) $forcedSeconds > 0) {
            $seconds = (int) $forcedSeconds;
        } else {
            /*
         * Kalau tidak ada forcedSeconds, berarti dipanggil dari close/assign.
         * Maka total = waktu yang sudah pernah tersimpan + waktu session aktif.
         */
            $sessionSeconds = 0;

            if ($session) {
                $sessionSeconds = (int) $session->elapsed_seconds;

                if ($session->status === 'running' && $session->started_at) {
                    $sessionSeconds += now()->diffInSeconds($session->started_at);
                }
            }

            $seconds = $loggedSeconds + $sessionSeconds;
        }

        if ($seconds > 0) {
            if (!$log) {
                $log = new TimeTrackingLog();
                $log->conversation_id = $conversation->id;
                $log->mailbox_id = $conversation->mailbox_id;
                $log->user_id = $userId;
                $log->source = $source;
            }

            /*
         * Pakai = bukan +=
         * Karena $seconds di sini sudah total akhir.
         */
            $log->seconds = $seconds;
            $log->note = $note;
            $log->save();
        }

        /*
     * Setelah waktu disimpan ke log, session dikosongkan.
     */
        if ($session) {
            $session->update([
                'started_at' => null,
                'elapsed_seconds' => 0,
                'status' => 'stopped',
            ]);
        }

        return $seconds;
    }

    public static function status(Conversation $conversation, $userId)
    {
        $loggedSeconds = (int) TimeTrackingLog::where('conversation_id', $conversation->id)
            ->where('user_id', $userId)
            ->sum('seconds');

        $session = TimeTrackingSession::where('conversation_id', $conversation->id)
            ->where('user_id', $userId)
            ->first();

        $activeSeconds = 0;
        $status = 'stopped';

        if ($session) {
            $activeSeconds = (int) $session->elapsed_seconds;
            $status = $session->status;

            if ($session->status === 'running' && $session->started_at) {
                $activeSeconds += now()->diffInSeconds($session->started_at);
            }
        }

        return [
            'logged_seconds' => $loggedSeconds,
            'active_seconds' => $activeSeconds,
            'total_seconds' => $loggedSeconds + $activeSeconds,
            'status' => $status,
        ];
    }

    public static function reset(Conversation $conversation, $userId)
    {
        TimeTrackingSession::where('conversation_id', $conversation->id)
            ->where('user_id', $userId)
            ->delete();

        TimeTrackingLog::where('conversation_id', $conversation->id)
            ->where('user_id', $userId)
            ->delete();
    }

    public static function logStatusChange(Conversation $conversation, $userId, $prevStatus = null, $newStatus = null)
    {
        $prevStatusName = $prevStatus !== null
            ? Conversation::statusCodeToName((int) $prevStatus)
            : '-';

        $newStatusName = $newStatus !== null
            ? Conversation::statusCodeToName((int) $newStatus)
            : Conversation::statusCodeToName((int) $conversation->status);

        $lastLog = TimeTrackingLog::where('conversation_id', $conversation->id)
            ->orderBy('created_at', 'desc')
            ->first();

        $startTime = $lastLog ? $lastLog->created_at : $conversation->created_at;
        $seconds = now()->diffInSeconds($startTime);

        TimeTrackingLog::create([
            'conversation_id' => $conversation->id,
            'mailbox_id'      => $conversation->mailbox_id,
            'user_id'         => $userId,
            'seconds'         => $seconds,
            'source'          => 'status_changed',
            'note'            => 'Status changed from ' . $prevStatusName . ' to ' . $newStatusName,
        ]);
    }
}
