<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Models\AreaSaleManager;
use App\Models\BranchManager;
use App\Models\DisplayCategory;
use App\Models\SaleStaff;
use App\Models\TrainingVideo;
use App\Models\TrainingVideoCompletion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TrainingVideoController extends Controller
{
    public function index()
    {
        $videos = TrainingVideo::all();
        $displayCategories = DisplayCategory::active()->orderBy('name')->get();

        return view('admin.trainingvideo.index', compact('videos', 'displayCategories'));
    }

    public function store(Request $request)
{
    $isNew = empty($request->id);

    $training = $request->id
            ? TrainingVideo::findOrFail($request->id)
            : new TrainingVideo();
    
      $image = $training->image;

     if ($request->hasFile('image')) {

            $file = $request->file('image');

            $filename = time().'.'.$file->getClientOriginalExtension();

            $file->move(public_path('admin/assets/images/users/'), $filename);

            $image = 'public/admin/assets/images/users/'.$filename;

        } else {

            $image = 'public/admin/assets/images/avator.png';

        }

     if ($request->training_type === 'product') {

            $training->training_type = $request->training_type;

            $training->roles = $request->roles;

            $training->product_code = $request->product_code;

            $training->product_name = $request->product_name;

            $training->product_category = $request->product_category;

            $training->product_sub_category = $request->product_sub_category;

            $training->product_size = $request->product_size;

            $training->product_color = $request->product_color;

            $training->price = $request->price;

            $training->training_details = $request->training_details;

            $training->image = $image;

            $training->audio = $this->resolveTrainingAudio($request, $training);

            $training->video_url = null;

            $training->category = null;

        }


        /*
        |--------------------------------------------------------------------------
        | Display Training
        |--------------------------------------------------------------------------
        */

        elseif ($request->training_type === 'display') {
            try {
                $request->validate([
                    'audio' => 'nullable|file|mimes:mp3,wav,ogg,m4a,webm,weba,aac|max:20480',
                    'audio_recording' => 'nullable|file|max:20480',
                ]);
            } catch (ValidationException $e) {
                return back()->withInput()->with('error', collect($e->errors())->flatten()->first());
            }

            $this->ensureTrainingCategoryColumn();

            $categorySlug = $this->resolveDisplayCategorySlug(
                $request->input('display_category', $request->input('category'))
            );
            if (!$categorySlug) {
                return back()->withInput()->with('error', 'Please select a display category.');
            }

            $training->category = $categorySlug;

            $training->roles = $request->roles;

            $training->training_type = $request->training_type;

            $training->title = $request->title;

            $training->description = $request->description;

            $training->video_url = null;

            $training->image = $image;

            $training->audio = $this->resolveTrainingAudio($request, $training);

            // Clear product data
            $training->product_code = null;
            $training->product_name = null;
            $training->product_category = null;
            $training->product_sub_category = null;
            $training->product_size = null;
            $training->product_color = null;
            $training->price = null;

        }


        /*
        |--------------------------------------------------------------------------
        | Customer Training
        |--------------------------------------------------------------------------
        */

        elseif ($request->training_type === 'customer') {
            $request->validate([
                'video' => 'nullable|file|mimes:mp4,mov,avi,webm|max:51200',
                'video_url' => 'nullable|string|max:2048',
            ]);

            $training->roles = $request->roles;

            $training->training_type = $request->training_type;

            $training->title = $request->title;

            $training->description = $request->description;

            $training->video_url = $this->resolveCustomerVideoUrl($request, $training);

            $training->image = null;

            $training->audio = null;

            $training->category = null;

            // Clear product data
            $training->product_code = null;
            $training->product_name = null;
            $training->product_category = null;
            $training->product_sub_category = null;
            $training->product_size = null;
            $training->product_color = null;
            $training->price = null;

        }


        $training->save();

        if ($request->training_type === 'display' && !empty($training->category)) {
            $this->ensureTrainingCategoryColumn();
            DB::table('training_videos')->where('id', $training->id)->update([
                'category' => $training->category,
            ]);
        }

        $this->seedPendingStatusForRoles($training);

        if ($isNew) {
            $title = trim((string) ($training->title ?: $training->product_name ?: 'New training'));
            $body = trim((string) ($training->description ?: $training->training_details ?: $title));

            NotificationHelper::sendToRoles(
                (array) ($training->roles ?? []),
                $title,
                $body !== '' ? $body : $title,
                [
                    'type' => 'training module',
                    'source' => 'training',
                    'training_id' => (string) $training->id,
                    'training_type' => (string) ($training->training_type ?? ''),
                ]
            );
        }

        return back()->with('success','Training Module Saved Successfully');
}

/**
 * When a training is assigned to roles, default user status = pending.
 */
private function seedPendingStatusForRoles(TrainingVideo $training): void
{
    $roles = $training->roles ?? [];
    if (!is_array($roles) || empty($roles)) {
        return;
    }

    $users = [];

    if (in_array('sales_staff', $roles, true)) {
        foreach (SaleStaff::query()->select('id')->cursor() as $staff) {
            $users[] = ['user_type' => 'sale_staff', 'user_id' => $staff->id];
        }
    }

    if (in_array('branch_manager', $roles, true)) {
        foreach (BranchManager::query()->select('id')->cursor() as $manager) {
            $users[] = ['user_type' => 'branch_manager', 'user_id' => $manager->id];
        }
    }

    if (in_array('asm', $roles, true)) {
        foreach (AreaSaleManager::query()->select('id')->cursor() as $asm) {
            $users[] = ['user_type' => 'area_sale_manager', 'user_id' => $asm->id];
        }
    }

    if (empty($users)) {
        return;
    }

    $existing = TrainingVideoCompletion::where('training_video_id', $training->id)
        ->get(['user_type', 'user_id'])
        ->map(function ($row) {
            return $row->user_type . ':' . $row->user_id;
        })
        ->all();

    $now = now();
    $rows = [];

    foreach ($users as $user) {
        $key = $user['user_type'] . ':' . $user['user_id'];
        if (in_array($key, $existing, true)) {
            continue;
        }

        $rows[] = [
            'training_video_id' => $training->id,
            'user_type' => $user['user_type'],
            'user_id' => $user['user_id'],
            'status' => 'pending',
            'completed_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    foreach (array_chunk($rows, 500) as $chunk) {
        TrainingVideoCompletion::insert($chunk);
    }
}

    private function ensureTrainingCategoryColumn(): void
    {
        if (!Schema::hasTable('training_videos')) {
            return;
        }

        if (!Schema::hasColumn('training_videos', 'category')) {
            Schema::table('training_videos', function ($table) {
                $table->string('category', 191)->nullable();
            });
            return;
        }

        try {
            $column = collect(DB::select("SHOW COLUMNS FROM training_videos WHERE Field = 'category'"))->first();
            $type = strtolower((string) ($column->Type ?? ''));
            if (strpos($type, 'enum') !== false) {
                DB::statement('ALTER TABLE training_videos MODIFY category VARCHAR(191) NULL');
            }
        } catch (\Throwable $e) {
            // Live DB may not allow ALTER; save still uses the existing column.
        }
    }

    private function resolveDisplayCategorySlug($value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $slug = Str::slug($raw);

        try {
            $category = DisplayCategory::query()
                ->where('slug', $raw)
                ->orWhere('slug', $slug)
                ->orWhereRaw('LOWER(name) = ?', [strtolower($raw)])
                ->when(ctype_digit($raw), function ($query) use ($raw) {
                    $query->orWhere('id', (int) $raw);
                })
                ->first();
        } catch (\Throwable $e) {
            return $slug !== '' ? $slug : null;
        }

        if ($category && !empty($category->slug)) {
            return $category->slug;
        }

        return $slug !== '' ? $slug : null;
    }

    private function resolveTrainingAudio(Request $request, TrainingVideo $training): ?string
    {
        if ($request->training_type === 'display' && $request->hasFile('audio_recording')) {
            return $this->storeTrainingMediaFile($request->file('audio_recording'));
        }

        if ($request->hasFile('audio')) {
            return $this->storeTrainingMediaFile($request->file('audio'));
        }

        return $training->audio;
    }

    private function storeTrainingMediaFile($file): string
    {
        $dir = public_path('admin/assets/training_videos');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = time() . '.' . $file->getClientOriginalExtension();
        $file->move($dir, $filename);

        return 'public/admin/assets/training_videos/' . $filename;
    }

    private function resolveCustomerVideoUrl(Request $request, TrainingVideo $training): ?string
    {
        if ($request->hasFile('video')) {
            $file = $request->file('video');
            $dir = public_path('admin/assets/training_videos');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->move($dir, $filename);

            return 'public/admin/assets/training_videos/' . $filename;
        }

        $typed = trim((string) $request->input('video_url', ''));
        if ($typed !== '') {
            return $typed;
        }

        return $training->video_url;
    }

public function delete($id)
{
    $video = TrainingVideo::findOrFail($id);
    $video->delete();

    return redirect()->route('training.video.index')->with('success', 'Training Module Deleted Successfully');
}
}
