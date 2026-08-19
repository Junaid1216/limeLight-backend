<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\AreaSaleManager;
use App\Models\BranchManager;
use App\Models\SaleStaff;
use App\Models\TrainingVideo;
use App\Models\TrainingVideoCompletion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TrainingVideoController extends Controller
{
    public function getTrainingVideos(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ResponseHelper::error(null, 'User not authenticated', 'unauthorized', 401);
            }

            $identity = $this->resolveUserIdentity($user);
            if (!$identity['role']) {
                return ResponseHelper::error(null, 'User role could not be determined', 'forbidden', 403);
            }

            try {
                $request->validate([
                    'status' => 'required|string|in:new,pending,completed',
                ]);
            } catch (ValidationException $e) {
                return ResponseHelper::error($e->errors(), 'Validation failed', 'validation_error', 422);
            }

            $status = strtolower($request->get('status'));
            $role = $identity['role'];
            $today = $this->trainingToday();
            [$todayStartUtc, $todayEndUtc] = $this->trainingTodayUtcBounds();

            $query = TrainingVideo::whereJsonContains('roles', $role)
                ->where('training_type', 'customer');

            if ($status === 'new') {
                $query->whereBetween('created_at', [$todayStartUtc, $todayEndUtc]);
            } elseif ($status === 'pending') {
                // Older incomplete only (today's go under "new")
                $query->where('created_at', '<', $todayStartUtc);
            }

            $videos = $query->latest()->get();

            // Ensure assigned videos have default pending status for this user
            $this->ensurePendingStatuses($videos, $identity);

            $completions = TrainingVideoCompletion::where('user_type', $identity['user_type'])
                ->where('user_id', $identity['user_id'])
                ->whereIn('training_video_id', $videos->pluck('id'))
                ->get()
                ->keyBy('training_video_id');

            $data = $videos->map(function (TrainingVideo $video) use ($completions) {
                $completion = $completions->get($video->id);
                $userStatus = $this->normalizeUserStatus($completion);

                return [
                    'id' => $video->id,
                    'title' => $video->title,
                    'description' => $video->description,
                    'video_url' => $video->video_url,
                    'training_type' => $video->training_type,
                    'status' => $userStatus,
                    'completed_at' => $completion && $userStatus === 'completed'
                        ? optional($completion->completed_at)->toDateTimeString()
                        : null,
                    'created_at' => optional($video->created_at)->toDateTimeString(),
                ];
            })->filter(function (array $row) use ($status, $today) {
                return $this->matchesStatusFilter($row, $status, $today);
            })->values();

            return ResponseHelper::success(
                $data,
                'Training videos retrieved successfully',
                '200',
                200
            );
        } catch (\Exception $e) {
            return ResponseHelper::error(
                $e->getMessage(),
                'An error occurred while retrieving training videos',
                'error',
                500
            );
        }
    }

     public function trainingProduct(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ResponseHelper::error(null, 'User not authenticated', 'unauthorized', 401);
            }

            $identity = $this->resolveUserIdentity($user);
            if (!$identity['role']) {
                return ResponseHelper::error(null, 'User role could not be determined', 'forbidden', 403);
            }

            try {
                $request->validate([
                    'status' => 'required|string|in:new,pending,completed',
                ]);
            } catch (ValidationException $e) {
                return ResponseHelper::error($e->errors(), 'Validation failed', 'validation_error', 422);
            }

            $status = strtolower($request->get('status'));
            $role = $identity['role'];
            $today = $this->trainingToday();
            [$todayStartUtc, $todayEndUtc] = $this->trainingTodayUtcBounds();

            $query = TrainingVideo::where('training_type', 'product')
                ->whereJsonContains('roles', $role);

            if ($status === 'new') {
                $query->whereBetween('created_at', [$todayStartUtc, $todayEndUtc]);
            } elseif ($status === 'pending') {
                $query->where('created_at', '<', $todayStartUtc);
            }

            $trainings = $query->latest()->get();
            $this->ensurePendingStatuses($trainings, $identity);

            $completions = TrainingVideoCompletion::where('user_type', $identity['user_type'])
                ->where('user_id', $identity['user_id'])
                ->whereIn('training_video_id', $trainings->pluck('id'))
                ->get()
                ->keyBy('training_video_id');

            $data = $trainings->map(function ($training) use ($completions) {
                $completion = $completions->get($training->id);
                $userStatus = $this->normalizeUserStatus($completion);

                return [
                    'id' => $training->id,
                    'training_type' => $training->training_type,
                    'product_name' => $training->product_name,
                    'product_code' => $training->product_code,
                    'product_category' => $training->product_category,
                    'product_sub_category' => $training->product_sub_category,
                    'color' => $training->product_color,
                    'price' => $training->price,
                    'training_details' => $training->training_details
                        ? preg_replace('/\s+/', ' ', trim($training->training_details))
                        : null,
                    'image' => $training->image ? asset($training->image) : null,
                    'audio' => $training->audio ? asset($training->audio) : null,
                    'status' => $userStatus,
                    'completed_at' => $completion && $userStatus === 'completed'
                        ? optional($completion->completed_at)->toDateTimeString()
                        : null,
                    'created_at' => optional($training->created_at)->toDateTimeString(),
                ];
            })->filter(function (array $row) use ($status, $today) {
                return $this->matchesStatusFilter($row, $status, $today);
            })->values();

            return ResponseHelper::success($data, 'Product training retrieved successfully', '200', 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 'Something went wrong', 'error', 500);
        }
    }

    public function trainingDisplay(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ResponseHelper::error(null, 'User not authenticated', 'unauthorized', 401);
            }

            $identity = $this->resolveUserIdentity($user);
            if (!$identity['role']) {
                return ResponseHelper::error(null, 'User role could not be determined', 'forbidden', 403);
            }

            try {
                $request->validate([
                    'category' => 'required|string',
                    'status' => 'required|string|in:new,pending,completed',
                ]);
            } catch (ValidationException $e) {
                return ResponseHelper::error($e->errors(), 'Validation failed', 'validation_error', 422);
            }

            $status = strtolower($request->get('status'));
            $categoryInput = trim((string) $request->get('category'));
            $categorySlug = \Illuminate\Support\Str::slug($categoryInput);

            // Accept slug or name (e.g. unstitched / Unstitched)
            $category = \App\Models\DisplayCategory::query()
                ->where(function ($q) use ($categoryInput, $categorySlug) {
                    $q->where('slug', $categorySlug)
                        ->orWhere('slug', strtolower($categoryInput))
                        ->orWhereRaw('LOWER(name) = ?', [strtolower($categoryInput)]);
                })
                ->first();

            if (!$category) {
                return ResponseHelper::error(null, 'Display category not found', 'not_found', 404);
            }

            $role = $identity['role'];
            $today = $this->trainingToday();
            [$todayStartUtc, $todayEndUtc] = $this->trainingTodayUtcBounds();

            $query = TrainingVideo::where('training_type', 'display')
                ->whereJsonContains('roles', $role)
                ->where('category', $category->slug);

            if ($status === 'new') {
                $query->whereBetween('created_at', [$todayStartUtc, $todayEndUtc]);
            } elseif ($status === 'pending') {
                $query->where('created_at', '<', $todayStartUtc);
            }

            $trainings = $query->latest()->get();
            $this->ensurePendingStatuses($trainings, $identity);

            $completions = TrainingVideoCompletion::where('user_type', $identity['user_type'])
                ->where('user_id', $identity['user_id'])
                ->whereIn('training_video_id', $trainings->pluck('id'))
                ->get()
                ->keyBy('training_video_id');

            $data = $trainings->map(function ($training) use ($completions, $category) {
                $completion = $completions->get($training->id);
                $userStatus = $this->normalizeUserStatus($completion);

                return [
                    'id' => $training->id,
                    'training_type' => $training->training_type,
                    'category' => $training->category,
                    'category_name' => $category->name,
                    'title' => $training->title,
                    'description' => $training->description,
                    'image' => $training->image ? asset($training->image) : null,
                    'audio' => $training->audio ? asset($training->audio) : null,
                    'status' => $userStatus,
                    'completed_at' => $completion && $userStatus === 'completed'
                        ? optional($completion->completed_at)->toDateTimeString()
                        : null,
                    'created_at' => optional($training->created_at)->toDateTimeString(),
                ];
            })->filter(function (array $row) use ($status, $today) {
                return $this->matchesStatusFilter($row, $status, $today);
            })->values();

            return ResponseHelper::success($data, 'Display training retrieved successfully', '200', 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 'Something went wrong', 'error', 500);
        }
    }

    /**
     * Mark a training module video as complete for the logged-in user (any role).
     * POST /api/training-videos/{id}/status
     * Body: { "status": "completed" }  (also accepts "complete")
     */
    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return ResponseHelper::error(null, 'User not authenticated', 'unauthorized', 401);
        }

        try {
            $request->validate([
                'status' => 'required|string|in:complete,completed',
            ]);
        } catch (ValidationException $e) {
            return ResponseHelper::error($e->errors(), 'Validation failed', 'validation_error', 422);
        }

        $identity = $this->resolveUserIdentity($user);
        if (!$identity['user_type'] || !$identity['role']) {
            return ResponseHelper::error(null, 'Unsupported user type', 'forbidden', 403);
        }

        $training = TrainingVideo::find($id);
        if (!$training) {
            return ResponseHelper::error(null, 'Training module not found', 'not_found', 404);
        }

        $roles = $training->roles ?? [];
        if (!is_array($roles)) {
            $roles = json_decode($roles, true) ?: [];
        }

        if (!in_array($identity['role'], $roles, true)) {
            return ResponseHelper::error(null, 'This training is not assigned to your role', 'forbidden', 403);
        }

        $completion = TrainingVideoCompletion::updateOrCreate(
            [
                'training_video_id' => $training->id,
                'user_type' => $identity['user_type'],
                'user_id' => $identity['user_id'],
            ],
            [
                'status' => 'completed',
                'completed_at' => now(),
            ]
        );

        return ResponseHelper::success([
            'training_video_id' => $training->id,
            'user_type' => $identity['user_type'],
            'user_id' => $identity['user_id'],
            'role' => $identity['role'],
            'status' => 'completed',
            'completed_at' => optional($completion->completed_at)->toDateTimeString(),
        ], 'Training status saved successfully', '200', 200);
    }

    /**
     * Create pending rows for assigned videos that this user has not tracked yet.
     */
    private function ensurePendingStatuses($videos, array $identity): void
    {
        if ($videos->isEmpty()) {
            return;
        }

        $existingIds = TrainingVideoCompletion::where('user_type', $identity['user_type'])
            ->where('user_id', $identity['user_id'])
            ->whereIn('training_video_id', $videos->pluck('id'))
            ->pluck('training_video_id')
            ->all();

        $now = now();
        $rows = [];

        foreach ($videos as $video) {
            if (in_array($video->id, $existingIds, true)) {
                continue;
            }

            $rows[] = [
                'training_video_id' => $video->id,
                'user_type' => $identity['user_type'],
                'user_id' => $identity['user_id'],
                'status' => 'pending',
                'completed_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($rows)) {
            TrainingVideoCompletion::insert($rows);
        }
    }

    private function normalizeUserStatus($completion): string
    {
        if (!$completion) {
            return 'pending';
        }

        $status = strtolower((string) $completion->status);
        if (in_array($status, ['complete', 'completed'], true)) {
            return 'completed';
        }

        return 'pending';
    }

    /**
     * Business "today" for training tabs (Pakistan).
     */
    private function trainingToday(): string
    {
        return Carbon::now('Asia/Karachi')->toDateString();
    }

    /**
     * UTC bounds for Pakistan "today" (for created_at queries).
     *
     * @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon}
     */
    private function trainingTodayUtcBounds(): array
    {
        $start = Carbon::now('Asia/Karachi')->startOfDay()->utc();
        $end = Carbon::now('Asia/Karachi')->endOfDay()->utc();

        return [$start, $end];
    }

    /**
     * new       = created today + not completed
     * pending   = older + not completed
     * completed = completed (any date)
     */
    private function matchesStatusFilter(array $row, string $status, string $today): bool
    {
        $createdDate = !empty($row['created_at'])
            ? Carbon::parse($row['created_at'], 'UTC')->timezone('Asia/Karachi')->toDateString()
            : null;

        $isToday = $createdDate === $today;
        $userStatus = $row['status'] ?? 'pending';

        if ($status === 'new') {
            return $isToday && $userStatus === 'pending';
        }

        if ($status === 'pending') {
            return !$isToday && $userStatus === 'pending';
        }

        if ($status === 'completed') {
            return $userStatus === 'completed';
        }

        return true;
    }

    private function resolveUserIdentity($user): array
    {
        if ($user instanceof SaleStaff) {
            return [
                'user_type' => 'sale_staff',
                'user_id' => $user->id,
                'role' => 'sales_staff',
            ];
        }

        if ($user instanceof BranchManager) {
            return [
                'user_type' => 'branch_manager',
                'user_id' => $user->id,
                'role' => 'branch_manager',
            ];
        }

        if ($user instanceof AreaSaleManager) {
            return [
                'user_type' => 'area_sale_manager',
                'user_id' => $user->id,
                'role' => 'asm',
            ];
        }

        // Fallback: resolve by employee_id like existing training APIs
        if (!empty($user->employee_id)) {
            if (AreaSaleManager::where('employee_id', $user->employee_id)->exists()) {
                $asm = AreaSaleManager::where('employee_id', $user->employee_id)->first();
                return [
                    'user_type' => 'area_sale_manager',
                    'user_id' => $asm->id,
                    'role' => 'asm',
                ];
            }
            if (BranchManager::where('employee_id', $user->employee_id)->exists()) {
                $bm = BranchManager::where('employee_id', $user->employee_id)->first();
                return [
                    'user_type' => 'branch_manager',
                    'user_id' => $bm->id,
                    'role' => 'branch_manager',
                ];
            }
            if (SaleStaff::where('employee_id', $user->employee_id)->exists()) {
                $staff = SaleStaff::where('employee_id', $user->employee_id)->first();
                return [
                    'user_type' => 'sale_staff',
                    'user_id' => $staff->id,
                    'role' => 'sales_staff',
                ];
            }
        }

        return [
            'user_type' => null,
            'user_id' => null,
            'role' => null,
        ];
    }
}
