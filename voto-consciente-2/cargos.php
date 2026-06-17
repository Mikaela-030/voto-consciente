<?php
include_once 'dados_cargos.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cargos</title>
    <link rel="stylesheet" href="css/estilosconsciente.css">
</head>
<body>

<header>
    <div class="header-text">
        <h1>Cargos</h1>
    </div>
    <div class="user-info">
        <a href="votoconsciente.php" class="logout-link">Voltar</a>
    </div>
</header>

<main style="padding: 130px 24px 24px; max-width: 1080px; margin: 0 auto;">
    <section>
        <p class="instrucao">Conheça melhor os cargos disponíveis na plataforma e a função de cada um no cenário político.</p>
    </section>

    <section class="cards-candidatos" style="margin-top: 1.5rem;">
        <?php foreach ($cargos_disponiveis as $cargo): ?>
            <div class="card-candidato">
                <div class="card-header">
                    <h3><?= htmlspecialchars($cargo['titulo']) ?></h3>
                </div>
                <div class="card-info">
                    <p><?= htmlspecialchars($cargo['descricao']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </section>
</main>

</body>
</html>