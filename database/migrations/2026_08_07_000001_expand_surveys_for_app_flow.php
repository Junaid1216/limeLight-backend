<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExpandSurveysForAppFlow extends Migration
{
    public function up()
    {
        Schema::table('surveys', function (Blueprint $table) {
            if (!Schema::hasColumn('surveys', 'title')) {
                $table->string('title')->nullable()->after('id');
            }
            if (!Schema::hasColumn('surveys', 'status')) {
                $table->string('status')->default('active')->after('title');
            }
        });

        if (!Schema::hasTable('survey_questions')) {
            Schema::create('survey_questions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('survey_id');
                $table->string('question');
                $table->boolean('is_required')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->foreign('survey_id')->references('id')->on('surveys')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('survey_options')) {
            Schema::create('survey_options', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('survey_question_id');
                $table->string('label');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->foreign('survey_question_id')->references('id')->on('survey_questions')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('survey_submissions')) {
            Schema::create('survey_submissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('survey_id');
                $table->string('user_type'); // sale_staff | branch_manager | area_sale_manager
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();

                $table->foreign('survey_id')->references('id')->on('surveys')->onDelete('cascade');
                $table->unique(['survey_id', 'user_type', 'user_id'], 'survey_user_unique');
                $table->index(['survey_id', 'branch_id']);
            });
        }

        if (!Schema::hasTable('survey_answers')) {
            Schema::create('survey_answers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('survey_submission_id');
                $table->unsignedBigInteger('survey_question_id');
                $table->unsignedBigInteger('survey_option_id');
                $table->timestamps();

                $table->foreign('survey_submission_id')->references('id')->on('survey_submissions')->onDelete('cascade');
                $table->foreign('survey_question_id')->references('id')->on('survey_questions')->onDelete('cascade');
                $table->foreign('survey_option_id')->references('id')->on('survey_options')->onDelete('cascade');
                $table->unique(['survey_submission_id', 'survey_question_id'], 'submission_question_unique');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('survey_answers');
        Schema::dropIfExists('survey_submissions');
        Schema::dropIfExists('survey_options');
        Schema::dropIfExists('survey_questions');

        Schema::table('surveys', function (Blueprint $table) {
            if (Schema::hasColumn('surveys', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('surveys', 'title')) {
                $table->dropColumn('title');
            }
        });
    }
}
