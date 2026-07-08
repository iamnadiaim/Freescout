@php
    $user = auth()->user();

    $timer = [
        'status' => 'stopped',
        'logged_seconds' => 0,
        'active_seconds' => 0,
        'total_seconds' => 0,
    ];

    $logs = collect();

    if ($conversation && $user) {
        $timer = \Modules\LaporPoliwangi\Services\TimeTrackingService::status($conversation, $user->id);

        $logs = \Modules\LaporPoliwangi\Models\TimeTrackingLog::with('user')
            ->where('conversation_id', $conversation->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    $isClosed = (int) $conversation->status === (int) \App\Conversation::STATUS_CLOSED;

    $currentStatusName = method_exists('\App\Conversation', 'statusCodeToName')
        ? \App\Conversation::statusCodeToName((int) $conversation->status)
        : $conversation->status;

    $totalSeconds = (int) ($timer['total_seconds'] ?? 0);
@endphp

<div class="conv-top-block tt-conv-timer"
    data-conversation-id="{{ $conversation->id }}"
    data-status-url="{{ route('laporpoliwangi.time_tracking.status') }}"
    data-is-closed="{{ $isClosed ? 1 : 0 }}">

    <div class="tt-timer-main-row">
        <span class="tt-auto-badge">
            <i class="glyphicon glyphicon-time"></i>
            Time Tracking
        </span>

        <span class="tt-total-value" data-total-seconds="{{ $totalSeconds }}">
            {{ gmdate('H:i:s', $totalSeconds) }}
        </span>

        <span class="tt-status-badge {{ $isClosed ? 'tt-status-closed' : 'tt-status-running' }}">
            {{ $isClosed ? 'Stopped' : 'Running' }}
        </span>

        <button type="button" class="tt-timelogs-toggle">
            Timelogs <span class="caret"></span>
        </button>
    </div>

    <div class="tt-timelogs-panel">
        <table class="table table-condensed tt-timelogs-table">
            <thead>
                <tr>
                    <th style="width: 34%;">Activity</th>
                    <th style="width: 24%;">Updated By</th>
                    <th style="width: 22%;">Date</th>
                    <th class="text-right">Time Spent</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($logs as $log)
                    @php
                        $logUserName = 'Unknown User';

                        if (!empty($log->user)) {
                            $logUserName = trim(($log->user->first_name ?? '') . ' ' . ($log->user->last_name ?? ''));

                            if (!$logUserName) {
                                $logUserName = $log->user->email ?? 'Unknown User';
                            }
                        }

                        $activityLabel = $log->note;

                        if (!$activityLabel) {
                            if ($log->source === 'closed') {
                                $activityLabel = 'Status changed to Closed';
                            } elseif ($log->source === 'status_changed') {
                                $activityLabel = 'Status changed';
                            } elseif ($log->source === 'assignee_changed') {
                                $activityLabel = 'Assignee changed';
                            } else {
                                $activityLabel = ucfirst($log->source ?: 'Auto');
                            }
                        }

                        $logDate = '-';

                        if (!empty($log->created_at)) {
                            try {
                                $logDate = \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i');
                            } catch (\Exception $e) {
                                $logDate = $log->created_at;
                            }
                        }
                    @endphp

                    <tr>
                        <td>
                            <span class="tt-log-activity">
                                {{ $activityLabel }}
                            </span>

                            @if (!empty($log->source))
                                <span class="tt-log-source">
                                    {{ $log->source }}
                                </span>
                            @endif
                        </td>

                        <td>
                            {{ $logUserName }}
                        </td>

                        <td>
                            {{ $logDate }}
                        </td>

                        <td class="text-right">
                            @if ((int) $log->seconds > 0)
                                <strong>{{ $log->time_spent_label }}</strong>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-muted">
                            No timelogs have been saved yet. Timelogs will appear after the conversation status is changed.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
