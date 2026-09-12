<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration {public function up(): void {Schema::create('editor_sessions',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->string('token',64)->unique();$t->timestamp('last_seen_at');$t->timestamp('expires_at')->index();$t->timestamps();});}public function down(): void {Schema::dropIfExists('editor_sessions');}};
