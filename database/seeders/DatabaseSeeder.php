<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(UsuarioSeeder::class);
        $this->call(AdministradorSeeder::class);
        $this->call(AdministradorResponsavelSeeder::class);
        $this->call(ProponenteSeeder::class);
        $this->call(GrandeAreaSeeder::class);
        $this->call(AreaSeeder::class);
        $this->call(SubAreaSeeder::class);
        $this->call(FuncaoParticipanteSeeder::class);
        $this->call(CoordenadorComissaoSeeder::class);
        $this->call(ParticipanteSeeder::class);
        $this->call(NaturezaSeeder::class);
        $this->call(RecomendacaoSeeder::class);
        $this->call(AvaliadorSeeder::class);
        $this->call(AreaTematicaSeeder::class);
        $this->call(CursoSeeder::class);
        $this->call(EventoSeeder::class);
        $this->call(TrabalhoSeeder::class);
        $this->call(ArquivoSeeder::class);
        $this->call(CampoAvaliacaoSeeder::class);
        $this->call(AvaliadorTrabalhoSeeder::class);
        $this->call(AvaliadorEventoSeeder::class);
        $this->call(AvaliacaoTrabalhosSeeder::class);
        $this->call(AvaliacaoRelatorioSeeder::class);
        $this->call(OdsSeeder::class);
    }
}
