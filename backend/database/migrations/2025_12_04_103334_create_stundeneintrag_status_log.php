<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stundeneintrag_status_log', function (Blueprint $table) {
            $table->id('ID'); // Primary Key

            // Verknüpfung zum Stundeneintrag
            $table->unsignedBigInteger('fk_stundeneintragID');
            $table->foreign('fk_stundeneintragID')
                ->references('EintragID')
                ->on('stundeneintrag')
                ->onDelete('cascade');

            // Verknüpfung zur Status Definition
            $table->unsignedBigInteger('fk_statusID');
            $table->foreign('fk_statusID')
                ->references('StatusID')
                ->on('status_definition');

            // Verknüpfung zum User (Bearbeiter)
            $table->unsignedBigInteger('modifiedBy')->nullable();
            $table->foreign('modifiedBy')->references('UserID')->on('user');

            $table->timestamp('modifiedAt')->useCurrent();
            $table->text('kommentar')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stundeneintrag_status_log');
    }
};
