
<?php $__env->startSection('title', 'Branchwise Monthly Targets'); ?>

<?php $__env->startSection('content'); ?>
    <div class="main-content" style="min-height: 562px;">
        <section class="section">
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Branchwise Monthly Targets</h4>
                            </div>
                            <div class="card-body table-striped table-bordered table-responsive">
								 <?php if(Auth::guard('admin')->check() ||
                                        ($sideMenuPermissions->has('Targets') && $sideMenuPermissions['Targets']->contains('create'))): ?>
                                    <a class="btn btn-primary mb-3" href="<?php echo e(route('target.create')); ?>">Create</a>
                                <?php endif; ?>
                                <table class="table responsive" id="table_id_events">
                                    <thead>
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Month, Year</th>
                                            <th>Branch</th>
                                            <th>Designation</th>
                                            <th>Category</th>
                                            <th>Monthly Target</th>
                                            <th>Week1</th>
                                            <th>Week2</th>
                                            <th>Week3</th>
                                            <th>Week4</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                                 <?php $__currentLoopData = $targets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $target): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <tr>
                                                        <td><?php echo e($loop->iteration); ?></td>

                                                        <td><?php echo e($target->month); ?>, <?php echo e($target->year); ?></td>

                                                        <td><?php echo e($target->branch->name ?? '-'); ?></td>

                                                        <td><?php echo e($target->designation->name ?? '-'); ?></td>

                                                        <td><?php echo e(ucfirst($target->category)); ?></td>

                                                        <td>
                                                            <strong><?php echo e($target->monthly_target); ?></strong>
                                                        </td>

                                                        
                                                        <td>
                                                            <?php echo e($target->week_1); ?>% <br>
                                                            <small class="text-success">
                                                                <?php echo e(($target->monthly_target * $target->week_1) / 100); ?> Products
                                                            </small>
                                                        </td>

                                                        <td>
                                                            <?php echo e($target->week_2); ?>% <br>
                                                            <small class="text-success">
                                                                <?php echo e(($target->monthly_target * $target->week_2) / 100); ?> Products
                                                            </small>
                                                        </td>

                                                        <td>
                                                            <?php echo e($target->week_3); ?>% <br>
                                                            <small class="text-success">
                                                                <?php echo e(($target->monthly_target * $target->week_3) / 100); ?> Products
                                                            </small>
                                                        </td>

                                                        <td>
                                                            <?php echo e($target->week_4); ?>% <br>
                                                            <small class="text-success">
                                                                <?php echo e(($target->monthly_target * $target->week_4) / 100); ?> Products
                                                            </small>
                                                        </td>

                                                        <td>
                                                        <label class="custom-switch">
                                                            <input type="checkbox" class="custom-switch-input toggle-status"
                                                                data-id="<?php echo e($target->id); ?>"
                                                                <?php echo e($target->toggle ? 'checked' : ''); ?>

                                                                <?php echo e($target->toggle ? 'disabled' : ''); ?>>
                                                            <span class="custom-switch-indicator"></span>
                                                            <span class="custom-switch-description">
                                                                <?php echo e($target->toggle ? 'Activated' : 'Deactivated'); ?>

                                                            </span>
                                                        </label>
                                                        </td>
                                                <td style="vertical-align: middle;">
                                                    <div class="d-flex align-items-center" style="gap: 6px;">
                                                        <?php if(Auth::guard('admin')->check() ||
                                                                ($sideMenuPermissions->has('Targets') && $sideMenuPermissions['Targets']->contains('edit'))): ?>
                                                           <?php if(!$target->toggle): ?>

                                                                <a href="<?php echo e(route('target.edit', $target->id)); ?>"
                                                                    class="btn btn-primary p-2"
                                                                    style="background-color: #609b90;">
                                                                    <i class="fa fa-edit"></i>
                                                                </a>

                                                            <?php else: ?>

                                                                <button class="btn btn-secondary p-2" disabled
                                                                    title="Activated targets cannot be edited">
                                                                    <i class="fa fa-lock"></i>
                                                                </button>

                                                            <?php endif; ?>
                                                        <?php endif; ?>

                                                        
                                                    </div>
                                                </td>

                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div> <!-- /.card-body -->
                        </div> <!-- /.card -->
                    </div> <!-- /.col -->
                </div> <!-- /.row -->
            </div> <!-- /.section-body -->
        </section>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('js'); ?>

<script>

$(document).ready(function () {

    // DataTable
    $('#table_id_events').DataTable();

    // Toggle Status
   $(document).on('change', '.toggle-status', function () {

    let checkbox = $(this);

    let status = checkbox.is(':checked') ? 1 : 0;

    let targetId = checkbox.data('id');

    // ❌ Prevent Deactivation
    if(status == 0)
    {
        toastr.error('Activated targets cannot be deactivated');

        checkbox.prop('checked', true);

        return;
    }

    // ✅ Activation Confirmation
    Swal.fire({
        title: 'Activate Target?',
        text: 'Once activated, this target cannot be edited or deactivated.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Activate',
        cancelButtonText: 'Cancel'
    }).then((result) => {

        if(result.isConfirmed)
        {
            updateStatus(targetId, status, checkbox);
        }
        else
        {
            checkbox.prop('checked', false);
        }

    });

});

    // AJAX FUNCTION
    function updateStatus(id, status, checkbox)
    {
        $.ajax({

            url: "<?php echo e(route('target.toggleStatus')); ?>",

            method: "POST",

            data: {
                _token: "<?php echo e(csrf_token()); ?>",
                id: id,
                status: status
            },

            success: function(response)
            {

                if(response.success)
                {
                    toastr.success(response.message);

                    setTimeout(() => {
                        location.reload();
                    }, 800);
                }
                else
                {
                    checkbox.prop('checked', !status);

                    toastr.error(response.message);
                }

            },

            error: function(xhr)
            {
                console.log(xhr.responseText);

                checkbox.prop('checked', !status);

                toastr.error('Something went wrong');
            }

        });
    }

});

</script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layout.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\limelight-sales-perfomance\resources\views/admin/target/index.blade.php ENDPATH**/ ?>