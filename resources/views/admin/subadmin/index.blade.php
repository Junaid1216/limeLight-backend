@extends('admin.layout.app')
@section('title', 'Sub Admins')

@section('content')
    <div class="main-content" style="min-height: 562px;">
        <section class="section">
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                               <h4>Sub Admins<small class="font-weight-bold text-danger"> (The default password for all sub-admins is 12345678. This password is automatically generated when a new sub-admin is created.)</small></h4>
                            </div>
                            <div class="card-body table-striped table-bordered table-responsive">
                                @if (Auth::guard('admin')->check() ||
                                        ($sideMenuPermissions->has('Sub Admins') && $sideMenuPermissions['Sub Admins']->contains('create')))
                                    <a class="btn btn-primary mb-3" href="{{ route('subadmin.create') }}">Create</a>
                                @endif
                                <table class="table" id="table_id_events">
                                    <thead>
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Image</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($subAdmins as $subAdmin)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $subAdmin->name }}</td>
                                                <td><a href="mailto:{{ $subAdmin->email }}">{{ $subAdmin->email }}</a></td>
                                                <td>{{ $subAdmin->roles->pluck('name')->join(', ') ?: 'No Role' }}</td>
                                                <td>
                                                    @if ($subAdmin->image && file_exists($subAdmin->image))
                                                        <img src="{{ asset($subAdmin->image) }}" width="50"
                                                            height="50" alt="Image">
                                                    @else
                                                        <img src="{{ asset('public/admin/assets/images/avator.png') }}"
                                                            width="50" height="50" alt="Default Image">
                                                    @endif
                                                </td>
                                                <td>
                                                    <label class="custom-switch">
                                                        <input type="checkbox" class="custom-switch-input toggle-status"
                                                            data-id="{{ $subAdmin->id }}"
                                                            {{ $subAdmin->status ? 'checked' : '' }}>
                                                        <span class="custom-switch-indicator"></span>
                                                        <span class="custom-switch-description">
                                                            {{ $subAdmin->status ? 'Activated' : 'Deactivated' }}
                                                        </span>
                                                    </label>
                                                </td>
                                                <td>
                                                    <div class="d-flex">
                                                        @if (Auth::guard('admin')->check() ||
                                                                ($sideMenuPermissions->has('Sub Admins') && $sideMenuPermissions['Sub Admins']->contains('edit')))
                                                            <a href="{{ route('subadmin.edit', $subAdmin->id) }}"
                                                                class="btn btn-primary mr-1">
                                                                <i class="fa fa-edit"></i>
                                                            </a>
                                                        @endif

                                                        @if (Auth::guard('admin')->check() ||
                                                                ($sideMenuPermissions->has('Sub Admins') && $sideMenuPermissions['Sub Admins']->contains('delete')))
                                                            <form id="delete-form-{{ $subAdmin->id }}"
                                                                action="{{ route('subadmin.destroy', $subAdmin->id) }}"
                                                                method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                            </form>

                                                            <button class="show_confirm btn"
                                                                style="background-color: #609b90;"
                                                                data-form="delete-form-{{ $subAdmin->id }}" type="button">
                                                                <span><i class="fa fa-trash"></i></span>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('js')

    <script type="text/javascript">
        $(document).ready(function() {

            // ✅ Initialize DataTable
            if ($.fn.DataTable.isDataTable('#table_id_events')) {
                $('#table_id_events').DataTable().destroy();
            }
            $('#table_id_events').DataTable();

            // ✅ Delete confirmation using SweetAlert2
            $(document).on('click', '.show_confirm', function(e) {
                e.preventDefault();
                let formId = $(this).data('form');
                let form = document.getElementById(formId);

                Swal.fire({
                    title: 'Are you sure you want to delete this record?',
                    text: "If you delete this Sub-Admin record, it will be gone forever.",
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
                                    text: 'Recored deleted successfully.',
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

            // ✅ Toggle Status
            $('.toggle-status').on('change', function() {
                var subAdminId = $(this).data('id');
                var status = $(this).is(':checked') ? 1 : 0;
                var $switch = $(this).closest('.custom-switch');
                var $description = $switch.find('.custom-switch-description');

                $.ajax({
                    url: "{{ route('admin.subadmin.toggleStatus') }}",
                    method: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        id: subAdminId,
                        status: status
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            $description.text(status ? 'Activated' : 'Deactivated');
                        } else {
                            toastr.error(response.message || 'Something went wrong!');
                            $switch.find('.toggle-status').prop('checked', !status);
                        }
                    },
                    error: function() {
                        toastr.error('Failed to update status.');
                        $switch.find('.toggle-status').prop('checked', !status);
                    }
                });
            });

        });
    </script>
@endsection
