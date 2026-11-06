// Abre e fecha o menu lateral.
// - Telas grandes: o menu fica ao lado do conteúdo e pode ser recolhido
//   (a escolha fica salva no navegador).
// - Telas pequenas: o menu abre por cima do conteúdo.

document.addEventListener('DOMContentLoaded', function () {
    const botao = document.getElementById('botaoMenu');
    const fundo = document.getElementById('fundoMenu');

    if (!botao) {
        return;
    }

    const telaPequena = window.matchMedia('(max-width: 900px)');

    // A preferência salva é aplicada logo no início do <body>
    // (ver includes/cabecalho.php).
    function salvarPreferencia(recolhido) {
        try {
            localStorage.setItem('menuRecolhido', recolhido ? 'sim' : 'nao');
        } catch (erro) {
            // Sem acesso ao armazenamento: apenas não salva a escolha.
        }
    }

    function menuVisivel() {
        return telaPequena.matches
            ? document.body.classList.contains('menu-aberto')
            : !document.body.classList.contains('menu-recolhido');
    }

    function atualizarBotao() {
        const aberto = menuVisivel();

        botao.setAttribute('aria-expanded', aberto);
        botao.setAttribute('aria-label', aberto ? 'Fechar menu' : 'Abrir menu');
        fundo.hidden = !(telaPequena.matches && aberto);
    }

    function fecharMenuCelular() {
        document.body.classList.remove('menu-aberto');
        atualizarBotao();
    }

    botao.addEventListener('click', function () {
        if (telaPequena.matches) {
            document.body.classList.toggle('menu-aberto');
        } else {
            const recolhido = document.body.classList.toggle('menu-recolhido');
            salvarPreferencia(recolhido);
        }

        atualizarBotao();
    });

    fundo.addEventListener('click', fecharMenuCelular);

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            fecharMenuCelular();
        }
    });

    telaPequena.addEventListener('change', fecharMenuCelular);

    atualizarBotao();
});
