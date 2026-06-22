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
    "SELECT nome_usuario AS nome, data_nascimento AS datadenascimento, email,
            aceite_privacidade, aceite_privacidade_em
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
    <title>Voto Consciente | Neo Science</title>

    <!-- Tipografia e  logos  -->
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">


    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link rel="stylesheet" href="css/estilosconsciente.css?v=1.1">
</head>
<body>

<header class="header">
    <div class="container header-inner">

        <div class="logo-area">
            <img src="img/logo.png"
                 alt="neo science logo"
                 class="logo"
                 loading="lazy">
            <div class="header-text">
                <h1>Voto Consciente</h1>
            </div>
        </div>

        <div class="user-info">
            <button id="profile-button" class="profile-toggle" type="button" aria-expanded="false" aria-controls="profile-card">
                <span id="user-name"><?= htmlspecialchars($logado) ?></span>
                <i class="fa-solid fa-circle-user profile-icon" aria-hidden="true"></i>
            </button>
            <a href="Sair.php" class="logout-link">
                <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Sair
            </a>
        </div>

        <div id="profile-card" class="profile-card">
            <h2><i class="fa-solid fa-id-card" aria-hidden="true"></i> Meu Perfil</h2>
            <?php if ($usuario): ?>
                <p><strong>Nome:</strong>              <?= htmlspecialchars($usuario['nome']) ?></p>
                <p><strong>Data de Nascimento:</strong><?= htmlspecialchars($usuario['datadenascimento']) ?></p>
                <p><strong>Email:</strong>             <?= htmlspecialchars($usuario['email']) ?></p>
                <p><strong>Consentimento LGPD:</strong>
                    <?= $usuario['aceite_privacidade'] ? 'Aceito em ' . htmlspecialchars($usuario['aceite_privacidade_em']) : 'Não aceito' ?>
                </p>
                <p><a href="privacidade.php">Ver política de privacidade</a></p>
                <form action="excluir_conta.php" method="post" onsubmit="return confirm('Tem certeza que deseja excluir sua conta e todos os seus dados?');">
                    <button type="submit" class="btn-excluir-conta">Excluir minha conta</button>
                </form>
            <?php else: ?>
                <p>Dados do perfil não encontrados.</p>
            <?php endif; ?>
        </div>

    </div>
</header>

<section class="participacao" id="busca">
    <div class="container">

        <h2 class="secao-titulo">Encontre seu Candidato</h2>

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
                        <h2><i class="fa-solid fa-landmark" aria-hidden="true"></i> Cargo</h2>
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
                        <h2><i class="fa-solid fa-users" aria-hidden="true"></i> Perfil</h2>
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
                        <h2><i class="fa-solid fa-file-lines" aria-hidden="true"></i> Proposta</h2>
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
                            <label for="seguranca">Segurança</label>
                        </div>
                    </div>

            </form>
            </aside>

            <main id="main">
                <div class="top-actions">
                    <button type="button" id="btn-buscar" class="btn-buscar">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Buscar Candidatos
                    </button>
                    <button type="button" id="btn-limpar" class="btn-limpar">
                        <i class="fa-solid fa-eraser" aria-hidden="true"></i> Limpar Filtros
                    </button>
                </div>

                <!-- Os resultados aparecem aqui, preenchidos pelo JS -->
                <div id="resultado-candidatos">
                    <p class="instrucao">Selecione os filtros ao lado e clique em <strong>Buscar Candidatos</strong>.</p>
                </div>
            </main>

        </div><!-- .janela -->

    </div>
</section>

<footer>
    <div class="container">
        <p>&copy; <?= date('Y') ?> - Todos os direitos reservados</p>
    </div>
</footer>

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

        // Montar lista de áreas de emendas a partir dos dados que já vêm do backend
        const areas = Array.isArray(candidato.emendas_por_area) && candidato.emendas_por_area.length > 0
            ? candidato.emendas_por_area.map(item =>
                `<span class="tag-area">${item.area}</span>`
              ).join('')
            : (candidato.areas_emendas
                ? candidato.areas_emendas.split(', ').map(a =>
                    `<span class="tag-area">${a}</span>`
                  ).join('')
                : '<span class="tag-area">Sem emendas registradas</span>');

        const emendasPorAreaHtml = Array.isArray(candidato.emendas_por_area) && candidato.emendas_por_area.length > 0
            ? candidato.emendas_por_area.map(item => {
                const totalFormatado = item.total
                    ? new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(item.total)
                    : 'R$ 0,00';
                const descricoesHtml = Array.isArray(item.descricoes) && item.descricoes.length > 0
                    ? item.descricoes.map(desc => `<p class="descricao-emenda">${desc}</p>`).join('')
                    : '';

                return `
                    <div class="area-emenda-item">
                        <div class="area-emenda-header">
                            <strong>${item.area}</strong>
                            <span>${item.quantidade} emenda(s)</span>
                            <span>${totalFormatado}</span>
                        </div>
                        ${descricoesHtml}
                    </div>
                `;
              }).join('')
            : '<p>Sem detalhamento de emendas por área.</p>';

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

                <div class="card-emendas-por-area">
                    <strong>Detalhes de emendas por área:</strong>
                    <div class="emendas-por-area-list">${emendasPorAreaHtml}</div>
                </div>

            </div>
        `;
    });

    html += '</div>';
    resultado.innerHTML = html;
}

// ─── Abrir/fechar o cartão de perfil ──────────────────────────────
// (botão já existia no HTML, mas não havia handler ligado a ele)
const profileButton = document.getElementById('profile-button');
const profileCard    = document.getElementById('profile-card');

profileButton.addEventListener('click', (evento) => {
    evento.stopPropagation();
    const aberto = profileCard.classList.toggle('aberto');
    profileButton.setAttribute('aria-expanded', aberto ? 'true' : 'false');
});

document.addEventListener('click', (evento) => {
    if (!profileCard.contains(evento.target) && evento.target !== profileButton) {
        profileCard.classList.remove('aberto');
        profileButton.setAttribute('aria-expanded', 'false');
    }
});
</script>

</body>
</html>