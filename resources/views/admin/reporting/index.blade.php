@extends('admin.layout.app')
@section('title', 'Reportings')

@section('content')
    <div class="main-content" style="min-height: 562px;">
        <section class="section">
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Performance Comparisons</h4>
                            </div>
                            <div class="card-body">
                                <form method="GET" action="{{ route('reporting.index') }}" id="reportingFilterForm" class="mb-4" novalidate>
                                    <div id="filterMessage" class="alert d-none mb-3" role="alert"></div>
                                    <div class="row align-items-end">
                                        <div class="col-md-3 mb-3">
                                            <label for="role">Role</label>
                                            <select name="role" id="role" class="form-control">
                                                <option value="" {{ empty($role) ? 'selected' : '' }}>Select Role</option>
                                                <option value="asm" {{ $role === 'asm' ? 'selected' : '' }}>Area Sales Manager</option>
                                                <option value="branch_manager" {{ $role === 'branch_manager' ? 'selected' : '' }}>Branch Manager</option>
                                                <option value="sale_staff" {{ $role === 'sale_staff' ? 'selected' : '' }}>Sales Staff</option>
                                            </select>
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <label for="id">Select by ID</label>
                                            <select name="id" id="id" class="form-control select2">
                                                <option value="">Select...</option>
                                            </select>
                                        </div>

                                        <div class="col-md-2 mb-3">
                                            <label for="period">Period</label>
                                            <select name="period" id="period" class="form-control">
                                                <option value="" {{ empty($period) ? 'selected' : '' }}>Select</option>
                                                <option value="weekly" {{ $period === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                                <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                            </select>
                                        </div>

                                        <div class="col-md-2 mb-3">
                                            <label for="from_date">From Date</label>
                                            <input type="date" name="from_date" id="from_date" class="form-control"
                                                   value="{{ $from_date ?? '' }}">
                                        </div>

                                        <div class="col-md-2 mb-3">
                                            <label for="to_date">To Date</label>
                                            <input type="date" name="to_date" id="to_date" class="form-control"
                                                   value="{{ $to_date ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="row align-items-end">
                                        <div class="col-md-2 mb-3 ml-auto">
                                            <button type="submit" class="btn btn-primary btn-block">Filter</button>
                                        </div>
                                    </div>
                                    <small class="text-muted">Required: Role, Select by ID, and either Period or From/To dates (not both).</small>
                                </form>

                                @if ($selected && $comparisons)
                                    <div class="alert alert-light border mb-4">
                                        <strong>
                                            @if ($role === 'asm')
                                                ASM
                                            @elseif ($role === 'branch_manager')
                                                Branch Manager
                                            @else
                                                Sales Staff
                                            @endif
                                            :
                                        </strong>
                                        {{ $selected->name }} (ID: {{ $selected->id }})
                                        <br>
                                        <small>
                                            Period: {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}
                                        </small>
                                    </div>

                                    @if ($comparisons['type'] === 'asm')
                                        @include('admin.reporting.partials.asm', ['data' => $comparisons])
                                    @elseif ($comparisons['type'] === 'branch_manager')
                                        @include('admin.reporting.partials.branch_manager', ['data' => $comparisons])
                                    @else
                                        @include('admin.reporting.partials.sale_staff', ['data' => $comparisons])
                                    @endif
                                @else
                                    <div class="alert alert-info mb-0">
                                        Select Role, person ID, and either Period or From/To dates to view performance comparisons.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('js')
<script>
$(document).ready(function () {
    const asms = @json($asmOptions);
    const branchManagers = @json($branchManagerOptions);
    const saleStaff = @json($saleStaffOptions);
    let selectedId = @json($id ? (string) $id : '');

    function showFilterMessage(type, text) {
        const $box = $('#filterMessage');
        $box.removeClass('d-none alert-danger alert-success alert-warning alert-info')
            .addClass(type === 'success' ? 'alert-success' : 'alert-danger')
            .text(text)
            .show();

        if (typeof toastr !== 'undefined') {
            if (type === 'success') {
                toastr.success(text);
            } else {
                toastr.error(text);
            }
        }
    }

    function populateIds(role) {
        let options = [];

        if (role === 'asm') {
            options = asms;
        } else if (role === 'branch_manager') {
            options = branchManagers;
        } else if (role === 'sale_staff') {
            options = saleStaff;
        }

        const $select = $('#id');
        $select.empty().append($('<option>', { value: '', text: 'Select...' }));

        options.forEach(function (item) {
            $select.append($('<option>', {
                value: item.id,
                text: item.label,
                selected: String(item.id) === selectedId
            }));
        });

        if ($select.hasClass('select2-hidden-accessible')) {
            $select.trigger('change.select2');
        }
    }

    populateIds($('#role').val() || '');

    $('#role').on('change', function () {
        selectedId = '';
        populateIds($(this).val());
    });

    if ($.fn.select2) {
        $('#id').select2({
            width: '100%',
            placeholder: 'Select...'
        });
    }

    function todayYmd() {
        const d = new Date();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return d.getFullYear() + '-' + m + '-' + day;
    }

    function applyDateLimits() {
        const today = todayYmd();
        $('#from_date').attr('max', today);
        $('#to_date').attr('max', today);

        const from = ($('#from_date').val() || '').trim();
        if (from) {
            $('#to_date').attr('min', from);
        } else {
            $('#to_date').removeAttr('min');
        }

        const to = ($('#to_date').val() || '').trim();
        if (to) {
            $('#from_date').attr('max', to < today ? to : today);
        } else {
            $('#from_date').attr('max', today);
        }
    }

    function validateDateSelection(changedId) {
        const today = todayYmd();
        let from = ($('#from_date').val() || '').trim();
        let to = ($('#to_date').val() || '').trim();
        let ok = true;

        if (to && to > today) {
            $('#to_date').val(today);
            to = today;
            showFilterMessage('error', 'To Date cannot be after today.');
            ok = false;
        }

        if (from && from > today) {
            $('#from_date').val(today);
            from = today;
            showFilterMessage('error', 'From Date cannot be after today.');
            ok = false;
        }

        if (from && to && from >= to) {
            if (changedId === 'from_date') {
                $('#from_date').val('');
                from = '';
                showFilterMessage('error', 'From Date must be less than To Date.');
            } else {
                $('#to_date').val('');
                to = '';
                showFilterMessage('error', 'To Date must be greater than From Date.');
            }
            ok = false;
        }

        applyDateLimits();
        return ok;
    }

    applyDateLimits();

    // Period OR date range — only one active at a time
    $('#period').on('change', function () {
        if (($(this).val() || '').trim()) {
            $('#from_date').val('');
            $('#to_date').val('');
            applyDateLimits();
        }
    });

    $('#from_date, #to_date').on('change input', function () {
        if (($('#from_date').val() || '').trim() || ($('#to_date').val() || '').trim()) {
            $('#period').val('');
        }
        validateDateSelection(this.id);
    });

    $('#reportingFilterForm').on('submit', function (e) {
        const role = ($('#role').val() || '').trim();
        const id = ($('#id').val() || '').trim();
        let period = ($('#period').val() || '').trim();
        let fromDate = ($('#from_date').val() || '').trim();
        let toDate = ($('#to_date').val() || '').trim();

        // Enforce single filter before submit
        if (period) {
            $('#from_date').val('');
            $('#to_date').val('');
            fromDate = '';
            toDate = '';
        } else if (fromDate || toDate) {
            $('#period').val('');
            period = '';
        }

        if (!role || !id) {
            e.preventDefault();
            showFilterMessage('error', 'Please select Role and Select by ID before filtering.');
            return false;
        }

        if (!period && !(fromDate && toDate)) {
            e.preventDefault();
            showFilterMessage('error', 'Please select Period, or set both From Date and To Date.');
            return false;
        }

        if (!period && (fromDate || toDate) && !validateDateSelection(fromDate ? 'from_date' : 'to_date')) {
            e.preventDefault();
            return false;
        }

        if (!period && fromDate && toDate && (fromDate >= toDate || toDate > todayYmd())) {
            e.preventDefault();
            showFilterMessage('error', 'From Date must be less than To Date, and To Date cannot be after today.');
            return false;
        }

        showFilterMessage('success', 'Applying filters...');
    });

    $('.report-datatable').each(function () {
        var $table = $(this);
        if ($table.find('tbody tr td[colspan]').length === 0 && $table.find('tbody tr').length > 0) {
            $table.DataTable({
                order: [[0, 'asc']],
                pageLength: 10
            });
        }
    });
});
</script>
@endsection
