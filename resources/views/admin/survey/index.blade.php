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
                                            <label>Survey Title <span style="color: red;">*</span></label>
                                            <input type="text"
                                                   name="title"
                                                   id="title"
                                                   class="form-control"
                                                   placeholder="e.g. Price Satisfaction Survey"
                                                   required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Status <span style="color: red;">*</span></label>
                                            <select name="status" id="status" class="form-control" required>
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Role <span style="color: red;">*</span></label>

                                             <select name="roles[]"
                                                    id="roles"
                                                    class="form-control select2"
                                                    multiple
                                                    required>

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
                                                   placeholder="First question (options: High / Fair / Low)"
                                                   required>
                                        </div>
                                    </div>

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
                                        <th>Title</th>
                                        <th>Question</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    @foreach($surveys as $survey)
                                    @php
                                        $questionList = $survey->questions->pluck('question')->filter()->values();
                                        if ($questionList->isEmpty() && !empty($survey->question)) {
                                            $questionList = collect([$survey->question]);
                                        }
                                    @endphp

                                    <tr>

                                        <td>{{ $loop->iteration }}</td>

                                        <td>
                                            {{ collect($survey->roles ?? [])
                                                ->map(fn($role) => ucwords(str_replace('_',' ',$role)))
                                                ->implode(', ') ?: '-' }}
                                        </td>

                                        <td>{{ $survey->title ?: '-' }}</td>

                                        <td style="min-width: 260px; max-width: 420px;">
                                            @if($questionList->isEmpty())
                                                <span class="text-muted">-</span>
                                            @else
                                                <ol class="mb-0 pl-3" style="padding-left: 18px;">
                                                    @foreach($questionList as $qText)
                                                        <li style="margin-bottom: 4px;">{{ $qText }}</li>
                                                    @endforeach
                                                </ol>
                                            @endif
                                        </td>

                                        <td>
                                            <span class="badge badge-{{ ($survey->status ?? 'active') === 'active' ? 'success' : 'secondary' }}">
                                                {{ strtoupper($survey->status ?? 'active') }}
                                            </span>
                                        </td>

                                        <td>
                                            <div class="d-flex align-items-center" style="gap: 6px;">
                                            @if (Auth::guard('admin')->check() ||
                                                                ($sideMenuPermissions->has('Surveys') && $sideMenuPermissions['Surveys']->contains('edit')))
                                            <button
                                                type="button"
                                                class="btn btn-primary btn-sm editVideo"
                                                data-id="{{ $survey->id }}"
                                                data-title="{{ $survey->title }}"
                                                data-status="{{ $survey->status ?? 'active' }}"
                                                data-roles='@json($survey->roles)'
                                                data-question="{{ $survey->question }}">

                                                <i class="fa fa-edit"></i>

                                            </button>

                                            <button
                                                type="button"
                                                class="btn btn-info btn-sm addQuestionBtn"
                                                data-id="{{ $survey->id }}"
                                                data-title="{{ $survey->title }}"
                                                title="Add Question">
                                                <i class="fa fa-plus"></i>
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
        $('#video_id').val($(this).data('id'));
        $('#title').val($(this).data('title'));
        $('#status').val($(this).data('status'));

        let roles = $(this).attr('data-roles');
        roles = JSON.parse(roles || '[]');
        $('#roles').val(roles).trigger('change');
        $('#question').val($(this).data('question'));

        $('html, body').animate({
            scrollTop: 0
        }, 300);
    });

    $('.addQuestionBtn').click(function(){
        let surveyId = $(this).data('id');
        let title = $(this).data('title') || 'Survey';

        Swal.fire({
            title: '<div style="font-size:20px;font-weight:600;color:#2c3e50;">Add Question</div>',
            html:
                '<div style="text-align:left;padding:4px 6px 0;">' +
                    '<div style="display:inline-block;background:#e8f5f2;color:#2f6f64;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:600;margin-bottom:14px;">' +
                        (title || 'Survey') +
                    '</div>' +
                    '<label style="display:block;font-size:13px;font-weight:600;color:#445;margin-bottom:6px;">Question <span style="color:#c0392b;">*</span></label>' +
                    '<textarea id="swal-question" rows="3" placeholder="Type your question here..." ' +
                        'style="width:100%;border:1px solid #d7e0e6;border-radius:10px;padding:12px 14px;font-size:14px;resize:vertical;outline:none;box-sizing:border-box;"></textarea>' +
                    '<div style="margin-top:12px;background:#f7fafb;border:1px dashed #c9d8d4;border-radius:10px;padding:10px 12px;">' +
                        '<div style="font-size:12px;color:#5a6a72;margin-bottom:8px;">Default options will be added automatically:</div>' +
                        '<span style="display:inline-block;background:#fff;border:1px solid #dbe7e4;border-radius:16px;padding:3px 10px;font-size:12px;margin-right:6px;color:#2f6f64;">High</span>' +
                        '<span style="display:inline-block;background:#fff;border:1px solid #dbe7e4;border-radius:16px;padding:3px 10px;font-size:12px;margin-right:6px;color:#2f6f64;">Fair</span>' +
                        '<span style="display:inline-block;background:#fff;border:1px solid #dbe7e4;border-radius:16px;padding:3px 10px;font-size:12px;color:#2f6f64;">Low</span>' +
                    '</div>' +
                '</div>',
            width: 520,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Add Question',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#609b90',
            cancelButtonColor: '#95a5a6',
            customClass: {
                popup: 'survey-add-question-modal'
            },
            didOpen: () => {
                const input = document.getElementById('swal-question');
                if (input) {
                    input.focus();
                    input.addEventListener('focus', function () {
                        this.style.borderColor = '#609b90';
                        this.style.boxShadow = '0 0 0 3px rgba(96,155,144,0.15)';
                    });
                    input.addEventListener('blur', function () {
                        this.style.borderColor = '#d7e0e6';
                        this.style.boxShadow = 'none';
                    });
                }
            },
            preConfirm: () => {
                const q = (document.getElementById('swal-question').value || '').trim();
                if (!q) {
                    Swal.showValidationMessage('Question is required');
                    return false;
                }
                return q;
            }
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: '{{ url("admin/surveys") }}/' + surveyId + '/add-question',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    question: result.value
                },
                success: function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Added!',
                        text: 'Question added with High / Fair / Low options.',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to add question.', 'error');
                }
            });
        });
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