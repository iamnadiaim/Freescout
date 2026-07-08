<?php

namespace Modules\LaporPoliwangi\Http\Controllers;

use App\Conversation;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\LaporPoliwangi\Services\TimeTrackingService;

class TimeTrackingController extends Controller
{
    private function canTrackConversation(Conversation $conversation)
    {
        if (!auth()->check()) {
            return false;
        }

        /*
         * Kalau belum assigned, user login boleh dihitung.
         * Kalau sudah assigned, hanya assignee yang dihitung.
         */
        if (empty($conversation->user_id)) {
            return true;
        }

        return (int) $conversation->user_id === (int) auth()->id();
    }

    /**
     * Endpoint ini hanya untuk membaca status timer.
     * Tidak ada tombol start, pause, stop, atau reset.
     */
    public function status(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|integer',
        ]);

        $conversation = Conversation::findOrFail($request->conversation_id);

        $this->authorize('view', $conversation);

        if (!$this->canTrackConversation($conversation)) {
            return response()->json([
                'status' => 'success',
                'timer'  => [
                    'status'         => 'unassigned',
                    'logged_seconds' => 0,
                    'active_seconds' => 0,
                    'total_seconds'  => 0,
                ],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'timer'  => TimeTrackingService::status($conversation, auth()->id()),
        ]);
    }
}
