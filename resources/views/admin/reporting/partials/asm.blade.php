<div class="mb-4">
    <h5 class="mb-3">Branch Conversion Comparison</h5>
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
                        <td colspan="5" class="text-center">No branch conversion data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mb-4">
    <h5 class="mb-3">Branch Category Comparison</h5>
    @foreach ($data['branch_category'] as $categoryBlock)
        <h6 class="mt-3">{{ $categoryBlock['category'] }}</h6>
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
                    @forelse ($categoryBlock['branches'] as $row)
                        @php $assigned = !empty($row['is_assigned']); @endphp
                        <tr>
                            <td>{{ $row['rank'] }}</td>
                            <td>{{ $row['branch_name'] }}</td>
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

<div class="mb-4">
    <h5 class="mb-3">Region Conversion Comparison</h5>
    @if (!empty($data['region_conversion']['your_region']))
        <div class="alert alert-success">
            <strong>Your Region:</strong>
            {{ $data['region_conversion']['your_region']['region'] }}
            — Rank #{{ $data['region_conversion']['your_region']['rank'] }},
            Conversion {{ $data['region_conversion']['your_region']['conversion_percentage'] }}%
            (Traffic: {{ number_format($data['region_conversion']['your_region']['traffic']) }},
            Invoices: {{ number_format($data['region_conversion']['your_region']['invoices']) }})
        </div>
    @endif
    <div class="table-responsive">
        <table class="table report-datatable">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Region</th>
                    <th>Traffic</th>
                    <th>Invoices</th>
                    <th>Conversion %</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data['region_conversion']['all'] as $row)
                    <tr class="{{ ($data['region_conversion']['your_region']['region_id'] ?? null) == $row['region_id'] ? 'table-success' : '' }}">
                        <td>{{ $row['rank'] }}</td>
                        <td>{{ $row['region'] }}</td>
                        <td>{{ number_format($row['traffic']) }}</td>
                        <td>{{ number_format($row['invoices']) }}</td>
                        <td>{{ $row['conversion_percentage'] }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">No region data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mb-2">
    <h5 class="mb-3">Staff Comparison (by Branch)</h5>
    @forelse ($data['staff_comparison'] as $branchBlock)
        <h6 class="mt-3">{{ $branchBlock['branch_name'] }}</h6>
        <div class="table-responsive">
            <table class="table report-datatable">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Staff</th>
                        <th>Target</th>
                        <th>Achieved</th>
                        <th>Remaining</th>
                        <th>Achievement %</th>
                        <th>Commission</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($branchBlock['staff'] as $row)
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
                                    {{ number_format($row['remaining']) }}
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
                                    {{ number_format($row['commission'], 2) }}
                                @else
                                    <span class="text-muted">Not Assigned</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No staff in this branch.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @empty
        <div class="alert alert-secondary">No staff comparison data.</div>
    @endforelse
</div>
