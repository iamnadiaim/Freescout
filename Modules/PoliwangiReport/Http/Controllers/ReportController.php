<?php

namespace Modules\PoliwangiReport\Http\Controllers;

use App\Conversation;
use App\Option;
use App\Thread;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;

class ReportController extends Controller
{
    public function timeTracking(Request $request)
    {
        $authUser = auth()->user();

        /*
        * Ambil setting Time Tracking dari menu Settings.
        * Ini mengikuti pola FreeScout karena setting disimpan di tabel options.
        */
        $timeTrackingMode = Option::get('time_tracking_mode', 'assigned');

        $timeTrackingSettings = [
            'mode' => $timeTrackingMode,
            'show_review_time_dialog' => Option::get('time_tracking_show_review_time_dialog', true),
            'display_timer_controls' => Option::get('time_tracking_display_timer_controls', true),
            'allow_add_time_manually' => Option::get('time_tracking_allow_add_time_manually', false),
            'allow_reset_timer' => Option::get('time_tracking_allow_reset_timer', true),
            'display_timelogs_to_users' => Option::get('time_tracking_display_timelogs_to_users', true),
        ];

        /*
         * Mailbox yang boleh dilihat user login.
         * Kita filter agar HANYA mailbox yang dikelola (punya izin 'Edit Mailbox') yang tampil di report.
         * Global Admin tentu saja bisa melihat semuanya.
         */
        $mailboxes = collect($authUser->mailboxesCanView(true))->filter(function ($m) use ($authUser) {
            return $authUser->isAdmin() || $authUser->hasManageMailboxPermission($m->id, \App\Mailbox::ACCESS_PERM_EDIT);
        });

        if (!$authUser->isAdmin() && $mailboxes->isEmpty()) {
            abort(403, 'Unauthorized action.');
        }

        /*
         * Mailbox terpilih.
         * Kalau belum pilih, ambil mailbox pertama.
         * Kalau user memaksa mailbox yang tidak boleh dilihat, kembalikan ke mailbox pertama.
         */
        $selectedMailboxId = $request->get('mailbox');

        if (!$selectedMailboxId && $mailboxes->count()) {
            $selectedMailboxId = $mailboxes->first()->id;
        }

        $allowedMailboxIds = $mailboxes->pluck('id')->toArray();

        if ($selectedMailboxId && !in_array((int) $selectedMailboxId, array_map('intval', $allowedMailboxIds))) {
            $selectedMailboxId = $mailboxes->count() ? $mailboxes->first()->id : null;
        }

        /*
         * Filter range tanggal.
         */
        $range = $request->get('range', 'custom');

        if ($range == 'today') {
            $dateFrom = Carbon::today()->format('Y-m-d');
            $dateTo   = Carbon::today()->format('Y-m-d');
        } elseif ($range == 'yesterday') {
            $dateFrom = Carbon::yesterday()->format('Y-m-d');
            $dateTo   = Carbon::yesterday()->format('Y-m-d');
        } elseif ($range == 'last_7_days') {
            $dateFrom = Carbon::now()->subDays(6)->format('Y-m-d');
            $dateTo   = Carbon::now()->format('Y-m-d');
        } elseif ($range == 'last_week') {
            $dateFrom = Carbon::now()->subWeek()->startOfWeek()->format('Y-m-d');
            $dateTo   = Carbon::now()->subWeek()->endOfWeek()->format('Y-m-d');
        } elseif ($range == 'month') {
            $dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
            $dateTo   = Carbon::now()->endOfMonth()->format('Y-m-d');
        } elseif ($range == 'last_month') {
            $dateFrom = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
            $dateTo   = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
        } elseif ($range == 'last_12_months') {
            $dateFrom = Carbon::now()->subMonths(11)->startOfMonth()->format('Y-m-d');
            $dateTo   = Carbon::now()->endOfMonth()->format('Y-m-d');
        } elseif ($range == 'year') {
            $dateFrom = Carbon::now()->startOfYear()->format('Y-m-d');
            $dateTo   = Carbon::now()->endOfYear()->format('Y-m-d');
        } else {
            $dateFrom = $request->get('date_from', date('Y-m-d', strtotime('-7 days')));
            $dateTo   = $request->get('date_to', date('Y-m-d'));
        }

        $startDateTime = Carbon::parse($dateFrom)->startOfDay();
        $endDateTime   = Carbon::parse($dateTo)->endOfDay();

        /*
         * Filter type.
         * Reply = thread message dari user/operator.
         * Note = internal note.
         */
        $type = $request->get('type', '');

        $replyType = defined(Thread::class . '::TYPE_MESSAGE') ? Thread::TYPE_MESSAGE : 'message';
        $noteType  = defined(Thread::class . '::TYPE_NOTE') ? Thread::TYPE_NOTE : 'note';

        /*
         * Ambil conversation ID dari mailbox terpilih.
         * Kalau ada filter tag, conversation juga akan difilter berdasarkan tag.
         */
        $conversationQuery = Conversation::where('mailbox_id', $selectedMailboxId);

        /*
        * Filter berdasarkan field tambahan dari modul lain (opsional).
        * Jika user memilih filter tertentu, conversation yang tampil
        * hanya conversation yang sesuai filter tersebut.
        */
        $conversationQuery = \Eventy::filter('report.filter_conversation_query', $conversationQuery, $request);

        $mailboxConversationIds = $selectedMailboxId ? $conversationQuery->pluck('id')->toArray() : [];

        /*
         * Ambil thread/update operator sesuai mailbox, tanggal, type, dan tag.
         */
        $reportThreadsQuery = Thread::whereIn('conversation_id', $mailboxConversationIds)
            ->whereNotNull('created_by_user_id')
            ->whereBetween('created_at', [$startDateTime, $endDateTime]);

        /*
 * Jika setting mode = off, report tidak menghitung waktu.
 */
        if ($timeTrackingMode == 'off') {
            $reportThreadsQuery->whereRaw('1 = 0');
        }

        /*
 * Jika mode view, report tetap menghitung semua update operator.
 * Jika mode reply, report hanya menghitung reply operator.
 * Jika mode note, report hanya menghitung internal note.
 */
        if ($timeTrackingMode == 'reply') {
            $reportThreadsQuery->where('type', $replyType);
        } elseif ($timeTrackingMode == 'note') {
            $reportThreadsQuery->where('type', $noteType);
        }

        /*
 * Filter manual dari halaman report tetap boleh override tampilan report.
 */
        if ($type == 'reply') {
            $reportThreadsQuery->where('type', $replyType);
        } elseif ($type == 'note') {
            $reportThreadsQuery->where('type', $noteType);
        }

        $reportThreads = $reportThreadsQuery
            ->orderBy('created_at', 'asc')
            ->get();

        /*
 * Conversation untuk perhitungan time tracking.
 * Ini hanya conversation yang punya update/reply/note dari operator.
 */
        $trackedConversationIds = $reportThreads->pluck('conversation_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        /*
 * Conversation untuk ditampilkan di tabel.
 * Ini mengambil SEMUA conversation pada mailbox terpilih,
 * meskipun belum ada reply/update dari operator.
 */
        $displayConversationQuery = Conversation::where('mailbox_id', $selectedMailboxId)
            ->where(function ($query) use ($startDateTime, $endDateTime) {
                $query->whereBetween('created_at', [$startDateTime, $endDateTime])
                    ->orWhereBetween('updated_at', [$startDateTime, $endDateTime]);

                if (Schema::hasColumn('conversations', 'closed_at')) {
                    $query->orWhereBetween('closed_at', [$startDateTime, $endDateTime]);
                }
            });
        $displayConversationQuery = \Eventy::filter('report.filter_conversation_query', $displayConversationQuery, $request);

        $conversations = $displayConversationQuery
            ->orderBy('updated_at', 'desc')
            ->get();

        $displayConversationIds = $conversations->pluck('id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        /*
        * Gabungan conversation ID:
        * - trackedConversationIds untuk hitungan time spent
        * - displayConversationIds supaya conversation tanpa reply tetap tampil
        */
        $conversationIds = collect($trackedConversationIds)
            ->merge($displayConversationIds)
            ->unique()
            ->values()
            ->toArray();
        /*
 * Ambil data time tracking dari tabel time_tracking_logs.
 * Ini sumber utama report time tracking.
 */
        $timeLogs = collect();

        if (count($conversationIds)) {
            $timeLogs = \Eventy::filter('report.time_tracking.get_logs', collect(), $conversationIds, $startDateTime, $endDateTime);
        }

        /*
 * Mapping customer per conversation.
 */
        $conversationCustomerMap = $conversations->pluck('customer_id', 'id')->toArray();

        /*
 * Container statistik.
 */
        $userStats = [];
        $conversationStats = [];
        $customerStats = [];
        $dailyStats = [];

        $totalMinutes = 0;
        $totalUpdates = 0;

        /*
 * Hitung time spent berdasarkan time_tracking_logs.
 */
        foreach ($timeLogs as $log) {
            $convId = (int) $log->conversation_id;
            $userId = (int) $log->user_id;
            $customerId = isset($conversationCustomerMap[$convId]) ? $conversationCustomerMap[$convId] : null;
            $day = Carbon::parse($log->created_at)->format('Y-m-d');

            $minutes = round(((int) $log->seconds) / 60, 2);

            if (!isset($userStats[$userId])) {
                $userStats[$userId] = [
                    'minutes' => 0,
                    'updates' => 0,
                ];
            }

            $userStats[$userId]['minutes'] += $minutes;
            $userStats[$userId]['updates'] += 1;

            if (!isset($conversationStats[$convId])) {
                $conversationStats[$convId] = [
                    'minutes' => 0,
                    'updates' => 0,
                ];
            }

            $conversationStats[$convId]['minutes'] += $minutes;
            $conversationStats[$convId]['updates'] += 1;

            if ($customerId) {
                if (!isset($customerStats[$customerId])) {
                    $customerStats[$customerId] = [
                        'minutes' => 0,
                        'updates' => 0,
                    ];
                }

                $customerStats[$customerId]['minutes'] += $minutes;
                $customerStats[$customerId]['updates'] += 1;
            }

            if (!isset($dailyStats[$day])) {
                $dailyStats[$day] = [
                    'minutes' => 0,
                    'updates' => 0,
                ];
            }

            $dailyStats[$day]['minutes'] += $minutes;
            $dailyStats[$day]['updates'] += 1;

            $totalMinutes += $minutes;
            $totalUpdates += 1;
        }
        /*
         * Ambil semua user yang di-assign ke mailbox terpilih (termasuk yang belum punya log).
         */
        $mapUserStats = function ($user) use ($userStats) {
            $stats = isset($userStats[$user->id]) ? $userStats[$user->id] : ['minutes' => 0, 'updates' => 0];

            $user->time_spent_minutes = $stats['minutes'];
            $user->time_spent_hours = round($stats['minutes'] / 60, 2);
            $user->update_count = $stats['updates'];
            if ($stats['updates'] > 0) {
                $user->avg_hours_per_update = round(($stats['minutes'] / 60) / $stats['updates'], 2);
            } else {
                $user->avg_hours_per_update = 0;
            }

            $user->time_spent_label = $this->formatTimeSpent($stats['minutes']);

            $avgMinutes = $stats['updates'] > 0 ? $stats['minutes'] / $stats['updates'] : 0;
            $user->avg_time_spent_label = $this->formatTimeSpent($avgMinutes);

            return $user;
        };

        $userBaseQuery = $selectedMailboxId ? User::whereHas('mailboxes', function ($mq) use ($selectedMailboxId) { $mq->where('mailboxes.id', $selectedMailboxId); }) : User::whereIn('id', count($userStats) ? array_keys($userStats) : [0]);



        $users = $userBaseQuery
            ->orderBy('first_name', 'asc')
            ->orderBy('last_name', 'asc')
            ->get()
            ->map($mapUserStats);





        /*
         * Tambahkan statistik ke conversation.
         */
        $conversations = $conversations->map(function ($conversation) use ($conversationStats) {
            $conversationId = (int) $conversation->id;

            $stats = isset($conversationStats[$conversationId])
                ? $conversationStats[$conversationId]
                : ['minutes' => 0, 'updates' => 0];

            $conversation->time_spent_minutes = (float) $stats['minutes'];
            $conversation->time_spent_hours = round($stats['minutes'] / 60, 2);
            $conversation->update_count = (int) $stats['updates'];
            $conversation->time_spent_label = $this->formatTimeSpent($stats['minutes']);

            return $conversation;
        });

        /*
         * Ambil customer dari conversation yang masuk report.
         */
        $customers = collect();

        /*
 * Ambil semua customer dari conversation yang tampil.
 * Jadi customer tetap muncul meskipun conversation belum dibalas / belum ada update.
 */
        $customers = collect();

        $customerIds = $conversations->pluck('customer_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (count($customerIds)) {
            $customers = \App\Customer::whereIn('id', $customerIds)
                ->orderBy('first_name', 'asc')
                ->orderBy('last_name', 'asc')
                ->get()
                ->map(function ($customer) use ($customerStats) {
                    $stats = isset($customerStats[$customer->id])
                        ? $customerStats[$customer->id]
                        : ['minutes' => 0, 'updates' => 0];

                    $customer->time_spent_minutes = $stats['minutes'];
                    $customer->time_spent_hours = round($stats['minutes'] / 60, 2);
                    $customer->update_count = $stats['updates'];
                    $customer->time_spent_label = $this->formatTimeSpent($stats['minutes']);

                    return $customer;
                });
        }
        /*
         * Siapkan data grafik berdasarkan USER yang menindaklanjuti.
         */
        $chartLabels = [];
        $chartValues = [];

        foreach ($users as $user) {
            $userName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

            $chartLabels[] = $userName ?: $user->email;
            $chartValues[] = $user->time_spent_hours;
        }

        $maxChartValue = count($chartValues) ? max($chartValues) : 0;
        $chartMaxScale = $maxChartValue > 0 ? ceil($maxChartValue) : 1;
        $chartScaleLabels = [];
        for ($i = 10; $i >= 0; $i--) {
            $chartScaleLabels[] = round(($chartMaxScale / 10) * $i, 1);
        }

        /*
         * Siapkan data grafik harian (Daily Trend).
         */
        ksort($dailyStats);
        $dailyChartLabels = [];
        $dailyChartValues = [];
        $dailyReportData = [];

        foreach ($dailyStats as $day => $stats) {
            $dailyChartLabels[] = Carbon::parse($day)->format('M d');
            $hours = round($stats['minutes'] / 60, 2);
            $dailyChartValues[] = $hours;
            
            $dailyReportData[] = [
                'date' => Carbon::parse($day)->format('d M Y'),
                'raw_date' => $day,
                'minutes' => $stats['minutes'],
                'hours' => $hours,
                'updates' => $stats['updates'],
                'time_spent_label' => $this->formatTimeSpent($stats['minutes'])
            ];
        }

        // Sort descending for daily table
        usort($dailyReportData, function($a, $b) {
            return strcmp($b['raw_date'], $a['raw_date']);
        });

        $maxDailyValue = count($dailyChartValues) ? max($dailyChartValues) : 0;
        $dailyChartMaxScale = $maxDailyValue > 0 ? ceil($maxDailyValue) : 1;
        $dailyChartScaleLabels = [];
        for ($i = 10; $i >= 0; $i--) {
            $dailyChartScaleLabels[] = round(($dailyChartMaxScale / 10) * $i, 1);
        }

        /*
         * Leaderboard calculations
         */
        $activeUsers = $users->filter(function($u) { return $u->update_count > 0; });
        $fastestUser = $activeUsers->sortBy('avg_hours_per_update')->first();
        $slowestUser = $activeUsers->sortByDesc('avg_hours_per_update')->first();

        $activeConvs = $conversations->filter(function($c) { return $c->time_spent_minutes > 0; });
        $fastestConv = $activeConvs->sortBy('time_spent_minutes')->first();
        $slowestConv = $activeConvs->sortByDesc('time_spent_minutes')->first();

        $summary = [
            'total_hours' => round($totalMinutes / 60, 2),
            'avg_hours_per_update' => $totalUpdates > 0
                ? round(($totalMinutes / 60) / $totalUpdates, 2)
                : 0,
            'total_updates' => $totalUpdates,
            'total_conversations' => count($conversationIds),
            'total_users' => $users->count(),
            'total_customers' => count($customerIds),
            'fastest_user' => $fastestUser,
            'slowest_user' => $slowestUser,
            'fastest_conversation' => $fastestConv,
            'slowest_conversation' => $slowestConv,
        ];

        /*
         * Download CSV.
         */
        if ($request->get('export') == 'csv') {
            $csv = [];

            $csv[] = ['Time Tracking Report'];
            $csv[] = ['Mailbox', $selectedMailboxId];
            $csv[] = ['Date From', $dateFrom];
            $csv[] = ['Date To', $dateTo];
            $csv[] = ['Type', $type ?: 'All'];
            $csv[] = [];
            
            // Users Export
            $csv[] = ['User', 'Updates', 'Time Spent Hours', 'Avg Hours per Update'];
            foreach ($users as $user) {
                $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                $csv[] = [
                    $name ?: $user->email,
                    $user->update_count,
                    $user->time_spent_hours,
                    $user->avg_hours_per_update,
                ];
            }
            $csv[] = [];
            
            // Conversations Export
            $csv[] = ['Conversation Number', 'Subject', 'Updates', 'Time Spent Hours'];
            foreach ($conversations as $conversation) {
                if ($conversation->update_count > 0 || $conversation->time_spent_minutes > 0) {
                    $csv[] = [
                        $conversation->number ?: $conversation->id,
                        $conversation->subject ?: $conversation->preview,
                        $conversation->update_count,
                        $conversation->time_spent_hours
                    ];
                }
            }
            $csv[] = [];

            // Customers Export
            $csv[] = ['Customer Name', 'Email', 'Updates', 'Time Spent Hours'];
            foreach ($customers as $customer) {
                if ($customer->update_count > 0 || $customer->time_spent_minutes > 0) {
                    $name = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
                    $csv[] = [
                        $name,
                        $customer->getMainEmail(),
                        $customer->update_count,
                        $customer->time_spent_hours
                    ];
                }
            }
            $csv[] = [];

            // Daily Export
            $csv[] = ['Date', 'Updates', 'Time Spent Hours'];
            foreach ($dailyReportData as $day) {
                $csv[] = [
                    $day['date'],
                    $day['updates'],
                    $day['hours']
                ];
            }

            $content = '';
            foreach ($csv as $row) {
                $line = fopen('php://temp', 'r+');
                fputcsv($line, $row);
                rewind($line);
                $content .= stream_get_contents($line);
                fclose($line);
            }

            return response($content, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="time-tracking-report.csv"',
            ]);
        }

        return view('poliwangireport::reports.time_tracking', [
            'mailboxes'         => $mailboxes,
            'selectedMailboxId' => $selectedMailboxId,
            'dateFrom'          => $dateFrom,
            'dateTo'            => $dateTo,
            'range'             => $range,
            'type'              => $type,
            'users'             => $users,
            'conversations'     => $conversations,
            'customers'         => $customers,
            'summary'           => $summary,
            'chartLabels'       => $chartLabels,
            'chartValues'       => $chartValues,
            'maxChartValue'     => $maxChartValue,

            'chartMaxScale'     => $chartMaxScale,
            'chartScaleLabels'  => $chartScaleLabels,
            'timeTrackingSettings' => $timeTrackingSettings,
            'dailyChartLabels'  => $dailyChartLabels,
            'dailyChartValues'  => $dailyChartValues,
            'dailyChartMaxScale'=> $dailyChartMaxScale,
            'dailyChartScaleLabels' => $dailyChartScaleLabels,
            'dailyReportData'   => $dailyReportData,
        ]);
    }
    private function formatTimeSpent($minutes)
    {
        $minutes = (float) $minutes;

        if ($minutes <= 0) {
            return '-';
        }

        $totalSeconds = (int) round($minutes * 60);

        if ($totalSeconds < 60) {
            return $totalSeconds . ' seconds';
        }

        $hours = floor($totalSeconds / 3600);
        $remainingSeconds = $totalSeconds % 3600;
        $mins = floor($remainingSeconds / 60);

        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours . ' ' . ($hours == 1 ? 'hour' : 'hours');
        }

        if ($mins > 0) {
            $parts[] = $mins . ' ' . ($mins == 1 ? 'minute' : 'minutes');
        }

        return implode(' ', $parts);
    }
}
