@extends('admin.layout.app')
@section('title', 'Sales Staff')

@section('content')
    <div class="main-content" style="min-height: 562px;">
        <section class="section">
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Sales Staff<small class="font-weight-bold text-danger"> (The default password for all sale staff is 12345678, automatically generated when a new sale staff member is created.)</small></h4>
                            </div>
                            <div class="card-body table-striped table-bordered table-responsive">
								 @if (Auth::guard('admin')->check() ||
                                        ($sideMenuPermissions->has('Sale Staff') && $sideMenuPermissions['Sale Staff']->contains('create')))
                                    <a class="btn btn-primary mb-3" href="{{ route('sale.staff.create') }}">Create</a>
                                @endif
                                <table class="table" id="table_id_events">
                                    <thead>
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Employee ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Branch</th>
                                            <th>Designation</th>
                                            <th>Image</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($salestaff as $salestaffmember)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $salestaffmember->employee_id ?? '-' }}</td>
                                                <td>{{ $salestaffmember->name ?? '-' }}</td>
                                                <td>{{ $salestaffmember->email ?? '-' }}</td>
                                                <td>{{ $salestaffmember->branch->name ?? '-' }}</td>
                                                <td>{{ $salestaffmember->designation->name ?? '-' }}</td>
                                                <td>
                                                    @if ($salestaffmember->image && file_exists($salestaffmember->image))
                                                        <img src="{{ asset($salestaffmember->image) }}" width="50"
                                                            height="50" alt="Image">
                                                    @else
                                                        <img src="{{ asset('public/admin/assets/images/avator.png') }}"
                                                            width="50" height="50" alt="Default Image">
                                                    @endif
                                                </td>
                                                <td style="vertical-align: middle;">
                                                    <div class="d-flex align-items-center" style="gap: 6px;">
                                                        @if (Auth::guard('admin')->check() ||
                                                                ($sideMenuPermissions->has('Sale Staff') && $sideMenuPermissions['Sale Staff']->contains('edit')))
                                                            <a href="{{ route('sale.staff.edit', $salestaffmember->id) }}"
                                                                class="btn btn-primary p-2"
                                                                style="background-color: #609b90;">
                                                                <i class="fa fa-edit"></i>
                                                            </a>
                                                        @endif

                                                        @if (Auth::guard('admin')->check() ||
                                                                ($sideMenuPermissions->has('Sale Staff') && $sideMenuPermissions['Sale Staff']->contains('delete')))
                                                            <form id="delete-form-{{ $salestaffmember->id }}"
                                                                action="{{ route('sale.staff.delete', $salestaffmember->id) }}"
                                                                method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                            </form>

                                                            <button class="show_confirm btn p-2"
                                                                style="background-color: #609b90;"
                                                                data-form="delete-form-{{ $salestaffmember->id }}" type="button">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>

                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div> <!-- /.card-body -->
                        </div> <!-- /.card -->
                    </div> <!-- /.col -->
                </div> <!-- /.row -->
            </div> <!-- /.section-body -->
        </section>
    </div>
@endsection

@section('js')

    <script>
        $(document).ready(function() {
            $('#table_id_events').DataTable();

        });
    </script>
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
                    text: "If you delete this Sales Staff record, it will be gone forever.",
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

     @if (session('message'))
        <script>
            toastr.success('{{ session('message') }}');
        </script>
    @endif
@endsection
