<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up():void {
  Schema::create('tenant_roles',function(Blueprint $t){$t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();$t->string('name',100);$t->string('slug',100);$t->boolean('protected')->default(false);$t->timestamps();$t->unique(['tenant_id','slug']);});
  Schema::create('tenant_role_permissions',function(Blueprint $t){$t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();$t->foreignId('role_id')->constrained('tenant_roles')->cascadeOnDelete();$t->string('permission',120);$t->unique(['role_id','permission']);});
  Schema::table('memberships',function(Blueprint $t){$t->foreignId('role_id')->nullable()->after('role')->constrained('tenant_roles')->nullOnDelete();});
  Schema::create('tenant_currencies',function(Blueprint $t){$t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();$t->char('code',3);$t->string('name',100);$t->string('symbol',10);$t->unsignedTinyInteger('precision')->default(2);$t->decimal('rate_to_base',22,8)->default(1);$t->boolean('is_base')->default(false);$t->boolean('active')->default(true);$t->timestamps();$t->unique(['tenant_id','code']);});
  Schema::create('currency_rate_history',function(Blueprint $t){$t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();$t->char('code',3);$t->char('base_code',3);$t->decimal('rate_to_base',22,8);$t->string('source',30)->default('manual');$t->unsignedBigInteger('changed_by')->nullable();$t->timestamp('effective_at');$t->timestamps();$t->index(['tenant_id','code','effective_at']);});
  Schema::table('deals',function(Blueprint $t){$t->decimal('rate_to_base',22,8)->nullable();$t->decimal('amount_base',20,2)->nullable();});
  Schema::table('sales_documents',function(Blueprint $t){$t->decimal('rate_to_base',22,8)->nullable();$t->decimal('total_base',20,2)->nullable();});
  Schema::create('tenant_integrations',function(Blueprint $t){$t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();$t->string('provider',40);$t->text('encrypted_key');$t->string('key_hint',12)->nullable();$t->timestamps();$t->unique(['tenant_id','provider']);});
  Schema::create('discovery_searches',function(Blueprint $t){$t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();$t->string('name',120);$t->string('query',150)->nullable();$t->string('industry',100)->nullable();$t->string('location',120)->nullable();$t->string('headcount',30)->nullable();$t->string('frequency',20)->default('manual');$t->boolean('active')->default(true);$t->timestamp('last_run_at')->nullable();$t->unsignedBigInteger('created_by');$t->timestamps();});
  Schema::create('discovery_runs',function(Blueprint $t){$t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();$t->foreignId('search_id')->nullable()->constrained('discovery_searches')->nullOnDelete();$t->string('provider',40)->default('apollo');$t->string('status',20);$t->integer('result_count')->default(0);$t->text('error')->nullable();$t->unsignedBigInteger('created_by')->nullable();$t->timestamps();});
  Schema::create('discovery_candidates',function(Blueprint $t){$t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();$t->foreignId('run_id')->constrained('discovery_runs')->cascadeOnDelete();$t->string('provider',40);$t->string('external_id',150);$t->string('name',255);$t->string('domain',255)->nullable();$t->string('industry',255)->nullable();$t->string('city',120)->nullable();$t->string('country',120)->nullable();$t->unsignedInteger('employees')->nullable();$t->unsignedTinyInteger('fit_score')->default(0);$t->string('status',20)->default('pending');$t->unsignedBigInteger('company_id')->nullable();$t->json('source_data')->nullable();$t->timestamp('reviewed_at')->nullable();$t->timestamps();$t->unique(['tenant_id','provider','external_id'],'uniq_discovery_candidate');$t->index(['tenant_id','status']);});
  foreach(DB::table('tenants')->get(['id','currency']) as $tenant){
   \App\Services\RoleService::seedForTenant((int)$tenant->id);
   \App\Services\CurrencyService::seedForTenant((int)$tenant->id,$tenant->currency?:'USD');
  }
 }
 public function down():void {
  Schema::dropIfExists('discovery_candidates');Schema::dropIfExists('discovery_runs');Schema::dropIfExists('discovery_searches');Schema::dropIfExists('tenant_integrations');
  Schema::table('sales_documents',fn(Blueprint $t)=>$t->dropColumn(['rate_to_base','total_base']));
  Schema::table('deals',fn(Blueprint $t)=>$t->dropColumn(['rate_to_base','amount_base']));
  Schema::dropIfExists('currency_rate_history');Schema::dropIfExists('tenant_currencies');
  Schema::table('memberships',fn(Blueprint $t)=>$t->dropConstrainedForeignId('role_id'));
  Schema::dropIfExists('tenant_role_permissions');Schema::dropIfExists('tenant_roles');
 }
};
