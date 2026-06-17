<?php

session_start();

if (!isset($_SESSION['nome'])) {
    header('Location: login.php');
    exit();
}

$logado = $_SESSION['nome'];

include_once('config.php');
include_once('dados_cargos.php');

$stmt = $conexao->prepare(
    "SELECT nome_usuario AS nome, data_nascimento AS datadenascimento, email
     FROM usuarios WHERE nome_usuario = ?"
);
$stmt->bind_param("s", $logado);
$stmt->execute();
$result   = $stmt->get_result();
$usuario  = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voto Consciente</title>
    <link rel="stylesheet" href="css/estilosconsciente.css">
</head>
<body>

<header>
    <div class="header-text">
        <h1>Voto Consciente</h1>
    </div>
    <div class="user-info">
        <a href="Sair.php" class="logout-link">Sair</a>
        <button id="profile-button" class="profile-toggle" type="button">
            <span id="user-name"><?= htmlspecialchars($logado) ?></span>
            <span class="profile-icon">👤</span>
        </button>
    </div>
</header>

<div id="profile-card" class="profile-card">
    <h2>Meu Perfil 👤</h2>
    <?php if ($usuario): ?>
        <p><strong>Nome:</strong>              <?= htmlspecialchars($usuario['nome']) ?></p>
        <p><strong>Data de Nascimento:</strong><?= htmlspecialchars($usuario['datadenascimento']) ?></p>
        <p><strong>Email:</strong>             <?= htmlspecialchars($usuario['email']) ?></p>
    <?php else: ?>
        <p>Dados do perfil não encontrados.</p>
    <?php endif; ?>
</div>

<div class="janela">

    <aside>
        <!--
            CORREÇÃO: adicionado name="cargo[]", name="perfil[]", name="proposta[]"
            e value em cada checkbox.
            O [] no name indica que podem vir múltiplos valores.
            O JS lê esses valores e envia para o endpoint.
        -->
        <form id="form-filtros">

            <div class="cargo">
                <h2>Cargo</h2>
                <div class="checkbox-row">
                    <input type="checkbox" name="cargo[]" value="Governador" id="governador">
                    <label for="governador">Governador</label>
                </div>
                <div class="checkbox-row">
                    <input type="checkbox" name="cargo[]" value="Senador" id="senador">
                    <label for="senador">Senador</label>
                </div>
            </div>

            <div class="perfil">
                <h2>Perfil</h2>
                <div class="checkbox-row">
                    <input type="checkbox" name="perfil[]" value="negro" id="negro">
                    <label for="negro">Negro(a)</label>
                </div>
                <div class="checkbox-row">
                    <input type="checkbox" name="perfil[]" value="mulher" id="mulher">
                    <label for="mulher">Mulher</label>
                </div>
                <div class="checkbox-row">
                    <input type="checkbox" name="perfil[]" value="lgbtqia" id="lgbtqia">
                    <label for="lgbtqia">LGBTQIA+</label>
                </div>
                <div class="checkbox-row">
                    <input type="checkbox" name="perfil[]" value="indigena" id="indigena">
                    <label for="indigena">Indígena</label>
                </div>
            </div>

            <div class="proposta">
                <h2>Proposta</h2>
                <div class="checkbox-row">
                    <input type="checkbox" name="proposta[]" value="educacao" id="educacao">
                    <label for="educacao">Educação</label>
                </div>
                <div class="checkbox-row">
                    <input type="checkbox" name="proposta[]" value="saude" id="saude">
                    <label for="saude">Saúde</label>
                </div>
                <div class="checkbox-row">
                    <input type="checkbox" name="proposta[]" value="meio-ambiente" id="meio-ambiente">
                    <label for="meio-ambiente">Meio Ambiente</label>
                </div>

                <div class="checkbox-row">
                    <input type="checkbox" name="proposta[]" value="cultura" id="cultura">
                    <label for="cultura">Cultura</label>
                </div>

                <div class="checkbox-row">
                    <input type="checkbox" name="proposta[]" value="seguranca" id="seguranca">
                    <label for="cultura">Segurança</label>
                </div>
            </div>

</form>
    </aside>

    <main id="main">
        <div class="top-actions">
            <button type="button" id="btn-buscar" class="btn-buscar">Buscar Candidatos</button>
            <button type="button" id="btn-limpar" class="btn-limpar">Limpar Filtros</button>
        </div>

        <!-- Os resultados aparecem aqui, preenchidos pelo JS -->
        <div id="resultado-candidatos">
            <p class="instrucao">Selecione os filtros ao lado e clique em <strong>Buscar Candidatos</strong>.</p>
        </div>
    </main>

</div><!-- .janela -->

<script>
// ============================================================
//  JS da tela de filtros
//  Lê os checkboxes, envia para o endpoint PHP e renderiza os resultados
// ============================================================

const btnBuscar  = document.getElementById('btn-buscar');
const btnLimpar  = document.getElementById('btn-limpar');
const resultado  = document.getElementById('resultado-candidatos');

// ─── Função principal: coleta filtros e chama o endpoint ──────────
btnBuscar.addEventListener('click', async () => {

    // Coletar todos os checkboxes marcados, agrupados por name
    const filtros = { cargo: [], perfil: [], proposta: [] };

    document.querySelectorAll('#form-filtros input[type="checkbox"]:checked')
        .forEach(checkbox => {
            const grupo = checkbox.name.replace('[]', '');
            // remove o [] do name: "cargo[]" → "cargo"

            if (filtros[grupo] !== undefined) {
                filtros[grupo].push(checkbox.value);
            }
        });

    // Mostrar estado de carregamento
    resultado.innerHTML = '<p>Buscando candidatos...</p>';

    try {
        const resposta = await fetch('public/candidatos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(filtros),
            // JSON.stringify → converte o objeto JS para texto JSON
        });

        const dados = await resposta.json();
        // .json() → converte a resposta de texto para objeto JS

        if (dados.erro) {
            resultado.innerHTML = `<p class="erro">Erro: ${dados.erro}</p>`;
            return;
        }

        renderizarCandidatos(dados);

    } catch (erro) {
        resultado.innerHTML = '<p class="erro">Não foi possível conectar ao servidor.</p>';
        console.error(erro);
    }
});

// ─── Limpar filtros e resultados ─────────────────────────────────
btnLimpar.addEventListener('click', () => {
    document.querySelectorAll('#form-filtros input[type="checkbox"]')
        .forEach(cb => cb.checked = false);

    resultado.innerHTML = '<p class="instrucao">Selecione os filtros ao lado e clique em <strong>Buscar Candidatos</strong>.</p>';
});

// ─── Renderizar os cards de candidatos ───────────────────────────
function renderizarCandidatos(dados) {

    if (dados.total === 0) {
        resultado.innerHTML = '<p>Nenhum candidato encontrado para os filtros selecionados.</p>';
        return;
    }

    let html = `<p class="total-resultado">${dados.total} candidato(s) encontrado(s)</p>`;
    html += '<div class="cards-candidatos">';

    dados.candidatos.forEach(candidato => {

        // Montar lista de áreas de emendas
        const areas = candidato.areas_emendas
            ? candidato.areas_emendas.split(', ').map(a =>
                `<span class="tag-area">${a}</span>`
              ).join('')
            : '<span class="tag-area">Sem emendas registradas</span>';

        html += `
            <div class="card-candidato">

                <div class="card-header">
                    <h3>${candidato.nome}</h3>
                    <span class="partido">${candidato.partido ?? '—'}</span>
                </div>

                <div class="card-info">
                    <p><strong>Cargo:</strong>    ${candidato.cargo ?? '—'}</p>
                    <p><strong>UF:</strong>       ${candidato.uf ?? '—'}</p>
                    <p><strong>Gênero:</strong>   ${candidato.genero ?? '—'}</p>
                    <p><strong>Raça/Cor:</strong> ${candidato.raca_cor ?? '—'}</p>
                </div>

                <div class="card-financeiro">
                    <p><strong>Patrimônio declarado:</strong> ${candidato.total_bens_formatado}</p>
                    <p><strong>Total em emendas:</strong>     ${candidato.total_emendas_formatado}</p>
                </div>

                <div class="card-propostas">
                    <strong>Áreas de atuação:</strong>
                    <div class="tags">${areas}</div>
                </div>

            </div>
        `;
    });

    html += '</div>';
    resultado.innerHTML = html;
}
</script>

</body>
</html>