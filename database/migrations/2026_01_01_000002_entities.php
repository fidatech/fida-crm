<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('contacts', function(Blueprint $t) {
   $t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
   $t->string('first_name',255);
   $t->string('last_name',255)->nullable();
   $t->string('email',255)->nullable();
   $t->string('phone',255)->nullable();
   $t->string('job_title',255)->nullable();
   $t->unsignedBigInteger('company_id')->nullable();
   $t->string('lifecycle_stage',255)->nullable();
   $t->unsignedBigInteger('owner_id')->nullable();
   $t->string('source',255)->nullable();
   $t->string('city',255)->nullable();
   $t->string('state',255)->nullable();
   $t->string('country',255)->nullable();
   $t->longText('description')->nullable();
   $t->boolean('email_opt_out')->nullable();
   $t->unsignedBigInteger('created_by')->nullable();$t->timestamps();$t->softDeletes();$t->index(['tenant_id','created_at']);
   $t->index(['tenant_id','email']);
  });
  Schema::create('companies', function(Blueprint $t) {
   $t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
   $t->string('name',255);
   $t->string('domain',255)->nullable();
   $t->string('industry',255)->nullable();
   $t->string('phone',255)->nullable();
   $t->string('email',255)->nullable();
   $t->string('website',1000)->nullable();
   $t->integer('employees')->nullable();
   $t->decimal('annual_revenue',18,2)->nullable();
   $t->unsignedBigInteger('owner_id')->nullable();
   $t->string('city',255)->nullable();
   $t->string('state',255)->nullable();
   $t->string('country',255)->nullable();
   $t->longText('description')->nullable();
   $t->unsignedBigInteger('created_by')->nullable();$t->timestamps();$t->softDeletes();$t->index(['tenant_id','created_at']);
  });
  Schema::create('leads', function(Blueprint $t) {
   $t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
   $t->string('name',255);
   $t->string('email',255)->nullable();
   $t->string('phone',255)->nullable();
   $t->string('company_name',255)->nullable();
   $t->unsignedBigInteger('company_id')->nullable();
   $t->unsignedBigInteger('contact_id')->nullable();
   $t->string('status',255)->nullable();
   $t->string('source',255)->nullable();
   $t->integer('score')->nullable();
   $t->decimal('estimated_value',18,2)->nullable();
   $t->unsignedBigInteger('owner_id')->nullable();
   $t->longText('notes')->nullable();
   $t->unsignedBigInteger('created_by')->nullable();$t->timestamps();$t->softDeletes();$t->index(['tenant_id','created_at']);
  });
  Schema::create('deals', function(Blueprint $t) {
   $t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
   $t->string('title',255);
   $t->decimal('amount',18,2)->nullable();
   $t->string('currency',255)->nullable();
   $t->unsignedBigInteger('company_id')->nullable();
   $t->unsignedBigInteger('contact_id')->nullable();
   $t->unsignedBigInteger('pipeline_id')->nullable();
   $t->unsignedBigInteger('stage_id')->nullable();
   $t->integer('probability')->nullable();
   $t->string('status',255)->nullable();
   $t->date('expected_close_at')->nullable();
   $t->unsignedBigInteger('owner_id')->nullable();
   $t->longText('description')->nullable();
   $t->unsignedBigInteger('created_by')->nullable();$t->timestamps();$t->softDeletes();$t->index(['tenant_id','created_at']);
   $t->index(['tenant_id','stage_id']);
  });
  Schema::create('tasks', function(Blueprint $t) {
   $t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
   $t->string('title',255);
   $t->longText('description')->nullable();
   $t->string('status',255)->nullable();
   $t->string('priority',255)->nullable();
   $t->dateTime('due_at')->nullable();
   $t->unsignedBigInteger('assigned_to')->nullable();
   $t->unsignedBigInteger('contact_id')->nullable();
   $t->unsignedBigInteger('deal_id')->nullable();
   $t->unsignedBigInteger('created_by')->nullable();$t->timestamps();$t->softDeletes();$t->index(['tenant_id','created_at']);
  });
  Schema::create('meetings', function(Blueprint $t) {
   $t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
   $t->string('title',255);
   $t->longText('description')->nullable();
   $t->dateTime('starts_at');
   $t->dateTime('ends_at')->nullable();
   $t->string('location',255)->nullable();
   $t->unsignedBigInteger('contact_id')->nullable();
   $t->unsignedBigInteger('deal_id')->nullable();
   $t->unsignedBigInteger('organizer_id')->nullable();
   $t->string('status',255)->nullable();
   $t->unsignedBigInteger('created_by')->nullable();$t->timestamps();$t->softDeletes();$t->index(['tenant_id','created_at']);
  });
  Schema::create('tickets', function(Blueprint $t) {
   $t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
   $t->string('subject',255);
   $t->longText('description')->nullable();
   $t->string('status',255)->nullable();
   $t->string('priority',255)->nullable();
   $t->string('channel',255)->nullable();
   $t->unsignedBigInteger('contact_id')->nullable();
   $t->unsignedBigInteger('company_id')->nullable();
   $t->unsignedBigInteger('assigned_to')->nullable();
   $t->dateTime('sla_due_at')->nullable();
   $t->unsignedBigInteger('created_by')->nullable();$t->timestamps();$t->softDeletes();$t->index(['tenant_id','created_at']);
   $t->index(['tenant_id','status']);
  });
  Schema::create('knowledge_articles', function(Blueprint $t) {
   $t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
   $t->string('title',255);
   $t->string('slug',255)->nullable();
   $t->longText('body');
   $t->string('category',255)->nullable();
   $t->string('status',255)->nullable();
   $t->unsignedBigInteger('created_by')->nullable();$t->timestamps();$t->softDeletes();$t->index(['tenant_id','created_at']);
  });
  Schema::create('products', function(Blueprint $t) {
   $t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
   $t->string('name',255);
   $t->string('sku',255)->nullable();
   $t->longText('description')->nullable();
   $t->decimal('unit_price',18,2)->nullable();
   $t->decimal('unit_cost',18,2)->nullable();
   $t->string('currency',255)->nullable();
   $t->integer('tax_rate')->nullable();
   $t->boolean('active')->nullable();
   $t->unsignedBigInteger('created_by')->nullable();$t->timestamps();$t->softDeletes();$t->index(['tenant_id','created_at']);
  });
  Schema::create('campaigns', function(Blueprint $t) {
   $t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
   $t->string('name',255);
   $t->string('channel',255)->nullable();
   $t->string('status',255)->nullable();
   $t->longText('objective')->nullable();
   $t->date('start_date')->nullable();
   $t->date('end_date')->nullable();
   $t->decimal('budget',18,2)->nullable();
   $t->string('currency',255)->nullable();
   $t->unsignedBigInteger('created_by')->nullable();$t->timestamps();$t->softDeletes();$t->index(['tenant_id','created_at']);
  });
  Schema::create('email_templates', function(Blueprint $t) {
   $t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
   $t->string('name',255);
   $t->string('subject',255);
   $t->longText('body');
   $t->string('category',255)->nullable();
   $t->unsignedBigInteger('created_by')->nullable();$t->timestamps();$t->softDeletes();$t->index(['tenant_id','created_at']);
  });
  Schema::create('segments', function(Blueprint $t) {
   $t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
   $t->string('name',255);
   $t->string('lifecycle_stage',255)->nullable();
   $t->string('country',255)->nullable();
   $t->string('industry',255)->nullable();
   $t->longText('description')->nullable();
   $t->unsignedBigInteger('created_by')->nullable();$t->timestamps();$t->softDeletes();$t->index(['tenant_id','created_at']);
  });
  Schema::create('web_forms', function(Blueprint $t) {
   $t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
   $t->string('name',255);
   $t->string('slug',255);
   $t->string('status',255)->nullable();
   $t->string('success_message',255)->nullable();
   $t->longText('fields')->nullable();
   $t->unsignedBigInteger('created_by')->nullable();$t->timestamps();$t->softDeletes();$t->index(['tenant_id','created_at']);
   $t->unique('slug');
  });
  Schema::create('pipelines', function(Blueprint $t) {
   $t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
   $t->string('name',255);
   $t->string('entity',255);
   $t->boolean('is_default')->nullable();
   $t->unsignedBigInteger('created_by')->nullable();$t->timestamps();$t->softDeletes();$t->index(['tenant_id','created_at']);
  });
  Schema::create('stages', function(Blueprint $t) {
   $t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
   $t->string('name',255);
   $t->unsignedBigInteger('pipeline_id');
   $t->integer('probability')->nullable();
   $t->integer('sort_order')->nullable();
   $t->boolean('is_won')->nullable();
   $t->boolean('is_lost')->nullable();
   $t->unsignedBigInteger('created_by')->nullable();$t->timestamps();$t->softDeletes();$t->index(['tenant_id','created_at']);
  });
  Schema::create('workflows', function(Blueprint $t) {
   $t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
   $t->string('name',255);
   $t->string('trigger_entity',255);
   $t->string('trigger_event',255);
   $t->string('condition_field',255)->nullable();
   $t->string('condition_value',255)->nullable();
   $t->string('action_type',255);
   $t->string('action_value',255)->nullable();
   $t->boolean('active')->nullable();
   $t->unsignedBigInteger('created_by')->nullable();$t->timestamps();$t->softDeletes();$t->index(['tenant_id','created_at']);
  });
  Schema::create('saved_views', function(Blueprint $t) {
   $t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
   $t->string('name',255);
   $t->string('entity',255);
   $t->longText('filters')->nullable();
   $t->boolean('shared')->nullable();
   $t->unsignedBigInteger('created_by')->nullable();$t->timestamps();$t->softDeletes();$t->index(['tenant_id','created_at']);
  });
  Schema::create('custom_fields', function(Blueprint $t) {
   $t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
   $t->string('name',255);
   $t->string('entity',255);
   $t->string('field_key',255);
   $t->string('field_type',255);
   $t->boolean('required')->nullable();
   $t->unsignedBigInteger('created_by')->nullable();$t->timestamps();$t->softDeletes();$t->index(['tenant_id','created_at']);
  });
 }
 public function down(): void {
  Schema::dropIfExists('custom_fields');
  Schema::dropIfExists('saved_views');
  Schema::dropIfExists('workflows');
  Schema::dropIfExists('stages');
  Schema::dropIfExists('pipelines');
  Schema::dropIfExists('web_forms');
  Schema::dropIfExists('segments');
  Schema::dropIfExists('email_templates');
  Schema::dropIfExists('campaigns');
  Schema::dropIfExists('products');
  Schema::dropIfExists('knowledge_articles');
  Schema::dropIfExists('tickets');
  Schema::dropIfExists('meetings');
  Schema::dropIfExists('tasks');
  Schema::dropIfExists('deals');
  Schema::dropIfExists('leads');
  Schema::dropIfExists('companies');
  Schema::dropIfExists('contacts');
 }
};
