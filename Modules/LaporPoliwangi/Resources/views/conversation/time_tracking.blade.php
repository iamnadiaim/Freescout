@php
    /*
     * Time tracking otomatis:
     * - dimulai saat laporan dibuka/dibaca
     * - disimpan saat tiket closed
     * - tidak memakai setting/tombol manual lagi
     */
    $isAssignedToMe = (int) $conversation->user_id === (int) Auth::id();

    $timeTrackingLogs = Modules\LaporPoliwangi\Models\TimeTrackingLog::with('user')
        ->where('conversation_id', $conversation->id)
        ->orderBy('updated_at', 'desc')
        ->get();

    $totalTrackedSeconds = $timeTrackingLogs->sum('seconds');

    $canViewTimelogs = Auth::user()->isAdmin() || $isAssignedToMe;
@endphp

@if ($isAssignedToMe || $canViewTimelogs)
    <div class="conv-block tt-conv-timer" data-conversation-id="{{ $conversation->id }}">

        <div class="tt-timer-main-row">


            @if ($canViewTimelogs)
                <button type="button" class="tt-timelogs-toggle">
                    Timelogs <span class="caret"></span>
                </button>
            @endif
        </div>

        @if ($canViewTimelogs)
            <div class="tt-timelogs-panel">
                <table class="tt-timelogs-table">
                    <thead>
                        <tr>
                            <th style="width: 90px;">Status</th>
                            <th>User</th>
                            <th class="tt-log-time">Time</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($timeTrackingLogs as $log)
                            @php
                                $logUserName = 'Unknown User';

                                if ($log->user) {
                                    $logUserName = trim(
                                        ($log->user->first_name ?? '') . ' ' . ($log->user->last_name ?? ''),
                                    );

                                    if (!$logUserName) {
                                        $logUserName = $log->user->email ?? 'Unknown User';
                                    }
                                }

                                $icon = 'glyphicon-ok';
                                $iconColor = '#4caf50';

                                $note = (string) $log->note;

                                if (strpos($note, 'to Closed') !== false) {
                                    $icon = 'glyphicon-ok';
                                    $iconColor = '#4caf50';
                                } elseif (strpos($note, 'to Pending') !== false) {
                                    $icon = 'glyphicon-time';
                                    $iconColor = '#ff9800';
                                } elseif (strpos($note, 'to Active') !== false) {
                                    $icon = 'glyphicon-inbox';
                                    $iconColor = '#0078d7';
                                } elseif (strpos($note, 'to Spam') !== false) {
                                    $icon = 'glyphicon-ban-circle';
                                    $iconColor = '#f44336';
                                } else {
                                    if ($conversation->status == \App\Conversation::STATUS_CLOSED) {
                                        $icon = 'glyphicon-ok';
                                        $iconColor = '#4caf50';
                                    } elseif ($conversation->status == \App\Conversation::STATUS_PENDING) {
                                        $icon = 'glyphicon-time';
                                        $iconColor = '#ff9800';
                                    } elseif ($conversation->status == \App\Conversation::STATUS_ACTIVE) {
                                        $icon = 'glyphicon-inbox';
                                        $iconColor = '#0078d7';
                                    } elseif ($conversation->status == \App\Conversation::STATUS_SPAM) {
                                        $icon = 'glyphicon-ban-circle';
                                        $iconColor = '#f44336';
                                    }
                                }
                            @endphp

                            <tr>
                                <td>
                                    <span class="tt-log-status" style="color: {{ $iconColor }};">
                                        <i class="glyphicon {{ $icon }}"></i>
                                    </span>
                                </td>
                                <td>{{ $logUserName }}</td>
                                <td class="tt-log-time">{{ gmdate('H:i:s', (int) $log->seconds) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-muted">
                                    Belum ada waktu yang tersimpan. Waktu akan tersimpan saat tiket
                                    di-Closed.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif

<style>
.tt-conv-timer {
    margin-bottom: 10px;
    font-family: inherit;
}
.tt-timer-main-row {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 10px;
}
.tt-auto-badge {
    background: #e8f5e9;
    color: #2e7d32;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 5px;
}
.tt-auto-badge i {
    top: 1px;
}
.tt-total-time {
    font-size: 14px;
    color: #555;
    flex-grow: 1;
    text-align: right;
    margin-right: 15px;
}
.tt-total-value {
    font-size: 18px;
    font-weight: bold;
    color: #1f2d3d;
    margin-left: 5px;
}
.tt-timelogs-toggle {
    background: #fff;
    border: 1px solid #d1d9e0;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
    color: #333;
}
.tt-timelogs-toggle:hover {
    background: #f5f5f5;
}
.tt-timelogs-panel {
    margin-top: 10px;
    display: none;
}
.tt-timelogs-panel.show {
    display: block;
}
.tt-timelogs-table {
    width: 100%;
    font-size: 13px;
    color: #333;
}
.tt-timelogs-table th {
    text-align: left;
    color: #777;
    font-weight: normal;
    border-bottom: 1px solid #eee;
    padding-bottom: 8px;
    margin-bottom: 5px;
}
.tt-timelogs-table td {
    padding: 8px 0;
    border-bottom: 1px solid #f9f9f9;
}
.tt-log-time {
    text-align: right;
    font-family: monospace;
    font-size: 14px;
}
.tt-log-status-saved {
    color: #4caf50;
}
</style>
