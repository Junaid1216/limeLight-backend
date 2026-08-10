<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'roles' => 'array',
    ];

    public function questions()
    {
        return $this->hasMany(SurveyQuestion::class)->orderBy('sort_order');
    }

    public function submissions()
    {
        return $this->hasMany(SurveySubmission::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForRole($query, $role)
    {
        return $query->whereJsonContains('roles', $role);
    }

    /**
     * Old surveys only had surveys.question — migrate into questions/options when empty.
     */
    public function ensureQuestionsSynced(): void
    {
        if ($this->questions()->exists()) {
            return;
        }

        $text = trim((string) $this->question);
        if ($text === '') {
            return;
        }

        $question = $this->questions()->create([
            'question' => $text,
            'is_required' => true,
            'sort_order' => 1,
        ]);

        foreach (['High', 'Fair', 'Low'] as $index => $label) {
            $question->options()->create([
                'label' => $label,
                'sort_order' => $index + 1,
            ]);
        }

        $this->unsetRelation('questions');
    }
}
