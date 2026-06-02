@extends('admin.layout.app')
@section('title', 'Users')

@section('content')
    <div class="main-content" style="min-height: 562px;">
        <section class="section">
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Users</h4>
                            </div>
                            <div class="card-body table-striped table-bordered table-responsive">
								 @if (Auth::guard('admin')->check() ||
                                        ($sideMenuPermissions->has('Users') && $sideMenuPermissions['Users']->contains('create')))
                                    <a class="btn btn-primary mb-3" href="{{ route('user.createview') }}">Create</a>
                                @endif
                                <table class="table" id="table_id_events">
                                    <thead>
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Image</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Toggle</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($users as $user)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                    @if ($user->image)
                                                    <img src="{{ asset($user->image) }}" style="width: 50px; height: 50px;">
                                                @else
                                                    <img src="{{ asset('public/admin/assets/images/avator.png') }}" style="width: 50px; height: 50px;">
                                                @endif

                                                </td>
                                                <td>{{ $user->name ?? '-' }}</td>
                                                <td><a href="mailto:{{ $user->email }}">{{ $user->email ?? '-' }}</a>
                                                </td>
                                                <td>{{ $user->phone ?? '-' }}</td>
                                                <td>
                                                    <label class="custom-switch">
                                                        <input type="checkbox" class="custom-switch-input toggle-status"
                                                            data-id="{{ $user->id }}"
                                                            {{ $user->toggle ? 'checked' : '' }}>
                                                        <span class="custom-switch-indicator"></span>
                                                        <span class="custom-switch-description">
                                                            {{ $user->toggle ? 'Activated' : 'Deactivated' }}
                                                        </span>
                                                    </label>
                                                </td>
                                                <td style="vertical-align: middle;">
                                                    <div class="d-flex align-items-center" style="gap: 6px;">
                                                        @if (Auth::guard('admin')->check() ||
                                                                ($sideMenuPermissions->has('Users') && $sideMenuPermissions['Users']->contains('edit')))
                                                            <a href="{{ route('user.edit', $user->id) }}"
                                                                class="btn btn-primary p-2"
                                                                style="background-color: #609b90;">
                                                                <i class="fa fa-edit"></i>
                                                            </a>
                                                        @endif

                                                        @if (Auth::guard('admin')->check() ||
                                                                ($sideMenuPermissions->has('Users') && $sideMenuPermissions['Users']->contains('delete')))
                                                            <form id="delete-form-{{ $user->id }}"
                                                                action="{{ route('user.delete', $user->id) }}"
                                                                method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                            </form>

                                                            <button class="show_confirm btn p-2"
                                                                style="background-color: #609b90;"
                                                                data-form="delete-form-{{ $user->id }}" type="button">
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

    <!-- Deactivation Modal -->
    <div class="modal fade" id="deactivationModal" tabindex="-1" role="dialog" aria-labelledby="deactivationModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Deactivation Reason</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="deactivationForm">
                        @csrf
                        <input type="hidden" name="user_id" id="deactivatingUserId">
                        <div class="form-group">
                            <label>Reason for deactivation:</label>
                            <textarea class="form-control" id="deactivationReason" name="reason" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmDeactivation">Submit</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')

    <script>
        $(document).ready(function() {
            $('#table_id_events').DataTable();

            let currentToggle = null;
            let currentUserId = null;

            $('.toggle-status').change(function() {
                let status = $(this).is(':checked') ? 1 : 0;
                currentToggle = $(this);
                currentUserId = $(this).data('id');

                if (status === 0) {
                    $('#deactivatingUserId').val(currentUserId);
                    $('#deactivationModal').modal('show');
                } else {
                    updateUserStatus(currentUserId, 1);
                }
            });

            $('#confirmDeactivation').click(function() {
                let reason = $('#deactivationReason').val();
                if (reason.trim() === '') {
                    toastr.error('Please provide a deactivation reason');
                    setTimeout(() => {
                        location.reload();
                    }, 800);
                    return;
                }

                $('#deactivationModal').modal('hide');
                $('#deactivationReason').val('');
                updateUserStatus(currentUserId, 0, reason);
            });

            $('#deactivationModal').on('hidden.bs.modal', function() {
                if ($('#deactivationReason').val().trim() === '') {
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                }
            });

            function updateUserStatus(userId, status, reason = null) {
                let $descriptionSpan = currentToggle.siblings('.custom-switch-description');
                $.ajax({
                    url: "{{ route('user.toggle-status') }}",
                    type: "POST",
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: userId,
                        status: status,
                        reason: reason
                    },
                    success: function(res) {
                        if (res.success) {
                            $descriptionSpan.text(res.new_status);
                            toastr.success(res.message);
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            currentToggle.prop('checked', !status);
                            toastr.error(res.message);
                        }
                    },
                    error: function() {
                        currentToggle.prop('checked', !status);
                        toastr.error('Error updating status');
                    }
                });
            }


            //deleting alert

            $(document).ready(function() {
                $(document).on('click', '.show_confirm', function(event) {
                    event.preventDefault();

                    // Get form ID and form element
                    var formId = $(this).data("form");
                    console.log("Form ID:", formId); // Debug

                    var form = document.getElementById(formId);
                    console.log("Form Element:", form); // Debug

                    if (!form) {
                        swal("Error", "Form not found. Please check data-form attribute.", "error");
                        return;
                    }

                    // Get CSRF token from meta tag
                    var csrfToken = $('meta[name="csrf-token"]').attr('content');
                    console.log("CSRF Token:", csrfToken); // Debug

                    // SweetAlert confirmation
                    swal({
                        title: "Are you sure you want to delete this record?",
                        text: "If you delete this User, it will be gone forever.",
                        icon: "warning",
                        buttons: true,
                        dangerMode: true,
                    }).then((willDelete) => {
                        if (willDelete) {
                            // AJAX request to delete
                            $.ajax({
                                url: form.action,
                                type: 'POST',
                                data: {
                                    _method: 'DELETE',
                                },
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken
                                },
                                success: function(response) {
                                    console.log("Delete Response:",
                                        response); // Debug
                                    swal({
                                        title: "Success!",
                                        text: "Record deleted successfully!",
                                        icon: "success",
                                        button: false,
                                        timer: 1000
                                    }).then(() => {
                                        location.reload();
                                    });
                                },
                                error: function(xhr, status, error) {
                                    console.error("AJAX Error:", xhr
                                        .responseText); // Debug
                                    swal("Error!",
                                        "Failed to delete record.\n" + xhr
                                        .responseText, "error");
                                }
                            });
                        }
                    });
                });
            });

        });
    </script>
@endsection
