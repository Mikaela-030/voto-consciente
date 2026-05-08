<?php

if(isset($_POST['submit'])) {
    require_once ('config.php');

    $nome = mysqli_real_escape_string($conexao, $_POST['nome']);
    $datadenascimento = mysqli_real_escape_string($conexao, $_POST['datadenascimento']);
    $cidade = mysqli_real_escape_string($conexao, $_POST['cidade']);
    $email = mysqli_real_escape_string($conexao, $_POST['email']);
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    $stmt = $conexao->prepare("INSERT INTO cadastro(nome,datadenascimento,cidade,email,senha) VALUES (?,?,?,?,?)");
    $stmt->bind_param("sssss", $nome, $datadenascimento, $cidade, $email, $senha);
    $result = $stmt->execute();
    
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

            <label for="nome" name="nome" class="form-label"> Nome de Usuário</label> <!-- Nome -->
            
            
            <input type="text" name="nome" class="form-control" placeholder="Digite seu nome de usuário">
            <br>
            

            <label for="datadenascimento" name="datadenascimento" class="form-label"> Data de Nascimento </label> <!-- Data de Nascimento -->
            


            <input type="date" name="datadenascimento" class="form-control">
            <br>
            

            <label for="cidade" name="cidade" class="form-label"> Cidade </label> <!-- Cidade -->
            
            
            <input type="text" name="cidade" placeholder="Digite sua cidade" class="form-control">
            <br>
           

            <label for="email" name="email" class="form-label"> Email</label> <!-- Email -->
            
            
            <input type="email" name="email" placeholder="nomesobrenome@gmail.com" class="form-control">
            <br>
            

            <label for="senha" name="senha" class="form-label"> Senha</label> <!-- senha -->
            
            
            <input type="password" name="senha" placeholder="Digite Sua Senha" class="form-control">
            <br>
            

            <button type="submit" name="submit" class="btn btn-primary"> Cadastrar-se </button>

            <a class="btn btn-primary" href="home.php"> Voltar </a>
            

        </form>

        <aside>


        </aside>

        </div>

    </section>
</body>
</html>