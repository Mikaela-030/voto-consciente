<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/estilosLogin.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

    <title>Login</title>
</head>

<body>
    

    <section class="container">
        
        <form method="post" action="testeLogin.php">
            <fieldset>
                <legend><b> Voto Consciente</b> </legend>
             <h1 class="mb-3"> Login </h1> 

             <input type="text" class="form-control mb-4" name="nome" placeholder="Digite seu nome de usuario" required>

             <input type="password" class="form-control mb-4" name="senha" placeholder="Digite sua senha cadastrada" required>

             <input type="submit" class="form-control mb-4" name="submit" value="Entrar">

             <a class="form-control mb-4" href="home.php"> Voltar </a>
            </fieldset>

        </form>
    </section>
</body>
</html>