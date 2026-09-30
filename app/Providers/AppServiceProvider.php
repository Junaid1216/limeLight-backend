<?php

namespace App\Providers;

use App\Models\FeedBack;
use App\Models\SubAdmin;
use App\Models\UserRolePermission;
use App\Repositories\Api\AuthRepository;
use App\Repositories\Api\Interfaces\AuthRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
  

public function boot()
{
    View::composer('*', function ($view) {
        $sideMenuPermissions = collect();

        if (Auth::guard('subadmin')->check()) {
            $user = Auth::guard('subadmin')->user();

            // Load roles from pivot
            $role = $user->roles()->first(); // assumes 1 role per subadmin

            if ($role) {
                $roleId = $role->id;

                $sideMenuPermissions = UserRolePermission::with(['permission', 'sideMenue'])
                    ->where('role_id', $roleId)
                    ->get()
                    ->groupBy(function ($item) {
                        return $item->sideMenue->name ?? 'undefined';
                    })
                    ->map(function ($items) {
                        return $items->pluck('permission.name');
                    });
            }
        }

        $view->with('sideMenuPermissions', $sideMenuPermissions);

        $feedbackUnreadCount = 0;
        if (
            (Auth::guard('admin')->check() || Auth::guard('subadmin')->check())
            && Schema::hasTable('feed_backs')
            && Schema::hasColumn('feed_backs', 'is_read')
        ) {
            $feedbackUnreadCount = FeedBack::where('is_read', 0)->count();
        }

        $view->with('feedbackUnreadCount', $feedbackUnreadCount);
    });
}




}
