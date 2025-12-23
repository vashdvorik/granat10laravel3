<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->increments('id');

// Уменьшаем длину строк, чтобы составной индекс влез в 1000 байт
            $table->string('table_name', 100);
            $table->string('column_name', 100);
            $table->integer('foreign_key')->unsigned();
            $table->string('locale', 10);

            $table->text('value');

            // Короткое имя для индекса, чтобы избежать проблем с длиной самого названия ключа
            $table->unique(['table_name', 'column_name', 'foreign_key', 'locale'], 'translations_unique_index');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('translations');
    }
}
