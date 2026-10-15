
<?php $__env->startSection('title', 'Training Modules'); ?>

<?php $__env->startSection('content'); ?>
<div class="main-content">
    <section class="section">
        <div class="section-body">

            <div class="row">
                <div class="col-12">

                   
<div class="card">

    <div class="card-header">
        <h4>Training Modules</h4>
    </div>

    <form action="<?php echo e(route('training.video.store')); ?>"
          method="POST"
          enctype="multipart/form-data">

        <?php echo csrf_field(); ?>

        <input type="hidden" name="id" id="video_id">

        <div class="card-body pb-1">

            
            <div class="row">

                <div class="col-md-4">
                    <div class="form-group">
                        <label>
                            Training Type <span style="color: red;">*</span>
                        </label>

                        <select name="training_type"
                                id="training_type"
                                class="form-control"
                                required>

                            <option value="">Select Training Type</option>

                            <option value="customer">Customer</option>
                            <option value="product">Product</option>
                            <option value="display">Display</option>

                        </select>
                    </div>
                </div>

                <div class="col-md-4" id="category_field" >
                    <div class="form-group">
                        <label>
                            Category <span style="color: red;">*</span>
                        </label>

                        <select name="category"
                                id="category"
                                class="form-control"
                                required>

                            <option value="">Select Category</option>
                            <?php $__currentLoopData = ($displayCategories ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $displayCategory): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($displayCategory->slug); ?>">
                                    <?php echo e($displayCategory->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                        </select>
                    </div>
                </div>

                
                <div class="col-md-4">
                    <div class="form-group">

                        <label>
                            Role <span style="color:red;">*</span>
                        </label>

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

                
               <div class="col-md-4" id="title_field">
                    <div class="form-group">
                        <label>Title <span style="color: red;">*</span></label>

                        <input type="text"
                            name="title"
                            id="title"
                            class="form-control">
                    </div>
                </div>

            </div>


            
            
            

            <div id="productTrainingFields"
                 style="display:none;">

                <div class="row">

                    
                    <div class="col-md-4">
                        <div class="form-group">

                            <label>Product Name</label>

                            <input type="text"
                                   name="product_name"
                                   id="product_name"
                                   class="form-control"
                                   placeholder="e.g. 2 Piece Jacquard Suit Dyed">

                        </div>
                    </div>


                    
                    <div class="col-md-4">
                        <div class="form-group">

                            <label>Product Code</label>

                            <input type="text"
                                   name="product_code"
                                   id="product_code"
                                   class="form-control"
                                   placeholder="e.g. A1708ST-XSL-143">

                        </div>
                    </div>


                    
                    <div class="col-md-4">
                        <div class="form-group">

                            <label>Price</label>

                            <input type="number"
                                   name="price"
                                   id="price"
                                   class="form-control"
                                   step="0.01"
                                   placeholder="e.g. 5999">

                        </div>
                    </div>


                    
                    
                    <div class="col-md-4">
                        <div class="form-group">

                            <label>Product Category</label>

                            <input type="text"
                                   name="product_category"
                                   id="product_category"
                                   class="form-control"
                                   placeholder="e.g. Printed">

                        </div>
                    </div>


                    
                    <div class="col-md-4">
                        <div class="form-group">

                            <label>Product Sub Category</label>

                            <input type="text"
                                   name="product_sub_category"
                                   id="product_sub_category"
                                   class="form-control"
                                   placeholder="e.g. Jacquard">

                        </div>
                    </div>


                    
                    <div class="col-md-4">
                        <div class="form-group">

                            <label>Size</label>

                            <input type="text"
                                   name="product_size"
                                   id="product_size"
                                   class="form-control"
                                   placeholder="e.g. Medium">

                        </div>
                    </div>


                    
                    <div class="col-md-6">
                        <div class="form-group">

                            <label>Color</label>

                            <input type="text"
                                   name="product_color"
                                   id="product_color"
                                   class="form-control"
                                   placeholder="e.g. Purple">

                        </div>
                    </div>


                    
                    <div class="col-md-6">
                        <div class="form-group">

                            <label>Status</label>

                            <select name="product_status"
                                    id="product_status"
                                    class="form-control">

                                <option value="new">
                                    New
                                </option>

                                <option value="pending">
                                    Pending
                                </option>

                                <option value="completed">
                                    Completed
                                </option>

                            </select>

                        </div>
                    </div>


                    
                    <div class="col-md-12">
                        <div class="form-group">

                            <label>
                                Training Details
                            </label>

                            <textarea name="training_details"
                                      id="training_details"
                                      rows="4"
                                      class="form-control"
                                      placeholder="Enter detailed product training information"></textarea>

                        </div>
                    </div>

                </div>

            </div>


            
            
            

            <div class="row">

                
                <div class="col-md-6" id="image_field">
                    <div class="form-group">
                        <label>Image</label>

                        <input type="file"
                            name="image"
                            id="image"
                            class="form-control"
                            accept="image/*">
                    </div>
                </div>


                
                <div class="col-md-6" id="audio_field">
                    <div class="form-group">
                        <label>Audio</label>

                        <input type="file"
                            name="audio"
                            id="audio"
                            class="form-control"
                            accept="audio/*">
                    </div>
                </div>

                
                <div class="col-md-6" id="audio_record_field">
                    <div class="form-group">
                        <label>Record voice</label>
                        <div class="d-flex align-items-center" style="gap: 8px; flex-wrap: wrap;">
                            <button type="button" class="btn btn-primary btn-sm" id="start_recording">Start recording</button>
                            <button type="button" class="btn btn-danger btn-sm" id="stop_recording" disabled>Stop</button>
                            <button type="button" class="btn btn-secondary btn-sm" id="discard_recording" disabled>Discard</button>
                            <span id="recording_status" class="text-muted"></span>
                        </div>
                        <audio id="recording_preview" controls style="display:none; width:100%; margin-top:8px;"></audio>
                        <input type="file" name="audio_recording" id="audio_recording" class="d-none" accept="audio/*">
                        <small class="text-muted">If you record and also upload a file, the recording is saved.</small>
                    </div>
                </div>


                
               <div class="col-md-6" id="video_url_field">
                    <div class="form-group">
                        <label>Video URL</label>

                        <input type="text"
                            name="video_url"
                            id="video_url"
                            class="form-control"
                            placeholder="Enter Video URL">
                    </div>
                </div>

                
                <div class="col-md-6" id="video_file_field">
                    <div class="form-group">
                        <label>Upload video</label>
                        <input type="file"
                            name="video"
                            id="video"
                            class="form-control"
                            accept="video/*">
                        <small class="text-muted">MP4, MOV, AVI or WEBM. Max 50MB. Uploaded file is used instead of the URL if both are provided.</small>
                    </div>
                </div>


                
                <div class="col-md-12" id="description_field">
                    <div class="form-group mb-0">
                        <label>Description <span style="color: red;">*</span></label>

                        <textarea name="description"
                                id="description"
                                rows="3"
                                class="form-control"></textarea>
                    </div>
                </div>

            </div>

        </div>


        <div class="card-footer text-center py-2">

            <button type="submit"
                    class="btn btn-primary mb-4">
                Save
            </button>

        </div>

    </form>

</div>

                    
                    <div class="card">

                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                            <h4 class="mb-0">Training Module List</h4>
                            <div class="mt-2 mt-md-0">
                                <button type="button" class="btn btn-sm training-type-filter active" data-type="customer" style="background:#609b90;color:#609b90;border-radius:20px;">Customer</button>
                                <button type="button" class="btn btn-sm training-type-filter active" data-type="product" style="background:#609b90;color:#609b90;border-radius:20px;">Product</button>
                                <button type="button" class="btn btn-sm training-type-filter active" data-type="display" style="background:#609b90;color:#609b90;border-radius:20px;">Display</button>
                            </div>
                        </div>

                        <div class="card-body table-responsive">

                            <?php
                                $customerVideos = $videos->where('training_type', 'customer')->values();
                                $productVideos = $videos->where('training_type', 'product')->values();
                                $displayVideos = $videos->where('training_type', 'display')->values();
                            ?>

                            
                            <div class="training-type-table table-responsive" id="table-customer">
                                <table class="table responsive table-bordered training-datatable">
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
                                        <?php $__empty_1 = true; $__currentLoopData = $customerVideos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $video): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td><?php echo e($loop->iteration); ?></td>
                                        <td>
                                                    <?php echo e(collect($video->roles)->map(fn($role) => ucwords(str_replace('_',' ',$role)))->implode(', ')); ?>

                                        </td>
                                                <td><?php echo e($video->title ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php if($video->publicVideoUrl()): ?>
                                                        <a href="<?php echo e($video->publicVideoUrl()); ?>" target="_blank" class="btn btn-primary btn-sm">View</a>
                                            <?php else: ?>
                                            N/A
                                            <?php endif; ?>
                                        </td>
                                                <td><?php echo e($video->description ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php echo $__env->make('admin.trainingvideo.partials.actions', ['video' => $video], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                        </td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No customer training modules found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            
                            <div class="training-type-table table-responsive" id="table-product" style="display:none;">
                                <table class="table responsive table-bordered training-datatable">
                                    <thead>
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Role</th>
                                            <th>Product Name</th>
                                            <th>Product Code</th>
                                            <th>Price</th>
                                            <th>Product Category</th>
                                            <th>Product Sub Category</th>
                                            <th>Size</th>
                                            <th>Color</th>
                                            <th>Status</th>
                                            <th>Training Details</th>
                                            <th>Image</th>
                                            <th>Audio</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $__empty_1 = true; $__currentLoopData = $productVideos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $video): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                            <tr>
                                                <td><?php echo e($loop->iteration); ?></td>
                                                <td>
                                                    <?php echo e(collect($video->roles)->map(fn($role) => ucwords(str_replace('_',' ',$role)))->implode(', ')); ?>

                                        </td>
                                                <td><?php echo e($video->product_name ?? 'N/A'); ?></td>
                                                <td><?php echo e($video->product_code ?? 'N/A'); ?></td>
                                                <td><?php echo e($video->price ?? 'N/A'); ?></td>
                                                <td><?php echo e($video->product_category ?? 'N/A'); ?></td>
                                                <td><?php echo e($video->product_sub_category ?? 'N/A'); ?></td>
                                                <td><?php echo e($video->product_size ?? 'N/A'); ?></td>
                                                <td><?php echo e($video->product_color ?? 'N/A'); ?></td>
                                                <td><?php echo e($video->product_status ? ucfirst($video->product_status) : 'N/A'); ?></td>
                                                <td><?php echo e($video->training_details ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php if($video->image && file_exists($video->image)): ?>
                                                        <img src="<?php echo e(asset($video->image)); ?>" width="50" height="50" alt="Image">
                                            <?php else: ?>
                                                        <img src="<?php echo e(asset('public/admin/assets/images/avator.png')); ?>" width="50" height="50" alt="Default Image">
                                            <?php endif; ?>  
                                        </td>
                                                <td>
                                                    <?php if($video->audio): ?>
                                                        <audio controls style="width:180px;">
                                                            <source src="<?php echo e(asset($video->audio)); ?>">
                                                        </audio>
                                            <?php else: ?>
                                            N/A
                                            <?php endif; ?>
                                        </td>
                                                <td>
                                                    <?php echo $__env->make('admin.trainingvideo.partials.actions', ['video' => $video], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                        </td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                            <tr>
                                                <td colspan="14" class="text-center">No product training modules found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            
                            <div class="training-type-table table-responsive" id="table-display"  style="display:none;">
                                <table class="table responsive table-bordered training-datatable">
                                    <thead>
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Role</th>
                                            <th>Category</th>
                                            <th>Title</th>
                                            <th>Description</th>
                                            <th>Image</th>
                                            <th>Audio</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $__empty_1 = true; $__currentLoopData = $displayVideos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $video): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                            <tr>
                                                <td><?php echo e($loop->iteration); ?></td>
                                                <td>
                                                    <?php echo e(collect($video->roles)->map(fn($role) => ucwords(str_replace('_',' ',$role)))->implode(', ')); ?>

                                        </td>
                                                <td><?php echo e($video->category ? ucfirst($video->category) : 'N/A'); ?></td>
                                                <td><?php echo e($video->title ?? 'N/A'); ?></td>
                                                <td><?php echo e($video->description ?? 'N/A'); ?></td>
                                        <td>
                                                    <?php if($video->image && file_exists($video->image)): ?>
                                                        <img src="<?php echo e(asset($video->image)); ?>" width="50" height="50" alt="Image">
                                                    <?php else: ?>
                                                        <img src="<?php echo e(asset('public/admin/assets/images/avator.png')); ?>" width="50" height="50" alt="Default Image">
                                                    <?php endif; ?>
                                                </td>
                                        <td>
                                            <?php if($video->audio): ?>
                                                        <audio controls style="width:180px;">
                                                    <source src="<?php echo e(asset($video->audio)); ?>">
                                                </audio>
                                                <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                                <td>
                                                    <?php echo $__env->make('admin.trainingvideo.partials.actions', ['video' => $video], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                        </td>
                                    </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                            <tr>
                                                <td colspan="8" class="text-center">No display training modules found.</td>
                                            </tr>
                                        <?php endif; ?>
                                </tbody>
                            </table>
                            </div>

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
$(document).ready(function () {

    /*
    |--------------------------------------------------------------------------
    | Select2
    |--------------------------------------------------------------------------
    */
    $('.select2').select2({
        width: '100%',
        placeholder: 'Select Roles'
    });


    /*
    |--------------------------------------------------------------------------
    | Training Type Fields
    |--------------------------------------------------------------------------
    */
    function toggleTrainingFields() {

        let trainingType = $('#training_type').val();

        // Hide all conditional fields first
        $('#image_field').hide();
        $('#audio_field').hide();
        $('#audio_record_field').hide();
        $('#video_url_field').hide();
        $('#video_file_field').hide();
        $('#title_field').hide();
        $('#description_field').hide();
        $('#category_field').hide();
        $('#productTrainingFields').hide();

        // Remove required attributes
        $('#title').prop('required', false);
        $('#description').prop('required', false);
        $('#category').prop('required', false);


        /*
        |--------------------------------------------------------------------------
        | CUSTOMER TRAINING
        |--------------------------------------------------------------------------
        */
        if (trainingType === 'customer') {

            $('#title_field').show();
            $('#description_field').show();
            $('#video_url_field').show();
            $('#video_file_field').show();

            $('#title').prop('required', true);
            $('#description').prop('required', true);

        }


        /*
        |--------------------------------------------------------------------------
        | PRODUCT TRAINING
        |--------------------------------------------------------------------------
        */
        else if (trainingType === 'product') {

            $('#productTrainingFields').show();

            $('#image_field').show();
            $('#audio_field').show();
            $('#audio_record_field').hide();

            // Title, Description, Video URL and Category hidden
            $('#title_field').hide();
            $('#description_field').hide();
            $('#video_url_field').hide();
            $('#video_file_field').hide();
            $('#category_field').hide();

        }


        /*
        |--------------------------------------------------------------------------
        | DISPLAY TRAINING
        |--------------------------------------------------------------------------
        */
        else if (trainingType === 'display') {

            $('#image_field').show();
            $('#audio_field').show();
            $('#audio_record_field').show();

            $('#title_field').show();
            $('#description_field').show();
            $('#category_field').show();

            // Video URL hidden
            $('#video_url_field').hide();
            $('#video_file_field').hide();

            $('#title').prop('required', true);
            $('#description').prop('required', true);
            $('#category').prop('required', true);

        }
    }


    /*
    |--------------------------------------------------------------------------
    | Training Type Change
    |--------------------------------------------------------------------------
    */
    $('#training_type').on('change', function () {

        let type = $(this).val();

        // Clear product fields when not product
        if (type !== 'product') {

            $('#product_name').val('');
            $('#product_code').val('');
            $('#price').val('');
            $('#product_category').val('');
            $('#product_sub_category').val('');
            $('#product_size').val('');
            $('#product_color').val('');
            $('#training_details').val('');

        }

        if (type !== 'display' && typeof window.discardVoiceRecording === 'function') {
            window.discardVoiceRecording();
        }

        toggleTrainingFields();
    });


    // Run when page loads
    toggleTrainingFields();


    /*
    |--------------------------------------------------------------------------
    | Edit Training
    |--------------------------------------------------------------------------
    */
    $(document).on('click', '.editVideo', function () {

        const type = $(this).data('training-type');

        let roles = $(this).attr('data-roles');

        roles = roles ? JSON.parse(roles) : [];

        $('#video_id').val($(this).data('id'));

        // Set training type first
        $('#training_type').val(type).trigger('change');

        // Set roles
        $('#roles').val(roles).trigger('change');

        // Category
        $('#category').val(
            $(this).data('category') || ''
        );

        // Customer / Display fields
        $('#title').val(
            $(this).data('title') || ''
        );

        $('#video_url').val(
            $(this).data('video') || ''
        );
        $('#video').val('');
        if (typeof window.discardVoiceRecording === 'function') {
            window.discardVoiceRecording();
        }

        $('#description').val(
            $(this).data('description') || ''
        );

        // Product fields
        $('#product_name').val(
            $(this).data('product-name') || ''
        );

        $('#product_code').val(
            $(this).data('product-code') || ''
        );

        $('#price').val(
            $(this).data('price') || ''
        );

        $('#product_category').val(
            $(this).data('product-category') || ''
        );

        $('#product_sub_category').val(
            $(this).data('product-sub-category') || ''
        );

        $('#product_size').val(
            $(this).data('product-size') || ''
        );

        $('#product_color').val(
            $(this).data('product-color') || ''
        );

        $('#product_status').val(
            $(this).data('product-status') || 'new'
        );

        $('#training_details').val(
            $(this).data('training-details') || ''
        );

        // Scroll to form
        $('html, body').animate({
            scrollTop: 0
        }, 300);

    });


    /*
    |--------------------------------------------------------------------------
    | Delete Confirmation
    |--------------------------------------------------------------------------
    */
    $(document).on('click', '.show_confirm', function (event) {

                event.preventDefault();

        let formId = $(this).data('form');
        let form = document.getElementById(formId);

                Swal.fire({
                    title: 'Are you sure you want to delete this record?',
            text: 'If you delete this Training Module record, it will be gone forever.',
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
                                _token: '<?php echo e(csrf_token()); ?>'
                            },

                    success: function () {

                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: 'Record deleted successfully.',
                                    showConfirmButton: false,
                                    timer: 2000
                        }).then(() => {

                            location.reload();

                        });

                    },

                    error: function () {

                        Swal.fire(
                            'Error!',
                            'Failed to delete the record.',
                            'error'
                        );

                    }

                });

        }

    });

});


    /*
    |--------------------------------------------------------------------------
    | Training Type Tabs
    |--------------------------------------------------------------------------
    */
    function initVisibleDataTable() {

        $('.training-type-table:visible .training-datatable').each(function () {

            if (!$.fn.DataTable.isDataTable(this)) {

                if ($(this).find('tbody tr td[colspan]').length === 0) {

                    $(this).DataTable({
                        pageLength: 10
                    });

                }

            }

        });

    }

    initVisibleDataTable();


   $('.training-type-filter').on('click', function () {

    const type = $(this).data('type');

    // Only change active styling
    $('.training-type-filter').removeClass('active');

    $(this).addClass('active');

    // Hide all tables
    $('.training-type-table').hide();

    // Show selected table
    $('#table-' + type).show();

    // Initialize DataTable for visible table
    initVisibleDataTable();
});

});
</script>
<script src="<?php echo e(asset('public/admin/assets/js/page/training-voice-recorder.js')); ?>"></script>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('admin.layout.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\limelight-sales-perfomance\resources\views/admin/trainingvideo/index.blade.php ENDPATH**/ ?>