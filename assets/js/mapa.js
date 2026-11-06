// Desenha mapas de viagens com o Leaflet.
//
// Uso no HTML (a página deve carregar o Leaflet antes deste arquivo):
//   <div class="mapa" data-viagens='[{"origem": "...", "destino": "...",
//        "titulo": "...", "linhas": ["Status: ...", ...]}]'></div>
//
// Opcional: data-geocodificar="sim" procura no OpenStreetMap (Nominatim)
// as cidades que não estão na lista CIDADES abaixo.
//
// As linhas são trajetos ilustrativos entre as cidades,
// não os trilhos reais.

// Coordenadas aproximadas das cidades já usadas nas viagens.
// Acrescente outras cidades quando necessário (nomes sem acento, em minúsculas).
const CIDADES = {
    'joinville': [-26.3044, -48.8487],
    'itajai': [-26.9101, -48.6705],
    'curitiba': [-25.4284, -49.2733],
    'sao francisco do sul': [-26.2433, -48.6380],
    'blumenau': [-26.9194, -49.0661],
    'florianopolis': [-27.5954, -48.5480],
    'jaragua do sul': [-26.4851, -49.0713],
    'barra velha': [-26.6329, -48.6846],
    'sao jose': [-27.6136, -48.6366],
    'balneario camboriu': [-26.9906, -48.6352],
    'navegantes': [-26.8943, -48.6546]
};

const COR_TRAJETO = '#6B4732';
const CENTRO_PADRAO = [-26.5, -48.7];

// "São Francisco do Sul " -> "sao francisco do sul"
function normalizarNome(nome) {
    return String(nome || '')
        .trim()
        .toLocaleLowerCase('pt-BR')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '');
}

function escaparHTML(valor) {
    return String(valor ?? '').replace(/[&<>"']/g, function (caractere) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[caractere];
    });
}

function textoPopup(titulo, linhas) {
    return '<strong>' + escaparHTML(titulo) + '</strong><br>' +
        linhas.map(escaparHTML).join('<br>');
}

const esperar = (ms) => new Promise((resolver) => setTimeout(resolver, ms));

// Procura uma cidade no OpenStreetMap. O serviço permite
// no máximo uma consulta por segundo, por isso há uma espera.
const cacheGeocodificacao = new Map();

async function geocodificar(nome) {
    const chave = normalizarNome(nome);

    if (cacheGeocodificacao.has(chave)) {
        return cacheGeocodificacao.get(chave);
    }

    await esperar(1100);

    const resposta = await fetch(
        'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' +
        encodeURIComponent(nome + ', Brasil')
    );
    const dados = await resposta.json();
    const coordenadas = dados.length ? [Number(dados[0].lat), Number(dados[0].lon)] : null;

    cacheGeocodificacao.set(chave, coordenadas);

    return coordenadas;
}

async function localizarCidade(nome, usarGeocodificacao) {
    const conhecida = CIDADES[normalizarNome(nome)];

    if (conhecida) {
        return conhecida;
    }

    if (!usarGeocodificacao || !nome) {
        return null;
    }

    try {
        return await geocodificar(nome);
    } catch (erro) {
        console.error('Erro ao localizar a cidade:', nome, erro);
        return null;
    }
}

async function desenharMapa(elemento) {
    const viagens = JSON.parse(elemento.dataset.viagens || '[]');
    const usarGeocodificacao = elemento.dataset.geocodificar === 'sim';

    const mapa = L.map(elemento);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(mapa);

    mapa.setView(CENTRO_PADRAO, 8);

    const pontos = [];
    const marcadores = new Set();

    for (const viagem of viagens) {
        const origem = await localizarCidade(viagem.origem, usarGeocodificacao);
        const destino = await localizarCidade(viagem.destino, usarGeocodificacao);

        if (!origem || !destino) {
            console.warn('Coordenadas não encontradas:', viagem.origem, viagem.destino);
            continue;
        }

        L.polyline([origem, destino], {
            color: COR_TRAJETO,
            weight: 4,
            opacity: 0.85,
            dashArray: '8, 8'
        })
            .addTo(mapa)
            .bindPopup(textoPopup(viagem.titulo, [
                viagem.origem + ' → ' + viagem.destino,
                ...(viagem.linhas || [])
            ]));

        // Um marcador por cidade, mesmo que apareça em várias viagens
        [[origem, viagem.origem], [destino, viagem.destino]].forEach(function ([coordenadas, cidade]) {
            const chave = coordenadas.join(',');

            if (!marcadores.has(chave)) {
                marcadores.add(chave);
                L.marker(coordenadas).addTo(mapa).bindPopup(textoPopup(cidade, []));
            }

            pontos.push(coordenadas);
        });
    }

    if (pontos.length > 0) {
        mapa.fitBounds(L.latLngBounds(pontos), { padding: [35, 35] });
    } else {
        L.popup()
            .setLatLng(CENTRO_PADRAO)
            .setContent('Nenhuma viagem possui origem e destino reconhecidos para exibição no mapa.')
            .openOn(mapa);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    if (typeof L === 'undefined') {
        console.error('Leaflet não foi carregado.');
        return;
    }

    document.querySelectorAll('.mapa[data-viagens]').forEach(desenharMapa);
});
