import sys
import psycopg2

# =========================
# UTF-8 no Windows
# =========================
sys.stdout.reconfigure(encoding='utf-8')

# =========================
# Recebe argumentos do PHP
# =========================
if len(sys.argv) != 4:
    sys.exit("Uso: python codigo.py <nota> <filial> <usuario>")

nota = sys.argv[1].strip()    # nronff
filial = sys.argv[2].strip()  # clifor
usuario = sys.argv[3].strip()

# =========================
# Configurações Banco 1 (Origem)
# =========================
db1_config = {
    "host": "10.100.245.4",
    "port": "8745",
    "database": "ssplus-hs",
    "user": "felipe",
    "password": "@HS2024"
}

# =========================
# Configurações Banco 2 (Destino)
# =========================
db2_config = {
    "host": "localhost",
    "port": "5432",
    "database": "postgres",
    "user": "postgres",
    "password": "@HS2024"
}

# =========================
# Query SELECT no Banco 1
# =========================
query_select = """
SELECT 
    TGFCAB.PLANIL AS nunota, 
    TGFCAB.NRONFF AS numnota,
    tgfpro.basico as codigo,
    tgfpro.marca as marca,
    tgfpro.descr1 as descricao,
    tgfpro.codire as cod_barra,
    tgflocal.localiza as locall,
    ROUND(tgfite.quanti,0) as quantidade,
	ROUND((select sum(TGFEST2.QTATUA - TGFEST2.OSINIC)
		from pccdite0 TGFPRO2 inner join					
	 		 pccmesd0 TGFEST2 on tgfest2.item = tgfpro2.codigo 
		where TGFEST2.EMPFIL = '0001'
  		  and tgfest2.dtmovi = (select est.dtmovi							
  				 	   		    from pccmesd0 est 		
					   		    WHERE est.item = tgfest2.item
					     		  AND EST.EMPFIL = '0001'
					   		    order by dtmovi desc		
					   	        limit 1)
          AND TGFEST2.ITEM = TGFITE.ITEM),0) AS estoque_atual,
    '' as contagem,
    tgfcab.clifor as filial,
    '' as usuario,
    TO_CHAR(TGFCAB.DTEMIS, 'DD-MM-YYYY') AS data_emissao,
    '' as inicio_separacao,
    'PENDENTE' as status
FROM pccmnfs0 tgfcab 
INNER JOIN pccmest0 tgfite ON tgfite.planil = tgfcab.planil 
INNER JOIN pccdite0 tgfpro ON tgfpro.codigo = tgfite.item 
INNER JOIN pccdloc0 tgflocal ON tgflocal.item = tgfpro.codigo
    AND tgflocal.empfil = tgfcab.empfil
WHERE tgfcab.operac = '1040'
  AND tgfcab.nronff = %s
  AND tgfcab.empfil = '0001'
  AND tgfcab.clifor = %s
"""

# =========================
# Query INSERT no Banco 2
# =========================
query_insert = """
INSERT INTO TGFPEDIDO
(nunota, numnota, codigo, marca, descricao, cod_barra, locall, quantidade, estoque_atual, contagem, filial, usuario, data_emissao, inicio_separacao, status)
VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
"""

# =========================
# Query UPDATE usuário no Banco 2
# =========================
query_update = """
UPDATE TGFPEDIDO
SET usuario = %s, inicio_separacao = NOW()
WHERE numnota = %s AND filial = %s
"""

# =========================
# Query para verificar se já existe no destino
# =========================
query_check = """
SELECT 1 FROM TGFPEDIDO WHERE numnota = %s AND filial = %s LIMIT 1
"""

# =========================
# Processo principal
# =========================
try:
    # Conectar Banco Origem
    conn1 = psycopg2.connect(**db1_config)
    cursor1 = conn1.cursor()

    # Conectar Banco Destino
    conn2 = psycopg2.connect(**db2_config)
    cursor2 = conn2.cursor()

    # Executar SELECT no Banco Origem
    cursor1.execute(query_select, (nota, filial))
    result = cursor1.fetchall()

    if result:
        # Verifica se a nota já existe no destino
        cursor2.execute(query_check, (nota, filial))
        exists = cursor2.fetchone()

        if exists:
            # Só atualiza o usuário
            cursor2.execute(query_update, (usuario, nota, filial))
        else:
            # Faz o insert
            cursor2.executemany(query_insert, result)
            # Depois atualiza o usuário
            cursor2.execute(query_update, (usuario, nota, filial))

        conn2.commit()

except Exception as e:
    sys.exit(f"Erro durante execução: {e}")

finally:
    # Fechar conexões
    try:
        if cursor1: cursor1.close()
        if conn1: conn1.close()
    except:
        pass
    try:
        if cursor2: cursor2.close()
        if conn2: conn2.close()
    except:
        pass
