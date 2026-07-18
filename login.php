<?php
session_start();

// =========================
// Usuários válidos
// =========================
$usuarios_validos = [
    "ADMIN"   => "123",
    "FELIPE"  => "0425",
    "ANDREZA" => "1994",
    "LUCAS"    => "2113",
    "PEDRO"    => "1012",
    "JONES"   => "1234",
    "ABRAAO"    => "0242",
    "MICAEL"   => "9814",
    "THIAGO"   => "1313",
    "CHRISTIAN" => "3004"
];

// =========================
// Receber dados do formulário
// =========================
$nota    = $_POST['nota'] ?? '';
$filial  = $_POST['filial'] ?? '';
$usuario = $_POST['usuario'] ?? '';
$senha   = $_POST['senha'] ?? '';

if (empty($nota) || empty($filial) || empty($usuario) || empty($senha)) {
    echo "<p style='color:red'>Informe nota, filial, usuário e senha!</p>";
    exit;
}

// =========================
// Validar usuário e senha
// =========================
if (!isset($usuarios_validos[$usuario]) || $usuarios_validos[$usuario] !== $senha) {
    echo "<p style='color:red'>Usuário ou senha inválidos!</p>";
    exit;
}

// =========================
// Caminho do Python
// =========================
$python_bin    = "C:\\Users\\Felipe\\AppData\\Local\\Programs\\Python\\Python313\\python.exe";
$python_script = __DIR__ . "\\codigo.py";

// =========================
// Executar Python
// =========================

$command = "\"$python_bin\" \"$python_script\" \"$nota\" \"$filial\" \"$usuario\" 2>&1";
exec($command, $output, $return_code);

if ($return_code !== 0) {
    echo "<p style='color:red'>Falha ao sincronizar dados: <br>" . implode("<br>", $output) . "</p>";
    exit;
}

// =========================
// Se sincronização OK → redireciona para index2.html com a nota
// =========================
header("Location: index2.html?nota=" . urlencode($nota));
exit;
