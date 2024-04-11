<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OdsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('objetivo_de_desenvolvimento_sustentavels')->insert([
            'nome' => 'Erradicação da Pobreza'
        ]);

        DB::table('objetivo_de_desenvolvimento_sustentavels')->insert([
            'nome' => 'Fome Zero e Agricultura Sustentável'
        ]);

        DB::table('objetivo_de_desenvolvimento_sustentavels')->insert([
            'nome' => 'Saúde e Bem-Estar'
        ]);

        DB::table('objetivo_de_desenvolvimento_sustentavels')->insert([
            'nome' => 'Educação de Qualidade'
        ]);

        DB::table('objetivo_de_desenvolvimento_sustentavels')->insert([
            'nome' => 'Igualdade de Gênero'
        ]);

        DB::table('objetivo_de_desenvolvimento_sustentavels')->insert([
            'nome' => 'Água Potável e Saneamento'
        ]);

        DB::table('objetivo_de_desenvolvimento_sustentavels')->insert([
            'nome' => 'Energia Limpa e Acessível'
        ]);

        DB::table('objetivo_de_desenvolvimento_sustentavels')->insert([
            'nome' => 'Trabalho Decente e Crescimento Econômico'
        ]);

        DB::table('objetivo_de_desenvolvimento_sustentavels')->insert([
            'nome' => 'Indústria, Inovação e Infraestrutura'
        ]);

        DB::table('objetivo_de_desenvolvimento_sustentavels')->insert([
            'nome' => 'Redução das Desigualdades'
        ]);

        DB::table('objetivo_de_desenvolvimento_sustentavels')->insert([
            'nome' => 'Cidades e Comunidades Sustentáveis'
        ]);

        DB::table('objetivo_de_desenvolvimento_sustentavels')->insert([
            'nome' => 'Consumo e Produção Responsáveis'
        ]);

        DB::table('objetivo_de_desenvolvimento_sustentavels')->insert([
            'nome' => 'Ação Contra a Mudança Global do Clima'
        ]);

        DB::table('objetivo_de_desenvolvimento_sustentavels')->insert([
            'nome' => 'Vida na Água'
        ]);

        DB::table('objetivo_de_desenvolvimento_sustentavels')->insert([
            'nome' => 'Vida Terrestre'
        ]);

        DB::table('objetivo_de_desenvolvimento_sustentavels')->insert([
            'nome' => 'Paz, Justiça e Instituições Eficazes'
        ]);

        DB::table('objetivo_de_desenvolvimento_sustentavels')->insert([
            'nome' => 'Parcerias e Meios de Implementação'
        ]);
    }
}
