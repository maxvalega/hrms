            <div class="col-12 mb-3" id="mark-attendance">
                <div class="card attendance-dashboard-card dash-section-card">
                    <div class="card-header">
                        <h5 class="dash-section-title"><i class="ti ti-clock"></i>{{ __('Mark Attendance') }}</h5>
                        <small class="dash-section-meta"><i class="ti ti-building me-1"></i>{{ __('Office Time') }}: {{ $officeTime['startTime'] ?? '09:00' }} – {{ $officeTime['endTime'] ?? '18:00' }}</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 attendance-actions">
                            <div class="col-md-6">
                                <div class="attendance-block attendance-clock-in rounded border p-3 h-100">
                                    <div class="attendance-label text-muted small text-uppercase mb-2">{{ __('Clock In') }}</div>
                                    @if (!empty($employeeAttendance) && !empty($employeeAttendance->clock_in))
                                        <div class="attendance-time-display">
                                            <span class="attendance-time-value">{{ \Auth::user()->timeFormat($employeeAttendance->clock_in) }}</span>
                                            <small class="d-block text-muted mt-1">{{ __('Clocked in – use Clock Out below') }}</small>
                                        </div>
                                    @else
                                        {{ Form::open(['url' => 'attendanceemployee/attendance', 'method' => 'post', 'id' => 'clockInForm', 'enctype' => 'multipart/form-data']) }}
                                        <input type="hidden" name="device_type" id="device_type" value="">
                                        <input type="hidden" name="latitude" id="latitude" value="">
                                        <input type="hidden" name="longitude" id="longitude" value="">
                                        <input type="hidden" name="address" id="address" value="">
                                        <input type="hidden" name="photo_base64" id="photo_base64" value="">
                                        <button type="button" id="clock_in" class="btn btn-primary w-100" data-hrms-open-clock-in="1">
                                            <i class="ti ti-login me-1"></i>{{ __('Clock In') }}
                                        </button>
                                        {{ Form::close() }}
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="attendance-block attendance-clock-out rounded border p-3 h-100">
                                    <div class="attendance-label text-muted small text-uppercase mb-2">{{ __('Clock Out') }}</div>
                                    @if (!empty($employeeAttendance) && (empty($employeeAttendance->clock_out) || $employeeAttendance->clock_out == '00:00:00'))
                                        {{ Form::model($employeeAttendance, ['route' => ['attendanceemployee.update', $employeeAttendance->id], 'method' => 'PUT', 'id' => 'clockOutForm']) }}
                                        <input type="hidden" name="device_type_out" id="device_type_out" value="">
                                        <input type="hidden" name="latitude_out" id="latitude_out" value="">
                                        <input type="hidden" name="longitude_out" id="longitude_out" value="">
                                        <input type="hidden" name="address_out" id="address_out" value="">
                                        <input type="hidden" name="photo_base64_out" id="photo_base64_out" value="">
                                        <input type="hidden" name="out" value="1">
                                        <button type="button" id="clock_out" class="btn btn-danger w-100" data-hrms-open-clock-out="1">
                                            <i class="ti ti-logout me-1"></i>{{ __('Clock Out') }}
                                        </button>
                                        {{ Form::close() }}
                                    @else
                                        <div class="attendance-time-display">
                                            <span class="attendance-time-value text-muted">{{ __('—') }}</span>
                                            <small class="d-block text-muted mt-1">{{ __('Clock in to start') }}</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @php
                            $todaySessions = $todaySessions ?? collect();
                            $todaySessionsCompleted = $todaySessions->filter(function ($s) {
                                $cout = trim((string) ($s->clock_out ?? '00:00:00'));
                                return $cout !== '' && $cout !== '00:00:00';
                            })->values();
                        @endphp
                        @if($todaySessionsCompleted->isNotEmpty())
                            <div class="mt-3 pt-3 border-top">
                                <h6 class="mb-2"><i class="ti ti-list me-1"></i>{{ __("Today's sessions") }}</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>{{ __('#') }}</th>
                                                <th>{{ __('Clock In') }}</th>
                                                <th>{{ __('Clock Out') }}</th>
                                                <th>{{ __('Duration') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $totalSeconds = 0; @endphp
                                            @foreach($todaySessionsCompleted as $idx => $sess)
                                                @php
                                                    $cin = trim((string) ($sess->clock_in ?? '00:00:00'));
                                                    $cout = trim((string) ($sess->clock_out ?? '00:00:00'));
                                                    if ($cin === '') { $cin = '00:00:00'; }
                                                    if ($cout === '' || $cout === '00:00:00') { $cout = '00:00:00'; }
                                                    $isOpen = ($cout === '00:00:00');
                                                    $diffSec = 0;
                                                    if (!$isOpen) {
                                                        try {
                                                            $t1 = \Carbon\Carbon::parse('1970-01-01 ' . $cin);
                                                            $t2 = \Carbon\Carbon::parse('1970-01-01 ' . $cout);
                                                            $diffSec = (int) $t1->diffInSeconds($t2, false);
                                                            if ($diffSec < 0) { $diffSec = 0; }
                                                            $totalSeconds += $diffSec;
                                                        } catch (\Throwable $e) {
                                                            $diffSec = 0;
                                                        }
                                                    }
                                                @endphp
                                                <tr>
                                                    <td>{{ $idx + 1 }}</td>
                                                    <td>{{ \Auth::user()->timeFormat($cin) }}</td>
                                                    <td>
                                                        @if($isOpen)
                                                            <span class="text-muted">{{ __('—') }}</span>
                                                        @else
                                                            {{ \Auth::user()->timeFormat($cout) }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($isOpen)
                                                            <span class="badge bg-warning text-dark">{{ __('In progress') }}</span>
                                                        @else
                                                            @php
                                                                $h = (int) floor($diffSec / 3600);
                                                                $m = (int) floor(($diffSec % 3600) / 60);
                                                                if ($h < 0) { $h = 0; }
                                                                if ($m < 0) { $m = 0; }
                                                            @endphp
                                                            {{ sprintf('%d h %02d m', $h, $m) }}
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th colspan="3" class="text-end">{{ __('Total time today') }}:</th>
                                                <th>
                                                    @php
                                                        $totalSeconds = max(0, (int) ($totalSeconds ?? 0));
                                                        $h = (int) floor($totalSeconds / 3600);
                                                        $m = (int) floor(($totalSeconds % 3600) / 60);
                                                    @endphp
                                                    {{ sprintf('%d h %02d m', $h, $m) }}
                                                </th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
