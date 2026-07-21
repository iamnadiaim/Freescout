<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>My Tickets - Poliwangi Portal</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f4f4;
            color: #2f3d4a;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .my-ticket-page {
            flex: 1;
            padding: 18px 20px 30px;
            border-left: 1px solid #d9d9d9;
            border-right: 1px solid #d9d9d9;
            border-bottom: 1px solid #d9d9d9;
            background: #f5f5f5;
        }

        .page-title {
            text-align: center;
            font-size: 18px;
            font-weight: normal;
            color: #3d4b57;
            margin: 0 0 20px;
        }

        .top-action {
            margin-bottom: 16px;
        }

        .ticket-table-wrapper {
            background: #ffffff;
            border: 1px solid #d9e0e7;
        }

        .ticket-table {
            width: 100%;
            border-collapse: collapse;
        }

        .ticket-table thead th {
            background: #dce9f5;
            color: #2e5d8a;
            font-size: 14px;
            font-weight: bold;
            text-align: left;
            padding: 10px 14px;
        }

        .ticket-table tbody td {
            padding: 10px 14px;
            vertical-align: middle;
            border-top: 1px solid #e8edf2;
            font-size: 14px;
            color: #5a6773;
        }

        .ticket-main-title {
            display: flex;
            align-items: center;
            gap: 7px;
            flex-wrap: wrap;
            font-size: 14px;
            font-weight: bold;
            color: #445d78;
            margin-bottom: 5px;
        }

        .ticket-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 2px 7px;
            border-radius: 12px;
            background: #eef4fb;
            border: 1px solid #d8e6f3;
            color: #2e5d8a;
            font-size: 12px;
            font-weight: normal;
            line-height: 1.4;
        }

        .mailbox-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 2px 8px;
            border-radius: 12px;
            background: #eaf7ef;
            border: 1px solid #ccebd8;
            color: #287a45;
            font-size: 12px;
            font-weight: normal;
            line-height: 1.4;
        }

        .ticket-subject {
            color: #445d78;
        }

        .ticket-preview {
            font-size: 13px;
            color: #a0a9b3;
            line-height: 1.5;
        }

        .status-badge {
            display: inline-block;
            font-size: 13px;
            color: #9aa6b2;
        }

        .status-closed {
            color: #7f8c97;
            font-weight: bold;
        }

        .status-open {
            color: #0a84df;
            font-weight: bold;
        }

        .activity-badge {
            display: inline-block;
            background: #edf1f5;
            color: #556270;
            font-size: 13px;
            font-weight: bold;
            padding: 8px 14px;
            border-radius: 18px;
            white-space: nowrap;
        }

        .ticket-meta-icons {
            display: inline-flex;
            gap: 8px;
            margin-left: 5px;
            vertical-align: middle;
            color: #a3acb5;
            font-size: 13px;
        }

        .ticket-count-box {
            display: inline-block;
            min-width: 18px;
            height: 18px;
            line-height: 18px;
            text-align: center;
            border: 1px solid #d6dde5;
            border-radius: 4px;
            font-size: 11px;
            color: #9da7b1;
            background: #f8fafc;
        }

        .no-ticket {
            text-align: center;
            padding: 30px 15px;
            color: #8b97a3;
            font-size: 14px;
        }

        .ticket-row {
            cursor: pointer;
            transition: background .15s ease-in-out;
        }

        .ticket-row:hover {
            background: #f5faff;
        }

        @media (max-width: 768px) {
            .my-ticket-page {
                padding: 15px 10px 25px;
            }

            .ticket-table thead {
                display: none;
            }

            .ticket-table,
            .ticket-table tbody,
            .ticket-table tr,
            .ticket-table td {
                display: block;
                width: 100%;
            }

            .ticket-table tbody tr {
                border-top: 1px solid #e8edf2;
                padding: 10px 0;
            }

            .ticket-table tbody td {
                border-top: none;
                padding: 8px 12px;
            }

            .activity-badge {
                border-radius: 10px;
            }
        }
    </style>
</head>

<body>
    @include('poliwangiportal::end_user_portal.partials.navbar', [
        'mailbox' => null,
        'email' => $email ?? session('end_user_portal_email'),
    ])

    <div class="my-ticket-page">
        <h1 class="page-title">My Tickets</h1>

        <div class="ticket-table-wrapper">
            <table class="ticket-table">
                <thead>
                    <tr>
                        <th style="width:65%;">Ticket</th>
                        <th style="width:15%;">Status</th>
                        <th style="width:20%;">Last Activity</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($tickets as $ticket)
                        <tr class="ticket-row"
                            onclick="window.location='{{ route('PoliwangiPortal.end_user_portal.ticket_detail', [$ticket['mailbox_id'], $ticket['id']]) }}'">
                            <td>
                                <div class="ticket-main-title">
                                    <span class="mailbox-badge">
                                        {{ $ticket['mailbox_name'] ?? '-' }}
                                    </span>

                                    @if (!empty($ticket['show_ticket_number']) && !empty($ticket['number']))
                                        <span class="ticket-number">
                                            #{{ $ticket['number'] }}
                                        </span>
                                    @endif

                                    <span class="ticket-subject">
                                        {{ $ticket['subject'] }}
                                    </span>

                                    @if (!empty($ticket['count']))
                                        <span class="ticket-meta-icons">
                                            <span class="ticket-count-box">
                                                {{ $ticket['count'] }}
                                            </span>
                                        </span>
                                    @endif
                                </div>

                                <div class="ticket-preview">
                                    {{ $ticket['preview'] }}
                                </div>
                            </td>

                            <td>
                                <span
                                    class="status-badge {{ $ticket['status'] == 'Closed' ? 'status-closed' : 'status-open' }}">
                                    {{ $ticket['status'] }}
                                </span>
                            </td>

                            <td>
                                <span class="activity-badge">
                                    {{ $ticket['last_activity'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">
                                <div class="no-ticket">
                                    Belum ada tiket.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('poliwangiportal::end_user_portal.partials.footer', [
        'mailbox' => null,
        'setting' => null,
    ])

</body>

</html>
