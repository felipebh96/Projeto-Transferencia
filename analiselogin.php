<?php
session_start();

// ======================================
// PROCESSAR LOGIN
// ======================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Receber dados
    $nota    = trim($_POST['nota'] ?? '');
    $filial  = trim($_POST['filial'] ?? '');

    // Validar campos
    if (
        empty($nota) ||
        empty($filial) 
    ) {
        $erro = "Preencha todos os campos.";
    }
            // REDIRECIONAR
            header(
            "Location: analise.php?nota=" .
            urlencode($nota) .
            "&filial=" .
            urlencode($filial)
        );

            exit;
        }
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <title>Análise de Notas</title>

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Arial, sans-serif;
        }

        body{
            height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
            background:
            linear-gradient(135deg,#0f172a,#1e3a8a);
        }

        .container{

            width:1000px;
            height:600px;

            display:flex;

            background:white;

            border-radius:25px;

            overflow:hidden;

            box-shadow:
            0 15px 40px rgba(0,0,0,0.3);
        }

        /* LADO ESQUERDO */

        .left{

            width:40%;

            background:
            linear-gradient(135deg,#2563eb,#1d4ed8);

            color:white;

            padding:50px;

            display:flex;

            flex-direction:column;

            justify-content:center;
        }

        .left h1{

            font-size:42px;

            margin-bottom:20px;
        }

        .left p{

            line-height:28px;

            font-size:18px;

            opacity:0.95;
        }

        .info{

            margin-top:40px;
        }

        .info div{

            margin-bottom:20px;

            font-size:17px;
        }

        /* LADO DIREITO */

        .right{

            width:60%;

            padding:60px;

            display:flex;

            flex-direction:column;

            justify-content:center;
        }

        .right h2{

            font-size:38px;

            color:#1e293b;

            margin-bottom:10px;
        }

        .right span{

            color:#64748b;

            margin-bottom:35px;
        }

        form{

            display:flex;

            flex-direction:column;
        }

        input{

            padding:15px;

            margin-bottom:20px;

            border:1px solid #cbd5e1;

            border-radius:12px;

            font-size:16px;

            transition:0.3s;
        }

        input:focus{

            border-color:#2563eb;

            outline:none;

            box-shadow:
            0 0 10px rgba(37,99,235,0.2);
        }

        button{

            padding:15px;

            border:none;

            border-radius:12px;

            background:#2563eb;

            color:white;

            font-size:17px;

            font-weight:bold;

            cursor:pointer;

            transition:0.3s;
        }

        button:hover{

            background:#1d4ed8;

            transform:translateY(-2px);
        }

        .erro{

            background:#fee2e2;

            color:#b91c1c;

            padding:12px;

            border-radius:10px;

            margin-bottom:20px;

            text-align:center;
        }

    </style>

</head>

<body>

<div class="container">

    <!-- ESQUERDA -->

    <div class="left">

        <h1>Análise de Notas</h1>

    </div>

    <!-- DIREITA -->

    <div class="right">

        <h2>Bem-vindo</h2>

        <span>
            Preencha os campos para continuar
        </span>

        <?php if(isset($erro)): ?>

            <div class="erro">
                <?= $erro ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <input
                type="text"
                name="nota"
                placeholder="Número da Nota"
            >

            <input
                type="text"
                name="filial"
                placeholder="Filial"
            >

            <button type="submit">
                Entrar no Sistema
            </button>

        </form>

    </div>

</div>

</body>
</html>