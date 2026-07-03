@extends('admin.layout.app')
@section('title', 'Reporting')

@section('content')
    <div class="main-content" style="min-height: 562px;">
        <section class="section">
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Reportings</h4>
                            </div>
                            <div class="card-body table-striped table-bordered table-responsive">
								 
                                <table class="table responsive" id="table_id_events">
                                    <thead>
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Invoice ID</th>
                                            <th>Branch ID</th>
                                            <th>Date&Time</th>
                                            <th>Sales Staff ID</th>
                                            <th>Sales Staff Name</th>
                                            <th>Line Item ID</th>
                                            <th>Line Item Name</th>
                                            <th>Quantity</th>
                                            <th>Category</th>
                                            <th>Amount</th>
                                            <th>Total Amount</th>
                                        </tr>
                                    </thead>
                                   <tbody>
                                        @foreach ($reports as $report)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $report->invoice_id ?? '-' }}</td>
                                            <td>{{ $report->branch_id ?? '-' }}</td>
                                            <td>{{ $target->month }}, {{ $target->year }}</td>
                                            <td>{{ $report->sales_staff_id ?? '-' }}</td>
                                            <td>{{ $report->sales_staff_name ?? '-' }}</td>
                                            <td>{{ $report->line_item_id ?? '-' }}</td>
                                            <td>{{ $report->line_item_name ?? '-' }}</td>
                                            <td>{{ $report->quantity ?? '-' }}</td>
                                            <td>{{ $report->category ?? '-' }}</td>
                                            <td>{{ $report->amount ?? '-' }}</td>
                                            <td>{{ $report->total_amount ?? '-' }}</td>
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

$(document).ready(function () {

    // DataTable
    $('#table_id_events').DataTable();
});

</script>

@endsection