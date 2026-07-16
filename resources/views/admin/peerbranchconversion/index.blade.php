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
                                        <div class="col-md-5 mb-3">
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

                                        <div class="col-md-4 mb-3">
                                            <label for="period">Period</label>
                                            <select name="period" id="period" class="form-control" required>
                                                <option value="weekly" {{ $period === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                                <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                            </select>
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <button type="submit" class="btn btn-primary btn-block">Filter</button>
                                        </div>
                                    </div>
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

    @if ($selected && count($rows))
        $('#table_id_events').DataTable({
            order: [[0, 'asc']],
            pageLength: 10
        });
    @endif
});
</script>
@endsection
