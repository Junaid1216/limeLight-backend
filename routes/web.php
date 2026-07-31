<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\AssignedTargetController;
use App\Http\Controllers\Admin\ASMController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\BranchManagerController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\CommissionController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\DesignationController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\HierarchyController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\RegionController;
use App\Http\Controllers\Admin\PeerBranchConversionController;
use App\Http\Controllers\Admin\ReportingController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\SalesHistoryController;
use App\Http\Controllers\Admin\SaleStaffController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\SeoController;
use App\Http\Controllers\Admin\SlabController;
use App\Http\Controllers\Admin\SubAdminController;
use App\Http\Controllers\Admin\SurveyController;
use App\Http\Controllers\Admin\TargetController;
use App\Http\Controllers\Admin\TestingVideoController;
use App\Http\Controllers\Admin\TrainingVideoController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebController;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SideMenue;
use App\Models\SideMenuHasPermission;
use App\Models\UserRolePermission;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
/*Admin routes
 * */

Route::get('/optimize-project', function () {

    Artisan::call('optimize:clear');
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('config:cache');
    Artisan::call('route:cache');
    Artisan::call('view:cache');
    Artisan::call('optimize');

    return 'Optimization Commands Executed Successfully';

});

Route::get('/admin', [AuthController::class, 'getLoginPage']);
Route::post('/login', [AuthController::class, 'Login']);
Route::get('/admin-forgot-password', [AdminController::class, 'forgetPassword']);
Route::post('/admin-reset-password-link', [AdminController::class, 'adminResetPasswordLink']);
Route::get('/change_password/{id}', [AdminController::class, 'change_password']);
Route::post('/admin-reset-password', [AdminController::class, 'ResetPassword']);

Route::prefix('admin')->middleware(['admin', 'check.subadmin.status'])->group(function () {
    Route::get('dashboard', [AdminController::class, 'getdashboard'])->name('admin.dashboard');
    Route::get('profile', [AdminController::class, 'getProfile']);
    Route::post('update-profile', [AdminController::class, 'update_profile']);

    // ############ Privacy-policy #################
    Route::get('privacy-policy', [SecurityController::class, 'PrivacyPolicy'])->middleware('check.permission:Privacy & Policy,view');
    Route::get('privacy-policy-edit', [SecurityController::class, 'PrivacyPolicyEdit'])->middleware('check.permission:Privacy & Policy,edit');
    Route::post('privacy-policy-update', [SecurityController::class, 'PrivacyPolicyUpdate']) ->middleware('check.permission:Privacy & Policy,edit');
    Route::get('privacy-policy-view', [SecurityController::class, 'PrivacyPolicyView']) ->middleware('check.permission:Privacy & Policy,view');

    // ############ Role Permissions #################

    // Route::get('roles-permission', [RolePermissionController::class, 'index'])->name('role-permission')->middleware('check.permission:role,view');



            // ############ Roles #################

        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index')->middleware('check.permission:Roles,view');

        Route::get('/roles-create', [RoleController::class, 'create'])->name('create.role')->middleware('check.permission:Roles,create');

        Route::post('/store-role', [RoleController::class, 'store'])->name('store.role')->middleware('check.permission:Roles,create');


        Route::get('/roles-permissions/{id}', [RoleController::class, 'permissions'])->name('role.permissions')->middleware('check.permission:Roles,edit');


        //////////////////////////////////////////
        Route::post('/admin/roles/{id}/permissions/store', [RoleController::class, 'storePermissions'])->name('roles.permissions.store')->middleware('check.permission:role,create');


        Route::delete('/delete-role/{id}', [RoleController::class, 'delete'])->name('delete.role')->middleware('check.permission:role,delete');

    

    // ############ Term & Condition #################
    Route::get('term-condition', [SecurityController::class, 'TermCondition']) ->middleware('check.permission:Terms & Conditions,view');
    Route::get('term-condition-edit', [SecurityController::class, 'TermConditionEdit']) ->middleware('check.permission:Terms & Conditions,edit');
    Route::post('term-condition-update', [SecurityController::class, 'TermConditionUpdate']) ->middleware('check.permission:Terms & Conditions
,edit');
    Route::get('term-condition-view', [SecurityController::class, 'TermConditionView']) ->middleware('check.permission:Terms & Conditions
,view');

    // ############ About Us #################
    Route::get('about-us', [SecurityController::class, 'AboutUs']) ->middleware('check.permission:About us,view');
    Route::get('about-us-edit', [SecurityController::class, 'AboutUsEdit']) ->middleware('check.permission:About us,edit');
    Route::post('about-us-update', [SecurityController::class, 'AboutUsUpdate']) ->middleware('check.permission:About us,edit');
    Route::get('about-us-view', [SecurityController::class, 'AboutUsView']) ->middleware('check.permission:About us,view');

    Route::get('logout', [AdminController::class, 'logout']);

        // ############ Faq #################
    Route::get('faq', [FaqController::class, 'Faq'])->middleware('check.permission:Faqs,view');
    Route::get('faq-edit/{id}', [FaqController::class, 'FaqsEdit'])->name('faq.edit') ->middleware('check.permission:Faqs,edit');
    Route::post('faq-update/{id}', [FaqController::class, 'FaqsUpdate'])->middleware('check.permission:Faqs,edit');
    Route::get('faq-view', [FaqController::class, 'FaqView']) ->middleware('check.permission:Faqs,view');
    Route::get('faq-create', [FaqController::class, 'Faqscreateview']) ->middleware('check.permission:Faqs,create');
    Route::post('faq-store', [FaqController::class, 'Faqsstore']) ->middleware('check.permission:Faqs,create');
      Route::delete('faq-destroy/{id}', [FaqController::class, 'faqdelete'])->name('faq.destroy');
    Route::post('/faqs/reorder', [FaqController::class, 'reorder'])->name('faq.reorder');

    // ############ Users #################

    Route::get('/user', [UserController::class, 'Index'])->name('user.index') ->middleware('check.permission:Users,view');
Route::get('/user-create', [UserController::class, 'createview'])->name('user.createview') ->middleware('check.permission:Users,create');
Route::post('/user-store', [UserController::class, 'create'])->name('user.create') ->middleware('check.permission:Users,create');
Route::get('/user-edit/{id}', [UserController::class, 'edit'])->name('user.edit') ->middleware('check.permission:Users,edit');
Route::post('/user-update/{id}', [UserController::class, 'update'])->name('user.update') ->middleware('check.permission:Users,edit');
Route::delete('/users-destory/{id}', [UserController::class, 'delete'])->name('user.delete') ->middleware('check.permission:Users,delete');
// Route::get('/users/trashed', [UserController::class, 'trashed']);
// Route::post('/users/{id}/restore', [UserController::class, 'restore']);
Route::delete('/users/{id}/force', [UserController::class, 'forceDelete'])->name('user.forceDelete') ->middleware('check.permission:Users,delete');

Route::post('/users/toggle-status', [UserController::class, 'toggleStatus'])->name('user.toggle-status');


// ############ Regions #################
Route::get('/region', [RegionController::class, 'index'])->name('region.index')->middleware('check.permission:Regions,view');
Route::get('/region-create', [RegionController::class, 'create'])->name('region.create')->middleware('check.permission:Regions,create');
Route::post('/region-store', [RegionController::class, 'store'])->name('region.store')->middleware('check.permission:Regions,create');
Route::get('/region-edit/{id}', [RegionController::class, 'edit'])->name('region.edit') ->middleware('check.permission:Regions,edit');
Route::post('/region-update/{id}', [RegionController::class, 'update'])->name('region.update') ->middleware('check.permission:Regions,edit');
Route::delete('/region-destroy/{id}', [RegionController::class, 'delete'])->name('region.delete') ->middleware('check.permission:Regions,delete');

// ############ Branches ##############
Route::get('/branch', [BranchController::class, 'index'])->name('branch.index')->middleware('check.permission:Branches,view');
Route::get('/branch-create', [BranchController::class, 'create'])->name('branch.create')->middleware('check.permission:Branches,create');
Route::post('/branch-store', [BranchController::class, 'store'])->name('branch.store')->middleware('check.permission:Branches,create');
Route::get('/branch-edit/{id}', [BranchController::class, 'edit'])->name('branch.edit')->middleware('check.permission:Branches,edit');
Route::post('/branch-update/{id}', [BranchController::class, 'update'])->name('branch.update')->middleware('check.permission:Branches,edit');
Route::delete('/branch-destroy/{id}', [BranchController::class, 'delete'])->name('branch.delete')->middleware('check.permission:Branches,delete');

// ############## ASM ##############
Route::get('/asm', [ASMController::class, 'index'])->name('asm.index')->middleware('check.permission:Area Sale Managers,view');
Route::get('/asm-create', [ASMController::class, 'create'])->name('asm.create')->middleware('check.permission:Area Sale Managers,create');
Route::post('/asm-store', [ASMController::class, 'store'])->name('asm.store')->middleware('check.permission:Area Sale Managers,create');
Route::get('/asm-edit/{id}', [ASMController::class, 'edit'])->name('asm.edit')->middleware('check.permission:Area Sale Managers,edit');
Route::post('/asm-update/{id}', [ASMController::class, 'update'])->name('asm.update')->middleware('check.permission:Area Sale Managers,edit');
Route::delete('/asm-destroy/{id}', [ASMController::class, 'delete'])->name('asm.delete')->middleware('check.permission:Area Sale Managers,delete');

// ############ Branch Manager ##############
Route::get('/branch-manager', [BranchManagerController::class, 'index'])->name('branch.manager.index')->middleware('check.permission:Branch Managers,view');
Route::get('/branch-manager-create', [BranchManagerController::class, 'create'])->name('branch.manager.create')->middleware('check.permission:Branch Managers,create');
Route::post('/branch-manager-store', [BranchManagerController::class, 'store'])->name('branch.manager.store')->middleware('check.permission:Branch Managers,create');
Route::get('/branch-manager-edit/{id}', [BranchManagerController::class, 'edit'])->name('branch.manager.edit')->middleware('check.permission:Branch Managers,edit');
Route::post('/branch-manager-update/{id}', [BranchManagerController::class, 'update'])->name('branch.manager.update')->middleware('check.permission:Branch Managers,edit');
Route::delete('/branch-manager-destroy/{id}', [BranchManagerController::class, 'delete'])->name('branch.manager.delete')->middleware('check.permission:Branch Managers,delete');

// ############ Sale Staff ##############
Route::get('/sale-staff', [SaleStaffController::class, 'index'])->name('sale.staff.index')->middleware('check.permission:Sale Staff,view');
Route::get('/sale-staff-create', [SaleStaffController::class, 'create'])->name('sale.staff.create')->middleware('check.permission:Sale Staff,create');
Route::post('/sale-staff-store', [SaleStaffController::class, 'store'])->name('sale.staff.store')->middleware('check.permission:Sale Staff,create');
Route::get('/sale-staff-edit/{id}', [SaleStaffController::class, 'edit'])->name('sale.staff.edit')->middleware('check.permission:Sale Staff,edit');
Route::post('/sale-staff-update/{id}', [SaleStaffController::class, 'update'])->name('sale.staff.update')->middleware('check.permission:Sale Staff,edit');
Route::delete('/sale-staff-destroy/{id}', [SaleStaffController::class, 'destroy'])->name('sale.staff.delete')->middleware('check.permission:Sale Staff,delete');

// ############ Category ################
Route::get('/category',[CategoryController::class, 'index'])->name('category.index')->middleware('check.permission:Line Items,view');
Route::post('line-item/{id}/update', [CategoryController::class, 'update']) ->name('lineitem.update');

// ############ Hierarchy ################
Route::get('/hierarchy', [HierarchyController::class, 'index'])->name('hierarchy.index')->middleware('check.permission:Hierarchy,view');
Route::post('/hierarchy-store', [HierarchyController::class, 'store'])->name('hierarchy.store')->middleware('check.permission:Hierarchy,edit');
Route::post('/hierarchy/remove-asm', [HierarchyController::class, 'removeAsm'])->name('hierarchy.removeAsm')->middleware('check.permission:Hierarchy,edit');
Route::get('/get-region-asms/{id}', [HierarchyController::class, 'getRegionAsms']);
Route::get('/get-branch-managers/{id}', [HierarchyController::class, 'getBranchManagers']);
Route::get('/get-region-branches/{regionId}', [HierarchyController::class, 'getRegionBranches']);

// ############ Target ################
Route::get('/target', [TargetController::class, 'index'])->name('target.index')->middleware('check.permission:Targets,view');
Route::get('/target/create', [TargetController::class, 'create'])->name('target.create')->middleware('check.permission:Targets,create');
Route::post('/target/store', [TargetController::class, 'store'])->name('target.store')->middleware('check.permission:Targets,create');
Route::get('/target/edit/{id}', [TargetController::class, 'edit'])->name('target.edit')->middleware('check.permission:Targets,edit');
Route::post('/target/update/{id}', [TargetController::class, 'update'])->name('target.update')->middleware('check.permission:Targets,edit');
Route::post('/toggle-target-status', [TargetController::class, 'toggleStatus'])->name('target.toggleStatus');
Route::get('/get-branch-designations/{branchId}', [TargetController::class, 'getBranchDesignations']);

// ############ Commission ################
Route::get('/commission', [CommissionController::class, 'index'])->name('commission.index')->middleware('check.permission:Commissions,view');
Route::post('/commission-store', [CommissionController::class, 'store'])->name('commission.store')->middleware('check.permission:Commissions,create');
Route::delete('/commission-delete/{id}', [CommissionController::class, 'delete'])->name('commission.delete')->middleware('check.permission:Commissions,delete');

// ############ Slip Bound Incentives ################
Route::get('/slab', [SlabController::class, 'index'])->name('slab.index')->middleware('check.permission:Slip Bound Incentives,view');
Route::post('/slab-store', [SlabController::class, 'store'])->name('slip.incentive.store')->middleware('check.permission:Slip Bound Incentives,edit');
Route::post('/slab-delete/{id}', [SlabController::class, 'delete'])->name('slip.incentive.delete')->middleware('check.permission:Slip Bound Incentives,edit');

// ############ Training Video #################
Route::get('/training_module',[TrainingVideoController::class, 'index'])->name('training.video.index')->middleware('check.permission:Training Modules,view');
Route::post('/training_module-store',[TrainingVideoController::class, 'store'])->name('training.video.store')->middleware('check.permission:Training Modules,create');
Route::post('/training_module-update/{id}',[TrainingVideoController::class, 'update'])->name('training.video.update')->middleware('check.permission:Training Modules,edit');
Route::delete('/training_module-destroy/{id}',[TrainingVideoController::class, 'delete'])->name('training.video.delete')->middleware('check.permission:Training Modules,delete');

// ############ Survey ##################
Route::get('/surveys', [SurveyController::class, 'index'])->name('survey.index')->middleware('check.permission:Surveys,view');
Route::post('/surveys/store', [SurveyController::class, 'store'])->name('survey.store')->middleware('check.permission:Surveys,create');
Route::post('/surveys/update/{id}', [SurveyController::class, 'update'])->name('survey.update')->middleware('check.permission:Surveys,edit');
Route::delete('/surveys/destroy/{id}', [SurveyController::class, 'delete'])->name('survey.destroy')->middleware('check.permission:Surveys,delete');


// ############ Reporting #################
Route::get('/reporting', [ReportingController::class, 'index'])->name('reporting.index')->middleware('check.permission:Reportings,view');

// ############ Sales History #################
Route::get('/sales-history', [SalesHistoryController::class, 'index'])->name('sales.history.index')->middleware('check.permission:Sales History,view');

// ############ Peer Branch Conversion #################
Route::get('/peer-branch-conversion', [PeerBranchConversionController::class, 'index'])->name('peer.branch.conversion.index')->middleware('check.permission:Peer Branch Conversion,view');

// ############ Announcements #################
Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcement.index')->middleware('check.permission:Announcements,view');
Route::post('/announcements/store', [AnnouncementController::class, 'store'])->name('announcement.store')->middleware('check.permission:Announcements,create');
Route::post('/announcements/update/{id}', [AnnouncementController::class, 'update'])->name('announcement.update')->middleware('check.permission:Announcements,edit');
Route::delete('/announcements/destroy/{id}', [AnnouncementController::class, 'delete'])->name('announcement.destroy')->middleware('check.permission:Announcements,delete');

// ############ Assigned Targets #################
Route::get('/assigned-targets', [AssignedTargetController::class, 'index'])->name('assigned.target.index')->middleware('check.permission:Assigned Targets,view');
Route::post('/assigned-targets/approve', [AssignedTargetController::class, 'approve'])->name('assigned.target.approve')->middleware('check.permission:Assigned Targets,edit');

    // ############ Sub Admin #################
    Route::controller(SubAdminController::class)->group(function () {
        Route::get('/subadmin',  'index')->name('subadmin.index') ->middleware('check.permission:Sub Admins,view');
        Route::get('/subadmin-create',  'create')->name('subadmin.create') ->middleware('check.permission:Sub Admins,create');
        Route::post('/subadmin-store',  'store')->name('subadmin.store') ->middleware('check.permission:Sub Admins,create');
        Route::get('/subadmin-edit/{id}',  'edit')->name('subadmin.edit') ->middleware('check.permission:Sub Admins,edit');
        Route::post('/subadmin-update/{id}',  'update')->name('subadmin.update') ->middleware('check.permission:Sub Admins,edit');
        Route::delete('/subadmin-destroy/{id}',  'destroy')->name('subadmin.destroy') ->middleware('check.permission:Sub Admins,delete');

        Route::post('/update-permissions/{id}', 'updatePermissions')->name('update.permissions');

        Route::post('/subadmin-StatusChange', 'StatusChange')->name('subadmin.StatusChange')->middleware('check.permission:Sub Admins,edit');

        Route::post('/admin/subadmin/toggle-status', [SubAdminController::class, 'toggleStatus'])->name('admin.subadmin.toggleStatus');

    });

    // ############ Cities #############
    Route::get('/cities',[CityController::class, 'index'])->name('city.index')->middleware('check.permission:Cities,view');
    Route::get('/cities-create',[CityController::class, 'create'])->name('city.create')->middleware('check.permission:Cities,create');
    Route::post('/cities-store',[CityController::class, 'store'])->name('city.store')->middleware('check.permission:Cities,create');
    Route::get('/cities-edit/{id}',[CityController::class, 'edit'])->name('city.edit')->middleware('check.permission:Cities,edit');
    Route::post('/cities-update/{id}',[CityController::class, 'update'])->name('city.update')->middleware('check.permission:Cities,edit');
    Route::delete('/cities-destroy/{id}',[CityController::class, 'delete'])->name('city.delete')->middleware('check.permission:Cities,delete');

    // ############ Designations #################
    Route::get('/designations', [DesignationController::class, 'index'])->name('designation.index')->middleware('check.permission:Designations,view');
    Route::get('/designations-create', [DesignationController::class, 'create'])->name('designation.create')->middleware('check.permission:Designations,create');
    Route::post('/designations-store', [DesignationController::class, 'store'])->name('designation.store')->middleware('check.permission:Designations,create');
    Route::get('/designations-edit/{id}', [DesignationController::class, 'edit'])->name('designation.edit')->middleware('check.permission:Designations,edit');
    Route::post('/designations-update/{id}', [DesignationController::class, 'update'])->name('designation.update')->middleware('check.permission:Designations,edit');
    Route::delete('/designations-destroy/{id}', [DesignationController::class, 'delete'])->name('designation.delete')->middleware('check.permission:Designations,delete');
            // ############ Blogs #################

    Route::get('/blogs-index', [BlogController::class, 'index'])->name('blog.index')->middleware('check.permission:Blogs,view');

    Route::get('/blogs-create', [BlogController::class, 'create'])->name('blog.createview')->middleware('check.permission:Blogs,create');

    Route::post('/blogs-store', [BlogController::class, 'store'])->name('blog.store')->middleware('check.permission:Blogs,create');

    Route::get('/blogs-edit/{id}', [BlogController::class, 'edit'])->name('blog.edit')->middleware('check.permission:Blogs,edit');
    Route::post('/blogs-update/{id}', [BlogController::class, 'update'])->name('blog.update')->middleware('check.permission:Blogs,edit');
    Route::delete('/blogs-destroy/{id}', [BlogController::class, 'delete'])->name('blog.destroy')->middleware('check.permission:Blogs,delete');

    Route::post('/blogs/toggle-status', [BlogController::class, 'toggleStatus'])->name('blog.toggle-status');
Route::post('/blogs/reorder', [BlogController::class, 'reorder'])->name('blog.reorder');


 // ############ Notifications #################

    Route::controller(NotificationController::class)->group(function () {

        Route::get('/notification',  'index')->name('notification.index') ->middleware('check.permission:Notifications,view');

        Route::post('/notification-store',  'store')->name('notification.store')->middleware('check.permission:Notifications,create');

        Route::delete('/notification-destroy/{id}',  'destroy')->name('notification.destroy') ->middleware('check.permission:Notifications,delete');
        Route::delete('/notifications/delete-all', 'deleteAll')->name('notifications.deleteAll');
        Route::get('/get-users-by-type', 'getUsersByType');

        Route::get('/admin-notification/{id}', 'read')->name('admin.notifications.read');

    });

    // ############ Seo Routes #################

     Route::get('/seo', [SeoController::class, 'index'])->name('seo.index');
    Route::get('/seo/{id}/edit', [SeoController::class, 'edit'])->name('seo.edit');
    Route::post('/seo/{id}', [SeoController::class, 'update'])->name('seo.update');
    Route::get('/admin/seo/page/{id}', [SeoController::class, 'getPage'])->name('seo.page');


    // ############ Web Routes #################

         Route::get('/home-page', [WebController::class, 'homepage'])->name('web.homepage');
         Route::get('/about-page', [WebController::class, 'aboutpage'])->name('web.aboutpage');
         Route::get('/contact-page', [WebController::class, 'contactpage'])->name('web.contactpage');




    // ############ Contact Us #################
Route::get('/admin/contact-us', [ContactController::class, 'index'])->name('contact.index') ->middleware('check.permission:Contact us,view');
Route::get('/admin/contact-us-create', [ContactController::class, 'create'])->name('contact.create') ->middleware('check.permission:Contact us,create');
Route::post('/admin/contact-us-store', [ContactController::class, 'store'])->name('contact.store') ->middleware('check.permission:Contact us,create');
Route::get('/admin/contact-us-edit/{id}', [ContactController::class, 'updateview'])->name('contact.updateview') ->middleware('check.permission:Contact us,edit');
Route::post('/admin/contact-us-update/{id}', [ContactController::class, 'update'])->name('contact.update') ;



});
