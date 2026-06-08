<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('data_rows');
        Schema::dropIfExists('data_types');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('translations');
    }

    public function down(): void {}
};
