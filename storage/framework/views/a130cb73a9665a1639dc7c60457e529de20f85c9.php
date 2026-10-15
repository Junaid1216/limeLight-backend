<div class="d-flex align-items-center" style="gap: 6px;">
    <?php if(Auth::guard('admin')->check() ||
            ($sideMenuPermissions->has('Training Modules') && $sideMenuPermissions['Training Modules']->contains('edit'))): ?>
        <button
            type="button"
            class="btn btn-primary btn-sm editVideo"
            data-id="<?php echo e($video->id); ?>"
            data-training-type="<?php echo e($video->training_type); ?>"
            data-roles='<?php echo json_encode($video->roles, 15, 512) ?>'
            data-category="<?php echo e($video->category); ?>"
            data-title="<?php echo e($video->title); ?>"
            data-video="<?php echo e($video->video_url); ?>"
            data-description="<?php echo e($video->description); ?>"
            data-product-name="<?php echo e($video->product_name); ?>"
            data-product-code="<?php echo e($video->product_code); ?>"
            data-price="<?php echo e($video->price); ?>"
            data-product-category="<?php echo e($video->product_category); ?>"
            data-product-sub-category="<?php echo e($video->product_sub_category); ?>"
            data-product-size="<?php echo e($video->product_size); ?>"
            data-product-color="<?php echo e($video->product_color); ?>"
            data-product-status="<?php echo e($video->product_status); ?>"
            data-training-details="<?php echo e($video->training_details); ?>">
            <i class="fa fa-edit"></i>
        </button>
    <?php endif; ?>

    <?php if(Auth::guard('admin')->check() ||
            ($sideMenuPermissions->has('Training Modules') && $sideMenuPermissions['Training Modules']->contains('delete'))): ?>
        <form id="delete-form-<?php echo e($video->id); ?>"
            action="<?php echo e(route('training.video.delete', $video->id)); ?>"
            method="POST">
            <?php echo csrf_field(); ?>
            <?php echo method_field('DELETE'); ?>
        </form>

        <button class="show_confirm btn p-2"
            style="background-color: #609b90;"
            data-form="delete-form-<?php echo e($video->id); ?>"
            type="button">
            <i class="fa fa-trash"></i>
        </button>
    <?php endif; ?>
</div>
<?php /**PATH C:\xampp\htdocs\limelight-sales-perfomance\resources\views/admin/trainingvideo/partials/actions.blade.php ENDPATH**/ ?>