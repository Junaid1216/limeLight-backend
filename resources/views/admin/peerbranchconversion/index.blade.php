@extends('admin.layout.app')
@section('title', 'Peer Branch Conversion')

@section('content')
    <div class="main-content" style="min-height: 562px;">
        <section class="section">
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Peer Branch Conversion</h4>
                            </div>
                            <div class="card-body">
                                <form method="GET" action="{{ route('peer.branch.conversion.index') }}" class="mb-4">
                                    <div class="row align-items-end">
                                        <div class="col-md-4 mb-3">
                                            <label for="id">Branch Manager</label>
                                            <select name="id" id="id" class="form-control select2" required>
                                                <option value="">Select Branch Manager</option>
                                                @foreach ($branchManagerOptions as $option)
                                                    <option value="{{ $option['id'] }}" {{ (string) $id === (string) $option['id'] ? 'selected' : '' }}>
                                                        {{ $option['label'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-2 mb-3">
                                            <label for="period">Period</label>
                                            <select name="period" id="period" class="form-control" required>
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

                                        <div class="col-md-2 mb-3">
                                            <button type="submit" class="btn btn-primary btn-block">Filter</button>
                                        </div>
                                    </div>
                                    <small class="text-muted">Optional: set From/To date to override the period range.</small>
                                </form>

                                @if ($selected)
                                    <div class="alert alert-light border mb-4">
                                        <strong>Branch Manager:</strong>
                                        {{ $selected->name }} (ID: {{ $selected->id }})
                                        @if ($selected->branch)
                                            — Branch: {{ $selected->branch->name }}
                                        @endif
                                        <br>
                                        <small>
                                            Period: {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}
                                        </small>
                                    </div>

                                    @if ($yourBranch)
                                        <div class="alert alert-success py-2">
                                            <strong>Your Branch:</strong>
                                            {{ $yourBranch['branch'] }}
                                            — Rank #{{ $yourBranch['rank'] }},
                                            Conversion {{ $yourBranch['conversion_percentage'] }}%
                                            (Traffic: {{ number_format($yourBranch['traffic']) }},
                                            Invoices: {{ number_format($yourBranch['invoices']) }})
                                        </div>
                                    @endif

                                    <div class="mb-4">
                                        <h5 class="mb-3">Peer Branch Conversion</h5>
                                        <div class="table-responsive">
                                            <table class="table" id="table_id_events">
                                                <thead>
                                                    <tr>
                                                        <th>Rank</th>
                                                        <th>Branch</th>
                                                        <th>Traffic</th>
                                                        <th>Invoices</th>
                                                        <th>Conversion %</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($rows as $row)
                                                        <tr class="{{ ($yourBranch && $row['branch_id'] == $yourBranch['branch_id']) ? 'table-success' : '' }}">
                                                            <td>{{ $row['rank'] }}</td>
                                                            <td>{{ $row['branch'] }}</td>
                                                            <td>{{ number_format($row['traffic']) }}</td>
                                                            <td>{{ number_format($row['invoices']) }}</td>
                                                            <td>{{ $row['conversion_percentage'] }}%</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="text-center">No conversion data.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-info mb-0">
                                        Select a branch manager and period to view peer branch conversion.
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
    if ($.fn.select2) {
        $('#id').select2({
            width: '100%',
            placeholder: 'Select Branch Manager'
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

        if (to && to > today) {
            $('#to_date').val(today);
            to = today;
            if (typeof toastr !== 'undefined') {
                toastr.error('To Date cannot be after today.');
            }
        }

        if (from && from > today) {
            $('#from_date').val(today);
            from = today;
            if (typeof toastr !== 'undefined') {
                toastr.error('From Date cannot be after today.');
            }
        }

        if (from && to && from >= to) {
            if (changedId === 'from_date') {
                $('#from_date').val('');
                if (typeof toastr !== 'undefined') {
                    toastr.error('From Date must be less than To Date.');
                }
            } else {
                $('#to_date').val('');
                if (typeof toastr !== 'undefined') {
                    toastr.error('To Date must be greater than From Date.');
                }
            }
        }

        applyDateLimits();
    }

    applyDateLimits();

    $('#period').on('change', function () {
        if (($(this).val() || '').trim()) {
            $('#from_date').val('');
            $('#to_date').val('');
            applyDateLimits();
        }
    });

    $('#from_date, #to_date').on('change input', function () {
        if (($('#from_date').val() || '').trim() || ($('#to_date').val() || '').trim()) {
            // keep period if required on this page — clear only when both dates set
            if (($('#from_date').val() || '').trim() && ($('#to_date').val() || '').trim()) {
                // optional: leave period as-is for peer (period still required in UI)
            }
        }
        validateDateSelection(this.id);
    });

    @if ($selected && count($rows))
        $('#table_id_events').DataTable({
            order: [[0, 'asc']],
            pageLength: 10
        });
    @endif
});
</script>
@endsection
