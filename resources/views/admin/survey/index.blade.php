@extends('admin.layout.app')
@section('title', 'Surveys')

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
                                Surveys
                            </h4>
                        </div>

                        <form action="{{ route('survey.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="id" id="video_id">
                            <div class="card-body pb-1">

                                <div class="row">

                                    <div class="col-md-6">
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

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Question <span style="color: red;">*</span></label>

                                            <input type="text"
                                                   name="question"
                                                   id="question"
                                                   class="form-control"
                                                   required>
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
                            <h4>Survey List</h4>
                        </div>

                        <div class="card-body table-responsive">

                            <table class="table table-bordered" id="table_id_events">

                                <thead>
                                    <tr>
                                        <th>Sr.</th>
                                        <th>Role</th>
                                        <th>Question</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    @foreach($surveys as $survey)

                                    <tr>

                                        <td>{{ $loop->iteration }}</td>

                                        <td>
                                                 {{ collect($survey->roles)
                                                ->map(fn($role) => ucwords(str_replace('_',' ',$role)))
                                                ->implode(', ') }}
                                            
                                        </td>

                                        <td>{{ $survey->question }}</td>

                                      

                                        <td>
                                            <div class="d-flex align-items-center" style="gap: 6px;">
                                            @if (Auth::guard('admin')->check() ||
                                                                ($sideMenuPermissions->has('Surveys') && $sideMenuPermissions['Surveys']->contains('edit')))
                                            <button
                                                type="button"
                                                class="btn btn-primary btn-sm editVideo"
                                                data-id="{{ $survey->id }}"
                                                data-roles='@json($survey->roles)'
                                                data-question="{{ $survey->question }}">

                                                <i class="fa fa-edit"></i>

                                            </button>
                                            @endif

                                             @if (Auth::guard('admin')->check() ||
                                                                ($sideMenuPermissions->has('Surveys') && $sideMenuPermissions['Surveys']->contains('delete')))
                                                            <form id="delete-form-{{ $survey->id }}"
                                                                action="{{ route('survey.destroy', $survey->id) }}"
                                                                method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                            </form>

                                                            <button class="show_confirm btn p-2"
                                                                style="background-color: #609b90;"
                                                                data-form="delete-form-{{ $survey->id }}" type="button">
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
        console.log($(this).data('id'))
        $('#video_id').val($(this).data('id'));

        let roles = $(this).attr('data-roles');

        roles = JSON.parse(roles);

        $('#roles').val(roles).trigger('change');

        $('#question').val($(this).data('question'));

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
                    text: "If you delete this Survey record, it will be gone forever.",
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