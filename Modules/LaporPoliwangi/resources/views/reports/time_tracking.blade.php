@extends('laporpoliwangi::layouts.app')

@section('title_full', __('Time Tracking Report'))

@section('module_content')
    <style>
        .tt-report-page {
            margin: -20px -15px 0 -15px;
            background: #fff;
            min-height: calc(100vh - 50px);
            font-family: Arial, sans-serif;
        }

        .tt-report-header {
            background: #d9eaf8;
            border-top: 1px solid #9fc4e4;
            padding: 14px 22px;
        }

        .tt-report-header-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            width: 100%;
        }

        .tt-report-title {
            flex: 0 0 auto;
            font-size: 22px;
            line-height: 1.5;
            color: #1f2d3d;
            font-weight: normal;
            margin: 0;
            white-space: nowrap;
        }

        .tt-filter-form {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
        }

        .tt-filter-main,
        .tt-filter-custom {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 10px;
        }

        .tt-filter-custom {
            margin-top: 0;
            padding-left: 0;
        }

        .tt-filter-form label {
            margin: 0 4px 0 10px;
            font-weight: normal;
            font-size: 13px;
            color: #1f2d3d;
            white-space: nowrap;
        }

        .tt-filter-form select,
        .tt-filter-form input {
            height: 34px;
            border: 1px solid #c9d3dd;
            border-radius: 3px;
            padding: 5px 8px;
            background: #fff;
            font-size: 13px;
            color: #333;
        }

        .tt-filter-form select {
            min-width: 92px;
        }

        .tt-filter-form .mailbox-select {
            min-width: 145px;
        }

        .tt-filter-form .tag-select {
            min-width: 145px;
        }

        .tt-filter-form .custom-select {
            min-width: 145px;
        }

        .tt-filter-form .custom-field-input {
            width: 150px;
        }

        .tt-filter-form .date-input {
            width: 115px;
        }

        .tt-filter-actions {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-left: 8px;
        }

        .tt-btn {
            height: 34px;
            min-width: 45px;
            border: 0;
            border-radius: 3px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            padding: 0 12px;
        }

        .tt-btn-blue {
            background: #0078d7;
            color: #fff;
        }

        .tt-btn-light {
            background: #f5f5f5;
            color: #111;
            border: 1px solid #c9d3dd;
        }

        .tt-summary {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 16px;
            padding: 24px;
            text-align: left;
            background: #fff;
            border-bottom: 1px solid #e1e8ed;
        }

        .tt-summary-card {
            background: #fff;
            border: 1px solid #eaebec;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            transition: transform 0.2s;
        }
        .tt-summary-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.04);
        }

        .tt-summary-label {
            color: #8c9bb0;
            font-size: 15px;
            margin-bottom: 4px;
        }

        .tt-summary-value {
            color: #1f2d3d;
            font-size: 30px;
            line-height: 32px;
            font-weight: normal;
        }

        .tt-summary-percent {
            color: #8c9bb0;
            font-size: 15px;
            margin-left: 4px;
        }

        .tt-chart {
            height: 300px;
            position: relative;
            margin-top: 8px;
            background: #fff;
            border: 1px solid #eaebec;
            border-radius: 8px;
            padding: 30px 20px 20px 60px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .tt-chart-grid {
            position: absolute;
            left: 28px;
            right: 28px;
            top: 0;
            bottom: 30px;
        }

        .tt-grid-line {
            position: absolute;
            left: 0;
            right: 0;
            border-top: 1px solid #e1e1e1;
            font-size: 12px;
            color: #666;
        }

        .tt-grid-line span {
            position: absolute;
            left: -25px;
            top: -8px;
        }

        .tt-chart-user {
            position: absolute;
            bottom: 7px;
            left: 50%;
            transform: translateX(-50%);
            color: #555;
            font-size: 12px;
        }

        .tt-content {
            padding: 12px 16px 24px 16px;
        }

        .tt-section {
            margin-bottom: 18px;
        }

        .tt-two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            align-items: start;
        }

        .tt-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
            font-size: 13px;
            border: 1px solid #ddd;
            background: #fff;
        }

        .tt-table thead th {
            background: #f7f7f7;
            border-bottom: 1px solid #ddd;
            padding: 7px 8px;
            color: #1f2d3d;
            font-weight: bold;
            text-align: left;
            text-transform: uppercase;
            font-size: 12px;
        }

        .tt-table tbody td {
            padding: 7px 8px;
            border-bottom: 1px solid #e5e5e5;
            color: #333;
            vertical-align: middle;
        }

        .tt-table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }

        .tt-table tbody tr:hover {
            background: #eef6ff;
        }

        .tt-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .tt-table a {
            color: #006dcc;
            text-decoration: none;
        }

        .tt-table a:hover {
            text-decoration: underline;
        }

        .tt-muted {
            color: #9aa7b7;
            font-size: 12px;
        }

        .tt-main-link {
            display: inline-block;
            color: #006dcc;
        }

        .tt-subtext {
            display: inline-block;
            margin-left: 4px;
            color: #9aa7b7;
            font-size: 12px;
        }

        .tt-time-main {
            color: #1f2d3d;
            font-weight: normal;
        }

        .tt-time-small {
            display: inline-block;
            margin-left: 5px;
            color: #9aa7b7;
            font-size: 12px;
        }

        .tt-chart-bars {
            position: absolute;
            left: 42px;
            right: 18px;
            top: 10px;
            bottom: 45px;
            display: flex;
            align-items: flex-end;
            gap: 8px;
            z-index: 2;
        }

        .tt-chart-bar-item {
            flex: 1;
            height: 100%;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            position: relative;
        }

        .tt-chart-bar {
            width: 70%;
            min-height: 2px;
            background: #0078d7;
            border-radius: 3px 3px 0 0;
        }

        .tt-chart-label {
            position: absolute;
            bottom: -24px;
            font-size: 11px;
            color: #555;
            white-space: nowrap;
        }

        .tt-chart-value {
            position: absolute;
            bottom: calc(100% + 5px);
            font-size: 11px;
            color: #333;
        }

        .tt-chart {
            height: 310px;
            border-bottom: 1px solid #cfcfcf;
            position: relative;
            margin-top: 8px;
            padding-left: 45px;
            padding-right: 18px;
            overflow: visible;
        }

        .tt-chart-grid {
            position: absolute;
            left: 45px;
            right: 18px;
            top: 10px;
            bottom: 45px;
        }

        .tt-grid-line {
            position: absolute;
            left: 0;
            right: 0;
            border-top: 1px solid #e1e1e1;
            font-size: 12px;
            color: #666;
        }

        .tt-grid-line span {
            position: absolute;
            left: -40px;
            top: -8px;
            width: 32px;
            text-align: right;
        }

        .tt-chart-bars {
            position: absolute;
            left: 45px;
            right: 18px;
            top: 10px;
            bottom: 45px;
            display: flex;
            align-items: flex-end;
            gap: 12px;
            z-index: 2;
        }

        .tt-chart-bar-item {
            flex: 1;
            height: 100%;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            position: relative;
            min-width: 60px;
        }

        .tt-chart-bar {
            width: 70%;
            min-height: 2px;
            background: #0078d7;
            border-radius: 3px 3px 0 0;
        }

        .tt-chart-label {
            position: absolute;
            bottom: -28px;
            font-size: 11px;
            color: #555;
            white-space: nowrap;
            max-width: 90px;
            overflow: hidden;
            text-overflow: ellipsis;
            text-align: center;
        }

        .tt-chart-value {
            position: absolute;
            bottom: calc(100% + 5px);
            font-size: 11px;
            color: #333;
        }

        .tt-filter-area .custom-field-input {
            min-width: 110px;
        }

        .tt-summary-percent {
            color: #8c9bb0;
            font-size: 15px;
            margin-left: 4px;
        }

        .tt-chart-bar {
            width: 65%;
            min-height: 2px;
            background: #42a5f5;
            border-radius: 2px 2px 0 0;
            position: relative;
            cursor: pointer;
        }

        .tt-chart-tooltip {
            position: absolute;
            left: 50%;
            top: -62px;
            transform: translateX(-50%);
            min-width: 70px;
            background: #fff;
            border: 1px solid #c9d3dd;
            border-radius: 4px;
            box-shadow: 0 2px 7px rgba(0, 0, 0, 0.25);
            padding: 6px 8px;
            font-size: 13px;
            color: #333;
            display: none;
            z-index: 10;
            text-align: left;
            white-space: nowrap;
        }

        .tt-chart-tooltip-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .tt-chart-tooltip-row {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .tt-chart-tooltip-color {
            width: 12px;
            height: 12px;
            background: #42a5f5;
            display: inline-block;
        }

        .tt-chart-bar:hover .tt-chart-tooltip {
            display: block;
        }



        @media (max-width: 1200px) {
            .tt-report-header-row {
                align-items: flex-start;
                flex-direction: column;
                gap: 10px;
            }

            .tt-filter-main,
            .tt-filter-custom {
                justify-content: flex-start;
                flex-wrap: wrap;
            }

            .tt-filter-custom {
                padding-left: 0;
            }
        }

        @media (max-width: 900px) {
            .tt-two-columns {
                grid-template-columns: 1fr;
            }
            .tt-summary {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 600px) {
            .tt-summary {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="tt-report-page">

        <div class="tt-report-header">
            <div class="tt-report-header-row">
                <h1 class="tt-report-title">{{ __('Time Tracking Report') }}</h1>

                <form method="GET" action="{{ route('laporpoliwangi.reports.time_tracking') }}" class="tt-filter-form">
                    {{-- BARIS PERTAMA: FILTER UTAMA --}}
                    <div class="tt-filter-main">
                        <label>Type</label>
                        <select name="type" onchange="this.form.submit()">
                            <option value="" {{ $type == '' ? 'selected' : '' }}>All</option>
                            <option value="reply" {{ $type == 'reply' ? 'selected' : '' }}>Reply</option>
                            <option value="note" {{ $type == 'note' ? 'selected' : '' }}>Note</option>
                        </select>

                        <label>Mailbox</label>
                        <select name="mailbox" class="mailbox-select" onchange="this.form.submit()">
                            @foreach ($mailboxes as $mailbox)
                                <option value="{{ $mailbox->id }}"
                                    {{ (int) $selectedMailboxId === (int) $mailbox->id ? 'selected' : '' }}>
                                    {{ $mailbox->name }}
                                </option>
                            @endforeach
                        </select>

                        <select name="range" class="custom-select" onchange="this.form.submit()">
                            <option value="custom" {{ $range == 'custom' ? 'selected' : '' }}>Custom</option>
                            <option value="today" {{ $range == 'today' ? 'selected' : '' }}>Today</option>
                            <option value="yesterday" {{ $range == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                            <option value="last_7_days" {{ $range == 'last_7_days' ? 'selected' : '' }}>Last 7 days
                            </option>
                            <option value="last_week" {{ $range == 'last_week' ? 'selected' : '' }}>Last Week</option>
                            <option value="month" {{ $range == 'month' ? 'selected' : '' }}>This Month</option>
                            <option value="last_month" {{ $range == 'last_month' ? 'selected' : '' }}>Last Month</option>
                            <option value="last_12_months" {{ $range == 'last_12_months' ? 'selected' : '' }}>Last 12
                                Months</option>
                            <option value="year" {{ $range == 'year' ? 'selected' : '' }}>This Year</option>
                        </select>

                        <input type="date" name="date_from" value="{{ $dateFrom }}" class="date-input"
                            onchange="this.form.submit()">

                        <input type="date" name="date_to" value="{{ $dateTo }}" class="date-input"
                            onchange="this.form.submit()">

                        <span class="tt-filter-actions">
                            <button type="submit" class="tt-btn tt-btn-blue" title="Refresh">
                                <i class="glyphicon glyphicon-refresh"></i>
                            </button>

                            <a href="{{ route('laporpoliwangi.reports.time_tracking') }}" class="tt-btn tt-btn-light"
                                title="Reset">
                                <i class="glyphicon glyphicon-remove"></i>
                            </a>

                            <button type="submit" name="export" value="csv" class="tt-btn tt-btn-blue"
                                title="Download">
                                <i class="glyphicon glyphicon-download-alt"></i>
                            </button>
                        </span>
                    </div>

                    {{-- BARIS KEDUA: CUSTOM FIELD --}}
                    @if ($customFields->count())
                        <div class="tt-filter-custom">
                            @foreach ($customFields as $customField)
                                @php
                                    $selectedValue = isset($selectedCustomFields[$customField->id])
                                        ? $selectedCustomFields[$customField->id]
                                        : '';
                                @endphp

                                @if (in_array($customField->type_field, ['dropdown', 'select', 'radio', 'tags', 'multiselect']))
                                    <label>{{ $customField->nama_field }}</label>

                                    <select name="custom_fields[{{ $customField->id }}]" class="custom-select"
                                        onchange="this.form.submit()">
                                        <option value="">All</option>

                                        @foreach ($customField->options_array as $option)
                                            @php
                                                $optionLabel = is_array($option)
                                                    ? $option['label'] ?? ($option['value'] ?? '')
                                                    : $option;

                                                $optionValue = is_array($option)
                                                    ? $option['value'] ?? ($option['label'] ?? '')
                                                    : $option;
                                            @endphp

                                            <option value="{{ $optionValue }}"
                                                {{ (string) $selectedValue === (string) $optionValue ? 'selected' : '' }}>
                                                {{ $optionLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <label>{{ $customField->nama_field }}</label>

                                    <input type="text" name="custom_fields[{{ $customField->id }}]"
                                        value="{{ $selectedValue }}" class="custom-field-input" placeholder="All"
                                        onchange="this.form.submit()">
                                @endif
                            @endforeach
                        </div>
                    @endif
                </form>
            </div>
        </div>

        <div class="tt-summary">
            <div class="tt-summary-card">
                <div class="tt-summary-label">Total Hours Spent</div>
                <div>
                    <span class="tt-summary-value">{{ number_format($summary['total_hours'], 2) }}</span>
                    <span class="tt-summary-percent">
                        {{ $summary['total_updates'] }} updates
                    </span>
                </div>
            </div>

            <div class="tt-summary-card">
                <div class="tt-summary-label">Avg. Hours Spent per Update</div>
                <div>
                    <span class="tt-summary-value">{{ number_format($summary['avg_hours_per_update'], 2) }}</span>
                    <span class="tt-summary-percent">
                        {{ $summary['total_conversations'] }} conversations
                    </span>
                </div>
            </div>

            <div class="tt-summary-card">
                <div class="tt-summary-label">Total Data Tracked</div>
                <div>
                    <span class="tt-summary-value">{{ $summary['total_conversations'] }}</span>
                    <span class="tt-summary-percent">
                        conversations by {{ $summary['total_users'] }} users
                    </span>
                </div>
            </div>

            <div class="tt-section tt-summary-card">
                <div class="tt-summary-label">Petugas Tercepat</div>
                <div>
                    <span class="tt-summary-value" style="font-size: 22px;">
                        @if($summary['fastest_user'])
                            {{ trim(($summary['fastest_user']->first_name ?? '') . ' ' . ($summary['fastest_user']->last_name ?? '')) ?: $summary['fastest_user']->email }}
                        @else
                            -
                        @endif
                    </span>
                    <div class="tt-summary-percent" style="margin-left: 0; margin-top: 4px;">
                        @if($summary['fastest_user'])
                            {{ number_format($summary['fastest_user']->avg_hours_per_update, 2) }} hrs/update
                        @endif
                    </div>
                </div>
            </div>

            <div class="tt-section tt-summary-card">
                <div class="tt-summary-label">Petugas Terlama</div>
                <div>
                    <span class="tt-summary-value" style="font-size: 22px;">
                        @if($summary['slowest_user'])
                            {{ trim(($summary['slowest_user']->first_name ?? '') . ' ' . ($summary['slowest_user']->last_name ?? '')) ?: $summary['slowest_user']->email }}
                        @else
                            -
                        @endif
                    </span>
                    <div class="tt-summary-percent" style="margin-left: 0; margin-top: 4px;">
                        @if($summary['slowest_user'])
                            {{ number_format($summary['slowest_user']->avg_hours_per_update, 2) }} hrs/update
                        @endif
                    </div>
                </div>
            </div>

            <div class="tt-summary-card">
                <div class="tt-summary-label">Fastest vs Slowest Conv.</div>
                <div>
                    <span class="tt-summary-value" style="font-size: 20px;">
                        @if($summary['fastest_conversation'] && $summary['slowest_conversation'])
                            <a href="{{ route('conversations.view', ['id' => $summary['fastest_conversation']->id]) }}">#{{ $summary['fastest_conversation']->number ?: $summary['fastest_conversation']->id }}</a>
                            vs
                            <a href="{{ route('conversations.view', ['id' => $summary['slowest_conversation']->id]) }}">#{{ $summary['slowest_conversation']->number ?: $summary['slowest_conversation']->id }}</a>
                        @else
                            -
                        @endif
                    </span>
                    <div class="tt-summary-percent" style="margin-left: 0; margin-top: 4px;">
                        @if($summary['fastest_conversation'] && $summary['slowest_conversation'])
                            {{ number_format($summary['fastest_conversation']->time_spent_hours, 1) }}h vs {{ number_format($summary['slowest_conversation']->time_spent_hours, 1) }}h
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="tt-content">

            <div class="tt-two-columns">
                <div class="tt-section" style="padding: 0; overflow: hidden;">
                    <div style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 12px 20px;">
                        <h3 style="margin: 0; font-size: 15px; color: #1e293b; font-weight: bold;">Tren Harian (Jam)</h3>
                    </div>
                    <div style="padding: 20px;">
                        <div class="tt-chart">
                            <div class="tt-chart-grid">
                                @foreach ($dailyChartScaleLabels as $index => $number)
                                    <div class="tt-grid-line" style="top: {{ $index * 10 }}%;">
                                        <span>{{ $number }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="tt-chart-bars">
                                @forelse ($dailyChartValues as $index => $value)
                                    @php
                                        $height = $dailyChartMaxScale > 0 ? ($value / $dailyChartMaxScale) * 100 : 0;
                                    @endphp

                                    <div class="tt-chart-bar-item">
                                        @if ($value > 0)
                                            <span class="tt-chart-value">{{ number_format($value, 1) }}</span>
                                        @endif

                                        <div class="tt-chart-bar" style="height: {{ $height }}%; background: #4caf50;">
                                            <div class="tt-chart-tooltip">
                                                <div class="tt-chart-tooltip-title">
                                                    {{ $dailyChartLabels[$index] }}
                                                </div>

                                                <div class="tt-chart-tooltip-row">
                                                    <span class="tt-chart-tooltip-color" style="background: #4caf50;"></span>
                                                    <span>{{ number_format($value, 1) }} hours</span>
                                                </div>
                                            </div>
                                        </div>

                                        <span class="tt-chart-label" title="{{ $dailyChartLabels[$index] }}">
                                            {{ $dailyChartLabels[$index] }}
                                        </span>
                                    </div>
                                @empty
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tt-section" style="padding: 0; overflow: hidden;">
                    <div style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 12px 20px;">
                        <h3 style="margin: 0; font-size: 15px; color: #1e293b; font-weight: bold;">Waktu Pengerjaan per Petugas</h3>
                    </div>
                    <div style="padding: 20px;">
                        <div class="tt-chart">
                            <div class="tt-chart-grid">
                                @foreach ($chartScaleLabels as $index => $number)
                                    <div class="tt-grid-line" style="top: {{ $index * 10 }}%;">
                                        <span>{{ $number }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="tt-chart-bars">
                                @forelse ($chartValues as $index => $value)
                                    @php
                                        $height = $chartMaxScale > 0 ? ($value / $chartMaxScale) * 100 : 0;
                                    @endphp

                                    <div class="tt-chart-bar-item">
                                        @if ($value > 0)
                                            <span class="tt-chart-value">{{ number_format($value, 1) }}</span>
                                        @endif

                                        <div class="tt-chart-bar" style="height: {{ $height }}%;">
                                            <div class="tt-chart-tooltip">
                                                <div class="tt-chart-tooltip-title">
                                                    {{ $chartLabels[$index] }}
                                                </div>

                                                <div class="tt-chart-tooltip-row">
                                                    <span class="tt-chart-tooltip-color"></span>
                                                    <span>{{ number_format($value, 1) }} hours</span>
                                                </div>
                                            </div>
                                        </div>

                                        <span class="tt-chart-label" title="{{ $chartLabels[$index] }}">
                                            {{ $chartLabels[$index] }}
                                        </span>
                                    </div>
                                @empty
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tt-two-columns">
                <div class="tt-section" style="padding: 0; overflow: hidden;">
                    <div style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 12px 20px;">
                        <h3 style="margin: 0; font-size: 15px; color: #1e293b; font-weight: bold;">Catatan Harian (Daily Log)</h3>
                    </div>
                    <table class="tt-table" style="margin: 0; border: none;">
                        <thead>
                            <tr>
                                <th style="width: 24%; border-top: none;">Tanggal</th>
                                <th style="width: 24%; border-top: none;">Time Spent</th>
                                <th style="border-top: none;">Total Updates</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($dailyReportData as $day)
                                <tr>
                                    <td>
                                        <strong>{{ $day['date'] }}</strong>
                                    </td>
                                    <td>
                                        @if ($day['minutes'] > 0)
                                            <span class="tt-time-main">
                                                {{ $day['time_spent_label'] ?? '-' }}
                                            </span>
                                        @else
                                            <span class="tt-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($day['updates'] > 0)
                                            <span class="tt-time-main">{{ $day['updates'] }} updates</span>
                                        @else
                                            <span class="tt-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="tt-muted">
                                        Belum ada data untuk filter ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="tt-section" style="padding: 0; overflow: hidden;">
                    <div style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 12px 20px;">
                        <h3 style="margin: 0; font-size: 15px; color: #1e293b; font-weight: bold;">Kinerja Petugas (Agent Performance)</h3>
                    </div>
                    <table class="tt-table" style="margin: 0; border: none;">
                        <thead>
                            <tr>
                                <th style="width: 24%; border-top: none;">Petugas</th>
                                <th style="width: 24%; border-top: none;">Time Spent</th>
                                <th style="border-top: none;">Rata-rata / Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr>
                                    <td>
                                        <a class="tt-main-link" href="{{ route('users.profile', ['id' => $user->id]) }}">
                                            {{ trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->email }}
                                        </a>
                                    </td>
                                    <td>
                                        @if (!empty($user->time_spent_minutes) && $user->time_spent_minutes > 0)
                                            <span class="tt-time-main">
                                                {{ $user->time_spent_label ?? '-' }}
                                            </span>
                                        @else
                                            <span class="tt-muted">-</span>
                                        @endif

                                        @if ($user->update_count > 0)
                                            <span class="tt-time-small">{{ $user->update_count }} updates</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($user->avg_time_spent_label ?? false)
                                            <span class="tt-time-main">{{ $user->avg_time_spent_label }}</span>
                                        @elseif ($user->avg_hours_per_update > 0)
                                            <span class="tt-time-main">{{ number_format($user->avg_hours_per_update, 2) }}
                                                hours/update</span>
                                        @else
                                            <span class="tt-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="tt-muted">
                                        Belum ada petugas yang menindaklanjuti pada filter ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tt-two-columns">
                <div class="tt-section" style="padding: 0; overflow: hidden;">
                    <div style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0; font-size: 15px; color: #1e293b; font-weight: bold;">
                            Daftar Laporan (Conversations)
                        </h3>
                        <div class="tt-filter-tabs" style="display: inline-flex; background: #e2e8f0; padding: 4px; border-radius: 8px; gap: 4px;">
                            <button type="button" class="tt-tab-btn active" data-target="all" style="border: none; background: #ffffff; color: #0ea5e9; font-weight: 700; padding: 6px 16px; font-size: 13px; border-radius: 6px; cursor: pointer; box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: all 0.2s;">Semua</button>
                            <button type="button" class="tt-tab-btn" data-target="done" style="border: none; background: transparent; color: #475569; font-weight: 600; padding: 6px 16px; font-size: 13px; border-radius: 6px; cursor: pointer; transition: all 0.2s;">Sudah Ditindaklanjuti</button>
                            <button type="button" class="tt-tab-btn" data-target="pending" style="border: none; background: transparent; color: #475569; font-weight: 600; padding: 6px 16px; font-size: 13px; border-radius: 6px; cursor: pointer; transition: all 0.2s;">Tertunda</button>
                        </div>
                    </div>
                    
                    <table class="tt-table" id="ttConversationTable" style="margin: 0; border: none;">
                        <thead>
                            <tr>
                                <th style="border-top: none;">Laporan (Conversation)</th>
                                <th style="width: 36%; border-top: none;">Time Spent</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($conversations as $conversation)
                                @php
                                    $isDone = !empty($conversation->time_spent_minutes) && $conversation->time_spent_minutes > 0;
                                @endphp
                                <tr class="tt-conv-row" data-status="{{ $isDone ? 'done' : 'pending' }}">
                                    <td>
                                        <a class="tt-main-link"
                                            href="{{ route('conversations.view', ['id' => $conversation->id]) }}">
                                            #{{ $conversation->number ?: $conversation->id }}
                                        </a>

                                        <span class="tt-subtext">
                                            {{ $conversation->subject ?: $conversation->preview }}
                                        </span>

                                        @if ($conversation->update_count > 0)
                                            <span class="tt-subtext">
                                                {{ $conversation->update_count }} updates
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($isDone)
                                            <span class="tt-time-main">
                                                {{ $conversation->time_spent_label ?? '-' }}
                                            </span>
                                        @else
                                            <span class="tt-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="tt-muted">
                                        Belum ada conversation pada filter ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <script {!! \Helper::cspNonceAttr() !!}>
                        document.addEventListener('DOMContentLoaded', () => {
                            const btns = document.querySelectorAll('.tt-tab-btn');
                            const rows = document.querySelectorAll('.tt-conv-row');

                            btns.forEach(btn => {
                                btn.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    const status = this.getAttribute('data-target');

                                    // Reset all buttons styling
                                    btns.forEach(b => {
                                        b.style.background = 'transparent';
                                        b.style.color = '#475569';
                                        b.style.fontWeight = '600';
                                        b.style.boxShadow = 'none';
                                        b.classList.remove('active');
                                    });

                                    // Set active button styling
                                    this.style.background = '#ffffff';
                                    this.style.color = '#0ea5e9';
                                    this.style.fontWeight = '700';
                                    this.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
                                    this.classList.add('active');

                                    // Filter rows
                                    rows.forEach(row => {
                                        if (status === 'all') {
                                            row.style.display = '';
                                        } else {
                                            if (row.getAttribute('data-status') === status) {
                                                row.style.display = '';
                                            } else {
                                                row.style.display = 'none';
                                            }
                                        }
                                    });
                                });
                            });
                        });
                    </script>
                </div>

                <div class="tt-section" style="padding: 0; overflow: hidden;">
                    <div style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 12px 20px;">
                        <h3 style="margin: 0; font-size: 15px; color: #1e293b; font-weight: bold;">Pengguna / Pelapor (Customer)</h3>
                    </div>
                    <table class="tt-table" style="margin: 0; border: none;">
                        <thead>
                            <tr>
                                <th style="border-top: none;">Customer</th>
                                <th style="width: 36%; border-top: none;">Time Spent</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($customers as $customer)
                                <tr>
                                    <td>
                                        @php
                                            $customerName = trim(
                                                ($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''),
                                            );
                                        @endphp

                                        <a class="tt-main-link"
                                            href="{{ route('customers.conversations', ['id' => $customer->id]) }}">
                                            {{ $customerName ?: 'Customer #' . $customer->id }}
                                        </a>

                                        @if ($customer->update_count > 0)
                                            <span class="tt-subtext">
                                                {{ $customer->update_count }} updates
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (!empty($customer->time_spent_minutes) && $customer->time_spent_minutes > 0)
                                            <span class="tt-time-main">
                                                {{ $customer->time_spent_label ?? '-' }}
                                            </span>
                                        @else
                                            <span class="tt-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="tt-muted">
                                        Belum ada customer pada filter ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
@endsection
