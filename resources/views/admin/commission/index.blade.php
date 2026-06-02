@extends('admin.layout.app')
@section('title','Commissions')

@section('content')

<div class="main-content">
    <section class="section">
        <div class="section-body">

            {{-- Add Commission Card --}}
            <div class="card">
                <div class="card-header">
                    <h4>Add Commission</h4>
                </div>

                <form action="{{ route('commission.store') }}" method="POST">
                    @csrf

                    <div class="card-body">

                        <div class="row">

                            <div class="col-md-5">
                                <div class="form-group">
                                    <label>Role</label>

                                    <select name="role" class="form-control" required>
                                        <option value="">Select Role</option>
                                        <option value="branch_manager">
                                            Branch Manager
                                        </option>
                                        <option value="sales_staff">
                                            Sales Staff
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-5">
                                <div class="form-group">
                                    <label>From Target</label>

                                    <input type="number"
                                           name="from_target"
                                           class="form-control"
                                           required>
                                </div>
                            </div>

                            <div class="col-md-5">
                                <div class="form-group">
                                    <label>To Target</label>

                                    <input type="number"
                                           name="to_target"
                                           class="form-control"
                                           required>
                                </div>
                            </div>

                            <div class="col-md-5">
                                <div class="form-group">
                                    <label>Commission</label>
                                    <div class="input-group">
                                    <input type="number"
                                           step="0.01"
                                           name="commission"
                                           class="form-control"
                                           required>
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>

                    <div class="card-footer text-center">
                        <button class="btn btn-primary">
                            Save Commission
                        </button>
                    </div>

                </form>
            </div>

            {{-- Commission Listing --}}
            <div class="card">
                <div class="card-header">
                    <h4>Commission List</h4>
                </div>

                <div class="card-body table-responsive">

                    <table class="table table-bordered" id="table_id_events">

                        <thead>
                            <tr>
                                <th>Sr.</th>
                                <th>Role</th>
                                <th>From Target</th>
                                <th>To Target</th>
                                <th>Commission</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($commissions as $commission)

                                <tr>
                                    <td>{{ $loop->iteration }}</td>

                                    <td>
                                        {{ $commission->role == 'branch_manager'
                                            ? 'Branch Manager'
                                            : 'Sales Staff' }}
                                    </td>

                                    <td>{{ $commission->from_target }}</td>

                                    <td>{{ $commission->to_target }}</td>

                                    <td>{{ $commission->commission }} %</td>

                                    <td>
                                    <div class="d-flex align-items-center" style="gap: 6px;">
                                        <a href="{{ route('commission.edit',$commission->id) }}"
                                           class="btn btn-primary btn-sm">
                                            <i class="fa fa-edit"></i>
                                        </a>

                                        @if (Auth::guard('admin')->check() ||
                                                                ($sideMenuPermissions->has('Commissions') && $sideMenuPermissions['Commissions']->contains('delete')))
                                                            <form id="delete-form-{{ $commission->id }}"
                                                                action="{{ route('commission.delete', $commission->id) }}"
                                                                method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                            </form>

                                                            <button class="show_confirm btn p-2"
                                                                style="background-color: #609b90;"
                                                                data-form="delete-form-{{ $commission->id }}" type="button">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                    </div>                 @endif
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>
            </div>

        </div>
    </section>
</div>

@endsection

@section('js')

     <script type="text/javascript">
        $(document).ready(function() {

            // ✅ DataTable initialize
            if ($.fn.DataTable.isDataTable('#table_id_events')) {
                $('#table_id_events').DataTable().destroy();
            }
            $('#table_id_events').DataTable();

            // ✅ Delete alert confirmation
            $(document).on('click', '.show_confirm', function(event) {
                event.preventDefault();
                var formId = $(this).data("form");
                var form = document.getElementById(formId);

                Swal.fire({
                    title: 'Are you sure you want to delete this record?',
                    text: "If you delete this Commission record, it will be gone forever.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: form.action,
                            method: 'POST',
                            data: {
                                _method: 'DELETE',
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(res) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: 'Record deleted successfully.',
                                    showConfirmButton: false,
                                    timer: 2000
                                }).then(() => location.reload());
                            },
                            error: function() {
                                Swal.fire('Error!', 'Failed to delete the record.',
                                    'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection