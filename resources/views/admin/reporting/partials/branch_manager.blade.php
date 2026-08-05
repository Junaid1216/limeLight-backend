@if (!empty($data['summary']))
    @php $summaryAssigned = !empty($data['summary']['is_assigned']); @endphp
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card card-statistic-1 mb-0">
                <div class="card-wrap">
                    <div class="card-header"><h4>Branch</h4></div>
                    <div class="card-body">{{ $data['summary']['branch'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card card-statistic-1 mb-0">
                <div class="card-wrap">
                    <div class="card-header"><h4>Monthly Target</h4></div>
                    <div class="card-body">
                        @if ($summaryAssigned)
                            {{ number_format($data['summary']['monthly_target']) }}
                        @else
                            <span class="text-muted">Not Assigned</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card card-statistic-1 mb-0">
                <div class="card-wrap">
                    <div class="card-header"><h4>Achievement %</h4></div>
                    <div class="card-body">
                        @if ($summaryAssigned)
                            {{ $data['summary']['achieved_percentage'] }}%
                        @else
                            <span class="text-muted">Not Assigned</span>
                        @endif
                    </div>
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
                    @php $assigned = !empty($row['is_assigned']); @endphp
                    <tr>
                        <td>{{ $row['rank'] }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td>
                            @if ($assigned)
                                {{ number_format($row['target']) }}
                            @else
                                <span class="text-muted">Not Assigned</span>
                            @endif
                        </td>
                        <td>
                            @if ($assigned)
                                {{ number_format($row['achieved']) }}
                            @else
                                <span class="text-muted">Not Assigned</span>
                            @endif
                        </td>
                        <td>
                            @if ($assigned)
                                {{ $row['achievement_percentage'] }}%
                            @else
                                <span class="text-muted">Not Assigned</span>
                            @endif
                        </td>
                        <td>
                            @if ($assigned)
                                {{ $row['remaining_percentage'] }}%
                            @else
                                <span class="text-muted">Not Assigned</span>
                            @endif
                        </td>
                        <td>
                            @if ($assigned)
                                {{ number_format($row['commission'], 2) }}
                            @else
                                <span class="text-muted">Not Assigned</span>
                            @endif
                        </td>
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

<div class="mb-2">
    <h5 class="mb-3">Peer Branch Category Comparison</h5>
    <form method="GET" action="{{ route('reporting.index') }}" class="row align-items-end mb-3">
        <input type="hidden" name="role" value="branch_manager">
        <input type="hidden" name="id" value="{{ request('id') }}">
        <input type="hidden" name="period" value="{{ request('period', 'weekly') }}">
        <div class="col-md-4 mb-2">
            <label for="branch_category_filter">Category Filter</label>
            <select name="branch_category_filter" id="branch_category_filter" class="form-control" onchange="this.form.submit()">
                @foreach ($data['branch_category_filters'] as $value => $label)
                    <option value="{{ $value }}" {{ ($data['branch_category_filter'] ?? 'overall') === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
    </form>

    @php
        $categoryBlock = $data['branch_category'];
        $allBranches = collect($categoryBlock['branches']);
        if (!empty($categoryBlock['your_branch'])) {
            $allBranches = $allBranches->prepend($categoryBlock['your_branch'])->sortBy('rank')->values();
        }
    @endphp

    <h6 class="mt-3">{{ $categoryBlock['category'] }}</h6>
    @if (!empty($categoryBlock['your_branch']))
        <div class="alert alert-success py-2">
            <strong>Your Branch:</strong>
            {{ $categoryBlock['your_branch']['branch'] }}
            - Rank #{{ $categoryBlock['your_branch']['rank'] }},
            @if (!empty($categoryBlock['your_branch']['is_assigned']))
                Achievement {{ $categoryBlock['your_branch']['achievement'] }}%
            @else
                <span class="text-muted">Not Assigned</span>
            @endif
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
                @forelse ($allBranches as $row)
                    @php $assigned = !empty($row['is_assigned']); @endphp
                    <tr class="{{ (!empty($categoryBlock['your_branch']) && $row['branch_id'] == $categoryBlock['your_branch']['branch_id']) ? 'table-success' : '' }}">
                        <td>{{ $row['rank'] }}</td>
                        <td>{{ $row['branch'] }}</td>
                        <td>
                            @if ($assigned)
                                {{ number_format($row['target'] ?? 0) }}
                            @else
                                <span class="text-muted">Not Assigned</span>
                            @endif
                        </td>
                        <td>
                            @if ($assigned)
                                {{ number_format($row['achieved'] ?? 0) }}
                            @else
                                <span class="text-muted">Not Assigned</span>
                            @endif
                        </td>
                        <td>
                            @if ($assigned)
                                {{ $row['achievement'] }}%
                            @else
                                <span class="text-muted">Not Assigned</span>
                            @endif
                        </td>
                        <td>
                            @if ($assigned)
                                {{ $row['remaining'] }}%
                            @else
                                <span class="text-muted">Not Assigned</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
