@extends('admin.layout.app')
@section('title', 'Assigned Targets')

@section('content')
<div class="main-content" style="min-height: 562px;">
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Assigned Targets</h4>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="{{ route('assigned.target.index') }}" class="mb-4">
                                <div class="row align-items-end">
                                    <div class="col-md-3 mb-3">
                                        <label>Status</label>
                                        <select name="status" class="form-control" required>
                                            <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label>Month</label>
                                        <select name="month" class="form-control" required>
                                            @foreach ($months as $m)
                                                <option value="{{ $m }}" {{ $month === $m ? 'selected' : '' }}>{{ $m }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label>Year</label>
                                        <select name="year" class="form-control" required>
                                            @foreach ($years as $y)
                                                <option value="{{ $y }}" {{ (string) $year === (string) $y ? 'selected' : '' }}>{{ $y }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <button type="submit" class="btn btn-primary btn-block">Filter</button>
                                    </div>
                                </div>
                            </form>

                            @forelse ($groups as $index => $group)
                                <div class="card mb-4 border">
                                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                                        <div>
                                            <h5 class="mb-1">{{ $group['branch_name'] }}</h5>
                                            <small class="text-muted">
                                                Branch Manager: {{ $group['branch_manager_name'] }}
                                                | Designation: {{ $group['designation'] }}
                                                | {{ $group['month'] }} {{ $group['year'] }}
                                                | Staff: {{ $group['staff_count'] }}
                                            </small>
                                        </div>
                                        <div class="d-flex align-items-center" style="gap:10px;">
                                            @if ($group['status'] === 'approved')
                                                <span class="badge badge-success">Approved</span>
                                            @else
                                                <span class="badge badge-warning">Pending</span>
                                                <form method="POST" action="{{ route('assigned.target.approve') }}" class="approve-form">
                                                    @csrf
                                                    <input type="hidden" name="branch_id" value="{{ $group['branch_id'] }}">
                                                    <input type="hidden" name="branch_manager_id" value="{{ $group['branch_manager_id'] }}">
                                                    <input type="hidden" name="month" value="{{ $group['month'] }}">
                                                    <input type="hidden" name="year" value="{{ $group['year'] }}">
                                                    <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h6 class="mb-0 font-weight-bold">Monthly Target Assignment</h6>
                                                <small class="text-muted">Distribute Among Sales Staff</small>
                                            </div>
                                            <span class="badge" style="background:#0d9488;color:#fff;border-radius:8px;padding:6px 10px;">
                                                {{ count($group['staff']) }}
                                            </span>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-bordered mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Garments</th>
                                                        <th>Unstitched</th>
                                                        <th>Accessories</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($group['staff'] as $staff)
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center" style="gap:10px;">
                                                                    <span style="width:36px;height:36px;border-radius:50%;background:#f3d9c4;color:#8a4b2d;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;">
                                                                        {{ $staff['initials'] }}
                                                                    </span>
                                                                    <span>{{ $staff['name'] }}</span>
                                                                </div>
                                                            </td>
                                                            <td>{{ number_format($staff['garments']) }}</td>
                                                            <td>{{ number_format($staff['unstitched']) }}</td>
                                                            <td>{{ number_format($staff['accessories']) }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="4" class="text-center">No staff targets found.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="alert alert-info mb-0">
                                    No assigned targets found from the app yet.
                                </div>
                            @endforelse
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
    $('.approve-form').on('submit', function (e) {
        e.preventDefault();
        const form = this;

        Swal.fire({
            title: 'Approve targets?',
            text: 'This will mark all targets in this assignment as approved.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#609b90',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, approve'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
</script>
@endsection
