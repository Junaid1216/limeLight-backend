@if (!empty($data['summary']))
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card card-statistic-1 mb-0">
                <div class="card-wrap">
                    <div class="card-header"><h4>Branch</h4></div>
                    <div class="card-body">{{ $data['summary']['branch'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card card-statistic-1 mb-0">
                <div class="card-wrap">
                    <div class="card-header"><h4>Monthly Target</h4></div>
                    <div class="card-body">{{ number_format($data['summary']['monthly_target']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card card-statistic-1 mb-0">
                <div class="card-wrap">
                    <div class="card-header"><h4>Achieved</h4></div>
                    <div class="card-body">{{ number_format($data['summary']['achieved'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card card-statistic-1 mb-0">
                <div class="card-wrap">
                    <div class="card-header"><h4>Achievement %</h4></div>
                    <div class="card-body">{{ $data['summary']['achieved_percentage'] }}%</div>
                </div>
            </div>
        </div>
    </div>
@endif

<div class="mb-4">
    <h5 class="mb-3">Staff Comparison</h5>
    <div class="table-responsive">
        <table class="table report-datatable">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Staff</th>
                    <th>Target</th>
                    <th>Achieved</th>
                    <th>Achievement %</th>
                    <th>Remaining %</th>
                    <th>Commission</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data['staff_comparison'] as $row)
                    <tr>
                        <td>{{ $row['rank'] }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ number_format($row['target']) }}</td>
                        <td>{{ number_format($row['achieved']) }}</td>
                        <td>{{ $row['achievement_percentage'] }}%</td>
                        <td>{{ $row['remaining_percentage'] }}%</td>
                        <td>{{ number_format($row['commission'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No staff found for this branch.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mb-4">
    <h5 class="mb-3">Peer Branch Conversion</h5>
    <div class="table-responsive">
        <table class="table report-datatable">
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
                @forelse ($data['branch_conversion'] as $row)
                    <tr>
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

<div class="mb-2">
    <h5 class="mb-3">Peer Branch Category Comparison</h5>
    @foreach ($data['branch_category'] as $categoryBlock)
        <h6 class="mt-3">{{ $categoryBlock['category'] }}</h6>
        @if (!empty($categoryBlock['your_branch']))
            <div class="alert alert-success py-2">
                <strong>Your Branch:</strong>
                {{ $categoryBlock['your_branch']['branch'] }}
                — Rank #{{ $categoryBlock['your_branch']['rank'] }},
                Achievement {{ $categoryBlock['your_branch']['achievement'] }}%
            </div>
        @endif
        <div class="table-responsive">
            <table class="table report-datatable">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Branch</th>
                        <th>Target</th>
                        <th>Achieved</th>
                        <th>Achievement %</th>
                        <th>Remaining %</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $allBranches = collect($categoryBlock['branches']);
                        if (!empty($categoryBlock['your_branch'])) {
                            $allBranches = $allBranches->prepend($categoryBlock['your_branch'])->sortBy('rank')->values();
                        }
                    @endphp
                    @forelse ($allBranches as $row)
                        <tr class="{{ (!empty($categoryBlock['your_branch']) && $row['branch_id'] == $categoryBlock['your_branch']['branch_id']) ? 'table-success' : '' }}">
                            <td>{{ $row['rank'] }}</td>
                            <td>{{ $row['branch'] }}</td>
                            <td>{{ number_format($row['target'] ?? 0) }}</td>
                            <td>{{ number_format($row['achieved'] ?? 0) }}</td>
                            <td>{{ $row['achievement'] }}%</td>
                            <td>{{ $row['remaining'] }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endforeach
</div>
