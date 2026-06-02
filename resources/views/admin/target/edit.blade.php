@extends('admin.layout.app')
@section('title', 'Edit Target Distribution')

@section('content')


<div class="main-content">
<section class="section">
<div class="section-body">

<a class="btn btn-primary mb-3" href="{{ route('target.index') }}">Back</a>

<form action="{{ route('target.update', $target->id) }}" method="POST">
@csrf
@method('POST')

<div class="card">
<h4 class="text-center my-4">Edit Target Distribution</h4>

<div class="row px-4">

    {{-- Month --}}
<div class="col-md-3">
    <div class="form-group">

        <label>Month <span style="color: red;">*</span></label>

        <select name="month" id="month" class="form-control" required>

            <option value="">Select Month</option>

            <option value="January" {{ $target->month == 'January' ? 'selected' : '' }}>January</option>

            <option value="February" {{ $target->month == 'February' ? 'selected' : '' }}>February</option>

            <option value="March" {{ $target->month == 'March' ? 'selected' : '' }}>March</option>

            <option value="April" {{ $target->month == 'April' ? 'selected' : '' }}>April</option>

            <option value="May" {{ $target->month == 'May' ? 'selected' : '' }}>May</option>

            <option value="June" {{ $target->month == 'June' ? 'selected' : '' }}>June</option>

            <option value="July" {{ $target->month == 'July' ? 'selected' : '' }}>July</option>

            <option value="August" {{ $target->month == 'August' ? 'selected' : '' }}>August</option>

            <option value="September" {{ $target->month == 'September' ? 'selected' : '' }}>September</option>

            <option value="October" {{ $target->month == 'October' ? 'selected' : '' }}>October</option>

            <option value="November" {{ $target->month == 'November' ? 'selected' : '' }}>November</option>

            <option value="December" {{ $target->month == 'December' ? 'selected' : '' }}>December</option>

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

        <label>Current Year <span style="color: red;">*</span></label>

        <input type="text"
               class="form-control"
               value="{{ date('Y') }}"
               disabled>

        <input type="hidden"
               name="year"
               value="{{ date('Y') }}">

    </div>
</div>

{{-- BRANCH --}}
<div class="col-md-3">
    <div class="form-group">

        <label>Branch <span style="color: red;">*</span></label>

        <select name="branch_id"
                id="branch_id"
                class="form-control"
                required>

            <option value="">Select Branch</option>

            @foreach($branches as $branch)

                <option value="{{ $branch->id }}"
                    {{ $target->branch_id == $branch->id ? 'selected' : '' }}>

                    {{ $branch->name }}

                </option>

            @endforeach

        </select>

    </div>
</div>

{{-- DESIGNATION --}}
<div class="col-md-3">
    <div class="form-group">

        <label>Designation <span style="color: red;">*</span></label>

        <select name="designation_id"
                id="designation_id"
                class="form-control"
                required>

            <option value="">Select Designation</option>

        </select>

    </div>
</div>

{{-- Category --}}
<div class="col-md-6">
    <div class="form-group">
        <label>Category <span style="color: red;">*</span></label>
        <select name="category" class="form-control" required>
            <option value="garments" {{ $target->category == 'garments' ? 'selected' : '' }}>Garments</option>
            <option value="unstitched" {{ $target->category == 'unstitched' ? 'selected' : '' }}>Unstitched</option>
            <option value="accessories" {{ $target->category == 'accessories' ? 'selected' : '' }}>Accessories</option>
        </select>
    </div>
</div>

{{-- Monthly Target --}}
<div class="col-md-6">
    <div class="form-group">
        <label>Monthly Target <span style="color: red;">*</span></label>
        <input type="number" id="monthly_target" placeholder="Add Number" name="monthly_target"
               value="{{ $target->monthly_target }}"
               class="form-control" required>
    </div>
</div>

{{-- Weekly Targets --}}
@for ($i = 1; $i <= 4; $i++)
<div class="col-md-3">
    <label class="font-weight-bold">Week {{ $i }} (%)</label>
    <input type="number"
           name="week_{{ $i }}"
           value="{{ $target['week_'.$i] }}"
           class="form-control week-input"
           data-week="{{ $i }}"
           required>

    <small class="text-success" id="week_{{ $i }}_pieces">
        0 pieces
    </small>
</div>
@endfor

</div>

<div class="card-footer text-center">
    <button class="btn btn-primary">Update Target</button>
</div>

</div>

</form>

</div>
</section>
</div>

@endsection


@section('js')

<script>
// =========================================
// Weekly calculations
// =========================================

function calculatePieces(){

    let monthly = parseFloat($('#monthly_target').val()) || 0;

    $('.week-input').each(function(){

        let percent = parseFloat($(this).val()) || 0;

        let week = $(this).data('week');

        let pieces = (monthly * percent) / 100;

        $('#week_' + week + '_pieces')
            .text(Math.round(pieces) + ' products');

    });

}


// =========================================
// On page load
// =========================================

$(document).ready(function(){

  

    calculatePieces();

});




// recalculate products
$(document).on(
    'input',
    '#monthly_target,.week-input',
    function(){

        calculatePieces();

    }
);


// prevent >100%
$('form').submit(function(e){

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

@php
    $targetsData = \App\Models\Target::where('id', '!=', $target->id)
        ->select('branch_id', 'month', 'year')
        ->get();
@endphp

<script>

const targets = @json($targetsData);

const currentYear = {{ $target->year }};
const currentBranchId = {{ $target->branch_id ?? 'null' }};
const currentDesignationId = {{ $target->designation_id ?? 'null' }};

</script>

<script>

$(document).ready(function () {

    loadBranches();

    loadDesignations(currentBranchId);

    // =========================
    // MONTH CHANGE
    // =========================

    $('#month').on('change', function () {

        loadBranches();

    });

    // =========================
    // BRANCH CHANGE
    // =========================

    $('#branch_id').on('change', function () {

        let branchId = $(this).val();

        loadDesignations(branchId);

    });

});

// =====================================
// LOAD BRANCHES
// =====================================

function loadBranches() {

    let selectedMonth = $('#month').val();

    let branchHtml =
        '<option value="">Select Branch</option>';

    if(selectedMonth !== '') {

        $('#branch_id').prop('disabled', false);

        @foreach($branches as $branch)

            let alreadyAssigned{{ $branch->id }} =
                targets.some(function(target){

                    return target.branch_id == {{ $branch->id }}
                        && target.month == selectedMonth
                        && target.year == currentYear;

                });

            // show current branch OR unassigned branch
            if(
                !alreadyAssigned{{ $branch->id }}
                ||
                {{ $branch->id }} == currentBranchId
            ){

                branchHtml += `
                    <option value="{{ $branch->id }}"
                        ${currentBranchId == {{ $branch->id }}
                            ? 'selected'
                            : ''}>
                        {{ $branch->name }}
                    </option>
                `;

            }

        @endforeach

        $('#branch_id').html(branchHtml);

    } else {

        $('#branch_id').prop('disabled', true);

    }

}

// =====================================
// LOAD DESIGNATIONS
// =====================================

function loadDesignations(branchId) {

    if(branchId !== '') {

        $('#designation_id').prop('disabled', false);

        $.ajax({

            url: "{{ url('admin/get-branch-designations') }}/" + branchId,

            type: "GET",

            success: function(res) {

                let html =
                    '<option value="">Select Designation</option>';

                res.forEach(function(item){

                    html += `
                        <option value="${item.id}"
                            ${item.id == currentDesignationId
                                ? 'selected'
                                : ''}>
                            ${item.name}
                        </option>
                    `;

                });

                $('#designation_id').html(html);

            }

        });

    } else {

        $('#designation_id').prop('disabled', true);

        $('#designation_id').html(
            '<option value="">Select Designation</option>'
        );

    }

}

</script>

@endsection