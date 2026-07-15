@if (!empty($data['summary']))
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card card-statistic-1 mb-0">
                <div class="card-wrap">
                    <div class="card-header"><h4>Branch</h4></div>
                    <div class="card-body">{{ $data['summary']['branch'] ?? '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card card-statistic-1 mb-0">
                <div class="card-wrap">
                    <div class="card-header"><h4>Target</h4></div>
                    <div class="card-body">{{ number_format($data['summary']['target']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card card-statistic-1 mb-0">
                <div class="card-wrap">
                    <div class="card-header"><h4>Achieved</h4></div>
                    <div class="card-body">{{ number_format($data['summary']['achieved']) }}</div>
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
    <h5 class="mb-3">Target vs Achievement</h5>
    <div class="table-responsive">
        <table class="table report-datatable">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Target</th>
                    <th>Achieved</th>
                    <th>Achievement %</th>
                    <th>Remaining %</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data['target_vs_achievement'] as $row)
                    <tr>
                        <td>{{ $row['category'] }}</td>
                        <td>{{ number_format($row['target']) }}</td>
                        <td>{{ number_format($row['achieved']) }}</td>
                        <td>
                            <div class="progress" style="height: 18px;">
                                <div class="progress-bar bg-success" role="progressbar"
                                     style="width: {{ $row['achieved_percentage'] }}%;"
                                     aria-valuenow="{{ $row['achieved_percentage'] }}"
                                     aria-valuemin="0" aria-valuemax="100">
                                    {{ $row['achieved_percentage'] }}%
                                </div>
                            </div>
                        </td>
                        <td>{{ $row['remaining_percentage'] }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">No target data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mb-2">
    <h5 class="mb-3">Branch Conversion Rate</h5>
    @if (!empty($data['peak']))
        <div class="alert alert-info">
            <strong>Peak Day:</strong>
            {{ $data['peak']['date'] }}
            — Conversion {{ $data['peak']['conversion_rate'] }}%
            (Footfall: {{ number_format($data['peak']['footfall']) }},
            Invoices: {{ number_format($data['peak']['invoices']) }})
        </div>
    @endif
    <div class="table-responsive">
        <table class="table report-datatable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Footfall</th>
                    <th>Invoices</th>
                    <th>Conversion %</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data['conversion_chart'] as $row)
                    <tr>
                        <td>{{ $row['date'] }}</td>
                        <td>{{ number_format($row['footfall']) }}</td>
                        <td>{{ number_format($row['invoices']) }}</td>
                        <td>{{ $row['conversion_rate'] }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">No conversion data for this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
