// divergencias.js - debugável e com mensagens claras
(function () {
  'use strict';

  const debugEl = () => document.getElementById('debug');
  const logDebug = (msg) => {
    console.log('[divergencias] ' + msg);
    const d = debugEl();
    if (d) { d.style.display = 'block'; d.textContent = msg; }
  };

  let nota = null;

  document.addEventListener('DOMContentLoaded', init);

  function init() {
    logDebug('DOM carregado');
    const urlParams = new URLSearchParams(window.location.search);
    nota = urlParams.get('nota');
    logDebug('Parâmetro nota = ' + String(nota));
    const span = document.getElementById('notaCarregada');
    if (span) span.textContent = nota || '(nenhuma nota)';

    if (!nota) {
      document.getElementById('resultado-body').innerHTML =
        "<tr><td colspan='6'>Nenhuma nota informada na URL. Use ?nota=XXXXX</td></tr>";
      return;
    }

    // ligar botões
    const btnBuscar = document.getElementById('btnBuscar');
    if (btnBuscar) btnBuscar.addEventListener('click', buscarProduto);

    const btnVoltar = document.getElementById('btnVoltar');
    if (btnVoltar) btnVoltar.addEventListener('click', () => {
      window.location.href = "index2.html?nota=" + encodeURIComponent(nota);
    });

    // primeiro carregamento
    carregarDivergencias();
  }

  async function carregarDivergencias() {
    try {
      const url = `backend.php?action=getDivergencias&nota=${encodeURIComponent(nota)}&_=${Date.now()}`;
      logDebug('Requisitando: ' + url);
      const res = await fetch(url, { cache: 'no-store' });
      logDebug('Status do fetch: ' + res.status);

      if (!res.ok) {
        const text = await res.text();
        logDebug('Resposta do servidor não OK. Status: ' + res.status);
        document.getElementById('resultado-body').innerHTML =
          `<tr><td colspan='6'>Erro no servidor (${res.status}). Ver console para detalhes.</td></tr>`;
        console.error('Resposta do backend:', text);
        return;
      }

      const data = await res.json();
      logDebug('Dados recebidos: ' + (Array.isArray(data) ? data.length + ' itens' : JSON.stringify(data).slice(0,200)));

      const tbody = document.getElementById('resultado-body');
      tbody.innerHTML = '';

      if (data.erro) {
        tbody.innerHTML = `<tr><td colspan='6'>${data.erro}</td></tr>`;
        return;
      }

      if (!Array.isArray(data) || data.length === 0) {
        tbody.innerHTML = "<tr><td colspan='6'>Nenhuma divergência encontrada!</td></tr>";
        return;
      }

      data.forEach(item => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${item.codigo ?? ""}</td>
          <td>${item.descricao ?? ""}</td>
          <td>${item.locall ?? ""}</td>
          <td>${item.quantidade ?? ""}</td>
          <td>${item.contagem ?? ""}</td>
          <td>${item.estoque_atual ?? ""}</td>
        `;
        tbody.appendChild(tr);
      });

      logDebug('Tabela atualizada com ' + data.length + ' linhas.');
    } catch (err) {
      console.error('Erro em carregarDivergencias:', err);
      document.getElementById('resultado-body').innerHTML =
        `<tr><td colspan='6'>Erro ao carregar divergências: ${err.message}</td></tr>`;
      logDebug('Erro: ' + err.message);
    }
  }

  function buscarProduto() {
    const termo = document.getElementById('search').value.toLowerCase();
    const linhas = document.querySelectorAll("#resultado-body tr");
    linhas.forEach((linha) => {
      const texto = linha.innerText.toLowerCase();
      linha.style.display = texto.includes(termo) ? "" : "none";
    });
  }

  // export (útil p/ debug manual no console)
  window.carregarDivergencias = carregarDivergencias;
})();
