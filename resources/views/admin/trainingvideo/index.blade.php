@extends('admin.layout.app')
@section('title', 'Training Videos')

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-body">

            <div class="row">
                <div class="col-12">

                    {{-- Add / Update Training Video --}}
                    <div class="card">

                        <div class="card-header">
                            <h4>
                                Training Videos
                            </h4>
                        </div>

                        <form action="{{ route('training.video.store') }}" method="POST">
                            @csrf

                            <input type="hidden" name="id" id="video_id">

                            <div class="card-body pb-1">

                                <div class="row">

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Role <span style="color: red;">*</span></label>

                                             <select name="roles[]"
                                                    id="roles"
                                                    class="form-control select2"
                                                    multiple
                                                    required>

                                                <option value="">
                                                    Select Role
                                                </option>

                                                <option value="asm">
                                                    Area Sale Manager
                                                </option>

                                                <option value="branch_manager">
                                                    Branch Manager
                                                </option>

                                                <option value="sales_staff">
                                                    Sales Staff
                                                </option>

                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Title <span style="color: red;">*</span></label>

                                            <input type="text"
                                                   name="title"
                                                   id="title"
                                                   class="form-control"
                                                   required>
                                        </div>
                                    </div>

                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label>Video URL <span style="color: red;">*</span></label>

                                            <input type="text"
                                                   name="video_url"
                                                   id="video_url"
                                                   class="form-control"
                                                   placeholder="Enter Video URL"
                                                   required>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group mb-0">
                                            <label>Description <span style="color: red;">*</span></label>

                                            <textarea name="description"
                                                      id="description"
                                                      rows="3"
                                                      class="form-control"></textarea>
                                        </div>
                                    </div>
`
                                </div>

                            </div>

                            <div class="card-footer text-center py-2">
                                <button type="submit" class="btn btn-primary mb-4">
                                    Save
                                </button>
                            </div>

                        </form>

                    </div>

                    {{-- Videos Listing --}}
                    <div class="card">

                        <div class="card-header">
                            <h4>Training Videos List</h4>
                        </div>

                        <div class="card-body table-responsive">

                            <table class="table table-bordered" id="table_id_events">

                                <thead>
                                    <tr>
                                        <th>Sr.</th>
                                        <th>Role</th>
                                        <th>Title</th>
                                        <th>Video</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    @foreach($videos as $video)

                                    <tr>

                                        <td>{{ $loop->iteration }}</td>

                                        <td>
                                                 {{ collect($video->roles)
                                                ->map(fn($role) => ucwords(str_replace('_',' ',$role)))
                                                ->implode(', ') }}
                                            
                                        </td>

                                        <td>{{ $video->title }}</td>

                                        <td>
                                            <a href="{{ $video->video_url }}"
                                               target="_blank"
                                               class="btn btn-primary btn-sm">
                                                View
                                            </a>
                                        </td>

                                        <td>{{ $video->description }}</td>

                                        <td>
                                            <div class="d-flex align-items-center" style="gap: 6px;">
                                            <button
                                                type="button"
                                                class="btn btn-primary btn-sm editVideo"
                                                data-id="{{ $video->id }}"
                                                data-roles='@json($video->roles)'
                                                data-title="{{ $video->title }}"
                                                data-video="{{ $video->video_url }}"
                                                data-description="{{ $video->description }}">

                                                <i class="fa fa-edit"></i>

                                            </button>

                                             @if (Auth::guard('admin')->check() ||
                                                                ($sideMenuPermissions->has('Training videos') && $sideMenuPermissions['Training videos']->contains('delete')))
                                                            <form id="delete-form-{{ $video->id }}"
                                                                action="{{ route('training.video.delete', $video->id) }}"
                                                                method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                            </form>

                                                            <button class="show_confirm btn p-2"
                                                                style="background-color: #609b90;"
                                                                data-form="delete-form-{{ $video->id }}" type="button">
                                                                <i class="fa fa-trash"></i>
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

<script>

$(document).ready(function(){

    if ($.fn.DataTable.isDataTable('#table_id_events')) {
        $('#table_id_events').DataTable().destroy();
    }

    $('#table_id_events').DataTable();

    $('.editVideo').click(function(){

        $('#video_id').val($(this).data('id'));

        let roles = $(this).attr('data-roles');

        roles = JSON.parse(roles);

        $('#roles').val(roles).trigger('change');

        $('#title').val($(this).data('title'));

        $('#video_url').val($(this).data('video'));

        $('#description').val($(this).data('description'));

        $('html, body').animate({
            scrollTop: 0
        }, 300);

    });

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
                    text: "If you delete this Training Video record, it will be gone forever.",
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
    <script>
$(document).ready(function () {

    $('.select2').select2({
        width: '100%',
        placeholder: 'Select Roles'
    });

});
</script>

@endsection