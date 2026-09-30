
<?php $__env->startSection('title', 'Feedback Detail'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $isAsm = filled($feedback->region_name);
?>
<div class="main-content" style="min-height: 562px;">
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">Feedback Detail</h4>
                            <a href="<?php echo e(route('feedback.index')); ?>" class="btn btn-secondary btn-sm">Back</a>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Type</strong></div>
                                <div class="col-md-9">
                                    <?php if($isAsm): ?>
                                        <span class="badge badge-info">ASM</span>
                                    <?php else: ?>
                                        <span class="badge badge-primary">Sales Staff</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Code</strong></div>
                                <div class="col-md-9"><?php echo e($feedback->code ?: '-'); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Name</strong></div>
                                <div class="col-md-9"><?php echo e($feedback->name ?: '-'); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-3"><strong><?php echo e($isAsm ? 'Region' : 'Branch'); ?></strong></div>
                                <div class="col-md-9">
                                    <?php echo e($isAsm ? ($feedback->region_name ?: '-') : ($feedback->branch_name ?: '-')); ?>

                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Submitted At</strong></div>
                                <div class="col-md-9"><?php echo e(optional($feedback->created_at)->format('d M Y, h:i A') ?: '-'); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Feedback</strong></div>
                                <div class="col-md-9" style="white-space: pre-wrap;"><?php echo e($feedback->feedback); ?></div>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <form id="delete-form-<?php echo e($feedback->id); ?>"
                                action="<?php echo e(route('feedback.destroy', $feedback->id)); ?>"
                                method="POST"
                                style="display:inline;">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="button" class="btn btn-danger delete-btn"
                                    data-form="delete-form-<?php echo e($feedback->id); ?>">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('js'); ?>
<script>
    $(document).on('click', '.delete-btn', function () {
        var formId = $(this).data('form');
        Swal.fire({
            title: 'Are you sure?',
            text: 'This feedback will be deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it'
        }).then(function (result) {
            if (result.isConfirmed) {
                document.getElementById(formId).submit();
            }
        });
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layout.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\limelight-sales-perfomance\resources\views/admin/feedback/show.blade.php ENDPATH**/ ?>