<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Calendário</title>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    />
    <link rel="stylesheet" href="calendario_trem_style.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Fjalla+One&family=Roboto:wght@400;500;700&display=swap"
      rel="stylesheet"
    />
  </head>
  <body>

        <header class="header">
        <div class="left">
            <i class="fas fa-bars" title="Menu"></i>
            <i class="fas fa-search" title="Pesquisar"></i>
        </div>
        <div class="logo">
            <img src="../../assets/logo_gauge_menor.png" alt="Gauge Logo">
        </div>
    
    </header>

    <main>
    <section>
      <h1 id="titulo">Calendário</h1>
      <div class="barra-pesquisa">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="16"
          height="16"
          fill="currentColor"
          class="lupa"
          viewBox="0 0 16 16"
        >
          <path
            d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"
          />
        </svg>
        <input type="text" placeholder="Linha Central" id="pesquisa_bar" />
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="16"
          height="16"
          fill="currentColor"
          class="microfone"
          viewBox="0 0 16 16"
        >
          <path d="M5 3a3 3 0 0 1 6 0v5a3 3 0 0 1-6 0z" />
          <path
            d="M3.5 6.5A.5.5 0 0 1 4 7v1a4 4 0 0 0 8 0V7a.5.5 0 0 1 1 0v1a5 5 0 0 1-4.5 4.975V15h3a.5.5 0 0 1 0 1h-7a.5.5 0 0 1 0-1h3v-2.025A5 5 0 0 1 3 8V7a.5.5 0 0 1 .5-.5"
          />
        </svg>
      </div>
    </section>

    <section>
      <table class="calendario">
        <tr>
          <th>Data</th>
          <th>Saída</th>
          <th>Chegada</th>
        </tr>
        <tr class="linha">
          <td><div>26/05/26</div></td>
          <td><div>07:15</div></td>
          <td><div>9:15</div></td>
        </tr>
        <tr class="linha">
          <td><div>26/05/26</div></td>
          <td><div>12:00</div></td>
          <td><div>14:00</div></td>
        </tr>
        <tr class="linha">
          <td><div>26/05/26</div></td>
          <td><div>18:30</div></td>
          <td><div>20:30</div></td>
        </tr>
        <tr class="linha">
          <td><div>27/05/26</div></td>
          <td><div>07:15</div></td>
          <td><div>09:15</div></td>
        </tr>
        <tr class="linha">
          <td><div>27/05/26</div></td>
          <td><div>12:00</div></td>
          <td><div>14:00</div></td>
        </tr>
        <tr class="linha">
          <td><div>27/05/26</div></td>
          <td><div>18:30</div></td>
          <td><div>20:30</div></td>
        </tr>
        <tr class="linha">
          <td><div>28/05/26</div></td>
          <td><div>07:15</div></td>
          <td><div>09:15</div></td>
        </tr>
      </table>
    </section>
    </main>

        <footer class="footer-gauge">
    <div class="footer-container">
        
        <div class="footer-bloco bloco-esquerda">
            <span class="footer-label">Entre em contato</span>
            <a href="tel:47999174896" class="footer-link">(47) 99917-4896</a>
        </div>

        <div class="footer-bloco bloco-centro">
            <img src="../../assets/logo_gauge_menor.png" alt="Gauge Logo" class="footer-logo">
            <p class="footer-copyright">&copy; 2026 Gauge. Todos os direitos reservados.</p>
        </div>

        <div class="footer-bloco bloco-direita">
            <span class="footer-label">Precisa de Suporte?</span>
            <a href="mailto:contato@gauge.com.br" class="footer-link link-sublinhado">contato@gauge.com.br</a>
        </div>

    </div>
</footer>
  </body>
</html>
