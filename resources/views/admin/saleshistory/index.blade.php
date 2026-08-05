@extends('admin.layout.app')
@section('title', 'Sales History')

@section('content')
    <div class="main-content" style="min-height: 562px;">
        <section class="section">
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Sales History</h4>
                            </div>
                            <div class="card-body">
                                <form method="GET" action="{{ route('sales.history.index') }}" class="mb-4">
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
                                                <!-- <option value="daily" {{ $period === 'daily' ? 'selected' : '' }}>Daily</option> -->
                                                <option value="weekly" {{ $period === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                                <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                            </select>
                                        </div>

                                        <div class="col-md-2 mb-3">
                                            <button type="submit" class="btn btn-primary btn-block">Filter</button>
                                        </div>
                                    </div>
                                </form>

                                @if ($selected)
                                    <div class="alert alert-light border mb-4">
                                        <div class="row">
                                            <div class="col-md-6">
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
                                                {{ $selected->name }}
                                                (ID: {{ $selected->id }}
                                                @if (!empty($selected->employee_id))
                                                    | Emp: {{ $selected->employee_id }}
                                                @endif
                                                )
                                                <br>
                                                <small>
                                                    Period:
                                                    {{ $from->format('d M Y') }}
                                                    –
                                                    {{ $to->format('d M Y') }}
                                                </small>
                                            </div>
                                            <div class="col-md-6 text-md-right mt-2 mt-md-0">
                                                <strong>Invoices:</strong> {{ $summary['invoices'] }}
                                                &nbsp;|&nbsp;
                                                <strong>Qty:</strong> {{ $summary['quantity'] }}
                                                &nbsp;|&nbsp;
                                                <strong>Total Sales:</strong>
                                                {{ number_format($summary['total_sales'], 2) }}
                                                @if ($role === 'branch_manager')
                                                    &nbsp;|&nbsp;
                                                    <strong>Commission:</strong>
                                                    {{ number_format($summary['commission'] ?? 0, 2) }}
                                                @endif
                                                @if ($role === 'sale_staff')
                                                    &nbsp;|&nbsp;
                                                    <strong>Slip Bound Incentive:</strong>
                                                    {{ number_format($summary['slip_bound_incentive'] ?? 0, 2) }}
                                                    &nbsp;|&nbsp;
                                                    <strong>Commission:</strong>
                                                    {{ number_format($summary['commission'] ?? 0, 2) }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table" id="table_id_events">
                                            <thead>
                                                <tr>
                                                    <th>Sr.</th>
                                                    <th>Invoice ID</th>
                                                    <th>Branch</th>
                                                    <th>Date</th>
                                                    <th>Salesperson</th>
                                                    <th>Quantity</th>
                                                    <th>Amount</th>
                                                    @if ($role === 'branch_manager')
                                                        <th>Commission</th>
                                                    @endif
                                                    @if ($role === 'sale_staff')
                                                        <th>Slip Bound Incentive</th>
                                                        <th>Commission</th>
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($rows as $row)
                                                    <tr>
                                                        <td>{{ $loop->iteration }}</td>
                                                        <td>{{ $row['invoice_id'] }}</td>
                                                        <td>{{ $row['branch'] ?? '-' }}</td>
                                                        <td>{{ $row['date'] ?? '-' }}</td>
                                                        <td>{{ $row['salesperson'] }}</td>
                                                        <td>{{ $row['quantity'] }}</td>
                                                        <td>{{ number_format($row['amount'], 2) }}</td>
                                                        @if ($role === 'branch_manager')
                                                            <td>{{ number_format($row['commission'] ?? 0, 2) }}</td>
                                                        @endif
                                                        @if ($role === 'sale_staff')
                                                            <td>{{ number_format($row['slip_bound_incentive'] ?? 0, 2) }}</td>
                                                            <td>{{ number_format($row['commission'] ?? 0, 2) }}</td>
                                                        @endif
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="{{ $role === 'sale_staff' ? 9 : ($role === 'branch_manager' ? 8 : 7) }}" class="text-center">No sales found for the selected filters.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-info mb-0">
                                        Select a role, person ID, and period, then click Filter to view sales history.
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

    @if ($selected && count($rows))
        $('#table_id_events').DataTable();
    @endif
});
</script>
@endsection
