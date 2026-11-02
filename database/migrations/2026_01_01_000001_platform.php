<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('users',function(Blueprint $t){$t->id();$t->string('name');$t->string('email')->unique();$t->timestamp('email_verified_at')->nullable();$t->string('password');$t->rememberToken();$t->boolean('platform_admin')->default(false);$t->timestamps();});
  Schema::create('password_reset_tokens',function(Blueprint $t){$t->string('email')->primary();});
  // Password reset token schema is defined in the follow-up statement (no macro dependencies).
  Schema::table('password_reset_tokens',function(Blueprint $t){$t->string('token');$t->timestamp('created_at')->nullable();});
  Schema::create('tenants',function(Blueprint $t){$t->id();$t->string('name');$t->string('slug')->unique();$t->string('currency',3)->default('USD');$t->string('timezone')->default('UTC');$t->string('status')->default('trial');$t->timestamp('trial_ends_at')->nullable();$t->string('settings')->nullable();$t->timestamps();});
  Schema::create('memberships',function(Blueprint $t){$t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->string('role')->default('member');$t->timestamps();$t->unique(['tenant_id','user_id']);});
  Schema::create('invitations',function(Blueprint $t){$t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();$t->string('email');$t->string('role');$t->string('token_hash',64)->unique();$t->timestamp('expires_at');$t->timestamps();});
  Schema::create('plans',function(Blueprint $t){$t->id();$t->string('name');$t->string('slug')->unique();$t->unsignedInteger('monthly_cents')->default(0);$t->unsignedInteger('seat_limit')->default(3);$t->string('stripe_price_id')->nullable();$t->timestamps();});
  Schema::create('subscriptions',function(Blueprint $t){$t->id();$t->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();$t->foreignId('plan_id')->nullable()->constrained();$t->string('status')->default('trial');$t->string('stripe_customer_id')->nullable();$t->string('stripe_subscription_id')->nullable();$t->timestamp('period_ends_at')->nullable();$t->timestamps();});
  Schema::create('sessions',function(Blueprint $t){$t->string('id')->primary();$t->foreignId('user_id')->nullable()->index();$t->string('ip_address',45)->nullable();$t->text('user_agent')->nullable();$t->longText('payload');$t->integer('last_activity')->index();});
  Schema::create('cache',function(Blueprint $t){$t->string('key')->primary();$t->mediumText('value');$t->integer('expiration');});
  Schema::create('cache_locks',function(Blueprint $t){$t->string('key')->primary();$t->string('owner');$t->integer('expiration');});
  Schema::create('jobs',function(Blueprint $t){$t->id();$t->string('queue')->index();$t->longText('payload');$t->unsignedTinyInteger('attempts');$t->unsignedInteger('reserved_at')->nullable();$t->unsignedInteger('available_at');$t->unsignedInteger('created_at');});
  Schema::create('failed_jobs',function(Blueprint $t){$t->id();$t->string('uuid')->unique();$t->text('connection');$t->text('queue');$t->longText('payload');$t->longText('exception');$t->timestamp('failed_at')->useCurrent();});
  Schema::create('job_batches',function(Blueprint $t){$t->string('id')->primary();$t->string('name');$t->integer('total_jobs');$t->integer('pending_jobs');$t->integer('failed_jobs');$t->longText('failed_job_ids');$t->mediumText('options')->nullable();$t->integer('cancelled_at')->nullable();$t->integer('created_at');$t->integer('finished_at')->nullable();});
 }
 public function down(): void {foreach(['job_batches','failed_jobs','jobs','cache_locks','cache','sessions','subscriptions','plans','invitations','memberships','tenants','password_reset_tokens','users'] as $n)Schema::dropIfExists($n);}
};
