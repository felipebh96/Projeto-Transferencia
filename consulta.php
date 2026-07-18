<?php
// Dados de conexão
$host = "localhost";
$port = "5432";
$dbname = "postgres";
$user = "postgres";
$password = "@HS2024";

// Conecta ao Postgres
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

// Verifica conexão
if (!$conn) {
    die("Erro ao conectar ao banco de dados.");
}

// Verifica se enviou o formulário
$numnota = $_GET['numnota'] ?? '';

$result = null;

if (!empty($numnota)) {
    $query = "
        SELECT 
            numnota,
            codigo,
            marca,
            descricao,
            quantidade,
            contagem,
            usuario
        FROM tgfpedido
        WHERE numnota = $1
        ORDER BY marca;
    ";

    $result = pg_query_params($conn, $query, [$numnota]);
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Nota</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container mt-4">
    <h2 class="mb-3">Consulta de Nota</h2>

    <form method="GET" class="card p-3 mb-4 shadow-sm">
        <div class="row g-2">
            <div class="col-md-4">
                <label class="form-label">Número da Nota</label>
                <input type="text" name="numnota" class="form-control" placeholder="066714" value="<?= htmlspecialchars($numnota) ?>">
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100">Buscar</button>
            </div>
        </div>
    </form>

    <?php if ($result): ?>
        <div class="card shadow-sm p-3">
            <h5>Resultado da Pesquisa</h5>
            <div class="table-responsive mt-3">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Nota</th>
                            <th>Código</th>
                            <th>Marca</th>
                            <th>Descrição</th>
                            <th>Quantidade</th>
                            <th>Contagem</th>
                            <th>Usuário</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (pg_num_rows($result) > 0): ?>
                            <?php while ($row = pg_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?= $row['numnota'] ?></td>
                                    <td><?= $row['codigo'] ?></td>
                                    <td><?= $row['marca'] ?></td>
                                    <td><?= $row['descricao'] ?></td>
                                    <td><?= $row['quantidade'] ?></td>
                                    <td><?= $row['contagem'] ?></td>
                                    <td><?= $row['usuario'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-danger">Nenhum registro encontrado.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
