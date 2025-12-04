<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('status_definition', function (Blueprint $table) {
            // Im Diagramm ist StatusID der Primary Key
            $table->id('StatusID');

            $table->text('name')->nullable();
            $table->text('beschreibung')->nullable();

            // Optional: Timestamps, falls gewünscht
            // $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('status_definition');
    }
};
