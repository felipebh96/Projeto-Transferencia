<?php
header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

$host = "localhost";
$port = "5432";
$dbname = "postgres";
$user = "postgres";
$password = "@HS2024";

try {
    $db = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $action = $_REQUEST['action'] ?? null;
    $nota   = $_REQUEST['nota'] ?? null;

    // Segurança no campo contagem (somente números)
    $contagem_safe = "NULLIF(REGEXP_REPLACE(contagem, '[^0-9]+', '', 'g'), '')::int";

    // ===========================================================
    // UPDATE CONTAGEM
    // ===========================================================
   if ($action === "updateContagem" && $_SERVER['REQUEST_METHOD'] === "POST") {

    $codigo = $_POST['codigo'] ?? null;

    if (!$nota || !$codigo) {
        echo json_encode([
            "sucesso" => false,
            "erro" => "Parâmetros inválidos"
        ]);
        exit;
    }

    $stmt = $db->prepare("
        SELECT
            quantidade,
            COALESCE(
                NULLIF(REGEXP_REPLACE(contagem, '[^0-9]+', '', 'g'), '')::int,
                0
            ) AS contagem_atual
        FROM tgfpedido
        WHERE numnota = :nota
          AND codigo = :codigo
    ");

    $stmt->execute([
        ":nota" => $nota,
        ":codigo" => $codigo
    ]);

    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        echo json_encode([
            "sucesso" => false,
            "erro" => "Item não encontrado."
        ]);
        exit;
    }

    $quantidade = (int)$item['quantidade'];
    $contagemAtual = (int)$item['contagem_atual'];

    if ($contagemAtual >= $quantidade) {
        echo json_encode([
            "sucesso" => false,
            "erro" => "Quantidade já separada."
        ]);
        exit;
    }

    $stmt = $db->prepare("
    UPDATE tgfpedido
       SET contagem = (
            COALESCE(
                NULLIF(REGEXP_REPLACE(contagem, '[^0-9]+', '', 'g'), '')::int,
                0
            ) + 1
       )::text
     WHERE numnota = :nota
       AND codigo = :codigo
");

$ok = $stmt->execute([
    ":nota" => $nota,
    ":codigo" => $codigo
]);

$novaContagem = $contagemAtual + 1;

    echo json_encode([
        "sucesso" => $ok,
        "contagem" => $novaContagem,
        "quantidade" => $quantidade,
        "finalizado" => ($novaContagem >= $quantidade)
    ]);

    exit;
}
    // ===========================================================
    // ITENS COM DIVERGÊNCIA
    // ===========================================================
     if ($action === "getDivergencias" && $nota) {
    $sql = "SELECT DISTINCT ON (codigo)
                    locall,
                    codigo,
                    marca,
                    descricao,
                    quantidade,
                    status,
                    {$contagem_safe} AS contagem,
                    estoque_atual
            FROM tgfpedido
            WHERE numnota = :nota
             AND COALESCE({$contagem_safe},0) <> COALESCE(quantidade,0)
            ORDER BY codigo,
                     contagem,
                     COALESCE(NULLIF(regexp_replace(locall, '\\D.*$', ''), '')::int, 0),
                     locall";
                     
    $stmt = $db->prepare($sql);
    $stmt->execute([":nota" => $nota]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// ===========================================================
// ALTERAR CONTAGEM - TELA CONFERIDOS
// ===========================================================
if ($action === "setContagem" && $_SERVER['REQUEST_METHOD'] === "POST") {

    $codigo = $_POST['codigo'] ?? null;
    $valor  = $_POST['valor'] ?? null;

    if (!$nota || !$codigo || $valor === null) {
        echo json_encode([
            "sucesso" => false,
            "erro" => "Parâmetros inválidos"
        ]);
        exit;
    }

    $stmt = $db->prepare("
        UPDATE tgfpedido
           SET contagem = :contagem
         WHERE numnota = :nota
           AND codigo = :codigo
    ");

    $ok = $stmt->execute([
        ":contagem" => $valor,
        ":nota"      => $nota,
        ":codigo"    => $codigo
    ]);

    echo json_encode([
        "sucesso" => $ok
    ]);

    exit;
}

    // ===========================================================
    // ITENS CONFERIDOS
    // ===========================================================
    if ($action === "getConferidos" && $nota) {
    $sql = "SELECT DISTINCT ON (codigo)
                    locall,
                    codigo,
                    marca,
                    descricao,
                    quantidade,
                    {$contagem_safe} AS contagem
            FROM tgfpedido
            WHERE numnota = :nota
              AND (
                    COALESCE({$contagem_safe},0) > 0
                    OR status <> 'PENDENTE'
                  )
            ORDER BY codigo,
                     contagem,
                     COALESCE(NULLIF(regexp_replace(locall, '\\D.*$', ''), '')::int, 0),
                     locall";
                     
    $stmt = $db->prepare($sql);
    $stmt->execute([":nota" => $nota]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

    // ===========================================================
    // ITENS POR CÓDIGO / CÓDIGO DE BARRAS
    // ===========================================================
    if ($action === "getItens" && $nota) {
        $buscar = $_GET['buscar'] ?? '';

        if ($buscar !== '') {
            $sql = "SELECT locall, codigo, marca, descricao, quantidade,
                           {$contagem_safe} AS contagem, cod_barra
                      FROM tgfpedido
                     WHERE numnota = :nota
                       AND (
                            codigo ILIKE :buscarCodigo 
                            OR cod_barra ILIKE :buscarBarras
                           )
                       AND COALESCE({$contagem_safe},0) < quantidade
                       AND COALESCE(status,'') NOT IN ('PARCIAL','FALTA_ESTOQUE')AND COALESCE(status,'') <> 'NAO_ENCONTRADO'
                  ORDER BY contagem,
                           COALESCE(NULLIF(regexp_replace(locall, '\\D.*$', ''), '')::int, 0),
                           locall;";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ":nota" => $nota,
                ":buscarCodigo" => "%{$buscar}%",
                ":buscarBarras" => "%{$buscar}%"
            ]);
        } else {
            $sql = "SELECT locall, codigo, marca, descricao, quantidade,
                           {$contagem_safe} AS contagem, cod_barra
                      FROM tgfpedido
                     WHERE numnota = :nota
                       AND COALESCE({$contagem_safe},0) < quantidade
                       AND COALESCE(status,'') NOT IN ('PARCIAL','FALTA_ESTOQUE')
                  ORDER BY contagem,
                           COALESCE(NULLIF(regexp_replace(locall, '\\D.*$', ''), '')::int, 0),
                           locall
                  LIMIT 1;";
            $stmt = $db->prepare($sql);
            $stmt->execute([":nota" => $nota]);
        }

        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // ===========================================================
    // ITENS DA NOTA (PADRÃO)
    // ===========================================================
    if ($nota && !$action) {
    $sql = "SELECT locall,
                   codigo,
                   marca,
                   descricao,
                   quantidade,
                   {$contagem_safe} AS contagem,
                   cod_barra
            FROM tgfpedido
            WHERE numnota = :nota
              AND COALESCE({$contagem_safe},0) < quantidade
              AND COALESCE(status,'') NOT IN ('PARCIAL','FALTA_ESTOQUE')
            ORDER BY contagem,
                     COALESCE(NULLIF(regexp_replace(locall, '\\D.*$', ''), '')::int, 0),
                     locall
            LIMIT 1;";
        $stmt = $db->prepare($sql);
        $stmt->execute([":nota" => $nota]);

        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }
    // ===========================================================
    // NÃO ENCONTRADO
    // ===========================================================
    
   if ($action === "naoEncontrado") {

    $codigo = $_POST['codigo'] ?? null;

    if (!$nota || !$codigo) {
        echo json_encode([
            "sucesso" => false,
            "erro" => "Parâmetros inválidos."
        ]);
        exit;
    }

    $stmt = $db->prepare("
        UPDATE tgfpedido
        SET
            status = 'FALTA_ESTOQUE',
            contagem = '0'
        WHERE numnota = :nota
          AND codigo = :codigo
    ");

    $ok = $stmt->execute([
        ':nota' => $nota,
        ':codigo' => $codigo
    ]);

    echo json_encode([
        'sucesso' => $ok
    ]);

    exit;
}

    // ===========================================================
// FINALIZAR PARCIAL
// ===========================================================
if ($action === "finalizarParcial") {

    $codigo = $_POST['codigo'] ?? null;

    if (!$nota || !$codigo) {
        echo json_encode([
            "sucesso" => false,
            "erro" => "Parâmetros inválidos."
        ]);
        exit;
    }

    $stmt = $db->prepare("
        UPDATE tgfpedido
        SET status = 'PARCIAL'
        WHERE numnota = :nota
          AND codigo = :codigo
    ");

    $ok = $stmt->execute([
        ":nota" => $nota,
        ":codigo" => $codigo
    ]);

    echo json_encode([
        "sucesso" => $ok
    ]);

    exit;
}

    // ===========================================================
    // AÇÃO INVÁLIDA
    // ===========================================================
    echo json_encode(["erro" => "Parâmetros inválidos ou ação desconhecida"]);


} catch (Exception $e) {
    echo json_encode(["erro" => $e->getMessage()]);
}
