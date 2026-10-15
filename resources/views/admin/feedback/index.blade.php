@extends('admin.layout.app')
@section('title', 'Feedback')

@section('content')
<div class="main-content" style="min-height: 562px;">
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                            <h4 class="mb-0">Feedback Submissions</h4>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="{{ route('feedback.index') }}" class="mb-4">
                                <div class="row align-items-end">
                                    <div class="col-md-3 mb-3">
                                        <label>Type</label>
                                        <select name="type" class="form-control">
                                            <option value="all" {{ $type === 'all' ? 'selected' : '' }}>All</option>
                                            <option value="staff" {{ $type === 'staff' ? 'selected' : '' }}>Sales Staff</option>
                                            <option value="branch_manager" {{ $type === 'branch_manager' ? 'selected' : '' }}>Branch Manager</option>
                                            <option value="asm" {{ $type === 'asm' ? 'selected' : '' }}>ASM</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <button type="submit" class="btn btn-primary">Filter</button>
                                    </div>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table" id="table_id_events">
                                    <thead>
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Type</th>
                                            <th>Code</th>
                                            <th>Name</th>
                                            <th>Branch / Region</th>
                                            <th>Feedback</th>
                                            <th>Submitted At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($feedbacks as $feedback)
                                            @php
                                                $userType = $feedback->user_type;
                                                if (!$userType) {
                                                    $userType = filled($feedback->region_name)
                                                        ? 'area_sale_manager'
                                                        : 'sale_staff';
                                                }

                                                if ($userType === 'area_sale_manager') {
                                                    $typeLabel = 'ASM';
                                                    $badgeClass = 'badge-info';
                                                    $location = $feedback->region_name ?: '-';
                                                } elseif ($userType === 'branch_manager') {
                                                    $typeLabel = 'Branch Manager';
                                                    $badgeClass = 'badge-warning';
                                                    $location = $feedback->branch_name ?: '-';
                                                } else {
                                                    $typeLabel = 'Sales Staff';
                                                    $badgeClass = 'badge-primary';
                                                    $location = $feedback->branch_name ?: '-';
                                                }
                                            @endphp
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                    <span class="badge {{ $badgeClass }}">{{ $typeLabel }}</span>
                                                </td>
                                                <td>{{ $feedback->code ?: '-' }}</td>
                                                <td>{{ $feedback->name ?: '-' }}</td>
                                                <td>{{ $location }}</td>
                                                <td style="white-space: pre-wrap;">{{ $feedback->feedback }}</td>
                                                <td>{{ $feedback->created_at ? $feedback->created_at->copy()->timezone('Asia/Karachi')->format('d M Y, h:i A') : '-' }}</td>
                                                <td>
                                                    @if (Auth::guard('admin')->check() ||
                                                            ($sideMenuPermissions->has('Feedback') && $sideMenuPermissions['Feedback']->contains('delete')))
                                                    <form id="delete-form-{{ $feedback->id }}"
                                                        action="{{ route('feedback.destroy', $feedback->id) }}"
                                                        method="POST"
                                                        style="display:inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button"
                                                            class="btn btn-danger btn-sm delete-btn"
                                                            data-form="delete-form-{{ $feedback->id }}"
                                                            title="Delete">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center text-muted">No feedback submissions found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
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
        if ($('#table_id_events tbody tr').length && !$('#table_id_events tbody tr td[colspan]').length) {
            $('#table_id_events').DataTable();
        }

        $(document).on('click', '.delete-btn', function () {
            var formId = $(this).data('form');
            Swal.fire({
                title: 'Are you sure?',
                text: 'This feedback will be deleted.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it'
            }).then(function (result) {
                if (result.isConfirmed) {
                    document.getElementById(formId).submit();
                }
            });
        });
    });
</script>
@endsection
