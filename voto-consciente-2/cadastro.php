<?php

$mensagem_erro = '';
$mensagem_sucesso = '';

if(isset($_POST['submit'])) {
    require_once ('config.php');

    $checkColumn = $conexao->query("SHOW COLUMNS FROM usuarios LIKE 'aceite_privacidade'");
    if ($checkColumn && $checkColumn->num_rows === 0) {
        $conexao->query(
            "ALTER TABLE usuarios
             ADD COLUMN aceite_privacidade TINYINT(1) NOT NULL DEFAULT 0,
             ADD COLUMN aceite_privacidade_em TIMESTAMP NULL"
        );
    }

    $aceite_privacidade = $_POST['aceite_privacidade'] ?? '0';
    if ($aceite_privacidade !== '1') {
        $mensagem_erro = 'Você deve aceitar a política de privacidade para continuar.';
    } else {
        $nome  = trim($_POST['nome']);
        $email = trim($_POST['email']);
        $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
        $aceite_privacidade_em = date('Y-m-d H:i:s');

        $stmt = $conexao->prepare(
            "INSERT INTO usuarios (nome_usuario, senha_hash, data_nascimento, email, aceite_privacidade, aceite_privacidade_em)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssssss", $nome, $senha, $_POST['datadenascimento'], $email, $aceite_privacidade, $aceite_privacidade_em);
        // Removido: cidade (não existe na tabela usuarios)
        
        if ($stmt->execute()) {
            header('Location: login.php');
            exit();
        } else {
            if ($conexao->errno == 1062) {
                $mensagem_erro = 'Nome de usuário ou email já cadastrado!';
            } else {
                $mensagem_erro = 'Erro ao realizar cadastro: ' . $conexao->error;
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/estilos.css">

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">





    <title>Cadastre-se</title>
  </head>
  <body>
    <section class="container">
        

        <div>

        <form action="cadastro.php" method="POST">
            <h2> Cadastro </h2>

            <?php if($mensagem_sucesso): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $mensagem_sucesso; ?>
                </div>
         <?php endif; ?>

            <?php if($mensagem_erro): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $mensagem_erro; ?>
                </div>
         <?php endif; ?>

            <label for="nome" name="nome" class="form-label"> Nome de Usuário</label> <!-- Nome -->
            
            
            <input type="text" name="nome" class="form-control" placeholder="Digite seu nome de usuário" required>
            <br>
            

            <label for="datadenascimento" name="datadenascimento" class="form-label"> Data de Nascimento </label> <!-- Data de Nascimento -->
            


            <input type="date" name="datadenascimento" class="form-control" required>
            <br>
           

            <label for="email" name="email" class="form-label"> Email</label> <!-- Email -->
            
            
            <input type="email" name="email" placeholder="nomesobrenome@gmail.com" class="form-control" required>
            <br>
            

            <label for="senha" name="senha" class="form-label"> Senha</label> <!-- senha -->
            
            
            <input type="password" name="senha" placeholder="Digite Sua Senha" class="form-control" required>
            <br>

            <input type="hidden" name="aceite_privacidade" value="0">
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="aceite_privacidade" id="aceite_privacidade" value="1" required>
                <label class="form-check-label" for="aceite_privacidade">
                    Eu li e aceito a <a href="privacidade.php" target="_blank">política de privacidade</a>.
                </label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" name="submit" class="btn btn-primary">Cadastrar-se</button>
                <a class="btn btn-secondary" href="home.php">Voltar</a>
            </div>

        </form>

        <aside>


        </aside>

        </div>

    </section>
</body>
</html>