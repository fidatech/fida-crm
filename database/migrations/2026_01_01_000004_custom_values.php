<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::create('custom_field_values',function(Blueprint $t){$t->id();$t->foreignId('tenant_id')->constrained()->cascadeOnDelete();$t->unsignedBigInteger('field_id');$t->string('entity');$t->unsignedBigInteger('entity_id');$t->longText('value')->nullable();$t->timestamps();$t->unique(['tenant_id','field_id','entity','entity_id'],'unique_custom_field_value');});}
 public function down():void {Schema::dropIfExists('custom_field_values');}
};
