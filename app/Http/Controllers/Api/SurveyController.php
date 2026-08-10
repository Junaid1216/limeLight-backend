<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\AreaSaleManager;
use App\Models\Branch;
use App\Models\BranchManager;
use App\Models\SaleStaff;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyOption;
use App\Models\SurveyQuestion;
use App\Models\SurveySubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SurveyController extends Controller
{
    /**
     * Legacy: questions filtered by role only.
     */
    public function getSurveyQuestions($role)
    {
        $surveys = Survey::whereJsonContains('roles', $role)
            ->select('id', 'question')
            ->get();

        return response()->json([
            'status' => '200',
            'message' => 'Survey questions retrieved successfully',
            'data' => $surveys,
        ]);
    }

    /**
     * Screen: Survey list — active surveys for logged-in role.
     * GET /api/surveys
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return ResponseHelper::error(null, 'User not authenticated', 'unauthorized', 401);
        }

        $role = $this->resolveAppRole($user);
        if (!$role) {
            return ResponseHelper::error(null, 'Unsupported user type for surveys', 'forbidden', 403);
        }

        $identity = $this->resolveUserIdentity($user);

        $surveys = Survey::active()
            ->forRole($role)
            ->orderByDesc('id')
            ->get()
            ->map(function (Survey $survey) use ($identity) {
                $survey->ensureQuestionsSynced();
                $questionsCount = $survey->questions()->count();

                $submitted = SurveySubmission::where('survey_id', $survey->id)
                    ->where('user_type', $identity['user_type'])
                    ->where('user_id', $identity['user_id'])
                    ->exists();

                return [
                    'id' => $survey->id,
                    'title' => $survey->title ?: 'Survey',
                    'status' => strtoupper($survey->status ?? 'active'),
                    'questions_count' => (int) $questionsCount,
                    'questions_label' => ((int) $questionsCount) . ' Questions',
                    'is_submitted' => $submitted,
                ];
            });

        return ResponseHelper::success($surveys, 'Surveys retrieved successfully', '200', 200);
    }

    /**
     * Screen: Survey entry — questions + options.
     * GET /api/surveys/{id}
     */
    public function show($id)
    {
        $user = Auth::user();
        if (!$user) {
            return ResponseHelper::error(null, 'User not authenticated', 'unauthorized', 401);
        }

        $role = $this->resolveAppRole($user);
        $identity = $this->resolveUserIdentity($user);

        $survey = Survey::active()
            ->forRole($role)
            ->find($id);

        if (!$survey) {
            return ResponseHelper::error(null, 'Survey not found', 'not_found', 404);
        }

        $survey->ensureQuestionsSynced();
        $survey->load(['questions.options']);

        $submission = SurveySubmission::where('survey_id', $survey->id)
            ->where('user_type', $identity['user_type'])
            ->where('user_id', $identity['user_id'])
            ->with('answers')
            ->first();

        $totalQuestions = $survey->questions->count();

        $questions = $survey->questions->map(function (SurveyQuestion $question, $index) use ($submission, $totalQuestions) {
            $selectedOptionId = null;
            if ($submission) {
                $answer = $submission->answers->firstWhere('survey_question_id', $question->id);
                $selectedOptionId = $answer ? $answer->survey_option_id : null;
            }

            return [
                'id' => $question->id,
                'question' => $question->question,
                'is_required' => (bool) $question->is_required,
                'sort_order' => (int) $question->sort_order,
                'progress_label' => ($index + 1) . ' of ' . $totalQuestions,
                'selected_option_id' => $selectedOptionId,
                'options' => $question->options->map(function (SurveyOption $option) {
                    return [
                        'id' => $option->id,
                        'label' => $option->label,
                        'sort_order' => (int) $option->sort_order,
                    ];
                })->values(),
            ];
        })->values();

        return ResponseHelper::success([
            'id' => $survey->id,
            'title' => $survey->title ?: 'Survey',
            'status' => strtoupper($survey->status ?? 'active'),
            'questions_count' => $questions->count(),
            'is_submitted' => (bool) $submission,
            'questions' => $questions,
        ], 'Survey details retrieved successfully', '200', 200);
    }

    /**
     * Screen: Submit Survey.
     * POST /api/surveys/{id}/submit
     * Body: { answers: [{ question_id, option_id }] }
     */
    public function submit(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return ResponseHelper::error(null, 'User not authenticated', 'unauthorized', 401);
        }

        try {
            $request->validate([
                'answers' => 'required|array|min:1',
                'answers.*.question_id' => 'required|integer|exists:survey_questions,id',
                'answers.*.option_id' => 'required|integer|exists:survey_options,id',
            ]);
        } catch (ValidationException $e) {
            return ResponseHelper::error($e->errors(), 'Validation failed', 'validation_error', 422);
        }

        $role = $this->resolveAppRole($user);
        $identity = $this->resolveUserIdentity($user);

        $survey = Survey::active()->forRole($role)->find($id);
        if (!$survey) {
            return ResponseHelper::error(null, 'Survey not found', 'not_found', 404);
        }

        $survey->ensureQuestionsSynced();
        $survey->load('questions');

        $already = SurveySubmission::where('survey_id', $survey->id)
            ->where('user_type', $identity['user_type'])
            ->where('user_id', $identity['user_id'])
            ->exists();

        if ($already) {
            return ResponseHelper::error(null, 'Survey already submitted', 'already_exists', 409);
        }

        $questionIds = $survey->questions->pluck('id');
        $requiredIds = $survey->questions->where('is_required', true)->pluck('id');
        $answeredIds = collect($request->answers)->pluck('question_id')->unique();

        foreach ($request->answers as $answer) {
            if (!$questionIds->contains($answer['question_id'])) {
                return ResponseHelper::error(null, 'Invalid question for this survey', 'validation_error', 422);
            }

            $option = SurveyOption::where('id', $answer['option_id'])
                ->where('survey_question_id', $answer['question_id'])
                ->first();

            if (!$option) {
                return ResponseHelper::error(null, 'Option does not belong to the question', 'validation_error', 422);
            }
        }

        $missingRequired = $requiredIds->diff($answeredIds);
        if ($missingRequired->isNotEmpty()) {
            return ResponseHelper::error(
                ['missing_question_ids' => $missingRequired->values()],
                'Please answer all required questions',
                'validation_error',
                422
            );
        }

        try {
            $submission = DB::transaction(function () use ($request, $survey, $identity) {
                $submission = SurveySubmission::create([
                    'survey_id' => $survey->id,
                    'user_type' => $identity['user_type'],
                    'user_id' => $identity['user_id'],
                    'branch_id' => $identity['branch_id'],
                    'submitted_at' => now(),
                ]);

                foreach ($request->answers as $answer) {
                    SurveyAnswer::create([
                        'survey_submission_id' => $submission->id,
                        'survey_question_id' => $answer['question_id'],
                        'survey_option_id' => $answer['option_id'],
                    ]);
                }

                return $submission->load('answers');
            });
        } catch (\Throwable $e) {
            return ResponseHelper::error($e->getMessage(), 'Failed to submit survey', 'server_error', 500);
        }

        return ResponseHelper::success([
            'submission_id' => $submission->id,
            'survey_id' => $survey->id,
            'submitted_at' => optional($submission->submitted_at)->toDateTimeString(),
        ], 'Survey submitted successfully', '200', 200);
    }

    /**
     * Screen: Survey Report (BM / ASM / Sales Staff).
     * GET /api/surveys/{id}/report?branch_id=
     */
    public function report(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return ResponseHelper::error(null, 'User not authenticated', 'unauthorized', 401);
        }

        if (!($user instanceof BranchManager || $user instanceof AreaSaleManager || $user instanceof SaleStaff)) {
            return ResponseHelper::error(null, 'Unauthorized to view survey reports', 'forbidden', 403);
        }

        $survey = Survey::with(['questions.options'])->find($id);
        if (!$survey) {
            return ResponseHelper::error(null, 'Survey not found', 'not_found', 404);
        }

        $branch = $this->resolveReportBranch($request, $user);
        if ($branch instanceof \Illuminate\Http\JsonResponse) {
            return $branch;
        }

        $staffQuery = SaleStaff::where('branch_id', $branch->id);
        $totalStaff = (clone $staffQuery)->count();
        $staffIds = (clone $staffQuery)->pluck('id');

        $submissions = SurveySubmission::where('survey_id', $survey->id)
            ->where('user_type', 'sale_staff')
            ->whereIn('user_id', $staffIds)
            ->with('answers.option')
            ->get();

        $completed = $submissions->count();
        $responseRate = $totalStaff > 0 ? round(($completed / $totalStaff) * 100) : 0;

        // Overall option breakdown (matches Figma High / Fair / Low bars)
        $optionTotals = [];
        $totalAnswers = 0;
        foreach ($submissions as $submission) {
            foreach ($submission->answers as $answer) {
                $label = optional($answer->option)->label;
                if (!$label) {
                    continue;
                }
                $optionTotals[$label] = ($optionTotals[$label] ?? 0) + 1;
                $totalAnswers++;
            }
        }

        $breakdown = collect($optionTotals)->map(function ($count, $label) use ($totalAnswers) {
            return [
                'label' => $label,
                'count' => $count,
                'percentage' => $totalAnswers > 0 ? round(($count / $totalAnswers) * 100) : 0,
            ];
        })->values();

        // Per-question breakdown (useful for detailed analytics)
        $perQuestion = $survey->questions->map(function (SurveyQuestion $question) use ($submissions) {
            $counts = [];
            foreach ($question->options as $option) {
                $counts[$option->id] = [
                    'option_id' => $option->id,
                    'label' => $option->label,
                    'count' => 0,
                    'percentage' => 0,
                ];
            }

            $answered = 0;
            foreach ($submissions as $submission) {
                $ans = $submission->answers->firstWhere('survey_question_id', $question->id);
                if ($ans && isset($counts[$ans->survey_option_id])) {
                    $counts[$ans->survey_option_id]['count']++;
                    $answered++;
                }
            }

            foreach ($counts as &$row) {
                $row['percentage'] = $answered > 0 ? round(($row['count'] / $answered) * 100) : 0;
            }

            return [
                'question_id' => $question->id,
                'question' => $question->question,
                'options' => array_values($counts),
            ];
        })->values();

        return ResponseHelper::success([
            'survey' => [
                'id' => $survey->id,
                'title' => $survey->title ?: 'Survey',
                'status' => strtoupper($survey->status ?? 'active'),
                'questions_count' => $survey->questions->count(),
            ],
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name ?? $branch->branch_name ?? null,
            ],
            'stats' => [
                'total_responses' => $completed,
                'total_staff' => $totalStaff,
                'responses_label' => $completed . '/' . $totalStaff,
                'response_rate' => $responseRate,
                'response_rate_label' => $responseRate . '% response rate',
            ],
            'response_breakdown' => $breakdown,
            'per_question_breakdown' => $perQuestion,
        ], 'Survey report retrieved successfully', '200', 200);
    }

    /**
     * Screen: Survey Response list (BM / ASM) — completed / pending staff.
     * GET /api/surveys/{id}/responses?branch_id=&status=all|completed|pending
     */
    public function responses(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return ResponseHelper::error(null, 'User not authenticated', 'unauthorized', 401);
        }

        if (!($user instanceof BranchManager || $user instanceof AreaSaleManager)) {
            return ResponseHelper::error(null, 'Only Branch Manager or ASM can view survey responses', 'forbidden', 403);
        }

        $survey = Survey::find($id);
        if (!$survey) {
            return ResponseHelper::error(null, 'Survey not found', 'not_found', 404);
        }

        $branch = $this->resolveReportBranch($request, $user);
        if ($branch instanceof \Illuminate\Http\JsonResponse) {
            return $branch;
        }

        $statusFilter = strtolower($request->get('status', 'all'));

        $staff = SaleStaff::where('branch_id', $branch->id)
            ->orderBy('name')
            ->get(['id', 'name', 'employee_id', 'branch_id']);

        $submissions = SurveySubmission::where('survey_id', $survey->id)
            ->where('user_type', 'sale_staff')
            ->whereIn('user_id', $staff->pluck('id'))
            ->get()
            ->keyBy('user_id');

        $branchName = $branch->name ?? $branch->branch_name ?? null;

        $list = $staff->map(function (SaleStaff $member) use ($submissions, $branchName) {
            $submission = $submissions->get($member->id);
            $completed = (bool) $submission;

            return [
                'staff_id' => $member->id,
                'name' => $member->name,
                'initials' => $this->initials($member->name),
                'employee_code' => $member->employee_id,
                'branch' => $branchName,
                'status' => $completed ? 'Completed' : 'Pending',
                'is_completed' => $completed,
                'submitted_at' => $completed && $submission->submitted_at
                    ? $submission->submitted_at->format('d M, h:i A')
                    : null,
                'submitted_at_raw' => $completed ? optional($submission->submitted_at)->toDateTimeString() : null,
            ];
        });

        $completedCount = $list->where('is_completed', true)->count();
        $pendingCount = $list->where('is_completed', false)->count();

        if ($statusFilter === 'completed') {
            $list = $list->where('is_completed', true)->values();
        } elseif ($statusFilter === 'pending') {
            $list = $list->where('is_completed', false)->values();
        } else {
            $list = $list->values();
        }

        return ResponseHelper::success([
            'survey' => [
                'id' => $survey->id,
                'title' => $survey->title ?: 'Survey',
                'status' => strtoupper($survey->status ?? 'active'),
            ],
            'branch' => [
                'id' => $branch->id,
                'name' => $branchName,
            ],
            'summary' => [
                'total_staff' => $staff->count(),
                'completed' => $completedCount,
                'pending' => $pendingCount,
            ],
            'responses' => $list,
        ], 'Survey responses retrieved successfully', '200', 200);
    }

    /**
     * ASM: list of branches assigned to ASM region (for survey report branch_id selection).
     * GET /api/asm-branches
     */
    public function asmBranches()
    {
        $user = Auth::user();
        if (!$user) {
            return ResponseHelper::error(null, 'User not authenticated', 'unauthorized', 401);
        }

        if (!($user instanceof AreaSaleManager)) {
            return ResponseHelper::error(null, 'Only ASM can access this endpoint', 'forbidden', 403);
        }

        if (!$user->region_id) {
            return ResponseHelper::error(null, 'ASM has no region assigned', 'not_found', 404);
        }

        $branches = Branch::where('region_id', $user->region_id)
            ->orderBy('name')
            ->get(['id', 'name', 'region_id'])
            ->map(function (Branch $branch) {
                return [
                    'id' => $branch->id,
                    'branch_id' => $branch->id,
                    'name' => $branch->name ?? $branch->branch_name ?? null,
                    'region_id' => $branch->region_id,
                ];
            })
            ->values();

        return ResponseHelper::success([
            'region_id' => $user->region_id,
            'branches_count' => $branches->count(),
            'branches' => $branches,
        ], 'ASM branches retrieved successfully', '200', 200);
    }

    private function resolveAppRole($user): ?string
    {
        if ($user instanceof SaleStaff) {
            return 'sales_staff';
        }
        if ($user instanceof BranchManager) {
            return 'branch_manager';
        }
        if ($user instanceof AreaSaleManager) {
            return 'asm';
        }

        return null;
    }

    private function resolveUserIdentity($user): array
    {
        if ($user instanceof SaleStaff) {
            return [
                'user_type' => 'sale_staff',
                'user_id' => $user->id,
                'branch_id' => $user->branch_id,
            ];
        }
        if ($user instanceof BranchManager) {
            return [
                'user_type' => 'branch_manager',
                'user_id' => $user->id,
                'branch_id' => $user->branch_id,
            ];
        }
        if ($user instanceof AreaSaleManager) {
            return [
                'user_type' => 'area_sale_manager',
                'user_id' => $user->id,
                'branch_id' => null,
            ];
        }

        return [
            'user_type' => 'unknown',
            'user_id' => $user->id ?? 0,
            'branch_id' => null,
        ];
    }

    /**
     * BM / Sales Staff: own branch (branch_id optional but must match).
     * ASM: branch_id required and must belong to ASM region.
     */
    private function resolveReportBranch(Request $request, $user)
    {
        if ($user instanceof BranchManager || $user instanceof SaleStaff) {
            $branchId = $request->get('branch_id', $user->branch_id);
            if ((int) $branchId !== (int) $user->branch_id) {
                return ResponseHelper::error(null, 'You can only view your own branch report', 'forbidden', 403);
            }

            $branch = Branch::find($user->branch_id);
            if (!$branch) {
                return ResponseHelper::error(null, 'Branch not found', 'not_found', 404);
            }

            return $branch;
        }

        if ($user instanceof AreaSaleManager) {
            $branchId = $request->get('branch_id');
            if (!$branchId) {
                return ResponseHelper::error(null, 'branch_id is required for ASM', 'validation_error', 422);
            }

            $branch = Branch::where('id', $branchId)
                ->where('region_id', $user->region_id)
                ->first();

            if (!$branch) {
                return ResponseHelper::error(null, 'Branch not found in your region', 'not_found', 404);
            }

            return $branch;
        }

        return ResponseHelper::error(null, 'Unauthorized', 'forbidden', 403);
    }

    private function initials(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '?';
        }

        $parts = preg_split('/\s+/', $name);
        $first = mb_substr($parts[0], 0, 1);
        $second = isset($parts[1]) ? mb_substr($parts[1], 0, 1) : '';

        return strtoupper($first . $second);
    }
}
