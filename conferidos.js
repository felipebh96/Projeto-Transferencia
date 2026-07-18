let nota = null;

// ===============================
// Atualizar contagem no backend
// ===============================
async function atualizarContagem(codigo, valor) {
    try {
        const response = await fetch("backend.php?action=setContagem&nota=" + nota, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `codigo=${encodeURIComponent(codigo)}&valor=${encodeURIComponent(valor)}`
        });

        const result = await response.json();
        if (!result.sucesso) {
            alert("Erro ao atualizar contagem!");
        } else {
            carregarConferidos(); // recarregar após atualização
        }
    } catch (error) {
        console.error("Erro ao atualizar contagem:", error);
    }
}

// ===============================
// Carregar itens conferidos
// ===============================
async function carregarConferidos() {
    try {
        const url = "backend.php?action=getConferidos&nota=" + nota;
        const response = await fetch(url);
        const data = await response.json();

        const tbody = document.getElementById("resultado-body");
        tbody.innerHTML = "";

        if (data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6">Nenhum item conferido ainda.</td></tr>`;
            return;
        }

        data.forEach((item) => {
            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td>${item.locall}</td>
                <td>${item.codigo}</td>
                <td>${item.marca}</td>
                <td>${item.descricao}</td>
                <td>${item.quantidade}</td>
                <td>
                    <input type="number" min="0" class="input-contagem"
                        value="${item.contagem != null ? item.contagem : ''}"
                        onchange="atualizarContagem('${item.codigo}', this.value)">
                </td>
            `;
            tbody.appendChild(tr);
        });

    } catch (error) {
        console.error("Erro ao carregar conferidos:", error);
    }
}

// ===============================
// Inicialização
// ===============================
document.addEventListener("DOMContentLoaded", () => {
    const urlParams = new URLSearchParams(window.location.search);
    nota = urlParams.get("nota");

    if (!nota) {
        alert("Nenhuma nota informada!");
        return;
    }

    // Mostra a nota no título
    const spanNota = document.getElementById("notaCarregada");
    if (spanNota) spanNota.textContent = nota;

    // Carrega itens conferidos
    carregarConferidos();

    // Botão voltar
    const btnVoltar = document.getElementById("btnVoltar");
    if (btnVoltar) {
        btnVoltar.addEventListener("click", () => {
            window.location.href = "index2.html?nota=" + encodeURIComponent(nota);
        });
    }
});
