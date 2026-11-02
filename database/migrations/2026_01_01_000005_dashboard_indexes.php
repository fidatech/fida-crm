<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tasks', fn (Blueprint $t) => $t->index(['tenant_id','status','due_at'], 'tasks_tenant_due_idx'));
        Schema::table('deals', fn (Blueprint $t) => $t->index(['tenant_id','status','expected_close_at'], 'deals_tenant_close_idx'));
        Schema::table('leads', fn (Blueprint $t) => $t->index(['tenant_id','status','score'], 'leads_tenant_score_idx'));
    }

    public function down(): void
    {
        Schema::table('tasks', fn (Blueprint $t) => $t->dropIndex('tasks_tenant_due_idx'));
        Schema::table('deals', fn (Blueprint $t) => $t->dropIndex('deals_tenant_close_idx'));
        Schema::table('leads', fn (Blueprint $t) => $t->dropIndex('leads_tenant_score_idx'));
    }
};
