<!DOCTYPE html>
<html>
<head>

</head>
<body>
	@if($nomeFuncao == "Avaliador")
	
		<h3>Prezado/a Avaliador/a, cordiais saudações!</h3>
		<p>
			Agradecemos a sua disponibilidade para participar do banco de avaliadores de propostas de @if($natureza == '1') Ensino @elseif($natureza=='2') Pesquisa @elseif($natureza == '3') Extensão @endif
			do {{$nomeEvento}} da Universidade de Pernambuco (UPE).
			<br><br>Solicitamos, gentilmente, que acesse o sistema Submeta através do <a href="{{ url('/') }}">LINK</a> e da senha {{$senhaTemporaria}}, para realizar o login no sistema e o seu aceite como avaliador de propostas de projetos @if($natureza == '3')de Extensão e Cultura @elseif($natureza == '1') Ensino @elseif($natureza == '2') Pesquisa @endif da UPE.

			<br><br>Por questões procedimentais, gostaríamos de solicitar que Vossa Senhoria faça o login no sistema Submeta e decida se deseja aceitar ou recusar o convite para se tornar um avaliador do edital {{$nomeEvento}}.
			
			@if($natureza == '3')
				<br><br>Desde já, agradecemos a disponibilidade de participar do banco de avaliadores Ad hoc de propostas de Extensão e Cultura da UPE.
				<br>Universidade de Pernambuco
				<br><br>Atenciosamente,

			@elseif($natureza == '2')
				<br><br>Atenciosamente,
				<br>Universidade de Pernambuco
			@else
				<br><br>Atenciosamente,
				<br>Universidade de Pernambuco
			@endif
		</p>

	@elseif($nomeFuncao == "Participante")
	
		<h3>Prezado(a)</h3>
		<p>
			{{ $nomeUsuarioPai }} convida Vossa Senhoria para integrar como Participante do projeto {{ $nomeTrabalho }} do Edital {{ $nomeEvento }}.
			Caso concorde em participar, segue a senha para se cadastrar no Sistema de Submissão de Projetos (Submeta). senha: {{$senhaTemporaria}} e o link: <a href="{{ url('/') }}">Submeta</a>.
			<br>Desde já, a UPE agradece toda a atenção dispensada por Vossa Senhoria.
		</p>


	@elseif($nomeFuncao == "Avaliador-Cadastrado")

	<h3>Prezado/a Avaliador/a, cordiais saudações!</h3>
		<p>
			Agradecemos a sua disponibilidade para participar do banco de avaliadores de propostas de @if($natureza == '1') Ensino @elseif($natureza=='2') Pesquisa @elseif($natureza == '3') Extensão @endif
			do {{$nomeEvento}} da Universidade de Pernambuco (UPE).
			<br><br>Solicitamos, gentilmente, que acesse o sistema Submeta através do <a href="{{ url('/') }}">LINK</a>, para realizar o login no sistema e o seu aceite como avaliador de propostas de projetos @if($natureza == '3')de Extensão e Cultura @elseif($natureza == '1') Ensino @elseif($natureza == '2') Pesquisa @endif da UPE.

			<br><br>Por questões procedimentais, gostaríamos de solicitar que Vossa Senhoria faça o login no sistema Submeta e decida se deseja aceitar ou recusar o convite para se tornar um avaliador do edital {{$nomeEvento}}.
			
			@if($natureza == '3')
				<br><br>Desde já, agradecemos a disponibilidade de participar do banco de avaliadores Ad hoc de propostas de Extensão e Cultura da UPE.
				<br><br>Atenciosamente,
				<br>Universidade de Pernambuco
			
			@elseif($natureza == '2')
				<br><br>Atenciosamente,
				<br>Universidade de Pernambuco
			
			@else
				<br><br>Atenciosamente,
				<br>Universidade de Pernambuco
			@endif
		</p>
	@endif
	

</body>
</html>