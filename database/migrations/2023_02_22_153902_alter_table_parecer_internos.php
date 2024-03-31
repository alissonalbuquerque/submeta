<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableParecerInternos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('parecer_internos', function (Blueprint $table) {
            $table->string('statusLinkGrupoPesquisa')->nullable()->change();
            $table->string('statusLinkLattesProponente')->nullable()->change();

            $table->string('statusAnexoProjeto')->nullable()->change();
            $table->string('statusAnexoDecisaoCONSU')->nullable()->change();
            $table->string('statusAnexoPlanilhaPontuacao')->nullable()->change();
            $table->string('statusAnexoLattesCoordenador')->nullable()->change();
            $table->string('statusAnexoGrupoPesquisa')->nullable()->change();
            $table->string('statusAnexoAtuorizacaoComiteEtica')->nullable()->change();
            $table->string('statusJustificativaAutorizacaoEtica')->nullable()->change();
            $table->string('statusPlanoTrabalho')->nullable()->change();
            $table->string('statusParecer')->nullable()->change();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('parecer_internos', function (Blueprint $table) {
            $table->string('statusLinkGrupoPesquisa')->nullable(false)->change();
            $table->string('statusLinkLattesProponente')->nullable(false)->change();

            $table->string('statusAnexoProjeto')->nullable(false)->change();
            $table->string('statusAnexoDecisaoCONSU')->nullable(false)->change();
            $table->string('statusAnexoPlanilhaPontuacao')->nullable(false)->change();
            $table->string('statusAnexoLattesCoordenador')->nullable(false)->change();
            $table->string('statusAnexoGrupoPesquisa')->nullable(false)->change();
            $table->string('statusAnexoAtuorizacaoComiteEtica')->nullable(false)->change();
            $table->string('statusJustificativaAutorizacaoEtica')->nullable(false)->change();
            $table->string('statusPlanoTrabalho')->nullable(false)->change();
            $table->string('statusParecer')->nullable(false)->change();
        });
    }
}
