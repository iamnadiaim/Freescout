@if (Auth::user())
    <li class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false" aria-haspopup="true" v-pre>
            {{ __('Lapor Poliwangi') }} <span class="caret"></span>
        </a>

        <ul class="dropdown-menu">
            <li>
                <a href="{{ route('laporpoliwangi.time_tracking_report.index') }}">
                    {{ __('Time Tracking Report') }}
                </a>
            </li>

            <li>
                <a href="{{ route('laporpoliwangi.notification_channels.index') }}">
                    {{ __('Notification Channels') }}
                </a>
            </li>
        </ul>
    </li>
@endif
