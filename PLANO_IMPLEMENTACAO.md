# PLANO DE IMPLEMENTAÇÃO

## Seção 1 — Identificação e visão geral

Equipe Gauge <br>  
Nome dos Integrantes: <br> Gustavo Henrique Rapouso; <br> Alice Lopes Fernandes; <br> Vitória Jaroczinski; <br> Davi Felski de Vargas.

### Mockup:
Mobile:https://www.figma.com/design/ZlR3q7Mhq8Aml1ugFxDBSS/Mockup?node-id=0-1&t=Dkwz7SfWT71LABeK-1 <br>
Desktop:https://www.figma.com/design/SRQWW2NRlSuZTv5nlgNLyQ/Desktop?node-id=0-1&t=p8QBmhttARt9gCcH-1  


### Resumo:
Gauge é um sistema de ferrorama que está voltado para a segurança, monitoramento, gerenciamento e uso de trens. Ademais, o site possui telas para três diferentes tipos de clientes, o gestor, o maquinista e o passageiro. Os passageiros acompanhem rotas, linhas, localização do trem em tempo real, horários de funcionamento e de parada, pontos de chegada e desembarque, e também previsões de chegada e partida. Além disso, o mockup tem acesso aos sensores cadastrados e relatórios sobre tudo o que acontece no site ou fora dele (com gráficos e tabelas), visualizações de usuários, trens (de carga ou de passageiro), pontos de parada, tempo do trabalho realizado (tempo do tráfego, do término de ocorrências, de trabalho) para gestores e até mesmo para os maquinistas.

Decisão técnica do grupo sobre a stack a ser adotada na implementação: HTML, CSS, JavaScript puro? Algum framework? Bootstrap ou CSS puro? Justifique brevemente cada escolha.

### Decisão Técnica:
HTML: Utilização para criar as páginas do site, estrutura de forme ordenada, para a criação da área de login, cadastro de sensores e visualização de rotas, relatórios, usuários. O HtML é a estrutura básica do site.
CSS: Será usado para estilizar o site, deixando proporcional os textos e imagens, chamativo, interface organizada e também para que seja feita uma identidade visual escolhida pela equipe. Sendo o uso puro do CSS.
JavaScript: O JavaScript ele será responsável pela funcionabilidade do sistema, como a validação de login, eventos quando for precionado um botão, atualização das informações em tempo real, a própria interação com o site (mapas, rotas, menu principal), comunicação entre outros usuários. Ele é o que deixa o site funcional.  
Bootstrap: Ele ajuda a personalizar mais o site, colocando ícones e criação de layouts. Ele ajuda a não precisar criar interfaces do zero.



## Seção 2 — Arquitetura de arquivos e pastas

GAUGE/
|-Index.html 
|-cadastro.html
|-dashboard.html 
|-perfil.html
|-carga.html
|-passageiro.html
|-usuarios.html
|-sensores.html
|-relatorios.html
|-suporte.html

css/
|-global.css
|-login.css
|-cadastro.css
|-dashboard.css
|-perfil.css
|-trens.css
|-usuarios.css
|-sensores.css
|-relatorios.css
|-suporte.css

js/
|-autentificação.js
|-dados.js
|-dashboard.js
|-sensores.js
|-relatorios.js
|-usuarios.js
|-main.js

assets/
|-imagens/
|-icones/
|-logos/
|-fontes/

docs/
|-requisitos.md
|-fluxos.md
|-planejamento.md

## Seção 3 - Componentes reutilizáveis identificados

#### Componente:
1. botão de ação   
2. Campo de texto (input)   
3. Menu suspenso (dropdown)   
4. Card de opção/menu   
5. Barra de navegação inferior   
6. Tela de mapa   
7. Lista de informações   
8. Tabela de dados   
9. Indicadores de status    
10. Ícone de perfil    
11. Tela de carregamento   
12. Mensagem de erro   
13. Rodapé institucional   
14. Gráficos   
15. Calendário/Listagem de horários   


#### Telas em que aparece:
1. Login, Cadastro, Perfil, Cadastro de Sensor, Relatórios, Rotas   
2. Login, Cadastro, Perfil, Adicionar Sensor, Remover Sensor   
3. Cadastro, Relatórios, Trem de Passageiro, Trem de Carga   
4. Dashboard, Página Inicial do Gestor, Cliente e Maquinista   
5. Dashboard, Perfil, Suporte e páginas principais   
6. Buscar, Minha Rota, Relatórios, Rotas dos trens   
7. Trem de Passageiro, Trem de Carga, Linha Central  
8. Lista de Sensores, Usuários, Relatórios   
9. Sensores, Usuários, Relatórios   
10. Cadastro, Perfil, Usuários   
11. Inicialização do aplicativo   
12. Cadastro de Sensor   
13. Relatórios, Sensores e gráficos   
14. Relatórios de Trem de Passageiro e Trem de Carga   
15. Trem de Passageiro e Trem de Carga   


#### Variações observadas:
1. Diferentes textos (Entrar, Salvar, Confirmar, Voltar, Ver rota, Excluir conta)  
2. Nome, senha, e-mail, telefone, observações, data de cadastro  
3. Perfil, tipo de trem, sensor, ocorrência, linha   
4. Card com ícone e título, card apenas com texto   
5. Ícone da página atual destacado  
6. Com marcadores diferentes e trajetos diferentes   
7. Quantidade de itens e informações diferentes   
8. Número de linhas e colunas varia   
9. Ativo (verde), Alerta (amarelo), Falha (vermelho)   
10. Perfil vazio ou preenchido  
11. Apenas logo e identidade visual   
12. Exibição de alerta de erro do sistema   
13. Contato, suporte e logo da empresa  
14. Dados diferentes para cada tipo de trem   
15. Horários e datas diferentes  


## Seção 4 — Ordem de implementação

1. Listagem e pesquisa de trens
Primeiro será desenvolvida a tela de listagem de trens de carga e passageiros, junto com a pesquisa por data e horário. Essa etapa vem primeiro porque os trens são a base do sistema e serão usados nas próximas funcionalidades.
2. Detalhamento do trem
Depois será criada a tela de detalhamento do trem. Ela depende da listagem, pois o usuário precisa escolher um trem para visualizar suas informações.
3. Cadastro de sensores
Em seguida serão desenvolvidas as telas de cadastro e gerenciamento de sensores. Como cada sensor estará ligado a um trem ou trilho, é necessário que essas informações já existam. Serão cadastrados dados como modelo, localização, datas e observações.
4. Relatórios
Após o cadastro de trens e sensores, serão criadas as telas de relatórios. Elas utilizarão os dados cadastrados anteriormente para gerar tabelas e gráficos.
5. Mapas e rotas
Com os dados já disponíveis, serão desenvolvidos os mapas e rotas do sistema. Esses componentes serão utilizados em telas como trens, relatórios e rotas.
6. Login e cadastro
Depois serão criadas as telas de login e cadastro de usuários. Elas serão responsáveis pelo controle de acesso ao sistema.
7. Perfil e usuários
Após a autenticação, serão desenvolvidas as telas de Perfil e Usuários, que utilizarão as informações cadastradas no login.
8. Dashboard e menu principal
Por último serão criados o Dashboard e o Menu Principal. Eles dependem de todas as funcionalidades anteriores, pois exibem informações de várias partes do sistema e serão adaptados para cada tipo de usuário (cliente, gestor ou maquinista).

## Seção 5 — Fluxos de navegação do usuário

Fluxo 1: Consultar informações de um trem
Perfil: Cliente
Tela de Login - faz login
Dashboard - acessa a área de trens
Lista de Trens - pesquisa o trem desejado
Seleciona um trem
Tela de Detalhes - visualiza informações do trem, trilho, rota e sensors


Fluxo 2: Verificar um alerta de sensor
Perfil: Maquinista
Tela de Login - faz login
Dashboard - visualiza os alertas
Clica em um alerta
Tela de Sensores - visualiza os detalhes
Sistema mostra o trem e a localização do problema
Maquinista acompanha a ocorrência


Fluxo 3: Cadastrar um sensor
Perfil: Gestor
Tela de Login - faz login
Dashboard - acessa a área de sensores
Clica em "Cadastrar Sensor"
Preenche os dados do sensor
Clica em "Salvar"
Sistema confirma o cadastro
Sensor aparece na lista



Fluxo 4: Gerar relatório
Perfil: Gestor
Tela de Login - faz login
Dashboard - acessa a área de relatórios
Escolhe o tipo de relatório e o período
Clica em "Gerar Relatório"
Sistema exibe tabelas e gráficos
Gestor visualiza ou exporta o relatório


Fluxo 5: Cadastrar um usuário
Perfil: Gestor
Tela de Login - faz login
Dashboard - acessa a área de usuários
Clica em "Adicionar Usuário"
Preenche os dados do usuário
Escolhe o perfil de acesso (Gestor, Maquinista ou Cliente)
Clica em "Salvar"
Sistema confirma o cadastro


Fluxo 6: Sair do sistema
Perfil: Todos os usuários
Dashboard - acessa o menu de perfil
Clica em "Sair"
Sistema encerra a sessão
Retorna para a Tela de Login
