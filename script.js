// script.js - versão aprimorada com exibição elegante de mensagens e backend seguro

let timerAtualizacao;
let ultimaAcao = Date.now();
const TEMPO_ESPERA = 15000; // 15s para auto refresh
let nota = null;
let itemAtual = null;
let validatedForCodigo = null;
let debounceTimer = null;

// ------------ utilitárias -------------
function normalizeCode(value) {
  if (!value) return "";
  return String(value).replace(/[^0-9a-zA-Z]/g, "").toUpperCase();
}

/**
 * Exibe uma mensagem flutuante elegante no canto inferior direito.
 * @param {string} text - Texto da mensagem.
 * @param {boolean} isOk - true para sucesso, false para erro.
 * @param {number} timeout - Tempo de exibição em ms.
 */
function showValidationMessage(text, isOk = true, timeout = 3000) {
  const oldMsg = document.querySelector(".msg-float");
  if (oldMsg) oldMsg.remove();

  const msg = document.createElement("div");
  msg.className = "msg-float";
  msg.textContent = text;
  msg.style.position = "fixed";
  msg.style.bottom = "20px";
  msg.style.right = "20px";
  msg.style.padding = "12px 18px";
  msg.style.borderRadius = "8px";
  msg.style.fontSize = "14px";
  msg.style.fontFamily = "system-ui, sans-serif";
  msg.style.boxShadow = "0 4px 12px rgba(0,0,0,0.15)";
  msg.style.color = "#fff";
  msg.style.zIndex = "9999";
  msg.style.opacity = "0";
  msg.style.transition = "opacity 0.3s ease";

  msg.style.backgroundColor = isOk ? "#28a745" : "#dc3545";

  document.body.appendChild(msg);
  setTimeout(() => (msg.style.opacity = "1"), 50);

  if (timeout > 0) {
    setTimeout(() => {
      msg.style.opacity = "0";
      setTimeout(() => msg.remove(), 300);
    }, timeout);
  }
}

// ------------ backend update -------------
async function atualizarContagem(codigo) {
  ultimaAcao = Date.now();

  try {
    const response = await fetch(`backend.php?action=updateContagem&nota=${encodeURIComponent(nota)}`, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `codigo=${encodeURIComponent(codigo)}`
    });

    const result = await response.json();

    if (!result.sucesso) {
      console.error("Erro retornado pelo backend:", result);
      showValidationMessage(result.erro || "Erro ao atualizar contagem!", false);
    } else {
      validatedForCodigo = null;
      showValidationMessage("Contagem atualizada com sucesso!", true);
      carregarItens();
    }

  } catch (error) {
    console.error("Erro ao atualizar contagem:", error);
    showValidationMessage("Erro de conexão ao atualizar contagem.", false);
  }
}

// ------------ finalizar parcial -------------
async function finalizarParcial() {

  if (!itemAtual) return;

  if (!confirm("Confirma que encontrou apenas parte da quantidade deste item?")) {
    return;
  }

  try {

    const response = await fetch(`backend.php?action=finalizarParcial&nota=${encodeURIComponent(nota)}`, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded"
      },
      body: `codigo=${encodeURIComponent(itemAtual.codigo)}`
    });

    const result = await response.json();

    if (result.sucesso) {

      showValidationMessage("Item marcado como PARCIAL.", true);

      carregarItens();

    } else {

      showValidationMessage(result.erro || "Erro ao finalizar item.", false);

    }

  } catch (e) {

    showValidationMessage("Erro de conexão.", false);

  }

}

// ------------ não encontrado -------------
async function naoEncontrado() {

  if (!itemAtual) return;

  if (!confirm("Confirma que nenhuma unidade foi encontrada?")) {
    return;
  }

  try {

    const response = await fetch(`backend.php?action=naoEncontrado&nota=${encodeURIComponent(nota)}`, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded"
      },
      body: `codigo=${encodeURIComponent(itemAtual.codigo)}`
    });

    const result = await response.json();

    if (result.sucesso) {

      showValidationMessage("Item marcado como NÃO ENCONTRADO.", true);

      carregarItens();

    } else {

      showValidationMessage(result.erro || "Erro.", false);

    }

  } catch (e) {

      showValidationMessage("Erro de conexão.", false);

  }

}

// ------------ validar item digitado -------------
function validarItem(buscaBruta) {
  if (!itemAtual) {
    showValidationMessage("Nenhum item carregado para validar.", false);
    return false;
  }

  const entrada = normalizeCode(buscaBruta);
  const codBarraItem = normalizeCode(itemAtual.cod_barra);

  if (!entrada) {
    showValidationMessage("Digite ou escaneie o código de barras.", false);
    return false;
  }

  // Se o item não tiver código de barras cadastrado
  if (!codBarraItem) {
    showValidationMessage("Este item não possui código de barras cadastrado.", false);
    return false;
  }

  // LIBERA SOMENTE SE FOR EXATAMENTE O CÓDIGO DE BARRAS
  if (entrada === codBarraItem) {

    const input = document.getElementById(`contagem-${itemAtual.codigo}`);

    let valorAtual = parseInt(input.value || 0);

    valorAtual++;

    input.value = valorAtual;

    atualizarContagem(itemAtual.codigo);

    return true;
}

  showValidationMessage("O código digitado não confere com o código de barras do item!", false);
  return false;
}

// ------------ carregar itens -------------
async function carregarItens() {
  try {
    const url = "backend.php?action=getItens&nota=" + encodeURIComponent(nota);
    const response = await fetch(url);
    const text = await response.text();

    console.log("Resposta bruta backend:", text);

    let data;
    try {
      data = JSON.parse(text);
    } catch (err) {
      console.error("Erro ao interpretar JSON:", err);
      showValidationMessage("Erro no retorno do servidor. Veja console.", false, 6000);
      document.querySelector("#tabela-itens tbody").innerHTML =
        `<tr><td colspan="6" style="color:red;">Erro ao interpretar resposta do servidor.</td></tr>`;
      return;
    }

    const tbody = document.querySelector("#tabela-itens tbody");
    tbody.innerHTML = "";

    if (data.erro) {
      console.error("Erro retornado pelo backend:", data.erro);
      tbody.innerHTML = `<tr><td colspan="6" style="color:red;">${data.erro}</td></tr>`;
      showValidationMessage(data.erro, false, 6000);
      return;
    }

    if (!Array.isArray(data) || data.length === 0) {
      tbody.innerHTML = `<tr><td colspan="6">Todos os itens foram separados!</td></tr>`;
      itemAtual = null;
      validatedForCodigo = null;
      return;
    }

    itemAtual = data[0];
    validatedForCodigo = null;

    const codigo = itemAtual.codigo ?? "";
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td data-label="Local">${itemAtual.locall ?? ""}</td>
      <td data-label="Código">${codigo}</td>
      <td data-label="Marca">${itemAtual.marca ?? ""}</td>
      <td data-label="Descrição">${itemAtual.descricao ?? ""}</td>
      <td data-label="Quantidade">${itemAtual.quantidade ?? ""}</td>
      <td data-label="Contagem">
        <input type="number" min="0" class="input-contagem"
               id="contagem-${codigo}" value="${itemAtual.contagem ?? 0}"
               readonly disabled>
      </td>
    `;
    tbody.appendChild(tr)

  } catch (error) {
    console.error("Erro geral ao carregar itens:", error);
    document.querySelector("#tabela-itens tbody").innerHTML =
      `<tr><td colspan="6" style="color:red;">Erro ao conectar com o servidor.</td></tr>`;
    showValidationMessage("Erro de conexão com o servidor.", false, 6000);
  }
}

// ------------ timer auto refresh -------------
function iniciarTimerAtualizacao() {
  clearInterval(timerAtualizacao);
  timerAtualizacao = setInterval(() => {
    const agora = Date.now();
    if (agora - ultimaAcao >= TEMPO_ESPERA) {
      carregarItens();
      ultimaAcao = Date.now();
    }
  }, 150000);
}

// ------------ inicialização -------------
document.addEventListener("DOMContentLoaded", () => {
  const urlParams = new URLSearchParams(window.location.search);
  nota = urlParams.get("nota");

  if (!nota) {
    showValidationMessage("Nenhuma nota informada!", false);
    return;
  }

  const spanNota = document.getElementById("notaCarregada");
  if (spanNota) spanNota.textContent = nota;

  carregarItens();
  iniciarTimerAtualizacao();

  document.getElementById("buscar")?.addEventListener("keypress", (e) => {
    if (e.key === "Enter") {
      validarItem(e.target.value);
      e.target.value = "";
    }
  });

  document.getElementById("btnValidar")?.addEventListener("click", () => {
    const b = document.getElementById("buscar");
    validarItem(b ? b.value : "");
    if (b) b.value = "";
  });

  document.getElementById("btnParcial")?.addEventListener("click", finalizarParcial);

document.getElementById("btnNaoEncontrado")?.addEventListener("click", naoEncontrado);

  document.getElementById("btnConferidos")?.addEventListener("click", () => {
    window.location.href = "conferidos.html?nota=" + encodeURIComponent(nota);
  });

  document.getElementById("btnFinalizar")?.addEventListener("click", () => {
    window.location.href = "divergencias.html?nota=" + encodeURIComponent(nota);
  });
});
