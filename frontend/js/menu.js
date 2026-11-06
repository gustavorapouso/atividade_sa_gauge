document.addEventListener("DOMContentLoaded", function () {
  const menuBtn = document.getElementById("menuBtn");
  const sidebar = document.getElementById("sidebar");

  // Verifica se os elementos existem
  if (!menuBtn || !sidebar) {
    console.error("Menu ou botão do menu não encontrado.");
    return;
  }

  // Abre e fecha o menu lateral
  menuBtn.addEventListener("click", function () {
    sidebar.classList.toggle("aberto");

    const menuAberto = sidebar.classList.contains("aberto");

    // Atualiza acessibilidade do botão
    menuBtn.setAttribute("aria-expanded", menuAberto);

    menuBtn.setAttribute(
      "aria-label",
      menuAberto ? "Fechar menu" : "Abrir menu",
    );

    menuBtn.setAttribute("title", menuAberto ? "Fechar menu" : "Abrir menu");
  });
});
