@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Attendance List') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Attendance List') }}</li>
@endsection

@push('css-page')
    <style>
        .attendance-kpi-card {
            border: 1px solid var(--bs-border-color);
            border-radius: 0.75rem;
            padding: 1rem;
            background: var(--bs-body-bg);
            height: 100%;
        }

        .attendance-kpi-card.kpi-clickable {
            cursor: pointer;
            transition: all .15s ease;
        }

        .attendance-kpi-card.kpi-clickable:hover {
            border-color: var(--bs-primary);
        }

        .attendance-kpi-card.kpi-active {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 1px var(--bs-primary);
        }

        .attendance-kpi-label {
            font-size: 0.75rem;
            color: var(--bs-secondary-color);
            margin-bottom: 0.35rem;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .attendance-kpi-value {
            font-size: 1.4rem;
            font-weight: 700;
            line-height: 1.1;
            margin: 0;
        }

        .attendance-chart-card {
            border: 1px solid var(--bs-border-color);
            border-radius: 0.75rem;
            padding: 1rem;
            height: 100%;
            background: var(--bs-body-bg);
        }

        .attendance-employee-table tbody tr td {
            vertical-align: middle;
        }

        #pending-swipe-requests .table-responsive {
            overflow-x: auto;
        }

        #pending-swipe-requests .table {
            min-width: 700px;
        }

        /* ── Month/Year Picker ── */
        .att-month-picker { position: relative; }
        .att-month-picker .att-mp-toggle {
            display: flex; align-items: center; gap: 6px;
            padding: 6px 12px; border: 1px solid #e2e8f0; border-radius: 8px;
            background: #fff; cursor: pointer; font-size: .85rem; font-weight: 500;
            color: #334155; transition: all .15s; min-width: 140px;
        }
        .att-month-picker .att-mp-toggle:hover { border-color: #4361ee; color: #4361ee; }
        .att-month-picker .att-mp-toggle.open { border-color: #4361ee; box-shadow: 0 0 0 2px rgba(67,97,238,.15); }

        .att-mp-dropdown {
            display: none; position: absolute; top: calc(100% + 6px); left: 0; z-index: 1050;
            background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,.12); padding: 16px; width: 260px;
        }
        .att-mp-dropdown.show { display: block; }

        .att-mp-year { display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 12px; }
        .att-mp-year .yr-nav {
            width: 30px; height: 30px; border-radius: 50%; border: 1px solid #e2e8f0;
            background: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center;
            font-size: .85rem; color: #475569; transition: all .15s;
        }
        .att-mp-year .yr-nav:hover { background: #4361ee; color: #fff; border-color: #4361ee; }
        .att-mp-year .yr-label { font-size: 1.1rem; font-weight: 700; color: #1e293b; min-width: 50px; text-align: center; }

        .att-mp-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 5px; }
        .att-mp-grid .mg-btn {
            padding: 7px 4px; border: 1px solid #e2e8f0; border-radius: 7px; text-align: center;
            cursor: pointer; font-size: .76rem; font-weight: 500; transition: all .15s;
            background: #fff; color: #334155;
        }
        .att-mp-grid .mg-btn:hover { border-color: #4361ee; background: #eff6ff; color: #4361ee; }
        .att-mp-grid .mg-btn.active { border-color: #4361ee; background: #4361ee; color: #fff; font-weight: 700; }
        .att-mp-grid .mg-btn.today { border-color: #059669; }
        .att-mp-grid .mg-btn.today::after {
            content: ''; display: block; width: 4px; height: 4px;
            background: #059669; border-radius: 50%; margin: 2px auto 0;
        }
    </style>
@endpush


@push('script-page')
    <script>
        $('input[name="type"]:radio').on('change', function(e) {
            var type = $(this).val();

            if (type == 'monthly') {
                $('.month').addClass('d-block');
                $('.month').removeClass('d-none');
                $('.date').addClass('d-none');
                $('.date').removeClass('d-block');
            } else {
                $('.date').addClass('d-block');
                $('.date').removeClass('d-none');
                $('.month').addClass('d-none');
                $('.month').removeClass('d-block');
            }
        });

        $('input[name="type"]:radio:checked').trigger('change');
    </script>

    {{-- Month/Year Picker --}}
    <script>
    (function(){
        const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        const fullMonths = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        const now = new Date();
        const todayKey = now.getFullYear() + '-' + String(now.getMonth()+1).padStart(2,'0');

        let curVal = document.getElementById('attMonthHidden').value || todayKey;
        let parts = curVal.split('-');
        let pickerYear = parseInt(parts[0]);
        let pickerMonth = parseInt(parts[1]);

        const toggle = document.getElementById('attMpToggle');
        const dropdown = document.getElementById('attMpDropdown');
        const label = document.getElementById('attMpLabel');
        const yrLabel = document.getElementById('attYrLabel');
        const grid = document.getElementById('attMonthGrid');
        const hidden = document.getElementById('attMonthHidden');

        function render(){
            yrLabel.textContent = pickerYear;
            grid.innerHTML = '';
            for(let m=1; m<=12; m++){
                const mPad = String(m).padStart(2,'0');
                const key = pickerYear + '-' + mPad;
                const btn = document.createElement('div');
                btn.className = 'mg-btn';
                btn.textContent = months[m-1];
                if(m === pickerMonth && pickerYear === parseInt(hidden.value.split('-')[0])) btn.classList.add('active');
                if(key === todayKey) btn.classList.add('today');
                btn.addEventListener('click', function(){
                    pickerMonth = m;
                    hidden.value = pickerYear + '-' + mPad;
                    label.textContent = fullMonths[m-1] + ' ' + pickerYear;
                    // sync month to reapply/sync forms
                    var rpMonth = document.querySelector('#reapplyPolicyForm input[name="month"]');
                    var spMonth = document.querySelector('#syncPayrollForm input[name="month"]');
                    if(rpMonth) rpMonth.value = hidden.value;
                    if(spMonth) spMonth.value = hidden.value;
                    render();
                    // auto-close after selection
                    setTimeout(function(){ dropdown.classList.remove('show'); toggle.classList.remove('open'); }, 150);
                });
                grid.appendChild(btn);
            }
            label.textContent = fullMonths[pickerMonth-1] + ' ' + pickerYear;
        }

        window.attYearNav = function(dir){
            pickerYear += dir;
            // Update hidden value when year changes so form submits correct year
            var mPad = String(pickerMonth).padStart(2,'0');
            hidden.value = pickerYear + '-' + mPad;
            render();
        };

        toggle.addEventListener('click', function(e){
            e.stopPropagation();
            dropdown.classList.toggle('show');
            toggle.classList.toggle('open');
        });

        document.addEventListener('click', function(e){
            if(!document.getElementById('attMonthPicker').contains(e.target)){
                dropdown.classList.remove('show');
                toggle.classList.remove('open');
            }
        });

        render();
    })();
    </script>

    <script>
        $(document).ready(function() {
            var b_id = $('#branch_id').val();
            // getDepartment(b_id);
        });
        $(document).on('change', 'select[name=branch]', function() {
            var branch_id = $(this).val();

            getDepartment(branch_id);
        });

        function getDepartment(bid) {

            $.ajax({
                url: '{{ route('monthly.getdepartment') }}',
                type: 'POST',
                data: {
                    "branch_id": bid,
                    "_token": "{{ csrf_token() }}",
                },
                success: function(data) {

                    $('.department_id').empty();
                    var emp_selct = `<select class="form-control department_id" name="department_id" id="choices-multiple"
                                            placeholder="{{__('Select Department')}}" >
                                            </select>`;
                    $('.department_div').html(emp_selct);

                    $('.department_id').append('<option value=""> {{ __('Select Department') }} </option>');
                    $.each(data, function(key, value) {
                        $('.department_id').append('<option value="' + key + '">' + value +
                            '</option>');
                    });
                    new Choices('#choices-multiple', {
                        removeItemButton: true,
                    });
                }
            });
        }
    </script>

    <script src="{{ asset('assets/js/plugins/apexcharts.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var uploadBtn = document.getElementById('attendanceExcelUploadBtn');
            var uploadInput = document.getElementById('attendanceExcelUploadInput');
            var uploadForm = document.getElementById('attendanceExcelUploadForm');
            if (uploadBtn && uploadInput && uploadForm) {
                uploadBtn.addEventListener('click', function() {
                    uploadInput.click();
                });
                uploadInput.addEventListener('change', function() {
                    if (uploadInput.files.length > 0) {
                        uploadForm.submit();
                    }
                });
            }

            // Reapply Policy button
            var reapplyBtn = document.getElementById('reapplyPolicyBtn');
            if (reapplyBtn) {
                reapplyBtn.addEventListener('click', function() {
                    var monthLabel = document.getElementById('attMpLabel') ? document.getElementById('attMpLabel').textContent : '';
                    if (confirm('Reapply attendance policy for all employees in ' + monthLabel + '? This will recalculate marks & deduction units.')) {
                        document.getElementById('reapplyPolicyForm').submit();
                    }
                });
            }

            // Sync for Payroll button
            var syncBtn = document.getElementById('syncPayrollBtn');
            if (syncBtn) {
                syncBtn.addEventListener('click', function() {
                    var monthLabel = document.getElementById('attMpLabel') ? document.getElementById('attMpLabel').textContent : '';
                    if (confirm('Sync attendance for ' + monthLabel + '?')) {
                        document.getElementById('syncPayrollForm').submit();
                    }
                });
            }

            if (typeof ApexCharts === 'undefined') {
                return;
            }

            const analytics = @json($attendanceAnalytics ?? []);
            if (!analytics || Object.keys(analytics).length === 0) {
                return;
            }

            const statusChartElement = document.querySelector('#attendance-status-chart');
            const employeeChartElement = document.querySelector('#attendance-employee-chart');
            if (!statusChartElement || !employeeChartElement) {
                return;
            }

            const css = getComputedStyle(document.documentElement);
            const primaryColor = (css.getPropertyValue('--bs-primary') || '#51459d').trim();
            const successColor = (css.getPropertyValue('--bs-success') || '#2ca58d').trim();
            const warningColor = (css.getPropertyValue('--bs-warning') || '#f0ad4e').trim();
            const dangerColor = (css.getPropertyValue('--bs-danger') || '#d9534f').trim();
            const infoColor = (css.getPropertyValue('--bs-info') || '#17a2b8').trim();

            const statusSeries = [
                Number(analytics.present_days || 0),
                Number(analytics.half_day_days || 0),
                Number(analytics.absent_leave_days || 0),
            ];

            if (window.attendanceStatusChart) {
                window.attendanceStatusChart.destroy();
            }

            window.attendanceStatusChart = new ApexCharts(statusChartElement, {
                chart: {
                    type: 'donut',
                    height: 300,
                    toolbar: {
                        show: false,
                    },
                },
                labels: [
                    "{{ __('Present') }}",
                    "{{ __('Half Day') }}",
                    "{{ __('Absent/Leave') }}",
                ],
                series: statusSeries,
                colors: [successColor, warningColor, dangerColor],
                legend: {
                    position: 'bottom',
                },
                dataLabels: {
                    enabled: true,
                },
                stroke: {
                    width: 2,
                },
            });
            window.attendanceStatusChart.render();

            const breakdown = Array.isArray(analytics.employee_breakdown) ? analytics.employee_breakdown : [];
            const topEmployees = breakdown.slice().sort(function(a, b) {
                return Number(b.total_deduction_units || 0) - Number(a.total_deduction_units || 0);
            }).slice(0, 8);

            let chartOptions;

            if (topEmployees.length > 0) {
                chartOptions = {
                    chart: {
                        type: 'bar',
                        height: 300,
                        stacked: true,
                        toolbar: {
                            show: false,
                        },
                    },
                    plotOptions: {
                        bar: {
                            borderRadius: 4,
                            columnWidth: '55%',
                        },
                    },
                    series: [{
                            name: "{{ __('Late In') }}",
                            data: topEmployees.map(function(item) {
                                return Number(item.late_marks || 0);
                            }),
                        },
                        {
                            name: "{{ __('Early Out') }}",
                            data: topEmployees.map(function(item) {
                                return Number(item.early_marks || 0);
                            }),
                        },
                        {
                            name: "{{ __('Less Hours Marks') }}",
                            data: topEmployees.map(function(item) {
                                return Number(item.less_hours_marks || 0);
                            }),
                        }
                    ],
                    xaxis: {
                        categories: topEmployees.map(function(item) {
                            return item.employee_name;
                        }),
                        labels: {
                            rotate: -15,
                            trim: true,
                        },
                    },
                    yaxis: {
                        title: {
                            text: "{{ __('Marks Count') }}",
                        },
                    },
                    colors: [primaryColor, infoColor, warningColor],
                    legend: {
                        position: 'bottom',
                    },
                    dataLabels: {
                        enabled: false,
                    },
                    tooltip: {
                        shared: true,
                        intersect: false,
                    },
                };
            } else {
                chartOptions = {
                    chart: {
                        type: 'bar',
                        height: 300,
                        toolbar: {
                            show: false,
                        },
                    },
                    series: [{
                        name: "{{ __('Marks') }}",
                        data: [0],
                    }],
                    xaxis: {
                        categories: ["{{ __('No Data') }}"],
                    },
                    colors: [primaryColor],
                    dataLabels: {
                        enabled: false,
                    },
                };
            }

            if (window.attendanceEmployeeChart) {
                window.attendanceEmployeeChart.destroy();
            }

            window.attendanceEmployeeChart = new ApexCharts(employeeChartElement, chartOptions);
            window.attendanceEmployeeChart.render();
        });
    </script>

    <script>
        // Delay to run AFTER simpleDatatables has initialized
        setTimeout(function() {
            const cards = document.querySelectorAll('.attendance-kpi-card.kpi-clickable');
            const resetBtn = document.getElementById('attendanceKpiReset');
            const filterLabel = document.getElementById('attendanceKpiFilterLabel');

            function getRows() {
                return document.querySelectorAll('#attendance-data-table tbody tr.attendance-row');
            }

            if (!cards.length || !getRows().length) {
                return;
            }

            function isMatch(row, key) {
                const status = (row.dataset.status || '').toLowerCase();
                const lateMark = row.dataset.lateMark === '1';
                const earlyMark = row.dataset.earlyMark === '1';
                const lessHoursMark = row.dataset.lessHoursMark === '1';
                const deductionUnits = parseFloat(row.dataset.deductionUnits || '0');

                switch (key) {
                    case 'present_days':
                        return status === 'present';
                    case 'half_day_days':
                        return status === 'half day';
                    case 'absent_leave_days':
                        return status === 'absent' || status === 'leave';
                    case 'late_marks':
                        return lateMark;
                    case 'early_marks':
                        return earlyMark;
                    case 'less_hours_marks':
                        return lessHoursMark;
                    case 'total_deduction_units':
                        return deductionUnits > 0;
                    case 'direct_late_half_day_dates':
                        return row.dataset.directLateHalfDay === '1';
                    case 'direct_early_half_day_dates':
                        return row.dataset.directEarlyHalfDay === '1';
                    case 'exempt_mark_dates':
                        return row.dataset.exemptMark === '1';
                    case 'post_exemption_mark_dates':
                        return row.dataset.postExemptionMark === '1';
                    case 'policy_deduction_sets':
                        return row.dataset.policyDeductionSet === '1';
                    case 'pending_mark_dates':
                        return row.dataset.pendingMark === '1';
                    case 'total_records':
                    default:
                        return true;
                }
            }

            function applyFilter(key, label) {
                cards.forEach(card => card.classList.remove('kpi-active'));
                const activeCard = document.querySelector('.attendance-kpi-card.kpi-clickable[data-kpi="' + key + '"]');
                if (activeCard) {
                    activeCard.classList.add('kpi-active');
                }

                getRows().forEach(function(row) {
                    row.style.display = isMatch(row, key) ? '' : 'none';
                });

                if (filterLabel) {
                    filterLabel.textContent = label ? ('Filtered: ' + label) : '';
                    filterLabel.classList.toggle('d-none', !label);
                }
                if (resetBtn) {
                    resetBtn.classList.toggle('d-none', !label || key === 'total_records');
                }

                const tableCard = document.getElementById('attendanceTableCard');
                if (tableCard) {
                    tableCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }

            cards.forEach(function(card) {
                card.addEventListener('click', function() {
                    applyFilter(card.dataset.kpi || 'total_records', card.dataset.kpiLabel || '');
                });
            });

            if (resetBtn) {
                resetBtn.addEventListener('click', function() {
                    applyFilter('total_records', '');
                });
            }
        }, 800);
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var tbl = document.getElementById('attendance-data-table');
            if (tbl && typeof simpleDatatables !== 'undefined') {
                // Inject sort-friendly invisible prefix into date cells before init
                var dateCol = document.querySelectorAll('#attendance-data-table tbody td[data-sort]');
                dateCol.forEach(function(td) {
                    var sortKey = td.getAttribute('data-sort') || '';
                    td.innerHTML = '<span style="display:none;">' + sortKey + '</span>' + td.innerHTML;
                });

                window.attendanceDT = new simpleDatatables.DataTable("#attendance-data-table", {
                    paging: false,
                    perPage: 0,
                    perPageSelect: false,
                    searchable: false,
                    sortable: true,
                    labels: {
                        noRows: "{{ __('No entries found') }}",
                    }
                });
            }
        });
    </script>

    {{-- Custom dropdown filters --}}
    <script>
    setTimeout(function() {
        function getRows() {
            return document.querySelectorAll('#attendance-data-table tbody tr.attendance-row');
        }
        var rows = getRows();
        if (!rows.length) return;

        // Populate employee dropdown from data attributes
        var empSel = document.getElementById('attn-filter-employee');
        if (empSel) {
            var names = new Set();
            rows.forEach(function(r) {
                var n = (r.dataset.employeeName || '').trim();
                if (n) names.add(n);
            });
            Array.from(names).sort().forEach(function(n) {
                var o = document.createElement('option');
                o.value = n; o.textContent = n;
                empSel.appendChild(o);
            });
        }

        function applyCustomFilters() {
            var fEmp = empSel ? empSel.value : '';
            var fDay = document.getElementById('attn-filter-date').value;
            var fStatus = document.getElementById('attn-filter-status').value;
            var fLate = document.getElementById('attn-filter-late').value;
            var fEarly = document.getElementById('attn-filter-early').value;

            var allRows = getRows();
            var visible = 0;
            var total = allRows.length;

            allRows.forEach(function(r) {
                var show = true;

                if (fEmp && (r.dataset.employeeName || '') !== fEmp) show = false;
                if (fDay && (r.dataset.day || '') !== fDay) show = false;
                if (fStatus && (r.dataset.status || '') !== fStatus) show = false;
                if (fLate === '1' && r.dataset.lateMark !== '1') show = false;
                if (fLate === '0' && r.dataset.lateMark === '1') show = false;
                if (fEarly === '1' && r.dataset.earlyMark !== '1') show = false;
                if (fEarly === '0' && r.dataset.earlyMark === '1') show = false;

                r.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            var countEl = document.getElementById('attn-filter-count');
            if (countEl) {
                if (visible < total) {
                    countEl.textContent = visible + ' / ' + total;
                    countEl.classList.remove('d-none');
                } else {
                    countEl.classList.add('d-none');
                }
            }
        }

        // Filter button
        var applyBtn = document.getElementById('attn-filter-apply');
        if (applyBtn) applyBtn.addEventListener('click', applyCustomFilters);

        // Also filter on dropdown change for instant feedback
        ['attn-filter-employee','attn-filter-date','attn-filter-status','attn-filter-late','attn-filter-early'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('change', applyCustomFilters);
        });

        // Reset
        document.getElementById('attn-filter-reset').addEventListener('click', function() {
            ['attn-filter-employee','attn-filter-date','attn-filter-status','attn-filter-late','attn-filter-early'].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.value = '';
            });
            applyCustomFilters();
        });
    }, 800);
    </script>
@endpush
@section('action-button')
    @if (\Auth::user()->type == 'employee')
        <button type="button"
            class="btn btn-sm btn-primary js-open-swipe-modal"
            data-bs-toggle="modal"
            data-bs-target="#swipeRequestModal"
            data-open-mode="top"
            data-attendance-id=""
            data-attendance-date=""
            data-issue-type="Leave"
            data-request-id=""
            data-request-status=""
            data-request-reason=""
            data-request-clock-in=""
            data-request-clock-out=""
            data-request-type="Leave"
            data-store-url="{{ route('attendanceemployee.swipe-request') }}"
            data-update-url="">
            <i class="ti ti-plus"></i> {{ __('Create Swipe Request') }}
        </button>
        <button type="button" class="btn btn-sm btn-outline-primary ms-1" id="js-open-swipe-history" data-bs-toggle="modal" data-bs-target="#swipeHistoryModal">
            <i class="ti ti-history"></i> {{ __('History') }}
        </button>
    @endif

    @if (\Auth::user()->type != 'employee' && !empty($pendingSwipeRequestCount) && $pendingSwipeRequestCount > 0)
        <a href="#pending-swipe-requests" class="btn btn-sm btn-warning">
            <i class="ti ti-bell"></i>
            {{ __('Swipe Requests') }} ({{ $pendingSwipeRequestCount }})
        </a>
    @endif
    @if (\Auth::user()->type != 'employee' && \Auth::user()->can('Manage Attendance'))
        <button type="button" class="btn btn-sm btn-outline-primary ms-1" data-bs-toggle="modal" data-bs-target="#swipeHistoryModal">
            <i class="ti ti-history"></i> {{ __('Swipe History') }}
        </button>
    @endif
@endsection
@section('content')
    @if (session('status'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {!! session('status') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {!! session('success') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {!! session('error') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    <div class="row">
        <div class="col-sm-12">
            <div class=" mt-2 " id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        {{ Form::open(['route' => ['attendanceemployee.index'], 'method' => 'get', 'id' => 'attendanceemployee_filter']) }}
                        <div class="row g-3 align-items-end">

                            <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                                <label class="form-label">{{ __('Type') }}</label>
                                <div class="d-flex gap-3 mt-1">
                                    <div class="form-check">
                                        <input type="radio" id="monthly" value="monthly" name="type"
                                            class="form-check-input"
                                            {{ ($resolvedFilterType ?? (isset($_GET['type']) ? $_GET['type'] : 'monthly')) == 'monthly' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="monthly">{{ __('Monthly') }}</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" id="daily" value="daily" name="type"
                                            class="form-check-input"
                                            {{ ($resolvedFilterType ?? (isset($_GET['type']) ? $_GET['type'] : 'monthly')) == 'daily' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="daily">{{ __('Daily') }}</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-sm-6 col-md-4 col-lg-auto month">
                                {{ Form::label('month', __('Month'), ['class' => 'form-label']) }}
                                <input type="hidden" name="month" id="attMonthHidden" value="{{ $resolvedFilterMonth ?? (isset($_GET['month']) ? $_GET['month'] : date('Y-m')) }}">
                                <div class="att-month-picker" id="attMonthPicker">
                                    <div class="att-mp-toggle" id="attMpToggle">
                                        <i class="ti ti-calendar-event" style="font-size:1rem;"></i>
                                        <span id="attMpLabel">—</span>
                                        <i class="ti ti-chevron-down" style="font-size:.8rem; margin-left:auto;"></i>
                                    </div>
                                    <div class="att-mp-dropdown" id="attMpDropdown">
                                        <div class="att-mp-year">
                                            <button type="button" class="yr-nav" onclick="attYearNav(-1)"><i class="ti ti-chevron-left"></i></button>
                                            <div class="yr-label" id="attYrLabel"></div>
                                            <button type="button" class="yr-nav" onclick="attYearNav(1)"><i class="ti ti-chevron-right"></i></button>
                                        </div>
                                        <div class="att-mp-grid" id="attMonthGrid"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4 col-lg-2 date">
                                {{ Form::label('date', __('Date'), ['class' => 'form-label']) }}
                                {{ Form::date('date', $resolvedFilterDate ?? (isset($_GET['date']) ? $_GET['date'] : ''), ['class' => 'form-control']) }}
                            </div>

                            @if (\Auth::user()->type != 'employee')
                                <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                                    {{ Form::label('branch', __('Branch'), ['class' => 'form-label']) }}
                                    {{ Form::select('branch', $branch, isset($_GET['branch']) ? $_GET['branch'] : '', ['class' => 'form-control select branch_id', 'id' => 'branch_id']) }}
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                                    {{ Form::label('department', __('Department'), ['class' => 'form-label']) }}
                                    {{ Form::select('department', $department, isset($_GET['department']) ? $_GET['department'] : '', ['class' => 'form-control select department_id', 'id' => 'department_id']) }}
                                </div>

                                @if (!empty($managerSelectionOptions) && $managerSelectionOptions->count() > 0)
                                    <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                                        {{ Form::label('manager_employee_id', __('Employee / User'), ['class' => 'form-label']) }}
                                        {{ Form::select('manager_employee_id', ['' => __('All')] + $managerSelectionOptions->toArray(), request('manager_employee_id', ''), ['class' => 'form-control select']) }}
                                    </div>
                                @endif
                            @else
                                @if (!empty($employeeSelectionOptions) && $employeeSelectionOptions->count() > 0)
                                    <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                                        {{ Form::label('employee_id', __('Employee'), ['class' => 'form-label']) }}
                                        {{ Form::select('employee_id', ['' => __('All Assigned')] + $employeeSelectionOptions->toArray(), request('employee_id', ''), ['class' => 'form-control select']) }}
                                    </div>
                                @endif
                            @endif

                            <div class="col-12 col-sm-auto">
                                <div class="d-flex gap-1">
                                    <a href="#" class="btn btn-sm btn-primary"
                                        onclick="document.getElementById('attendanceemployee_filter').submit(); return false;"
                                        data-bs-toggle="tooltip" title="{{ __('Apply') }}">
                                        <i class="ti ti-search"></i>
                                    </a>
                                    <a href="{{ route('attendanceemployee.index') }}" class="btn btn-sm btn-danger"
                                        data-bs-toggle="tooltip" title="{{ __('Reset') }}">
                                        <i class="ti ti-refresh text-white-off"></i>
                                    </a>
                                    @if(in_array(\Auth::user()->type, ['super admin', 'company']))
                                    <a href="#" data-url="{{ route('attendance.file.import') }}"
                                        data-ajax-popup="true" data-title="{{ __('Import  Attendance CSV File') }}"
                                        data-bs-toggle="tooltip" title="{{ __('Import') }}" class="btn btn-sm btn-primary">
                                        <i class="ti ti-file"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-secondary" id="attendanceExcelUploadBtn" data-bs-toggle="tooltip" title="{{ __('Upload Excel / CSV attendance') }}">
                                        <i class="ti ti-upload me-1"></i>{{ __('Upload Excel') }}
                                    </button>
                                    @if($resolvedFilterType === 'monthly')
                                    <a href="{{ route('attendance.export-monthly-excel', ['month' => $resolvedFilterMonth ?? date('Y-m'), 'branch' => request('branch', ''), 'department' => request('department', '')]) }}"
                                       class="btn btn-sm btn-success" data-bs-toggle="tooltip" title="{{ __('Download monthly attendance as Excel') }}">
                                        <i class="ti ti-file-spreadsheet me-1"></i>{{ __('Export Excel') }}
                                    </a>
                                    <button type="button" class="btn btn-sm btn-info" id="reapplyPolicyBtn" data-bs-toggle="tooltip" title="{{ __('Recalculate late/early marks & deduction units for all employees') }}">
                                        <i class="ti ti-recycle me-1"></i>{{ __('Reapply Policy') }}
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning" id="syncPayrollBtn" data-bs-toggle="tooltip" title="{{ __('Sync attendance data for payroll processing') }}">
                                        <i class="ti ti-refresh me-1"></i>{{ __('Sync for Payroll') }}
                                    </button>
                                    @endif
                                    @endif
                                </div>
                            </div>

                        </div>
                    </div>
                    {{ Form::close() }}
                </div>
            </div>
        </div>

        {{-- Reapply Policy & Sync for Payroll forms (outside filter form to avoid nested forms) --}}
        @if(in_array(\Auth::user()->type, ['super admin', 'company']) && ($resolvedFilterType ?? 'monthly') === 'monthly')
        <form method="POST" action="{{ route('attendance.reapply-policy') }}" id="reapplyPolicyForm" class="d-none">
            @csrf
            <input type="hidden" name="month" value="{{ $resolvedFilterMonth }}">
        </form>
        <form method="POST" action="{{ route('attendance.sync-for-payroll') }}" id="syncPayrollForm" class="d-none">
            @csrf
            <input type="hidden" name="month" value="{{ $resolvedFilterMonth }}">
        </form>
        @endif
        @if(in_array(\Auth::user()->type, ['super admin', 'company']))
        <form method="POST" action="{{ route('attendance.upload-excel') }}" id="attendanceExcelUploadForm" class="d-none" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="month" value="{{ $resolvedFilterMonth ?? date('Y-m') }}">
            <input type="hidden" name="branch" value="{{ request('branch', '') }}">
            <input type="hidden" name="department" value="{{ request('department', '') }}">
            <input type="hidden" name="manager_employee_id" value="{{ request('manager_employee_id', request('employee_id', '')) }}">
            <input type="file" name="attendance_file" id="attendanceExcelUploadInput" accept=".xlsx,.xls,.csv,.txt">
        </form>
        @endif

        <div class="col-xl-12">
            @if (!empty($pendingSwipeRequests) && $pendingSwipeRequests->count() > 0)
                <div class="card mb-3" id="pending-swipe-requests">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('Swipe Modification Requests') }} ({{ $pendingSwipeRequests->count() }})</h5>
                    </div>
                    <div class="card-body table-border-style">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>{{ __('Action') }}</th>
                                        <th>{{ __('Employee') }}</th>
                                        <th>{{ __('Date') }}</th>
                                        <th>{{ __('Current Swipe') }}</th>
                                        <th>{{ __('Requested Swipe') }}</th>
                                        <th>{{ __('Reason') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($pendingSwipeRequests as $requestItem)
                                        <tr class="{{ !empty($focusRequest) && (int) $focusRequest->id === (int) $requestItem->id ? 'table-warning' : '' }}">
                                            <td>
                                                <div class="d-flex flex-wrap gap-1 align-items-start">
                                                    <a class="btn btn-sm btn-outline-primary"
                                                        href="{{ route('attendanceemployee.index', ['type' => 'daily', 'date' => !empty($requestItem->attendance) ? $requestItem->attendance->date : null, 'employee_id' => !empty($requestItem->employee) ? $requestItem->employee->id : null, 'swipe_request_id' => $requestItem->id]) }}">
                                                        {{ __('Open Day') }}
                                                    </a>
                                                    {{ Form::open(['route' => ['attendanceemployee.swipe-request.process', $requestItem->id], 'method' => 'POST', 'class' => 'd-inline-flex align-items-center gap-1']) }}
                                                    {{ Form::hidden('decision', 'Approved') }}
                                                    <input type="text" name="manager_comment" class="form-control form-control-sm" style="width:120px;" placeholder="{{ __('Comment') }}">
                                                    <button type="submit" class="btn btn-sm btn-success">{{ __('Approve') }}</button>
                                                    {{ Form::close() }}

                                                    {{ Form::open(['route' => ['attendanceemployee.swipe-request.process', $requestItem->id], 'method' => 'POST', 'class' => 'd-inline-flex align-items-center gap-1']) }}
                                                    {{ Form::hidden('decision', 'Rejected') }}
                                                    <input type="text" name="manager_comment" class="form-control form-control-sm" style="width:120px;" placeholder="{{ __('Reason') }}">
                                                    <button type="submit" class="btn btn-sm btn-danger">{{ __('Reject') }}</button>
                                                    {{ Form::close() }}
                                                </div>
                                            </td>
                                            <td>{{ !empty($requestItem->employee) ? $requestItem->employee->name : __('N/A') }}</td>
                                            <td>
                                                {{ !empty($requestItem->attendance) ? \Auth::user()->dateFormat($requestItem->attendance->date) : __('N/A') }}
                                            </td>
                                            <td>
                                                @if (!empty($requestItem->attendance))
                                                    {{ $requestItem->attendance->clock_in != '00:00:00' ? \Auth::user()->timeFormat($requestItem->attendance->clock_in) : '00:00' }}
                                                    -
                                                    {{ $requestItem->attendance->clock_out != '00:00:00' ? \Auth::user()->timeFormat($requestItem->attendance->clock_out) : '00:00' }}
                                                @else
                                                    {{ __('N/A') }}
                                                @endif
                                            </td>
                                            <td>
                                                @if (!empty($requestItem->requested_clock_in) || !empty($requestItem->requested_clock_out))
                                                    {{ !empty($requestItem->requested_clock_in) ? \Auth::user()->timeFormat($requestItem->requested_clock_in) : '--:--' }}
                                                    -
                                                    {{ !empty($requestItem->requested_clock_out) ? \Auth::user()->timeFormat($requestItem->requested_clock_out) : '--:--' }}
                                                @else
                                                    {{ __('Status:') }} {{ $requestItem->requested_status ?? __('N/A') }}
                                                @endif
                                            </td>
                                            <td>{{ $requestItem->reason }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Facial Verification Card (employees only) --}}
            @if (\Auth::user()->type == 'employee')
            <div class="card mb-3" id="facial-verification-card">
                <div class="card-header d-flex align-items-center justify-content-between py-2">
                    <div>
                        <h6 class="mb-0 fw-semibold" style="color:#4361ee;">
                            <i class="ti ti-shield-check me-1"></i>{{ __('Identity Verification') }}
                        </h6>
                        <p class="text-muted mb-0" style="font-size:0.78rem;">{{ __('FacialRecognitionService compares your latest clock-in/out photos with your profile photo (same check as at swipe).') }}</p>
                    </div>
                    <div id="fv-badge-area"></div>
                </div>
                <div class="card-body py-3">
                    @if (!empty($profilePhotoUrl))
                        {{-- Clock-in verification --}}
                        @if (!empty($attendanceSelfieUrl))
                        <div class="mb-3 pb-3 border-bottom">
                            <p class="text-muted small mb-2 fw-semibold"><i class="ti ti-login me-1"></i>{{ __('Clock-in photo') }}</p>
                            <div class="row g-3 align-items-center">
                                <div class="col-auto text-center">
                                    <p class="text-muted mb-1" style="font-size:0.75rem;">{{ __('Profile Photo') }}</p>
                                    <img id="fv-profile-img" src="{{ $profilePhotoUrl }}"
                                        crossorigin="anonymous"
                                        style="width:80px;height:80px;object-fit:cover;border-radius:50%;border:2px solid #dee2e6;"
                                        onerror="this.src='{{ asset('assets/images/user.png') }}'">
                                </div>
                                <div class="col-auto text-center px-2">
                                    <i class="ti ti-arrows-exchange" style="font-size:1.5rem;color:#adb5bd;"></i>
                                </div>
                                <div class="col-auto text-center">
                                    <p class="text-muted mb-1" style="font-size:0.75rem;">{{ __('Clock-in Photo') }}</p>
                                    <img id="fv-selfie-img" src="{{ $attendanceSelfieUrl }}"
                                        crossorigin="anonymous"
                                        style="width:80px;height:80px;object-fit:cover;border-radius:50%;border:2px solid #dee2e6;"
                                        onerror="this.src='{{ asset('assets/images/user.png') }}'">
                                </div>
                                <div class="col text-start ps-3">
                                    <div id="fv-result" class="d-flex flex-column gap-1">
                                        @if (!empty($facialRecognitionClockIn))
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                @if (!empty($facialRecognitionClockIn['verified']))
                                                    <i class="ti ti-circle-check" style="font-size:1.5rem;color:#2ecc71;"></i>
                                                    <span class="fw-semibold" style="color:#2ecc71;font-size:0.95rem;">{{ __('Match with profile') }}</span>
                                                    <span class="badge bg-light text-success border" style="font-size:0.7rem;">FacialRecognitionService</span>
                                                @else
                                                    <i class="ti ti-alert-circle" style="font-size:1.5rem;color:#e74c3c;"></i>
                                                    <span class="fw-semibold" style="color:#e74c3c;font-size:0.95rem;">{{ __('No match with profile') }}</span>
                                                    <span class="badge bg-light text-danger border" style="font-size:0.7rem;">FacialRecognitionService</span>
                                                @endif
                                            </div>
                                            <small class="text-muted" style="font-size:0.8rem;">{{ $facialRecognitionClockIn['message'] ?? '' }}</small>
                                            @if (!empty($facialRecognitionClockIn['attendance_date']))
                                                <small class="text-muted" style="font-size:0.72rem;">{{ __('Photo from') }}: {{ \Auth::user()->dateFormat($facialRecognitionClockIn['attendance_date']) }}</small>
                                            @endif
                                            @if (array_key_exists('stored_photo_verified', $facialRecognitionClockIn) && $facialRecognitionClockIn['stored_photo_verified'] !== null)
                                                <small class="text-muted" style="font-size:0.72rem;">{{ __('Saved at clock-in') }}: {{ $facialRecognitionClockIn['stored_photo_verified'] ? __('Verified') : __('Not verified') }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted" style="font-size:0.85rem;">{{ __('Server check could not run (missing profile file or photo path).') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Clock-out verification --}}
                        @if (!empty($attendanceSelfieOutUrl))
                        <div class="mb-0">
                            <p class="text-muted small mb-2 fw-semibold"><i class="ti ti-logout me-1"></i>{{ __('Clock-out photo') }}</p>
                            <div class="row g-3 align-items-center">
                                <div class="col-auto text-center">
                                    <p class="text-muted mb-1" style="font-size:0.75rem;">{{ __('Profile Photo') }}</p>
                                    <img id="fv-profile-img-out" src="{{ $profilePhotoUrl }}"
                                        crossorigin="anonymous"
                                        style="width:80px;height:80px;object-fit:cover;border-radius:50%;border:2px solid #dee2e6;"
                                        onerror="this.src='{{ asset('assets/images/user.png') }}'">
                                </div>
                                <div class="col-auto text-center px-2">
                                    <i class="ti ti-arrows-exchange" style="font-size:1.5rem;color:#adb5bd;"></i>
                                </div>
                                <div class="col-auto text-center">
                                    <p class="text-muted mb-1" style="font-size:0.75rem;">{{ __('Clock-out Photo') }}</p>
                                    <img id="fv-selfie-img-out" src="{{ $attendanceSelfieOutUrl }}"
                                        crossorigin="anonymous"
                                        style="width:80px;height:80px;object-fit:cover;border-radius:50%;border:2px solid #dee2e6;"
                                        onerror="this.src='{{ asset('assets/images/user.png') }}'">
                                </div>
                                <div class="col text-start ps-3">
                                    <div id="fv-result-out" class="d-flex flex-column gap-1">
                                        @if (!empty($facialRecognitionClockOut))
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                @if (!empty($facialRecognitionClockOut['verified']))
                                                    <i class="ti ti-circle-check" style="font-size:1.5rem;color:#2ecc71;"></i>
                                                    <span class="fw-semibold" style="color:#2ecc71;font-size:0.95rem;">{{ __('Match with profile') }}</span>
                                                    <span class="badge bg-light text-success border" style="font-size:0.7rem;">FacialRecognitionService</span>
                                                @else
                                                    <i class="ti ti-alert-circle" style="font-size:1.5rem;color:#e74c3c;"></i>
                                                    <span class="fw-semibold" style="color:#e74c3c;font-size:0.95rem;">{{ __('No match with profile') }}</span>
                                                    <span class="badge bg-light text-danger border" style="font-size:0.7rem;">FacialRecognitionService</span>
                                                @endif
                                            </div>
                                            <small class="text-muted" style="font-size:0.8rem;">{{ $facialRecognitionClockOut['message'] ?? '' }}</small>
                                            @if (!empty($facialRecognitionClockOut['attendance_date']))
                                                <small class="text-muted" style="font-size:0.72rem;">{{ __('Photo from') }}: {{ \Auth::user()->dateFormat($facialRecognitionClockOut['attendance_date']) }}</small>
                                            @endif
                                            @if (array_key_exists('stored_photo_verified', $facialRecognitionClockOut) && $facialRecognitionClockOut['stored_photo_verified'] !== null)
                                                <small class="text-muted" style="font-size:0.72rem;">{{ __('Saved at clock-out') }}: {{ $facialRecognitionClockOut['stored_photo_verified'] ? __('Verified') : __('Not verified') }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted" style="font-size:0.85rem;">{{ __('Server check could not run (missing profile file or photo path).') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    @endif

                    @php
                        $fvSummaryOk = (!empty($facialRecognitionClockIn) && !empty($facialRecognitionClockIn['verified']))
                            || (!empty($facialRecognitionClockOut) && !empty($facialRecognitionClockOut['verified']));
                        $fvSummaryBad = (!empty($facialRecognitionClockIn) && empty($facialRecognitionClockIn['verified']))
                            || (!empty($facialRecognitionClockOut) && empty($facialRecognitionClockOut['verified']));
                    @endphp
                    @if (!empty($profilePhotoUrl) && (!empty($facialRecognitionClockIn) || !empty($facialRecognitionClockOut)))
                        <script>
                        (function() {
                            var badgeArea = document.getElementById('fv-badge-area');
                            if (!badgeArea) return;
                            @if ($fvSummaryOk && ! $fvSummaryBad)
                                badgeArea.innerHTML = '<span class="badge px-3 py-2 rounded-pill fw-semibold" style="background:#d4edda;color:#155724;border:1px solid #c3e6cb;font-size:0.8rem;"><i class="ti ti-shield-check me-1"></i>{{ __('FacialRecognitionService: match') }}</span>';
                            @elseif ($fvSummaryBad)
                                badgeArea.innerHTML = '<span class="badge px-3 py-2 rounded-pill fw-semibold" style="background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;font-size:0.8rem;"><i class="ti ti-shield-x me-1"></i>{{ __('Review photos below') }}</span>';
                            @endif
                        })();
                        </script>
                    @endif

                    @if (empty($profilePhotoUrl))
                        <div class="d-flex align-items-center gap-2 text-muted">
                            <i class="ti ti-user-off" style="font-size:1.3rem;"></i>
                            <span style="font-size:0.85rem;">{{ __('Profile photo not set. Please upload a profile photo to enable verification.') }}</span>
                        </div>
                    @elseif (empty($attendanceSelfieUrl) && empty($attendanceSelfieOutUrl))
                        <div class="d-flex align-items-center gap-2 text-muted">
                            <i class="ti ti-camera-off" style="font-size:1.3rem;"></i>
                            <span style="font-size:0.85rem;">{{ __('No attendance photo found for this period. Clock-in/out with a photo to enable verification.') }}</span>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <h5 class="mb-1">{{ __('Attendance Analytics') }}</h5>
                            <p class="text-muted mb-0">{{ __('Visual summary for current filter selection') }}</p>
                        </div>
                        <span class="badge bg-light-primary text-primary">{{ __('Live Filter View') }}</span>
                    </div>
                </div>
                <div class="card-body">
                    @if (!empty($attendanceAnalytics))
                        @php $multiEmp = ($attendanceAnalytics['unique_employees'] ?? 1) > 1; @endphp
                        @if($multiEmp)
                            <div class="alert alert-info py-2 mb-3" style="font-size:.82rem;">
                                <i class="ti ti-info-circle me-1"></i>
                                {{ __('Showing combined data for') }} <strong>{{ $attendanceAnalytics['unique_employees'] }}</strong> {{ __('employees. Values are totals across all employees.') }}
                            </div>
                        @endif
                        <div class="row g-3 mb-4">
                            <div class="col-xl-3 col-md-6">
                                <div class="attendance-kpi-card kpi-clickable" data-kpi="total_records" data-kpi-label="{{ __('Total Records') }}">
                                    <p class="attendance-kpi-label">{{ __('Total Records') }}</p>
                                    <h3 class="attendance-kpi-value">{{ $attendanceAnalytics['total_records'] ?? 0 }}</h3>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                @php
                                    $kpiTotal = ($attendanceAnalytics['total_records'] ?? 0);
                                    $kpiPresent = ($attendanceAnalytics['present_days'] ?? 0);
                                    $kpiPercent = $kpiTotal > 0 ? round(($kpiPresent / $kpiTotal) * 100, 1) : 0;
                                @endphp
                                <div class="attendance-kpi-card kpi-clickable" data-kpi="present_days" data-kpi-label="{{ __('Present') }}">
                                    <p class="attendance-kpi-label">{{ __('Present') }}</p>
                                    <h3 class="attendance-kpi-value">{{ $kpiPresent }} <small style="font-size:.6em;color:#64748b;">({{ $kpiPercent }}%)</small></h3>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="attendance-kpi-card kpi-clickable" data-kpi="half_day_days" data-kpi-label="{{ __('Half Day') }}">
                                    <p class="attendance-kpi-label">{{ __('Half Day') }}</p>
                                    <h3 class="attendance-kpi-value">{{ $attendanceAnalytics['half_day_days'] ?? 0 }}</h3>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="attendance-kpi-card kpi-clickable" data-kpi="absent_leave_days" data-kpi-label="{{ __('Absent/Leave') }}">
                                    <p class="attendance-kpi-label">{{ __('Absent/Leave') }}</p>
                                    <h3 class="attendance-kpi-value">{{ $attendanceAnalytics['absent_leave_days'] ?? 0 }}</h3>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-xl-3 col-md-6">
                                <div class="attendance-kpi-card kpi-clickable" data-kpi="late_marks" data-kpi-label="{{ __('Late In') }}">
                                    <p class="attendance-kpi-label">{{ __('Late In') }}</p>
                                    <h3 class="attendance-kpi-value">{{ $attendanceAnalytics['late_marks'] ?? 0 }}</h3>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="attendance-kpi-card kpi-clickable" data-kpi="early_marks" data-kpi-label="{{ __('Early Out') }}">
                                    <p class="attendance-kpi-label">{{ __('Early Out') }}</p>
                                    <h3 class="attendance-kpi-value">{{ $attendanceAnalytics['early_marks'] ?? 0 }}</h3>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="attendance-kpi-card kpi-clickable" data-kpi="less_hours_marks" data-kpi-label="{{ __('Less Hours Marks') }}">
                                    <p class="attendance-kpi-label">{{ __('Less Hours Marks') }}</p>
                                    <h3 class="attendance-kpi-value">{{ $attendanceAnalytics['less_hours_marks'] ?? 0 }}</h3>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="attendance-kpi-card kpi-clickable" data-kpi="total_deduction_units" data-kpi-label="{{ __('Total Deduction Units') }}">
                                    <p class="attendance-kpi-label">{{ __('Total Deduction Units') }}</p>
                                    <h3 class="attendance-kpi-value">{{ number_format((float) ($attendanceAnalytics['total_deduction_units'] ?? 0), 1) }}</h3>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-lg-6">
                                <div class="attendance-chart-card">
                                    <h6 class="mb-1">{{ __('Status Distribution') }}</h6>
                                    <p class="text-muted small mb-3">{{ __('Present, half day and absent/leave split') }}</p>
                                    <div id="attendance-status-chart"></div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="attendance-chart-card">
                                    <h6 class="mb-1">
                                        {{ \Auth::user()->type == 'employee' ? __('Your Mark Overview') : __('User-wise Mark Comparison') }}
                                    </h6>
                                    <p class="text-muted small mb-3">
                                        {{ \Auth::user()->type == 'employee' ? __('Late, early and less-hour marks for your selected period') : __('Top users by deduction impact with mark distribution') }}
                                    </p>
                                    <div id="attendance-employee-chart"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-xl-3 col-md-6">
                                <div class="attendance-kpi-card kpi-clickable" data-kpi="direct_late_half_day_dates" data-kpi-label="{{ __('Direct Half Day (Late >= threshold)') }}">
                                    <p class="attendance-kpi-label">{{ __('Direct Half Day (Late >= threshold)') }}</p>
                                    <h3 class="attendance-kpi-value">{{ $attendanceAnalytics['direct_late_half_day_dates'] ?? 0 }}</h3>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="attendance-kpi-card kpi-clickable" data-kpi="direct_early_half_day_dates" data-kpi-label="{{ __('Direct Half Day (Early >= threshold)') }}">
                                    <p class="attendance-kpi-label">{{ __('Direct Half Day (Early >= threshold)') }}</p>
                                    <h3 class="attendance-kpi-value">{{ $attendanceAnalytics['direct_early_half_day_dates'] ?? 0 }}</h3>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="attendance-kpi-card kpi-clickable" data-kpi="exempt_mark_dates" data-kpi-label="{{ __('Exempted Marks (Rule 2)') }}">
                                    <p class="attendance-kpi-label">{{ __('Exempted Marks (Rule 2)') }}</p>
                                    <h3 class="attendance-kpi-value">{{ $attendanceAnalytics['exempt_mark_dates'] ?? 0 }}</h3>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="attendance-kpi-card kpi-clickable" data-kpi="post_exemption_mark_dates" data-kpi-label="{{ __('Post-Exemption Marks') }}">
                                    <p class="attendance-kpi-label">{{ __('Post-Exemption Marks') }}</p>
                                    <h3 class="attendance-kpi-value">{{ $attendanceAnalytics['post_exemption_mark_dates'] ?? 0 }}</h3>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="attendance-kpi-card kpi-clickable" data-kpi="policy_deduction_sets" data-kpi-label="{{ __('Completed Policy Deduction Sets') }}">
                                    <p class="attendance-kpi-label">{{ __('Completed Policy Deduction Sets') }}</p>
                                    <h3 class="attendance-kpi-value">{{ $attendanceAnalytics['policy_deduction_sets'] ?? 0 }}</h3>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="attendance-kpi-card kpi-clickable" data-kpi="pending_mark_dates" data-kpi-label="{{ __('Pending Marks (Not enough for next set)') }}">
                                    <p class="attendance-kpi-label">{{ __('Pending Marks (Not enough for next set)') }}</p>
                                    <h3 class="attendance-kpi-value">{{ $attendanceAnalytics['pending_mark_dates'] ?? 0 }}</h3>
                                </div>
                            </div>
                        </div>

                        @if (\Auth::user()->type != 'employee' && !empty($attendanceSummary) && count($attendanceSummary) > 0)
                            <div class="table-responsive mt-4">
                                <table class="table table-sm attendance-employee-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Employee') }}</th>
                                            <th class="text-center">{{ __('Days') }}</th>
                                            <th class="text-center">{{ __('P') }}</th>
                                            <th class="text-center">{{ __('L') }}</th>
                                            <th class="text-center">{{ __('A') }}</th>
                                            <th class="text-center">{{ __('HD') }}</th>
                                            <th class="text-center">{{ __('Late In') }}</th>
                                            <th class="text-center">{{ __('Early Out') }}</th>
                                            <th class="text-center">{{ __('W/OFF') }}</th>
                                            <th class="text-center">{{ __('OT Hrs') }}</th>
                                            <th class="text-center">{{ __('Payroll Sync') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($attendanceSummary as $s)
                                            @php
                                                $empId = $s['employee_id'];
                                                $synced = ($syncedAttendance[$empId] ?? null);
                                            @endphp
                                            <tr>
                                                <td><strong>{{ $s['employee_name'] }}</strong></td>
                                                <td class="text-center">{{ $s['month_total_days'] ?? 0 }}</td>
                                                <td class="text-center"><span class="text-success fw-bold">{{ number_format($s['present_count'] ?? 0, 1) }}</span></td>
                                                <td class="text-center">
                                                    @if(($s['leave_only_count'] ?? 0) > 0)<span class="text-secondary fw-bold">{{ number_format($s['leave_only_count'], 1) }}</span>@else <span class="text-muted">0</span> @endif
                                                </td>
                                                <td class="text-center">
                                                    @if(($s['absent_only_count'] ?? 0) > 0)<span class="text-danger fw-bold">{{ number_format($s['absent_only_count'], 1) }}</span>@else <span class="text-muted">0</span> @endif
                                                </td>
                                                <td class="text-center">
                                                    @if(($s['hd_deduction'] ?? 0) > 0)<span class="text-warning fw-bold">{{ number_format($s['hd_deduction'], 1) }}</span>@else <span class="text-muted">0</span> @endif
                                                </td>
                                                <td class="text-center">
                                                    @if(($s['late_count'] ?? 0) > 0)<span class="text-info fw-bold">{{ $s['late_count'] }}</span>@else <span class="text-muted">0</span> @endif
                                                </td>
                                                <td class="text-center">
                                                    @if(($s['early_count'] ?? 0) > 0)<span class="text-info fw-bold">{{ $s['early_count'] }}</span>@else <span class="text-muted">0</span> @endif
                                                </td>
                                                <td class="text-center">{{ $s['weekly_offs'] ?? 0 }}</td>
                                                <td class="text-center">
                                                    @if(!empty($s['overtime_enabled']) && ($s['overtime_hours'] ?? 0) > 0)
                                                        <span class="fw-bold" style="color:#ff6b00;" data-bs-toggle="tooltip" title="{{ ($s['overtime_days'] ?? 0) }} {{ __('days') }}">{{ number_format($s['overtime_hours'], 1) }}h</span>
                                                    @elseif(!empty($s['overtime_enabled']))
                                                        <span class="text-muted">0</span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if($synced)
                                                        <span class="badge bg-success" data-bs-toggle="tooltip" title="{{ __('Synced') }}: {{ \Carbon\Carbon::parse($synced['synced_at'])->format('d M Y h:i A') }}">
                                                            <i class="ti ti-check me-1"></i>{{ __('Synced') }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary bg-opacity-25 text-muted">{{ __('Not Synced') }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                    @else
                        <p class="text-muted mb-0">{{ __('No attendance records found for selected filter.') }}</p>
                    @endif
                </div>
            </div>
        </div>{{-- end analytics col-xl-12 --}}

        {{-- Side by side: Policy Summary 40% | Datatable 60% --}}
        <div class="col-xl-5">
            <div class="card" style="max-height:85vh;overflow-y:auto;">
                <div class="card-header py-2">
                    <h6 class="mb-0"><i class="ti ti-list-check me-1"></i>{{ __('Rule-wise Policy Summary') }}</h6>
                </div>
                <div class="card-body pt-2">
                    @if (!empty($attendanceSummary) && count($attendanceSummary) > 0)
                        {{-- Policy Settings Info Bar --}}
                        @php
                            $ps = $attendanceSummary[0] ?? [];
                        @endphp
                        <div class="mb-3 p-2 rounded border bg-white">
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <i class="ti ti-settings text-primary" style="font-size:1rem;"></i>
                                <strong style="font-size:.8rem;">{{ __('Active Policy') }}</strong>
                            </div>
                            <div class="d-flex gap-2 flex-wrap" style="font-size:.72rem;">
                                <span class="badge bg-light text-dark border"><i class="ti ti-clock-pause me-1"></i>{{ __('Grace Late') }}: <strong>{{ $policySettings['grace_late'] ?? 0 }} {{ __('min') }}</strong></span>
                                <span class="badge bg-light text-dark border"><i class="ti ti-clock-pause me-1"></i>{{ __('Grace Early') }}: <strong>{{ $policySettings['grace_early'] ?? 0 }} {{ __('min') }}</strong></span>
                                <span class="badge bg-info bg-opacity-10 text-info border"><i class="ti ti-shield-check me-1"></i>{{ __('Exempt Limit') }}: <strong>{{ $policySettings['exception_limit'] ?? 0 }} {{ __('marks') }}</strong></span>
                                <span class="badge bg-danger bg-opacity-10 text-danger border"><i class="ti ti-alert-triangle me-1"></i>{{ __('½ Day Threshold') }}: <strong>{{ $policySettings['half_day_deduction_minutes'] ?? 60 }} {{ __('min') }}</strong></span>
                                <span class="badge bg-warning bg-opacity-10 text-warning border"><i class="ti ti-calculator me-1"></i>{{ __('Policy') }}: <strong>{{ $policySettings['deduction_policy'] ?? 'every1' }}</strong> = <strong>{{ $ps['deduction_trigger_count'] ?? 1 }} {{ __('mark = ½ day ded') }}</strong></span>
                            </div>
                        </div>

                        @foreach ($attendanceSummary as $summary)
                            @php
                                // Build tooltip hints for each stat
                                $dgCount = $summary['deduction_groups_count'] ?? 0;
                                $ehdCount = $summary['early_hd_count'] ?? 0;
                                $hdpfCount = $summary['hd_policy_forced_count'] ?? 0;
                                $rawLeaves = count($summary['leave_dates'] ?? []);
                                $hdLeaves = $summary['hd_leave_count'] ?? 0;
                                $rawAbsents = count($summary['absent_dates'] ?? []);
                                $hdAbsents = $summary['hd_absent_count'] ?? 0;

                                $pHint = __('P = DAYS - L - A - HD - W/OFF') . "\n= " . ($summary['month_total_days'] ?? 0) . ' - ' . number_format($summary['leave_only_count'] ?? 0, 1) . ' - ' . number_format($summary['absent_only_count'] ?? 0, 1) . ' - ' . number_format($summary['hd_deduction'] ?? 0, 1) . ' - ' . ($summary['weekly_offs'] ?? 0) . ' = ' . number_format($summary['present_count'] ?? 0, 1);

                                $lParts = [];
                                if ($rawLeaves > 0) $lParts[] = $rawLeaves . ' ' . __('full leave');
                                if ($hdLeaves > 0) $lParts[] = $hdLeaves . ' ' . __('HD leave (x0.5)');
                                $lHint = __('L') . ' = ' . (count($lParts) ? implode(' + ', $lParts) : '0') . ' = ' . number_format($summary['leave_only_count'] ?? 0, 1);

                                $aParts = [];
                                if ($rawAbsents > 0) $aParts[] = $rawAbsents . ' ' . __('full absent');
                                if ($hdAbsents > 0) $aParts[] = $hdAbsents . ' ' . __('HD absent (x0.5)');
                                $aHint = __('A') . ' = ' . (count($aParts) ? implode(' + ', $aParts) : '0') . ' = ' . number_format($summary['absent_only_count'] ?? 0, 1);

                                $hdParts = [];
                                if ($dgCount > 0) $hdParts[] = $dgCount . ' ' . __('non-exempt x0.5') . '=' . number_format($dgCount * 0.5, 1);
                                if ($ehdCount > 0) $hdParts[] = $ehdCount . ' ' . __('early ½ day x0.5') . '=' . number_format($ehdCount * 0.5, 1);
                                if ($hdpfCount > 0) $hdParts[] = $hdpfCount . ' ' . __('late ½ day x0.5') . '=' . number_format($hdpfCount * 0.5, 1);
                                $hdHint = __('HD') . ' = ' . (count($hdParts) ? implode(' + ', $hdParts) : '0') . ' = ' . number_format($summary['hd_deduction'] ?? 0, 1);
                            @endphp
                            <div class="mb-3 p-3 border rounded" style="background:#fafbfc;">
                                        {{-- Employee Header with Shift & Quick Stats --}}
                                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3 pb-2 border-bottom">
                                            <div>
                                                <h6 class="mb-1 fw-bold">
                                                    <i class="ti ti-user me-1"></i>{{ $summary['employee_name'] }}
                                                    <span class="badge bg-primary ms-2" style="font-size:.7rem;">
                                                        <i class="ti ti-clock me-1"></i>{{ $summary['shift_name'] ?? 'Default' }}
                                                        ({{ \Carbon\Carbon::parse($summary['shift_start'])->format('h:i A') }} – {{ \Carbon\Carbon::parse($summary['shift_end'])->format('h:i A') }})
                                                    </span>
                                                </h6>
                                            </div>
                                            <div class="d-flex gap-2 flex-wrap" style="font-size:.75rem;">
                                                <span class="badge bg-primary text-white border">{{ __('DAYS') }}: <strong>{{ $summary['month_total_days'] ?? 0 }}</strong></span>
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{{ $pHint }}">{{ __('P') }}: <strong>{{ number_format($summary['present_count'] ?? 0, 1) }}</strong> <i class="ti ti-info-circle" style="font-size:.7rem;opacity:.7;"></i></span>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{{ $lHint }}">{{ __('L') }}: <strong>{{ number_format($summary['leave_only_count'] ?? 0, 1) }}</strong> <i class="ti ti-info-circle" style="font-size:.7rem;opacity:.7;"></i></span>
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{{ $aHint }}">{{ __('A') }}: <strong>{{ number_format($summary['absent_only_count'] ?? 0, 1) }}</strong> <i class="ti ti-info-circle" style="font-size:.7rem;opacity:.7;"></i></span>
                                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{{ $hdHint }}">{{ __('HD') }}: <strong>{{ number_format($summary['hd_deduction'] ?? 0, 1) }}</strong> <i class="ti ti-info-circle" style="font-size:.7rem;opacity:.7;"></i></span>
                                                <span class="badge bg-info bg-opacity-10 text-info border border-info">{{ __('Late In') }}: <strong>{{ $summary['late_count'] ?? 0 }}</strong></span>
                                                <span class="badge bg-info bg-opacity-10 text-info border border-info">{{ __('Early Out') }}: <strong>{{ $summary['early_count'] ?? 0 }}</strong></span>
                                                <span class="badge bg-light text-dark border">{{ __('W/OFF') }}: <strong>{{ $summary['weekly_offs'] ?? 0 }}</strong></span>
                                                @if(!empty($summary['overtime_enabled']))
                                                    <span class="badge border" style="background:#ff6b00;color:#fff;border-color:#ff6b00 !important;">{{ __('OT') }}: <strong>{{ number_format($summary['overtime_hours'] ?? 0, 1) }}h</strong> ({{ $summary['overtime_days'] ?? 0 }} {{ __('days') }})</span>
                                                @endif
                                                <span class="badge bg-dark bg-opacity-75 text-white" style="font-size:.65rem;">{{ $summary['deduction_trigger_count'] }} {{ __('mark = ½ day ded') }}</span>
                                            </div>
                                        </div>

                                        {{-- Policy Rules - Clean Card Layout --}}
                                        <div class="row g-2">
                                            {{-- Exempted Marks --}}
                                            <div class="col-md-6">
                                                <div class="p-2 rounded border bg-white h-100">
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <span class="badge bg-info" style="font-size:.65rem;">{{ __('EXEMPT') }}</span>
                                                        <small class="fw-semibold">{{ __('First') }} {{ $summary['exception_limit'] }} {{ __('late/early marks — no deduction') }}</small>
                                                    </div>
                                                    <small class="text-muted">
                                                        {{ __('Dates') }}:
                                                        @if(count($summary['exempt_dates']))
                                                            @foreach($summary['exempt_dates'] as $d)
                                                                <span class="badge bg-info bg-opacity-10 text-info border" style="font-size:.7rem;">{{ $d }}</span>
                                                            @endforeach
                                                        @else
                                                            <span class="text-muted">{{ __('None') }}</span>
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>

                                            {{-- Non-Exempted Marks --}}
                                            <div class="col-md-6">
                                                <div class="p-2 rounded border bg-white h-100">
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <span class="badge bg-danger" style="font-size:.65rem;">{{ __('NON-EXEMPT') }}</span>
                                                        <small class="fw-semibold">{{ __('Marks after exemption limit') }}</small>
                                                    </div>
                                                    <small class="text-muted">
                                                        {{ __('Dates') }}:
                                                        @if(count($summary['post_exemption_dates']))
                                                            @foreach($summary['post_exemption_dates'] as $d)
                                                                <span class="badge bg-danger bg-opacity-10 text-danger border" style="font-size:.7rem;">{{ $d }}</span>
                                                            @endforeach
                                                        @else
                                                            <span class="text-muted">{{ __('None') }}</span>
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>

                                            {{-- Direct Half Day - Late --}}
                                            <div class="col-md-6">
                                                <div class="p-2 rounded border bg-white h-100">
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <span class="badge bg-danger" style="font-size:.65rem;">{{ __('LATE ½ DAY') }}</span>
                                                        <small class="fw-semibold">{{ __('Late In') }} {{ $summary['half_day_minutes'] }}+ {{ __('min → auto half day') }}</small>
                                                    </div>
                                                    <small class="text-muted">
                                                        {{ __('Dates') }}:
                                                        @if(count($summary['direct_late_dates']))
                                                            @foreach($summary['direct_late_dates'] as $d)
                                                                <span class="badge bg-danger bg-opacity-10 text-danger border" style="font-size:.7rem;">{{ $d }}</span>
                                                            @endforeach
                                                        @else
                                                            <span class="text-muted">{{ __('None') }}</span>
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>

                                            {{-- Direct Half Day - Early --}}
                                            <div class="col-md-6">
                                                <div class="p-2 rounded border bg-white h-100">
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <span class="badge bg-warning text-dark" style="font-size:.65rem;">{{ __('EARLY ½ DAY') }}</span>
                                                        <small class="fw-semibold">{{ __('Early Out') }} {{ $summary['half_day_minutes'] }}+ {{ __('min → auto half day') }}</small>
                                                    </div>
                                                    <small class="text-muted">
                                                        {{ __('Dates') }}:
                                                        @if(count($summary['direct_early_dates']))
                                                            @foreach($summary['direct_early_dates'] as $d)
                                                                <span class="badge bg-warning bg-opacity-10 text-warning border" style="font-size:.7rem;">{{ $d }}</span>
                                                            @endforeach
                                                        @else
                                                            <span class="text-muted">{{ __('None') }}</span>
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>

                                            @php
                                                // Forced half day dates (already shown in LATE/EARLY ½ DAY tabs)
                                                $forcedDates = array_merge($summary['direct_late_dates'] ?? [], $summary['direct_early_dates'] ?? []);
                                                // Policy-forced dates are shown in LATE ½ DAY, not in ABSENT
                                                $policyForcedDates = $summary['hd_policy_forced_dates'] ?? [];
                                                // Filter out forced dates from leave/absent tabs to avoid duplicates
                                                $filteredHdLeaveDates = array_diff($summary['hd_leave_dates'] ?? [], $forcedDates);
                                                $filteredHdAbsentDates = array_diff($summary['hd_absent_dates'] ?? [], $forcedDates, $policyForcedDates);
                                            @endphp

                                            {{-- Leave (full + half day leaves, excluding forced dates) --}}
                                            <div class="col-md-6">
                                                <div class="p-2 rounded border bg-white h-100">
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <span class="badge bg-secondary" style="font-size:.65rem;">{{ __('LEAVE') }}</span>
                                                    </div>
                                                    <small class="text-muted">
                                                        @if(!empty($summary['leave_dates']) || count($filteredHdLeaveDates) > 0)
                                                            @foreach(($summary['leave_dates'] ?? []) as $d)
                                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border" style="font-size:.7rem;">{{ $d }}</span>
                                                            @endforeach
                                                            @foreach($filteredHdLeaveDates as $d)
                                                                <span class="badge bg-secondary bg-opacity-10 text-warning border" style="font-size:.7rem;" data-bs-toggle="tooltip" title="{{ __('Half Day Leave') }}">{{ $d }} <small>½</small></span>
                                                            @endforeach
                                                        @else
                                                            <span class="text-muted">{{ __('None') }}</span>
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>

                                            {{-- Absent (full + half day absents, excluding forced dates) --}}
                                            <div class="col-md-6">
                                                <div class="p-2 rounded border bg-white h-100">
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <span class="badge bg-danger" style="font-size:.65rem;">{{ __('ABSENT') }}</span>
                                                    </div>
                                                    <small class="text-muted">
                                                        @if(!empty($summary['absent_dates']) || count($filteredHdAbsentDates) > 0)
                                                            @foreach(($summary['absent_dates'] ?? []) as $d)
                                                                <span class="badge bg-danger bg-opacity-10 text-danger border" style="font-size:.7rem;">{{ $d }}</span>
                                                            @endforeach
                                                            @foreach($filteredHdAbsentDates as $d)
                                                                <span class="badge bg-danger bg-opacity-10 text-warning border" style="font-size:.7rem;" data-bs-toggle="tooltip" title="{{ __('Half Day Absent') }}">{{ $d }} <small>½</small></span>
                                                            @endforeach
                                                        @else
                                                            <span class="text-muted">{{ __('None') }}</span>
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                        @endforeach
                    @else
                        <p class="text-muted py-3 text-center">{{ __('No policy data available.') }}</p>
                    @endif
                </div>
            </div>
        </div>{{-- end col-xl-5 policy --}}

        <div class="col-xl-7">
            <div class="card" id="attendanceTableCard">
                <div class="card-header card-body table-border-style">
                    {{-- Custom Filters --}}
                    <div class="row g-2 mb-3 align-items-end" id="attn-custom-filters">
                        @if (!empty($showEmployeeColumn) && $showEmployeeColumn)
                        <div class="col-auto">
                            <label class="form-label mb-1" style="font-size:.78rem;">{{ __('Employee') }}</label>
                            <select id="attn-filter-employee" class="form-select form-select-sm" style="min-width:130px;">
                                <option value="">{{ __('All Employees') }}</option>
                            </select>
                        </div>
                        @endif
                        <div class="col-auto">
                            <label class="form-label mb-1" style="font-size:.78rem;">{{ __('Date') }}</label>
                            <select id="attn-filter-date" class="form-select form-select-sm" style="min-width:80px;">
                                <option value="">{{ __('All') }}</option>
                                @for($d = 1; $d <= 31; $d++)
                                    <option value="{{ $d }}">{{ $d }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-auto">
                            <label class="form-label mb-1" style="font-size:.78rem;">{{ __('Status') }}</label>
                            <select id="attn-filter-status" class="form-select form-select-sm" style="min-width:110px;">
                                <option value="">{{ __('All') }}</option>
                                <option value="present">{{ __('Present') }}</option>
                                <option value="half day">{{ __('Half Day') }}</option>
                                <option value="absent">{{ __('Absent') }}</option>
                                <option value="leave">{{ __('Leave') }}</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <label class="form-label mb-1" style="font-size:.78rem;">{{ __('Late In') }}</label>
                            <select id="attn-filter-late" class="form-select form-select-sm" style="min-width:80px;">
                                <option value="">{{ __('All') }}</option>
                                <option value="1">{{ __('Yes') }}</option>
                                <option value="0">{{ __('No') }}</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <label class="form-label mb-1" style="font-size:.78rem;">{{ __('Early Out') }}</label>
                            <select id="attn-filter-early" class="form-select form-select-sm" style="min-width:80px;">
                                <option value="">{{ __('All') }}</option>
                                <option value="1">{{ __('Yes') }}</option>
                                <option value="0">{{ __('No') }}</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="button" id="attn-filter-apply" class="btn btn-sm btn-primary">
                                <i class="ti ti-filter"></i> {{ __('Filter') }}
                            </button>
                        </div>
                        <div class="col-auto">
                            <button type="button" id="attn-filter-reset" class="btn btn-sm btn-outline-secondary">
                                <i class="ti ti-refresh"></i>
                            </button>
                        </div>
                        <div class="col-auto">
                            <span id="attn-filter-count" class="badge bg-primary" style="font-size:.75rem;"></span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span id="attendanceKpiFilterLabel" class="badge bg-light-primary text-primary d-none"></span>
                        <button type="button" id="attendanceKpiReset" class="btn btn-sm btn-outline-secondary d-none">{{ __('Reset KPI Filter') }}</button>
                    </div>
                    @if (\Auth::user()->type == 'employee')
                        <p class="text-muted small mb-2"><i class="ti ti-user-check me-1"></i>{{ __('Clock In Photo and Clock Out Photo are verified with your profile photo. Both must match for attendance.') }}</p>
                    @endif
                    <div class="table-responsive">
                        <table class="table" id="attendance-data-table">
                            <thead>
                                <tr>
                                    @if (!empty($showEmployeeColumn) && $showEmployeeColumn)
                                        <th>{{ __('Employee') }}</th>
                                    @endif
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Clock In') }}</th>
                                    <th>{{ __('Clock Out') }}</th>
                                    <th>{{ __('Late In') }}</th>
                                    <th>{{ __('Early Out') }}</th>
                                    <th>{{ __('Overtime') }}</th>
                                    <th>{{ __('Device Type') }}</th>
                                    <th>{{ __('Location') }}</th>
                                    <th>{{ __('Clock In Photo') }}</th>
                                    <th>{{ __('Device Type Out') }}</th>
                                    <th>{{ __('Location Out') }}</th>
                                    <th>{{ __('Clock Out Photo') }}</th>
                                    <th>{{ __('Reporting Manager') }}</th>
                                    <th>{{ __('Shift') }}</th>
                                    @if (Gate::check('Edit Attendance') || Gate::check('Delete Attendance'))
                                        <th width="200px">{{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($attendanceEmployee as $attendance)
                                    @php
                                        $summaryRow = null;
                                        if (!empty($attendanceSummary) && count($attendanceSummary) > 0) {
                                            foreach ($attendanceSummary as $summaryCandidate) {
                                                if ((int) ($summaryCandidate['employee_id'] ?? 0) === (int) $attendance->employee_id) {
                                                    $summaryRow = $summaryCandidate;
                                                    break;
                                                }
                                            }
                                        }

                                        $attendanceDay = \Carbon\Carbon::parse($attendance->date)->format('j');
                                        $directLateDates = $summaryRow['direct_late_dates'] ?? [];
                                        $directEarlyDates = $summaryRow['direct_early_dates'] ?? [];
                                        $exemptDates = $summaryRow['exempt_dates'] ?? [];
                                        $postExemptionDates = $summaryRow['post_exemption_dates'] ?? [];
                                        $pendingDates = $summaryRow['pending_dates'] ?? [];
                                        $deductionGroups = $summaryRow['deduction_groups'] ?? [];

                                        $inPolicySet = false;
                                        foreach ($deductionGroups as $groupDates) {
                                            if (in_array($attendanceDay, $groupDates)) {
                                                $inPolicySet = true;
                                                break;
                                            }
                                        }
                                    @endphp
                                    <tr class="attendance-row"
                                        data-employee-name="{{ !empty($attendance->employee) ? $attendance->employee->name : '' }}"
                                        data-day="{{ \Carbon\Carbon::parse($attendance->date)->format('j') }}"
                                        data-status="{{ strtolower((string) $attendance->status) }}"
                                        data-late-mark="{{ (int) ($attendance->late_mark ?? 0) }}"
                                        data-early-mark="{{ (int) ($attendance->early_mark ?? 0) }}"
                                        data-less-hours-mark="{{ (int) ($attendance->less_hours_mark ?? 0) }}"
                                        data-deduction-units="{{ (float) ($attendance->deduction_units ?? 0) }}"
                                        data-direct-late-half-day="{{ in_array($attendanceDay, $directLateDates) ? '1' : '0' }}"
                                        data-direct-early-half-day="{{ in_array($attendanceDay, $directEarlyDates) ? '1' : '0' }}"
                                        data-exempt-mark="{{ in_array($attendanceDay, $exemptDates) ? '1' : '0' }}"
                                        data-post-exemption-mark="{{ in_array($attendanceDay, $postExemptionDates) ? '1' : '0' }}"
                                        data-policy-deduction-set="{{ $inPolicySet ? '1' : '0' }}"
                                        data-pending-mark="{{ in_array($attendanceDay, $pendingDates) ? '1' : '0' }}">
                                        @php
                                            $latestRequest = !empty($latestRequestByAttendance) ? ($latestRequestByAttendance[$attendance->id] ?? null) : null;
                                            $isOwnAttendance = !empty($currentEmployeeId) && (int) $attendance->employee_id === (int) $currentEmployeeId;
                                            $issueType = 'Present';
                                            if (strtolower((string) $attendance->status) === 'half day') {
                                                $issueType = 'Half Day';
                                            } elseif (in_array(strtolower((string) $attendance->status), ['leave', 'absent'])) {
                                                $issueType = 'Leave';
                                            } elseif (($attendance->late ?? '00:00:00') !== '00:00:00') {
                                                $issueType = 'Late Mark';
                                            } elseif (($attendance->early_leaving ?? '00:00:00') !== '00:00:00') {
                                                $issueType = 'Early After Leave';
                                            }
                                        @endphp

                                        @if (!empty($showEmployeeColumn) && $showEmployeeColumn)
                                            <td style="min-width: 260px;">
                                                {{ !empty($attendance->employee) ? $attendance->employee->name : '' }}

                                                @if (\Auth::user()->type == 'employee' && $isOwnAttendance && !empty($latestRequest))
                                                    <div class="mt-1">
                                                        <span class="badge @if ($latestRequest->status === 'Approved') bg-success @elseif($latestRequest->status === 'Rejected') bg-danger @else bg-warning @endif">
                                                            {{ $latestRequest->status }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </td>
                                        @endif
                                        <td data-sort="{{ $attendance->date }}">
                                            {{ \Auth::user()->dateFormat($attendance->date) }}
                                            @if (!empty($latestRequest))
                                                <div class="mt-1">
                                                    <span class="badge bg-warning text-dark">{{ __('Swipe Requested') }}</span>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $attendance->status }}
                                            @if (!empty($latestRequest))
                                                <div class="mt-1">
                                                    <span class="badge @if ($latestRequest->status === 'Approved') bg-success @elseif($latestRequest->status === 'Rejected') bg-danger @else bg-warning text-dark @endif">
                                                        {{ __('Swipe Request') }}: {{ $latestRequest->status }}
                                                    </span>
                                                </div>
                                            @endif
                                        </td>
                                        <td>{{ $attendance->clock_in != '00:00:00' ? \Auth::user()->timeFormat($attendance->clock_in) : '00:00' }}
                                        </td>
                                        <td>{{ $attendance->clock_out != '00:00:00' ? \Auth::user()->timeFormat($attendance->clock_out) : '00:00' }}
                                        </td>
                                        <td>{{ $attendance->late }}</td>
                                        <td>{{ $attendance->early_leaving }}</td>
                                        <td>{{ $attendance->overtime }}</td>
                                        <td>
                                            @if($attendance->device_type)
                                                <span class="badge badge-sm 
                                                    @if($attendance->device_type == 'Mobile') bg-info
                                                    @elseif($attendance->device_type == 'Desktop') bg-primary
                                                    @elseif($attendance->device_type == 'Tablet') bg-warning
                                                    @else bg-secondary
                                                    @endif">
                                                    {{ $attendance->device_type }}
                                                </span>
                                            @else
                                                <span class="text-muted">{{ __('N/A') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($attendance->latitude && $attendance->longitude)
                                                <a href="https://www.google.com/maps?q={{ $attendance->latitude }},{{ $attendance->longitude }}" 
                                                   target="_blank" 
                                                   class="btn btn-sm btn-info" 
                                                   data-bs-toggle="tooltip" 
                                                   title="{{ $attendance->address ?? 'View on Map' }}">
                                                    <i class="ti ti-map-pin"></i> {{ __('View Location') }}
                                                </a>
                                                @if($attendance->address)
                                                    <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($attendance->address, 50) }}</small>
                                                @endif
                                            @else
                                                <span class="text-muted">{{ __('N/A') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($attendance->photo_url)
                                                <a href="{{ $attendance->photo_url }}" target="_blank">
                                                    <img src="{{ $attendance->photo_url }}" 
                                                         alt="{{ __('Clock In Photo') }}" 
                                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px; cursor: pointer;"
                                                         data-bs-toggle="tooltip"
                                                         title="{{ __('Click to view full image') }}">
                                                </a>
                                                <div class="mt-1 small">
                                                    @if ($attendance->photo_verified == true || $attendance->photo_verified === 1)
                                                        <span class="badge bg-success">{{ __('Verified') }}</span>
                                                    @else
                                                        <span class="badge bg-danger">{{ __('Not verified') }}</span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted">{{ __('N/A') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($attendance->device_type_out)
                                                <span class="badge badge-sm 
                                                    @if($attendance->device_type_out == 'Mobile') bg-info
                                                    @elseif($attendance->device_type_out == 'Desktop') bg-primary
                                                    @elseif($attendance->device_type_out == 'Tablet') bg-warning
                                                    @else bg-secondary
                                                    @endif">
                                                    {{ $attendance->device_type_out }}
                                                </span>
                                            @else
                                                <span class="text-muted">{{ __('N/A') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($attendance->latitude_out && $attendance->longitude_out)
                                                <a href="https://www.google.com/maps?q={{ $attendance->latitude_out }},{{ $attendance->longitude_out }}" 
                                                   target="_blank" 
                                                   class="btn btn-sm btn-info" 
                                                   data-bs-toggle="tooltip" 
                                                   title="{{ $attendance->address_out ?? 'View on Map' }}">
                                                    <i class="ti ti-map-pin"></i> {{ __('View Location') }}
                                                </a>
                                                @if($attendance->address_out)
                                                    <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($attendance->address_out, 50) }}</small>
                                                @endif
                                            @else
                                                <span class="text-muted">{{ __('N/A') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($attendance->photo_out_url)
                                                <a href="{{ $attendance->photo_out_url }}" target="_blank">
                                                    <img src="{{ $attendance->photo_out_url }}" 
                                                         alt="{{ __('Clock Out Photo') }}" 
                                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px; cursor: pointer;"
                                                         data-bs-toggle="tooltip"
                                                         title="{{ __('Click to view full image') }}">
                                                </a>
                                                <div class="mt-1 small">
                                                    @if ($attendance->photo_out_verified == true || $attendance->photo_out_verified === 1)
                                                        <span class="badge bg-success">{{ __('Verified') }}</span>
                                                    @else
                                                        <span class="badge bg-danger">{{ __('Not verified') }}</span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted">{{ __('N/A') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if (!empty($attendance->employee) && !empty($attendance->employee->reportingManager))
                                                @if (\Auth::user()->can('Show Employee Profile'))
                                                    <a href="{{ route('show.employee.profile', \Illuminate\Support\Facades\Crypt::encrypt($attendance->employee->reportingManager->id)) }}"
                                                        class="text-primary">
                                                        {{ $attendance->employee->reportingManager->name }}
                                                    </a>
                                                @else
                                                    {{ $attendance->employee->reportingManager->name }}
                                                @endif
                                            @else
                                                {{ __('N/A') }}
                                            @endif
                                        </td>
                                        <td>
                                            @if($attendance->employee && $attendance->employee->shift)
                                                <span class="badge badge-sm bg-primary">{{ $attendance->employee->shift->name }}</span>
                                                <br><small class="text-muted">{{ \Carbon\Carbon::parse($attendance->employee->shift->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($attendance->employee->shift->end_time)->format('H:i') }}</small>
                                            @else
                                                <span class="text-muted">{{ __('N/A') }}</span>
                                            @endif
                                        </td>
                                        @if (Gate::check('Edit Attendance') || Gate::check('Delete Attendance'))
                                            <td class="Action">
                                                        @can('Edit Attendance')
                                                            <div class="action-btn me-2">
                                                                <a href="#" class="mx-3 btn btn-sm bg-info align-items-center"
                                                                    data-size="lg"
                                                                    data-url="{{ URL::to('attendanceemployee/' . $attendance->id . '/edit') }}"
                                                                    data-ajax-popup="true" data-size="md" data-bs-toggle="tooltip"
                                                                    title="" data-title="{{ __('Edit Attendance') }}"
                                                                    data-bs-original-title="{{ __('Edit') }}">
                                                                    <span class="text-white"><i class="ti ti-pencil"></i></span>
                                                                </a>
                                                            </div>
                                                        @endcan

                                                        @can('Delete Attendance')
                                                            <div class="action-btn">
                                                                {!! Form::open([
                                                                    'method' => 'DELETE',
                                                                    'route' => ['attendanceemployee.destroy', $attendance->id],
                                                                    'id' => 'delete-form-' . $attendance->id,
                                                                ]) !!}
                                                                <a href="#"
                                                                    class="mx-3 btn btn-sm bg-danger align-items-center bs-pass-para"
                                                                    data-bs-toggle="tooltip" title=""
                                                                    data-bs-original-title="Delete" aria-label="Delete"><span class="text-white"><i
                                                                        class="ti ti-trash"></i></span></a>
                                                                </form>
                                                            </div>
                                                        @endcan
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="swipeRequestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Swipe Modification Request') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="swipeRequestForm" action="{{ route('attendanceemployee.swipe-request') }}">
                    @csrf
                    <input type="hidden" name="attendance_employee_id" id="swipe_attendance_employee_id" value="">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">{{ __('Request Date') }}</label>
                            <input type="date" class="form-control" name="request_date" id="swipe_request_date">
                        </div>

                        <div class="row">
                            <div class="col-6 mb-2">
                                <label class="form-label">{{ __('Requested In') }}</label>
                                <input type="time" class="form-control" name="requested_clock_in" id="swipe_requested_clock_in">
                            </div>
                            <div class="col-6 mb-2">
                                <label class="form-label">{{ __('Requested Out') }}</label>
                                <input type="time" class="form-control" name="requested_clock_out" id="swipe_requested_clock_out">
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">{{ __('Reason') }}</label>
                            <textarea class="form-control" rows="3" name="reason" id="swipe_reason" required></textarea>
                        </div>

                        <div class="alert alert-warning py-2 mb-0">
                            {{ __('On manager approval, this attendance day will be marked as full day leave.') }}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary" id="swipeSubmitBtn">{{ __('Send Request') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if (\Auth::user()->can('Manage Attendance'))
    <div class="modal fade" id="swipeHistoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Swipe Request History') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">{{ __('Filtered by current page filters (date/month, employee).') }}</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    @if (\Auth::user()->type != 'employee')
                                        <th>{{ __('Employee') }}</th>
                                    @endif
                                    <th>{{ __('Requested Status') }}</th>
                                    <th>{{ __('Requested In/Out') }}</th>
                                    <th>{{ __('Reason') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Manager Comment') }}</th>
                                    <th>{{ __('Created') }}</th>
                                </tr>
                            </thead>
                            <tbody id="swipeHistoryTableBody">
                                <tr><td colspan="{{ \Auth::user()->type == 'employee' ? 7 : 8 }}" class="text-center text-muted">{{ __('Loading...') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var swipeHistoryModal = document.getElementById('swipeHistoryModal');
            if (swipeHistoryModal) {
                swipeHistoryModal.addEventListener('shown.bs.modal', function() {
                    var form = document.getElementById('attendanceemployee_filter');
                    var params = [];
                    if (form) {
                        var fd = new FormData(form);
                        fd.forEach(function(v, k) { params.push(encodeURIComponent(k) + '=' + encodeURIComponent(v)); });
                    }
                    var qs = params.join('&');
                    fetch('{{ route("attendanceemployee.swipe-history") }}?' + qs, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    }).then(function(r) { return r.json(); }).then(function(data) {
                        var tbody = document.getElementById('swipeHistoryTableBody');
                        var requests = data.requests || [];
                        var isEmployee = {{ \Auth::user()->type == 'employee' ? 'true' : 'false' }};
                        function timeStr(t) { return t ? t.substring(0, 5) : '--'; }
                        if (requests.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="' + (isEmployee ? 7 : 8) + '" class="text-center text-muted">{{ __("No swipe requests found for the selected filters.") }}</td></tr>';
                            return;
                        }
                        tbody.innerHTML = requests.map(function(r) {
                            var row = '<tr><td>' + (r.date || '') + '</td>';
                            if (!isEmployee) row += '<td>' + (r.employee_name || '') + '</td>';
                            row += '<td>' + (r.requested_status || '') + '</td>';
                            row += '<td>' + timeStr(r.requested_clock_in) + ' / ' + timeStr(r.requested_clock_out) + '</td>';
                            row += '<td>' + (r.reason ? r.reason.substring(0, 80) + (r.reason.length > 80 ? '...' : '') : '') + '</td>';
                            row += '<td><span class="badge bg-' + (r.status === 'Approved' ? 'success' : r.status === 'Rejected' ? 'danger' : 'warning') + '">' + (r.status || '') + '</span></td>';
                            row += '<td>' + (r.manager_comment || '') + '</td>';
                            row += '<td>' + (r.created_at || '') + '</td></tr>';
                            return row;
                        }).join('');
                    }).catch(function() {
                        var tbody = document.getElementById('swipeHistoryTableBody');
                        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">{{ __("Failed to load history.") }}</td></tr>';
                    });
                });
            }
        });
    </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modalButtons = document.querySelectorAll('.js-open-swipe-modal');
            const form = document.getElementById('swipeRequestForm');
            const attendanceInput = document.getElementById('swipe_attendance_employee_id');
            const requestDateInput = document.getElementById('swipe_request_date');
            const inInput = document.getElementById('swipe_requested_clock_in');
            const outInput = document.getElementById('swipe_requested_clock_out');
            const reasonInput = document.getElementById('swipe_reason');
            const submitBtn = document.getElementById('swipeSubmitBtn');

            if (!form || !attendanceInput || !requestDateInput || !reasonInput || !submitBtn) {
                return;
            }

            modalButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const openMode = button.getAttribute('data-open-mode') || 'row';
                    const attendanceId = button.getAttribute('data-attendance-id') || '';
                    const attendanceDate = button.getAttribute('data-attendance-date') || '';
                    const requestStatus = button.getAttribute('data-request-status') || '';
                    const requestReason = button.getAttribute('data-request-reason') || '';
                    const requestClockIn = button.getAttribute('data-request-clock-in') || '';
                    const requestClockOut = button.getAttribute('data-request-clock-out') || '';
                    const storeUrl = button.getAttribute('data-store-url') || form.getAttribute('action');
                    const updateUrl = button.getAttribute('data-update-url') || '';

                    attendanceInput.value = attendanceId;
                    requestDateInput.value = attendanceDate;
                    if (inInput) inInput.value = requestClockIn;
                    if (outInput) outInput.value = requestClockOut;
                    reasonInput.value = requestReason;

                    if (openMode === 'top') {
                        attendanceInput.value = '';
                        if (!requestDateInput.value) {
                            requestDateInput.value = "{{ date('Y-m-d') }}";
                        }
                        requestDateInput.removeAttribute('readonly');
                        requestDateInput.setAttribute('required', 'required');
                    } else {
                        requestDateInput.setAttribute('readonly', 'readonly');
                        requestDateInput.removeAttribute('required');
                    }

                    if (requestStatus === 'Pending' && updateUrl) {
                        form.setAttribute('action', updateUrl);
                        submitBtn.textContent = "{{ __('Update Request') }}";
                    } else {
                        form.setAttribute('action', storeUrl);
                        submitBtn.textContent = "{{ __('Send Request') }}";
                    }
                });
            });
        });
    </script>

    @if (\Auth::user()->type != 'employee' && !empty($pendingSwipeRequestCount) && $pendingSwipeRequestCount > 0)
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                try {
                    const audioCtx = new(window.AudioContext || window.webkitAudioContext)();
                    const osc = audioCtx.createOscillator();
                    const gain = audioCtx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(880, audioCtx.currentTime);
                    gain.gain.setValueAtTime(0.001, audioCtx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.08, audioCtx.currentTime + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.2);
                    osc.connect(gain);
                    gain.connect(audioCtx.destination);
                    osc.start();
                    osc.stop(audioCtx.currentTime + 0.22);
                } catch (e) {
                }
            });
        </script>
    @endif
@endsection
