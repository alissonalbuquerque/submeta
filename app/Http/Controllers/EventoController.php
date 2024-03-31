<?php

namespace App\Http\Controllers;

use App\Area;
use App\Atividade;
use App\Evento;
use App\Coautor;
use App\Revisor;
use App\Atribuicao;
use App\Modalidade;
use App\ComissaoEvento;
use App\User;
use App\Proponente;
use App\Trabalho;
use App\AreaModalidade;
use App\Natureza;
use App\CoordenadorComissao;
use App\CampoAvaliacao;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Rules\ExcelRule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Endereco;
use App\Mail\EventoCriado;
use geekcom\ValidatorDocs\Rules\Ddd;
use Illuminate\Support\Facades\Mail;
use ZipArchive;
use Illuminate\Validation\Rule;


class EventoController extends Controller
{
    public function index(Request $request)
    {
        if($request->buscar == null){
            $eventos = Evento::all()->sortBy('nome');
            // $comissaoEvento = ComissaoEvento::all();
            // $eventos = Evento::where('coordenadorId', Auth::user()->id)->get();
            $hoje = Carbon::today('America/Recife');
            $hoje = $hoje->toDateString();
            return view('coordenador.home',['eventos'=>$eventos, 'hoje'=>$hoje, 'palavra'=>'', 'flag'=>'false']);
        }else{
            $eventos = Evento::where('nome','ilike','%'.$request->buscar.'%')->get();
            $hoje = Carbon::today('America/Recife');
            $hoje = $hoje->toDateString();
            return view('coordenador.home',['eventos'=>$eventos, 'hoje'=>$hoje, 'palavra'=>$request->buscar, 'flag'=>'true']);
        }

    }

    public function listar()
    {
        //
        $eventos = Evento::all()->sortBy('nome');
        // $comissaoEvento = ComissaoEvento::all();
        // $eventos = Evento::where('coordenadorId', Auth::user()->id)->get();

        return view('evento.listarEvento',['eventos'=>$eventos]);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $coordenadors = CoordenadorComissao::with('user')->get();
        $naturezas = Natureza::orderBy('nome')->get();
        $yesterday = Carbon::yesterday('America/Recife');
        $yesterday = $yesterday->toDateString();
        return view('evento.criarEvento', ['coordenadors' => $coordenadors, 'naturezas' => $naturezas, 'ontem' => $yesterday]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        /** @var Carbon */
        $mytime = Carbon::now('America/Recife');

        /** @var Carbon */
        $yesterday = Carbon::yesterday('America/Recife');

        /** @var string */
        $yesterday = $yesterday->toDateString();

        /** @var integer */
        $user_id = Auth()->user()->id;

        if(isset($request->docTutorial)){
            $request->validate([
                'docTutorial' => ['file', 'max:2048', new ExcelRule($request->file('docTutorial'))],
            ]);
        }

        //--Salvando os anexos da submissão temporariamente
        $evento = $this->armazenarAnexosTemp($request);

        // validar datas nulas antes, pois pode gerar um bug
        if(!isset($request->inicioSubmissao) || !isset($request->fimSubmissao) || !isset($request->inicioRevisao) || !isset($request->fimRevisao) || !isset($request->resultado))
        {   
            $validatedData = $request->validate([
                'nome'                => ['required', 'string'],
                'descricao'           => ['required', 'string'],
                'tipo'                => ['required', 'string'],
                'natureza'            => ['required'],
                'coordenador_id'      => ['required'],
                'tipoAvaliacao'       => ['required'],
                'inicioSubmissao'     => ['required', 'date'],
                'fimSubmissao'        => ['required', 'date'],
                'inicioRevisao'       => ['required', 'date'],
                'fimRevisao'          => ['required', 'date'],
                'inicio_recurso'      => ['required', 'date'],
                'fim_recurso'         => ['required', 'date'],
                'resultado_final'     => ['required', 'date'],
                'resultado_preliminar'=> ['required', 'date'],

                'dt_inicioRelatorioParcial'  => $request->dt_inicioRelatorioParcial?['date']:[],
                'dt_fimRelatorioParcial'     => $request->dt_fimRelatorioParcial?['date']:[],
                'dt_inicioRelatorioFinal'  => $request->dt_inicioRelatorioFinal?['date']:[],
                'dt_fimRelatorioFinal'     => $request->dt_fimRelatorioFinal?['date']:[],
                'pdfEdital'           => [($request->pdfEditalPreenchido!=='sim'?'required':''), 'file', 'mimes:pdf', 'max:2048'],
                'nome_docExtra'       => [Rule::requiredIf($request->check_docExtra != null), 'max:255'],
                'pdfRecurso'        => [($request->pdfRecursoPreenchido !== 'sim' ? 'required' : ''), 'file', 'mimes:pdf', 'max:2048'],
                'modeloDocumento'   => [($request->modeloDocumentoPreenchido!=='sim'?'required':''), 'file', 'mimes:pdf', 'max:2048'],
            ]);
        }

        //validacao normal
        $validatedData = $request->validate([
            'nome'                => ['required', 'string'],
            'descricao'           => ['required', 'string','max:1500'],
            'tipo'                => ['required', 'string'],
            'natureza'            => ['required'],
            'coordenador_id'      => ['required'],
            'nome_docExtra'       => [Rule::requiredIf($request->check_docExtra != null),'max:255'],

            'inicioSubmissao'     => ['required', 'date', 'after:yesterday'],
            'fimSubmissao'        => ['required', 'date', 'after_or_equal:inicioSubmissao'],
            'inicioRevisao'       => ['required', 'date', 'after:yesterday'],
            'fimRevisao'          => ['required', 'date', 'after:inicioRevisao', 'after:fimSubmissao'],
            'resultado_preliminar'=> ['required', 'date', 'after_or_equal:fimRevisao'],
            'inicio_recurso'      => ['required', 'date', 'after_or_equal:resultado_preliminar'],
            'fim_recurso'         => ['required', 'date', 'after:inicio_recurso'],
            'resultado_final'     => ['required', 'date', 'after:fim_recurso'],
            'dt_inicioRelatorioParcial'  => $request->dt_inicioRelatorioParcial?['date', 'after:resultado_preliminar']:[],
            'dt_fimRelatorioParcial'     => $request->dt_fimRelatorioParcial?['date', 'after_or_equal:dt_inicioRelatorioParcial']:[],
            'dt_inicioRelatorioFinal'  => $request->dt_inicioRelatorioFinal?['date', 'after:dt_fimRelatorioParcial']:[],
            'dt_fimRelatorioFinal'     => $request->dt_fimRelatorioFinal?['date', 'after_or_equal:dt_inicioRelatorioFinal']:[],
            'pdfEdital'           => [($request->pdfEditalPreenchido!=='sim'?'required':''), 'file', 'mimes:pdf', 'max:2048'],
            'pdfRecurso'        => [($request->pdfRecursoPreenchido!=='sim'?'required':''), 'file', 'mimes:doc,docx,pdf', 'max:2048'],
            'modeloDocumento'   => [($request->modeloDocumentoPreenchido!=='sim'?'required':''), 'file', 'mimes:doc,docx,pdf', 'max:2048'],
        ]);


        // Validação quando avaliação for por Barema
        $validateCampo = $request->validate([
            'inputField.*.nome'        => ['required', 'string'],
            'inputField.*.nota_maxima' => ['required'],
            'inputField.*.prioridade'  => ['required'],
            'somaNotas'                => ['required', 'numeric', 'max:' . $request->pontuacao, 'min:' . $request->pontuacao],
            ['somaNotas.*'        => 'A soma das notas máximas deve ser igual a pontuação total definida.']
        ]);
        
        // Mount Evento Array
        $evento['nome']                = $request->nome;
        $evento['descricao']           = $request->descricao;
        $evento['tipo']                = $request->tipo;
        $evento['natureza_id']         = $request->natureza;
        if($request->check_docExtra != null){
            $evento['nome_docExtra']   = $request->nome_docExtra;
        }
        $evento['inicioSubmissao']     = $request->inicioSubmissao;
        $evento['fimSubmissao']        = $request->fimSubmissao;
        $evento['inicioRevisao']       = $request->inicioRevisao;
        $evento['fimRevisao']          = $request->fimRevisao;
        $evento['inicio_recurso']      = $request->inicio_recurso;
        $evento['fim_recurso']         = $request->fim_recurso;
        $evento['resultado_preliminar']= $request->resultado_preliminar;
        $evento['resultado_final']     = $request->resultado_final;

        if ($request->dt_inicioRelatorioParcial) {
            $evento['dt_inicioRelatorioParcial']  = $request->dt_inicioRelatorioParcial;
        }
        if ($request->dt_fimRelatorioParcial) {
            $evento['dt_fimRelatorioParcial']     = $request->dt_fimRelatorioParcial;
        }
        if ($request->dt_inicioRelatorioFinal) {
            $evento['dt_inicioRelatorioFinal']  = $request->dt_inicioRelatorioFinal;
        }
        if ($request->dt_fimRelatorioFinal) {
            $evento['dt_fimRelatorioFinal']     = $request->dt_fimRelatorioFinal;
        }

        $evento['coordenadorId']       = $request->coordenador_id;
        $evento['criador_id']          = $user_id;
        $evento['numParticipantes']    = $request->numParticipantes;
        $evento['consu']               = $request->has('consu');
        $evento['cotaDoutor']               = $request->has('cotaDoutor');
        $evento['obrigatoriedade_docExtra'] = $request->has('obrigatoriedade_docExtra');
        $evento['anexosStatus']        = 'final';
        $evento['tipoAvaliacao']       = 'campos';

        //-- Salvando anexos finais
        if(isset($request->pdfEdital)){
            $pdfEdital = $request->pdfEdital;
            $path = 'pdfEdital/' . $evento->id . '/';
            $nome = "edital.pdf";
            Storage::putFileAs($path, $pdfEdital, $nome);
            $evento->pdfEdital = $path . $nome;
        }

        if(isset($request->modeloDocumento)){
            $modeloDocumento = $request->modeloDocumento;
            $extension = $modeloDocumento->extension();
            $path = 'modeloDocumento/' . $evento->id . '/';
            $nome = "modelo" . "." . $extension;
            Storage::putFileAs($path, $modeloDocumento, $nome);

            $evento->modeloDocumento = $path . $nome;
        }

        if(isset($request->pdfFormAvalRelatorio)){
            $pdfFormAvalRelatorio = $request->pdfFormAvalRelatorio;
            $extension = $pdfFormAvalRelatorio->extension();
            $path = 'pdfFormAvalRelatorio/' . $evento->id . '/';
            $nome = "formulario de avaliação do relatorio" . "." . $extension;
            Storage::putFileAs($path, $pdfFormAvalRelatorio, $nome);

            $evento->formAvaliacaoRelatorio = $path . $nome;
        }

        if(isset($request->pdfRecurso)){
            $pdfRecurso = $request->pdfRecurso;
            $extension = $pdfRecurso->extension();
            $path = 'pdfRecurso/' . $evento->id . '/';
            $nome = "recurso" . "." . $extension;
            Storage::putFileAs($path, $pdfRecurso, $nome);

            $evento->docRecurso = $path . $nome;
        }

        if(isset($request->pdfRelatorio)){
            $pdfRelatorio = $request->pdfRelatorio;
            $extension = $pdfRelatorio->extension();
            $path = 'pdfRelatorio/' . $evento->id . '/';
            $nome = "relatorio" . "." . $extension;
            Storage::putFileAs($path, $pdfRelatorio, $nome);

            $evento->docRelatorio = $path . $nome;
        }

        $evento->update();
        
        // Criando campos de avaliacao
        if ($request->tipoAvaliacao == 'campos') {
            if($request->has('campos')){
                foreach ($request->get('campos') as $key => $value) {
                    $campoAval = new CampoAvaliacao();
                    $campoAval->nome = $request->inputField[$value]['nome'];
                    $campoAval->nota_maxima = $request->inputField[$value]['nota_maxima'];
                    if ($request->inputField[$value]['descricao'] != null){
                        $campoAval->descricao = $request->inputField[$value]['descricao'];
                    }
                    $campoAval->prioridade = $request->inputField[$value]['prioridade'];
                    $campoAval->evento_id = $evento->id;
                    $campoAval->save();
                }
            }
        }

        return redirect()->route('admin.editais')->with(['mensagem' => 'Edital criado com sucesso!']);
    }

    /** @return Evento */
    public function armazenarAnexosTemp(Request $request){

        //---Anexos do Projeto
        $eventoTemp = Evento::where('criador_id', Auth::user()->id)->where('anexosStatus', 'temporario')
            ->orderByDesc('updated_at')->first();

        if($eventoTemp == null){
            $eventoTemp = new Evento();
            $eventoTemp->criador_id = Auth::user()->id;
            $eventoTemp->anexosStatus = 'temporario';
            $eventoTemp->save();
        }

        if(!(is_null($request->pdfEdital)) ) {
            $pasta = 'pdfEdital/' . $eventoTemp->id;
            $eventoTemp->pdfEdital = Storage::putFileAs($pasta, $request->pdfEdital, 'edital.pdf');
        }
        if (!(is_null($request->modeloDocumento))) {
            $extension = $request->modeloDocumento->extension();
            $path = 'modeloDocumento/' . $eventoTemp->id;
            $nome = "modelo" . "." . $extension;
            $eventoTemp->modeloDocumento = Storage::putFileAs($path, $request->modeloDocumento, $nome);
        }
        
        if(!(is_null($request->pdfFormAvalRelatorio)) ) {
            $pasta = 'pdfFormAvalRelatorio/' . $eventoTemp->id;
            $eventoTemp->formAvaliacaoRelatorio = Storage::putFileAs($pasta, $request->pdfFormAvalRelatorio, 'formulario de avaliação do relatorio.pdf');
        }

        if(!(is_null($request->pdfRecurso)) ) {
            $pasta = 'pdfRecurso/' . $eventoTemp->id;
            $nome = "recurso" . "." . $extension;
            $eventoTemp->docRecurso = Storage::putFileAs($pasta, $request->pdfRecurso, $nome);
        }
        if(!(is_null($request->pdfRelatorio)) ) {
            $pasta = 'pdfRelatorio/' . $eventoTemp->id;
            $nome = "relatorio" . "." . $extension;
            $eventoTemp->docRelatorio = Storage::putFileAs($pasta, $request->pdfRelatorio, $nome);
        }

        $eventoTemp->update();

        return $eventoTemp;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Evento  $evento
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $evento = Evento::find($id);
        $proponente = Proponente::where('user_id', Auth::user()->id)->first();
        if($proponente != null){
            $hasTrabalho = false;
            $hasFile = false;
            $trabalhos = $proponente->trabalhos()->where('evento_id', $evento->id )->get();
            $trabalhosCount = $proponente->trabalhos()->where('evento_id', $evento->id )->count();

            if($trabalhosCount != 0){
                $hasTrabalho = true;
                $hasFile = true;
            }
        }else{
            $hasTrabalho = false;
            $hasFile = false;
            $trabalhos = 0;
            $trabalhosCount = 0;
        }

        $trabalhosId = Trabalho::where('evento_id', $evento->id)->select('id')->get();

        
        $hoje = Carbon::today('America/Recife');
        $hoje = $hoje->toDateString();
        // dd(false);
        return view('evento.visualizarEvento', [
            'evento'              => $evento,
            'trabalhos'           => $trabalhos,
            // 'trabalhosCoautor'    => $trabalhosCoautor,
            'hasTrabalho'         => $hasTrabalho,
            // 'hasTrabalhoCoautor'  => $hasTrabalhoCoautor,
            'hasFile'             => $hasFile,
            'hoje'              => $hoje
        ]);
    }

    public function showNaoLogado($id)
    {
        $evento = Evento::find($id);
        $hasTrabalho = false;
        $hasTrabalhoCoautor = false;
        $hasFile = false;
        $trabalhos = null;
        $trabalhosCoautor = null;

        $hoje = Carbon::today('America/Recife');
        $hoje = $hoje->toDateString();
        // dd(false);
        return view('evento.visualizarEvento', [
            'evento'              => $evento,
            'trabalhos'           => $trabalhos,
            'trabalhosCoautor'    => $trabalhosCoautor,
            'hasTrabalho'         => $hasTrabalho,
            'hasTrabalhoCoautor'  => $hasTrabalhoCoautor,
            'hasFile'             => $hasFile,
            'hoje'              => $hoje
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Evento  $evento
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $evento = Evento::find($id);
        $coordenadors = CoordenadorComissao::with('user')->get();
        $coordEvent = CoordenadorComissao::find($evento->coordenadorId);
        $naturezas = Natureza::orderBy('nome')->get();
        $yesterday = Carbon::yesterday('America/Recife');
        $yesterday = $yesterday->toDateString();
        $camposAvaliacao = CampoAvaliacao::where('evento_id', $id)->get();

        $pontuacao = 0;
        foreach ($camposAvaliacao as $campo) {
            $pontuacao += $campo->nota_maxima;
        }

        return view('evento.editarEvento',['evento'=>$evento,
            'coordenadores'=>$coordenadors,
            'naturezas'=>$naturezas,
            'ontem'=>$yesterday,
            'coordEvent'=>$coordEvent,
            'camposAvaliacao'=>$camposAvaliacao,
            'pontuacao'=>$pontuacao]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Evento  $evento
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {   
        $tipo_usuario = Auth()->user()->tipo;
        
        $evento = Evento::find($id);
        $yesterday = Carbon::yesterday('America/Recife');
        $yesterday = $yesterday->toDateString();
        $camposAvaliacao = CampoAvaliacao::where('evento_id', $id);
        if(!isset($request->inicioSubmissao) || !isset($request->fimSubmissao) || !isset($request->inicioRevisao) || !isset($request->fimRevisao) || !isset($request->resultado)) {
            if($request->tipo == 'CONTINUO'){
                $validatedData = $request->validate(Evento::$continuos_dates_rules);
            } else {
                $validatedData = $request->validate(Evento::$dates_rules);
            }
        }

        if($request->tipo == 'CONTINUO'){
            $validatedData = $request->validate(Evento::$continuos_edit_rules);
        } else {
            $validatedData = $request->validate(Evento::$edit_rules);
        }

        if($request->tipo != 'CONTINUO')
        {
            $validateCampo = $request->validate([
                'inputField.*.nome'        => ['required', 'string'],
                'inputField.*.nota_maxima' => ['required'],
                'inputField.*.prioridade'  => ['required'],
                'somaNotas'                => ['required', 'numeric', 'max:' . $request->pontuacao, 'min:' . $request->pontuacao],
                ['somaNotas.*'        => 'A soma das notas máximas deve ser igual a pontuação total definida.']
            ]);

            $evento->inicioRevisao        = $request->inicioRevisao;
            $evento->fimRevisao           = $request->fimRevisao;
            $evento->inicio_recurso       = $request->inicio_recurso;
            $evento->fim_recurso          = $request->fim_recurso;
            $evento->resultado_preliminar = $request->resultado_preliminar;
            $evento->resultado_final      = $request->resultado_final;
            $evento->dt_inicioRelatorioParcial   = $request->dt_inicioRelatorioParcial;
            $evento->dt_fimRelatorioParcial      = $request->dt_fimRelatorioParcial;
            $evento->dt_inicioRelatorioFinal   = $request->dt_inicioRelatorioFinal;
            $evento->dt_fimRelatorioFinal      = $request->dt_fimRelatorioFinal;
            $evento->cotaDoutor                = $request->has('cotaDoutor');
            $evento->tipoAvaliacao       = 'campos';
        }

        $evento->nome                 = $request->nome;
        $evento->descricao            = $request->descricao;
        $evento->tipo                 = $request->tipo;
        $evento->natureza_id          = $request->natureza;
        $evento->numParticipantes     = $request->numParticipantes;
        if($request->check_docExtra != null){
            $evento->nome_docExtra    = $request->nome_docExtra;
        }else{
            $evento->nome_docExtra    = null;
        }
        
        $evento->inicioSubmissao      = $request->inicioSubmissao;
        $evento->fimSubmissao         = $request->fimSubmissao;        
        $evento->coordenadorId        = $request->coordenador_id;
        $evento->consu                = $request->has('consu');
        $evento->obrigatoriedade_docExtra                = $request->has('obrigatoriedade_docExtra');

        if($request->pdfEdital != null){
            $pdfEdital = $request->pdfEdital;
            $path = 'pdfEdital/' . $evento->id . '/';
            $nome = "edital.pdf";
            Storage::putFileAs($path, $pdfEdital, $nome);
        }

        if($request->modeloDocumento != null){
            $count = count($request->modeloDocumento);
            $zip = new ZipArchive;
            $filename = "storage/app/modeloDocumento/$evento->id/modelo.zip";

            // Crie o diretório se ele não existir
            if (!file_exists("storage/app/modeloDocumento/$evento->id")) {
                mkdir("storage/app/modeloDocumento/$evento->id", 0777, true);
            }
            $zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            for ($i = 0; $i < $count; $i++) {
                $zip->addFile($request->modeloDocumento[$i]->getRealPath(), $request->modeloDocumento[$i]->getClientOriginalName());
            }
            $zip->close();
            $evento->modeloDocumento = $filename;
            $evento->save();
        }

        if(isset($request->pdfFormAvalRelatorio)){
            $pdfFormAvalRelatorio = $request->pdfFormAvalRelatorio;
            $extension = $pdfFormAvalRelatorio->extension();
            $path = 'pdfFormAvalRelatorio/' . $evento->id . '/';
            $nome = "formulario de avaliação do relatorio" . "." . $extension;
            Storage::putFileAs($path, $pdfFormAvalRelatorio, $nome);

            $evento->formAvaliacaoRelatorio = $path . $nome;
        }

        // Editando campos de avaliacao
        if ($request->tipoAvaliacao == 'campos') {
            if($request->has('campos')){
                $camposAvaliacao->forceDelete();
                foreach ($request->get('campos') as $key => $value) {
                    $campoAval = new CampoAvaliacao();
                    $campoAval->nome = $request->inputField[$value]['nome'];
                    $campoAval->nota_maxima = $request->inputField[$value]['nota_maxima'];
                    if ($request->inputField[$value]['descricao'] != null){
                        $campoAval->descricao = $request->inputField[$value]['descricao'];
                    }
                    $campoAval->prioridade = $request->inputField[$value]['prioridade'];
                    $campoAval->evento_id = $evento->id;
                    $campoAval->save();
                }
            }
        }

        // Mudança de tipo de avaliação
        if ($request->tipoAvaliacao != 'campos') {
            //Apaga campos de avaliacao
            $camposAvaliacao->forceDelete();
        }

        $evento->update();

        $eventos = Evento::orderBy('nome')->get();
        //dd('FINAL');
        if($tipo_usuario == 'coordenador'){
            return redirect( route('coordenador.editais') )->with(['mensagem' => 'Edital salvo com sucesso!', 'eventos'=>$eventos]);
        }


        return redirect( route('admin.editais') )->with(['mensagem' => 'Edital salvo com sucesso!', 'eventos'=>$eventos]);
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Evento  $evento
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $evento = Evento::find($id);

        // $areas = Area::where('eventoId', $id);
        $atividades = Atividade::where('eventoId', $id);
        $comissao = ComissaoEvento::where('eventosId', $id);
        $revisores = Revisor::where('eventoId', $id);
        $trabalhos = Trabalho::where('evento_id', $id);
        $camposAvaliacao = CampoAvaliacao::where('evento_id', $id);

        // if(isset($areas)){
        //     $areas->delete();
        // }
        if(isset($atividades)){
            $atividades->delete();
        }
        if(isset($comissao)){
            $comissao->delete();
        }
        if(isset($revisores)){
            $revisores->delete();
        }
        if(isset($trabalhos)){
            $trabalhos->delete();
            Trabalho::withTrashed()->where('evento_id', $id)->update(['evento_id' => null]);
        }
        if(isset($camposAvaliacao)){
            $camposAvaliacao->delete();
            CampoAvaliacao::withTrashed()->where('evento_id', $id)->update(['evento_id' => null]);
        }

        $pdfEditalPath = 'pdfEdital/' . $evento->id;
        if (Storage::disk()->exists($pdfEditalPath)) {
            Storage::deleteDirectory($pdfEditalPath);
        }

        $modeloDocumentoPath = 'modeloDocumento/' . $evento->id;
        if (Storage::disk()->exists($modeloDocumentoPath)) {
            Storage::deleteDirectory($modeloDocumentoPath);
        }

        $evento->delete();

        return redirect()->back()->with(['mensagem' => 'Edital deletado com sucesso!']);
    }

    public function detalhes(Request $request){
        $evento = Evento::find($request->eventoId);
        $this->authorize('isCoordenador', $evento);

        $ComissaoEvento = ComissaoEvento::where('eventosId',$evento->id)->get();
        // dd($ComissaoEventos);
        $ids = [];
        foreach($ComissaoEvento as $ce){
            array_push($ids,$ce->userId);
        }
        $users = User::find($ids);

        $areas = Area::where('eventoId', $evento->id)->get();
        $areasId = Area::where('eventoId', $evento->id)->select('id')->get();
        $trabalhosId = Trabalho::whereIn('areaId', $areasId)->select('id')->get();
        $revisores = Revisor::where('eventoId', $evento->id)->get();
        $modalidades = Modalidade::all();
        $areaModalidades = AreaModalidade::whereIn('areaId', $areasId)->get();
        $trabalhos = Trabalho::whereIn('areaId', $areasId)->orderBy('id')->get();
        $trabalhosEnviados = Trabalho::whereIn('areaId', $areasId)->count();
        $trabalhosPendentes = Trabalho::whereIn('areaId', $areasId)->where('avaliado', 'processando')->count();
        $trabalhosAvaliados = Atribuicao::whereIn('trabalhoId', $trabalhosId)->where('parecer', '!=', 'processando')->count();

        $numeroRevisores = Revisor::where('eventoId', $evento->id)->count();
        $numeroComissao = ComissaoEvento::where('eventosId',$evento->id)->count();
        // $atribuicoesProcessando;
        // dd($trabalhosEnviados);
        $revs = Revisor::where('eventoId', $evento->id)->with('user')->get();

        return view('coordenador.detalhesEvento', [
            'evento'                  => $evento,
            'areas'                   => $areas,
            'revisores'               => $revisores,
            'revs'                    => $revs,
            'users'                   => $users,
            'modalidades'             => $modalidades,
            'areaModalidades'         => $areaModalidades,
            'trabalhos'               => $trabalhos,
            'trabalhosEnviados'       => $trabalhosEnviados,
            'trabalhosAvaliados'      => $trabalhosAvaliados,
            'trabalhosPendentes'      => $trabalhosPendentes,
            'numeroRevisores'         => $numeroRevisores,
            'numeroComissao'          => $numeroComissao
        ]);
    }

    public function numTrabalhos(Request $request){
        $evento = Evento::find($request->eventoId);
        $this->authorize('isCoordenador', $evento);
        $validatedData = $request->validate([
            'eventoId'                => ['required', 'integer'],
            'trabalhosPorAutor'       => ['required', 'integer'],
            'numCoautor'              => ['required', 'integer']
        ]);

        $evento->numMaxTrabalhos = $request->trabalhosPorAutor;
        $evento->numMaxCoautores = $request->numCoautor;
        $evento->save();

        return redirect()->route('coord.detalhesEvento', ['eventoId' => $request->eventoId]);
    }

    public function setResumo(Request $request){
        $evento = Evento::find($request->eventoId);
        $this->authorize('isCoordenador', $evento);
        $validatedData = $request->validate([
            'eventoId'                => ['required', 'integer'],
            'hasResumo'               => ['required', 'string']
        ]);
        if($request->hasResumo == 'true'){
            $evento->hasResumo = true;
        }
        else{
            $evento->hasResumo = false;
        }

        $evento->save();
        return redirect()->route('coord.detalhesEvento', ['eventoId' => $request->eventoId]);
    }

    public function setFotoEvento(Request $request){
        $evento = Evento::find($request->eventoId);
        $this->authorize('isCoordenador', $evento);
        // dd($request);
        $validatedData = $request->validate([
            'eventoId'                => ['required', 'integer'],
            'fotoEvento'              => ['required', 'file', 'mimes:png']
        ]);

        $file = $request->fotoEvento;
        $path = 'public/eventos/' . $evento->id;
        $nome = '/logo.png';
        Storage::putFileAs($path, $file, $nome);
        $evento->fotoEvento = $path . $nome;
        $evento->save();
        return redirect()->route('coord.detalhesEvento', ['eventoId' => $request->eventoId]);
    }

    public function areaParticipante() {

        $eventos = Evento::all();

        return view('user.areaParticipante',['eventos'=>$eventos]);

    }

    public function listComissao() {

        $comissaoEvento = ComissaoEvento::where('userId', Auth::user()->id)->get();
        $eventos = Evento::all();
        $evnts = [];

        foreach ($comissaoEvento as $comissao) {
            foreach ($eventos as $evento) {
                if($comissao->eventosId == $evento->id){
                    array_push($evnts,$evento);
                }
            }
        }

        return view('user.comissoes',['eventos'=>$evnts]);

    }

    public function listComissaoTrabalhos(Request $request) {

        $evento = Evento::find($request->eventoId);
        $areasId = Area::where('eventoId', $evento->id)->select('id')->get();
        $trabalhos = Trabalho::whereIn('areaId', $areasId)->orderBy('id')->get();

        return view('user.areaComissao', ['trabalhos' => $trabalhos]);
    }

    public function baixarEdital($id) {
        $evento = Evento::find($id);

        if (Storage::disk()->exists($evento->pdfEdital)) {
            ob_end_clean();
            return Storage::download($evento->pdfEdital);
        }

        return abort(404);
    }

    public function baixarModelos($id)
    {
        $evento = Evento::findOrFail($id);
        $path = $evento->modeloDocumento;
        return response()->download($path);
    }

    // public function baixarModelos($id) {
    //     $evento = Evento::find($id);

    //     if (Storage::disk()->exists($evento->modeloDocumento)) {
    //         ob_end_clean();
    //         return Storage::download($evento->modeloDocumento);
    //     }

    //     return abort(404);
    // }
}
