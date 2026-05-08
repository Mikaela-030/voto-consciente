<?php
session_start();

if (!isset($_SESSION['nome'])) {
    header('Location: login.php');
    exit();
}

$logado = $_SESSION['nome'];

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voto Consciente</title>
    <link href="css/estiloshome2.css" rel="stylesheet">
</head>
<body>
    <header>
        <div class="header-text">
            <h1>Voto Consciente</h1>
        </div>

        <div class="user-info">
          <span id="user-name"><?= htmlspecialchars($logado) ?></span>
          <span id="user-avatar" class="avatar">
              <img src="img/avatar.jpg" alt="Avatar do Usuário">
          </span>
       </div>
    </header>

    <aside>
        <form action="votoconsciente.php" method="POST">

          <div class="cargo">
            <h2>Cargo</h2>
            <div class="checkbox-row">
              <input type="checkbox" id="vereador">
              <label for="vereador">Vereador</label>
            </div>
            <div class="checkbox-row">
              <input type="checkbox" id="deputado">
              <label for="deputado">Deputado Estadual</label>
            </div>
            <div class="checkbox-row">
              <input type="checkbox" id="deputadofederal">
              <label for="deputadofederal">Deputado Federal</label>
            </div>
            <div class="checkbox-row">
              <input type="checkbox" id="senador">
              <label for="senador">Senador</label>
            </div>
          </div>

          <div class="perfil">
            <h2>Perfil</h2>
            <div class="checkbox-row">
              <input type="checkbox" id="negro(a)">
              <label for="negro(a)">Negro(a)</label>
            </div>
            <div class="checkbox-row">
              <input type="checkbox" id="mulher">
              <label for="mulher">Mulher</label>
            </div>
            <div class="checkbox-row">
              <input type="checkbox" id="lgbtqia+">
              <label for="lgbtqia+">LGBTQIA+</label>
            </div>
            <div class="checkbox-row">
              <input type="checkbox" id="indigena">
              <label for="indigena">Indígena</label>
            </div>
          </div>

          <div class="proposta">
            <h2>Proposta</h2>
            <div class="checkbox-row">
              <input type="checkbox" id="educacao">
              <label for="educacao">Educação</label>
            </div>
            <div class="checkbox-row">
              <input type="checkbox" id="saude">
              <label for="saude">Saúde</label>
            </div>
            <div class="checkbox-row">
              <input type="checkbox" id="meio-ambiente">
              <label for="meio-ambiente">Meio Ambiente</label>
            </div>
          </div>

        </form>
    </aside>
    <script src="js/script.js"></script>    
</body>
</html>