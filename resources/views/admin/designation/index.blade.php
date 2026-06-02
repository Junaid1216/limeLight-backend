@extends('admin.layout.app')
@section('title', 'Designations')

@section('content')
    <div class="main-content" style="min-height: 562px;">
        <section class="section">
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Designations</h4>
                            </div>
                            <div class="card-body table-striped table-bordered table-responsive">
								 @if (Auth::guard('admin')->check() ||
                                        ($sideMenuPermissions->has('Designations') && $sideMenuPermissions['Designations']->contains('create')))
                                    <a class="btn btn-primary mb-3" href="{{ route('designation.create') }}">Create</a>
                                @endif
                                <table class="table" id="table_id_events">
                                    <thead>
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Name</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($designations as $designation)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $designation->name ?? '-' }}</td>
                                                <td style="vertical-align: middle;">
                                                    <div class="d-flex align-items-center" style="gap: 6px;">
                                                        @if (Auth::guard('admin')->check() ||
                                                                ($sideMenuPermissions->has('Designations') && $sideMenuPermissions['Designations']->contains('edit')))
                                                            <a href="{{ route('designation.edit', $designation->id) }}"
                                                                class="btn btn-primary p-2"
                                                                style="background-color: #609b90;">
                                                                <i class="fa fa-edit"></i>
                                                            </a>
                                                        @endif

                                                        @if (Auth::guard('admin')->check() ||
                                                                ($sideMenuPermissions->has('Designations') && $sideMenuPermissions['Designations']->contains('delete')))
                                                            <form id="delete-form-{{ $designation->id }}"
                                                                action="{{ route('designation.delete', $designation->id) }}"
                                                                method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                            </form>

                                                            <button class="show_confirm btn p-2"
                                                                style="background-color: #609b90;"
                                                                data-form="delete-form-{{ $designation->id }}" type="button">
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
                    text: "If you delete this Designation record, it will be gone forever.",
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
