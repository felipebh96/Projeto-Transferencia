<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel - Buscar Produtos</title>
</head>
<body>
    <h2>Bem-vindo, <?php echo $_SESSION['usuario']; ?>!</h2>
    <p>Nota selecionada: <?php echo $_SESSION['nota']; ?></p>

    <input type="text" id="buscar" placeholder="Digite o número da nota">
    <button onclick="buscarProdutos()">Buscar</button>

    <h3>Resultado:</h3>
    <pre id="resultado"></pre>

    <script>
        function buscarProdutos() {
            let codigo = document.getElementById("buscar").value;

            fetch("backend.php?buscar=" + codigo)
                .then(res => {
                    if (!res.ok) throw new Error("Erro " + res.status);
                    return res.json();
                })
                .then(data => {
                    document.getElementById("resultado").textContent =
                        JSON.stringify(data, null, 2);
                })
                .catch(err => {
                    document.getElementById("resultado").textContent = err;
                });
        }
    </script>
</body>
</html>
