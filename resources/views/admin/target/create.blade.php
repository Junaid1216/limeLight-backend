@extends('admin.layout.app')
@section('title', 'Target Distribution')

@section('content')

<div class="main-content">
<section class="section">
<div class="section-body">

<a class="btn btn-primary mb-3" href="{{ route('target.index') }}">Back</a>

<form id="targetForm" action="{{ route('target.store') }}" method="POST">
@csrf

<div class="card">
<h4 class="text-center my-4">Target Distribution</h4>

<div class="row px-4">

{{-- Month --}}
<div class="col-md-3">
    <div class="form-group">

        <label>Month <span style="color: red;">*</span></label>

        <select name="month" id="month" class="form-control" required>

            <option value="">Select Month</option>

            <option value="January">January</option>
            <option value="February">February</option>
            <option value="March">March</option>
            <option value="April">April</option>
            <option value="May">May</option>
            <option value="June">June</option>
            <option value="July">July</option>
            <option value="August">August</option>
            <option value="September">September</option>
            <option value="October">October</option>
            <option value="November">November</option>
            <option value="December">December</option>

        </select>

    </div>
</div>

{{-- Branch Manager --}}
{{-- <div class="col-md-4">
    <div class="form-group">
        <label>Branch Manager <span style="color:red">*</span></label>

        <select name="branch_manager_id"
                id="branch_manager_id"
                class="form-control"
                required>

            <option value="">Select Branch Manager</option>

        </select>

    </div>
</div> --}}

<div class="col-md-3">
    <div class="form-group">

        <label>Current Year</label>

        <input type="text"
               class="form-control"
               value="{{ date('Y') }}"
               disabled>

        <input type="hidden"
               name="year"
               value="{{ date('Y') }}">

    </div>
</div>

<div class="col-md-3">
    <div class="form-group">
        <label>Branch <span style="color: red;">*</span></label>

        <select name="branch_id" id="branch_id" class="form-control" disabled>
            <option value="">Select Branch</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
            @endforeach
        </select>
    </div>
</div>

{{-- DESIGNATION --}}
<div class="col-md-3">
    <div class="form-group">
        <label>Designation <span style="color: red;">*</span></label>

        <select name="designation_id" id="designation_id" class="form-control" disabled>
            <option value="">Select Designation</option>
        </select>
    </div>
</div>

{{-- Category --}}
<div class="col-md-6">
    <div class="form-group">
        <label>Category <span style="color: red;">*</span></label>
        <select name="category" class="form-control" required>
            <option value="">Select Category</option>
            <option value="garments">Garments</option>
            <option value="unstitched">Unstitched</option>
            <option value="accessories">Accessories</option>
        </select>
    </div>
</div>

{{-- Monthly Target --}}
<div class="col-md-6">
    <div class="form-group">
        <label>Monthly Target <span style="color: red;">*</span></label>
        <input type="number" name="monthly_target" id="monthly_target" placeholder="Add Number" class="form-control" required>
    </div>
</div>

{{-- Weekly Targets --}}
<div class="col-md-3">
    <label class="font-weight-bold">1st Week (%) <span style="color: red;">*</span></label>
    <input type="number" name="week_1" class="form-control week-input" data-week="1" required>
    <small class="text-success pieces" id="week_1_pieces">0</small>
</div>

<div class="col-md-3">
    <label class="font-weight-bold">2nd Week (%) <span style="color: red;">*</span></label>
    <input type="number" name="week_2" class="form-control week-input" data-week="2" required>
    <small class="text-success pieces" id="week_2_pieces">0</small>
</div>

<div class="col-md-3">
    <label class="font-weight-bold">3rd Week (%) <span style="color: red;">*</span></label>
    <input type="number" name="week_3" class="form-control week-input" data-week="3" required>
    <small class="text-success pieces" id="week_3_pieces">0</small>
</div>

<div class="col-md-3">
    <label class="font-weight-bold">4th Week (%) <span style="color: red;">*</span></label>
    <input type="number" name="week_4" class="form-control week-input" data-week="4" required>
    <small class="text-success pieces" id="week_4_pieces">0</small>
</div>

</div>

<div class="card-footer text-center">
    <button class="btn btn-primary">Save Target</button>
</div>

</div>

</form>

</div>
</section>
</div>

@endsection
@section('js')
<script>
    function calculatePieces() {

        let monthly = parseFloat($('#monthly_target').val()) || 0;

        $('.week-input').each(function () {

            let percent = parseFloat($(this).val()) || 0;
            let week = $(this).data('week');

            let pieces = (monthly * percent) / 100;

            $('#week_' + week + '_pieces').text(pieces.toFixed(0) + ' products');
        });
    }

    $(document).on('input', '#monthly_target, .week-input', function () {
        calculatePieces();
    });

    // Validate before save
$('#targetForm').submit(function(e){

    let totalPercent = 0;

    $('.week-input').each(function(){

        totalPercent += parseFloat($(this).val()) || 0;

    });

    if(totalPercent > 100){

        e.preventDefault();

        toastr.error(
            'Weekly Percentages Cannot Exceed 100%'
        );

        return false;
    }

});
</script>

<script>

const targets = @json(
    \App\Models\Target::select(
        'branch_id',
        'month',
        'year'
    )->get()
);

const currentYear = {{ date('Y') }};

</script>

<script>

$(document).ready(function () {

    // MONTH CHANGE
    $('#month').on('change', function () {

        let selectedMonth = $(this).val();

        let branchHtml = '<option value="">Select Branch</option>';

        // reset
        $('#designation_id').html(
            '<option value="">Select Designation</option>'
        );

        $('#designation_id').prop('disabled', true);

        if (selectedMonth !== '') {

            $('#branch_id').prop('disabled', false);

            @foreach($branches as $branch)

                // check if branch already has target
                let alreadyAssigned{{ $branch->id }} = targets.some(function(target){

                    return target.branch_id == {{ $branch->id }}
                        && target.month == selectedMonth
                        && target.year == currentYear;

                });

                // show only unassigned branches
                if(!alreadyAssigned{{ $branch->id }}){

                    branchHtml += `
                        <option value="{{ $branch->id }}">
                            {{ $branch->name }}
                        </option>
                    `;

                }

            @endforeach

            $('#branch_id').html(branchHtml);

        } else {

            $('#branch_id').prop('disabled', true);

            $('#branch_id').html(
                '<option value="">Select Branch</option>'
            );

        }

    });

    // BRANCH CHANGE
    $('#branch_id').on('change', function () {

        let branchId = $(this).val();

        if (branchId !== '') {

            $('#designation_id').prop('disabled', false);

            $.ajax({

                url: "{{ url('admin/get-branch-designations') }}/" + branchId,

                type: "GET",

                success: function (res) {

                    let options =
                        '<option value="">Select Designation</option>';

                    res.forEach(function (item) {

                        options += `
                            <option value="${item.id}">
                                ${item.name}
                            </option>
                        `;

                    });

                    $('#designation_id').html(options);

                }

            });

        } else {

            $('#designation_id').prop('disabled', true);

            $('#designation_id').html(
                '<option value="">Select Designation</option>'
            );

        }

    });

});

</script>
@endsection