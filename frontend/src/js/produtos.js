(() => {
  const grid = document.getElementById("produtos");
  const detalhe = document.getElementById("produto");
  const apiBase =
    window.API_BASE_URL ||
    `${window.location.protocol}//${window.location.hostname}:8000/api`;
  const imagensBase = apiBase.replace(/\/api$/, "") + "/images";

  function escapar(valor = "") {
    return String(valor).replace(
      /[&<>"']/g,
      (caractere) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#39;",
        })[caractere],
    );
  }

  function moeda(valor) {
    return Number(valor).toLocaleString("pt-BR", {
      style: "currency",
      currency: "BRL",
    });
  }

  function normalizarLista(resposta) {
    if (Array.isArray(resposta)) return resposta;
    if (Array.isArray(resposta?.data)) return resposta.data;
    return [];
  }

    async function atualizarBadgeCarrinho() {
    const badge = document.getElementById("badge");
    if (!badge) return;

    try {
      const carrinho = await window.api("/carrinho");
      const total = (carrinho.items || []).reduce(
        (soma, item) => soma + Number(item.quantity),
        0,
      );
      badge.textContent = total;
      badge.hidden = total === 0;
      document
        .getElementById("btnCarrinho")
        ?.setAttribute("aria-label", `Carrinho, ${total} itens`);
    } catch {
      badge.hidden = true;
    }
  }

  function imagemDoProduto(produto) {
    if (!produto.image) return "";
    const imagem = String(produto.image).trim();
    if (imagem.startsWith("data:") || /^(https?:)?\/\//i.test(imagem)) {
      return imagem;
    }
    const arquivo = imagem.split("/").pop();
    return `${imagensBase}/${encodeURIComponent(arquivo)}`;
  }

  async function carregarCategorias() {
    const menu = document.getElementById("menuCategorias");
    if (!menu) return;

    try {
      const categorias = await window.api("/categorias");
      const categoriasLista = normalizarLista(categorias);

      menu.innerHTML = [
        '<a href="#produtos" data-category-id="">Todas as categorias</a>',
        ...categoriasLista.map(
          (categoria) =>
            `<a href="#produtos" data-category-id="${Number(categoria.id)}">${escapar(categoria.name)}</a>`,
        ),
      ].join("");
      menu.addEventListener("click", (event) => {
        const link = event.target.closest("[data-category-id]");
        if (!link) return;
        event.preventDefault();
        menu
          .querySelectorAll("a")
          .forEach((item) => item.classList.remove("ativo"));
        link.classList.add("ativo");
        carregarProdutos();
      });
    } catch (error) {
      console.error("Não foi possível carregar as categorias:", error);
    }
  }

  async function carregarProdutos() {
    if (!grid) return;

    const busca = document.getElementById("busca")?.value.trim() || "";
    const categoriaId = document.querySelector(
      "#menuCategorias [data-category-id].ativo",
    )?.dataset.categoryId;
    const parametros = new URLSearchParams();
    if (busca) parametros.set("q", busca);
    if (categoriaId) parametros.set("category_id", categoriaId);

    grid.innerHTML = '<p class="text-muted">Carregando produtos...</p>';
    try {
      const produtos = await window.api(
        `/produtos${parametros.size ? `?${parametros}` : ""}`,
      );
      const produtosLista = normalizarLista(produtos);

      grid.innerHTML = produtosLista.length
        ? produtosLista
            .map(
              (produto) => `
                <article class="col-12 col-sm-6 col-lg-4">
                  <div class="card h-100 shadow-sm">
                      ${imagemDoProduto(produto) ? `<img class="card-img-top produto-imagem" src="${escapar(imagemDoProduto(produto))}" alt="${escapar(produto.name)}" loading="lazy">` : ""}
                    <div class="card-body d-flex flex-column">
                      <span class="badge text-bg-secondary align-self-start">${escapar(produto.category)}</span>
                      <h2 class="h5 mt-3">${escapar(produto.name)}</h2>
                      <p class="text-muted">${escapar(produto.description || "")}</p>
                      <strong class="mt-auto">${moeda(produto.price)}</strong>
                      <p class="small mt-2 mb-3">${Number(produto.stock) > 0 ? "Em estoque" : "Sem estoque"}</p>
                      <a href="produto.html?id=${encodeURIComponent(produto.id)}" class="btn btn-dark">Ver produto</a>
                    </div>
                  </div>
                </article>`,
            )
            .join("")
        : '<p class="text-muted">Nenhum produto encontrado.</p>';
    } catch (error) {
      grid.innerHTML = `<p class="alert alert-danger" role="alert">${escapar(error.message)}</p>`;
    }
  }

  async function carregarDetalhe() {
    const id = new URLSearchParams(window.location.search).get("id");
    if (!id || !/^\d+$/.test(id)) {
      detalhe.innerHTML =
        '<p class="alert alert-warning">Produto inválido ou não informado.</p>';
      return;
    }

    detalhe.innerHTML = "<p>Carregando produto...</p>";
    try {
      const produto = await window.api(`/produtos/${encodeURIComponent(id)}`);
      detalhe.innerHTML = `
        ${imagemDoProduto(produto) ? `<img class="produto-imagem produto-imagem-detalhe mb-4" src="${escapar(imagemDoProduto(produto))}" alt="${escapar(produto.name)}">` : ""}
        <span class="badge text-bg-secondary">${escapar(produto.category)}</span>
        <h1 class="mt-3">${escapar(produto.name)}</h1>
        <p>${escapar(produto.description || "")}</p>
        <h2>${moeda(produto.price)}</h2>
        <p>Estoque: ${Number(produto.stock)}</p>
        <label for="quantidade" class="form-label">Quantidade</label>
        <input id="quantidade" type="number" min="1" max="${Number(produto.stock)}" value="1" class="form-control mb-3" style="max-width: 120px" ${Number(produto.stock) < 1 ? "disabled" : ""}>
        <button id="adicionarCarrinho" class="btn btn-dark" ${Number(produto.stock) < 1 ? "disabled" : ""}>Adicionar ao carrinho</button>
        <p id="produtoMsg" class="mt-3" role="status"></p>`;

      document
        .getElementById("adicionarCarrinho")
        .addEventListener("click", async () => {
          const quantidade = document.getElementById("quantidade");
          const mensagem = document.getElementById("produtoMsg");
          const botao = document.getElementById("adicionarCarrinho");
          const valor = Number(quantidade.value);
          if (
            !Number.isInteger(valor) ||
            valor < 1 ||
            valor > Number(produto.stock)
          ) {
            mensagem.textContent =
              "Informe uma quantidade disponível em estoque.";
            mensagem.className = "text-danger mt-3";
            return;
          }

          botao.disabled = true;
          try {
            await window.api("/carrinho", {
              method: "POST",
              body: JSON.stringify({
                product_id: Number(produto.id),
                quantity: valor,
              }),
            });
            window.location.href = "carrinho.html";
          } catch (error) {
            if (error.status === 401) {
              window.location.href = "login.html";
              return;
            }
            mensagem.textContent = error.message;
            mensagem.className = "text-danger mt-3";
            botao.disabled = false;
          }
        });
    } catch (error) {
      detalhe.innerHTML = `<p class="alert alert-danger" role="alert">${escapar(error.message)}</p>`;
    }
  }

  if (grid) {
    document
      .getElementById("formBusca")
      ?.addEventListener("submit", (event) => {
        event.preventDefault();
        carregarProdutos();
      });
    document.getElementById("busca")?.addEventListener("input", () => {
      window.clearTimeout(window.buscaProdutosTimer);
      window.buscaProdutosTimer = window.setTimeout(carregarProdutos, 250);
    });
    document.getElementById("search")?.addEventListener("click", () => {
      document.getElementById("busca")?.focus();
    });
    carregarCategorias().then(carregarProdutos);
  }

   if (detalhe) carregarDetalhe();

  atualizarBadgeCarrinho();
})();
