<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateObjetivoDeDesenvolvimentoSustentavelEventoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('objetivo_de_desenvolvimento_sustentavel_evento', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->integer('objetivo_de_desenvolvimento_sustentavel_id');
            $table->foreign('objetivo_de_desenvolvimento_sustentavel_id')->references('id')->on('objetivo_de_desenvolvimento_sustentavels');

            $table->integer('evento_id');
            $table->foreign('evento_id')->references('id')->on('eventos');
            
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
        Schema::dropIfExists('objetivo_de_desenvolvimento_sustentavel_evento');
    }
}
