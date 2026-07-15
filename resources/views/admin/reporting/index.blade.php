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
                                <form method="GET" action="{{ route('reporting.index') }}" class="mb-4">
                                    <div class="row align-items-end">
                                        <div class="col-md-3 mb-3">
                                            <label for="role">Role</label>
                                            <select name="role" id="role" class="form-control" required>
                                                <option value="" disabled {{ empty($role) ? 'selected' : '' }}>Select Role</option>
                                                <option value="asm" {{ $role === 'asm' ? 'selected' : '' }}>Area Sales Manager</option>
                                                <option value="branch_manager" {{ $role === 'branch_manager' ? 'selected' : '' }}>Branch Manager</option>
                                                <option value="sale_staff" {{ $role === 'sale_staff' ? 'selected' : '' }}>Sales Staff</option>
                                            </select>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="id">Select by ID</label>
                                            <select name="id" id="id" class="form-control select2" required>
                                                <option value="">Select...</option>
                                            </select>
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <label for="period">Period</label>
                                            <select name="period" id="period" class="form-control" required>
                                                <option value="weekly" {{ $period === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                                <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                            </select>
                                        </div>

                                        <div class="col-md-2 mb-3">
                                            <button type="submit" class="btn btn-primary btn-block">Filter</button>
                                        </div>
                                    </div>
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
                                        Select a role, person ID, and period to view performance comparisons (as shown in the app).
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
