<?php

// =====================================
// CONEXÃO COM POSTGRESQL
// =====================================
$host = "localhost";
$port = "5432";
$dbname = "postgres";
$user = "postgres";
$password = "@HS2024";

try {

    // CONEXÃO PDO POSTGRESQL
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname",
        $user,
        $password
    );

    // MOSTRAR ERROS PDO
    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {

    die("
        <div style='
            background:#fee2e2;
            color:#991b1b;
            padding:20px;
            border-radius:10px;
            margin:20px;
            font-family:Arial;
        '>

            <h2>Erro na conexão com banco</h2>

            <p>{$e->getMessage()}</p>

        </div>
    ");
}

// =====================================
// PEGAR NOTA DA URL
// =====================================
$nota = trim($_GET['nota'] ?? '');
$filial = trim($_GET['filial'] ?? '');

// VALIDAR NOTA
if (empty($nota)) {

    die("
        <div style='
            background:#fef3c7;
            color:#92400e;
            padding:20px;
            border-radius:10px;
            margin:20px;
            font-family:Arial;
        '>

            <h2>Nota não informada</h2>

        </div>
    ");
}

// =====================================
// CONSULTA SQL
// =====================================
//
// O ERRO ESTAVA AQUI:
//
// Você estava usando bindParam(':nota')
// MAS na query não existia :nota
//
// Agora foi corrigido.
// =====================================

$sql = "
SELECT
    CODIGO,
    MARCA,
    DESCRICAO,
    LOCALL,
    QUANTIDADE,
    CONTAGEM,
    ESTOQUE_ATUAL,
    USUARIO
FROM tgfpedido
WHERE NUMNOTA = :nota
  AND filial = :filial
ORDER BY DESCRICAO
";

// PREPARAR QUERY
$stmt = $pdo->prepare($sql);

// PASSAR PARÂMETRO
$stmt->bindParam(':nota', $nota, PDO::PARAM_STR) &
$stmt->bindParam(':filial', $filial, PDO::PARAM_STR);

// EXECUTAR
$stmt->execute();

// PEGAR RESULTADOS
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Análise da Nota</title>

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Arial, sans-serif;
        }

        body{

            background:
            linear-gradient(135deg,#0f172a,#1e3a8a);

            min-height:100vh;

            padding:40px;
        }

        .container{

            width:95%;

            margin:auto;

            background:white;

            border-radius:20px;

            padding:30px;

            box-shadow:
            0 10px 30px rgba(0,0,0,0.3);

            animation: aparecer 0.4s ease;
        }

        h1{

            color:#1e293b;

            margin-bottom:10px;

            font-size:35px;
        }

        .subtitulo{

            color:#64748b;

            margin-bottom:30px;

            font-size:18px;
        }

        .nota{

            color:#2563eb;

            font-weight:bold;
        }

        table{

            width:100%;

            border-collapse:collapse;

            overflow:hidden;

            border-radius:15px;
        }

        th{

            background:#2563eb;

            color:white;

            padding:15px;

            font-size:15px;
        }

        td{

            padding:14px;

            text-align:center;

            border-bottom:1px solid #e2e8f0;

            color:#334155;
        }

        tr:hover{

            background:#f8fafc;

            transition:0.3s;
        }

        .sem-registro{

            background:#fef2f2;

            color:#991b1b;

            padding:20px;

            border-radius:10px;

            text-align:center;

            font-size:18px;
        }

        .topo{

            display:flex;

            justify-content:space-between;

            align-items:center;

            margin-bottom:30px;

            gap:20px;
        }

        .btn{

            background:#2563eb;

            color:white;

            padding:12px 20px;

            border-radius:10px;

            text-decoration:none;

            transition:0.3s;
        }

        .btn:hover{

            background:#1d4ed8;
        }

        @keyframes aparecer{

            from{
                opacity:0;
                transform:translateY(-20px);
            }

            to{
                opacity:1;
                transform:translateY(0);
            }
        }

        @media(max-width:900px){

            .container{
                overflow:auto;
            }

            table{
                min-width:900px;
            }

            .topo{
                flex-direction:column;
                align-items:flex-start;
            }
        }

    </style>

</head>

<body>

<div class="container">

    <div class="topo">

        <div>

            <h1>📊 Análise da Nota</h1>

            <div class="subtitulo">

                Consultando nota:
                <span class="nota">
                    <?= htmlspecialchars($nota) ?>
                </span>

            </div>

        </div>

        <a href="javascript:history.back()" class="btn">
            ← Voltar
        </a>

    </div>

    <?php if(count($resultados) > 0): ?>

        <table>

            <thead>

                <tr>

                    <th>Código</th>
                    <th>Marca</th>
                    <th>Descrição</th>
                    <th>Local</th>
                    <th>Quantidade</th>
                    <th>Contagem</th>
                    <th>Estoque Atual</th>
                    <th>Usuario</th>

                </tr>

            </thead>

            <tbody>

            <?php foreach($resultados as $linha): ?>

                <tr>

                    <td>
                        <?= htmlspecialchars($linha['codigo']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($linha['marca']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($linha['descricao']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($linha['locall']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($linha['quantidade']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($linha['contagem']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($linha['estoque_atual']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($linha['usuario']) ?>
                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    <?php else: ?>

        <div class="sem-registro">

            Nenhum registro encontrado para a nota
            <strong>
                <?= htmlspecialchars($nota) ?>
            </strong>

        </div>

    <?php endif; ?>

</div>

</body>
</html>