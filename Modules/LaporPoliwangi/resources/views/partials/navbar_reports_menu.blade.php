<li class="dropdown {{ $isActive ? 'active' : '' }}">
    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false" aria-haspopup="true" v-pre>
        {{ __('Reports') }} <span class="caret"></span>
    </a>

    <ul class="dropdown-menu">
        <li class="{{ Route::currentRouteName() == 'laporpoliwangi.reports.time_tracking' ? 'active' : '' }}">
            <a href="{{ route('laporpoliwangi.reports.time_tracking') }}">
                {{ __('Time Tracking Report') }}
            </a>
        </li>
    </ul>
</li>
