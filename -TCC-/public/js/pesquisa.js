const pesquisaInput = document.getElementById('pesquisaInput');
const resultadosPerfis = document.getElementById('resultadosPerfis');
const resultadosPoles = document.getElementById('resultadosPoles');
const resultadosEventos = document.getElementById('resultadosEventos');
let tempoPesquisa;
let versaoPesquisa = 0;

function mensagemSemResultado(destino, texto) {
    const p = document.createElement('p');
    p.className = 'pesquisa-sem-resultados';
    p.textContent = texto;
    destino.appendChild(p);
}

async function buscar(url) {
    const response = await fetch(url, { credentials: 'same-origin' });
    if (!response.ok) throw new Error('HTTP ' + response.status);
    return response.json();
}

function adicionarTexto(container, tag, texto) {
    const elemento = document.createElement(tag);
    elemento.textContent = texto;
    container.appendChild(elemento);
}

pesquisaInput.addEventListener('input', function () {
    clearTimeout(tempoPesquisa);
    const pesquisa = this.value.trim();
    const versaoAtual = ++versaoPesquisa;
    resultadosPerfis.replaceChildren();
    resultadosPoles.replaceChildren();
    resultadosEventos.replaceChildren();
    if (!pesquisa) return;

    tempoPesquisa = setTimeout(async () => {
        try {
            const termo = encodeURIComponent(pesquisa);
            const [perfis, poles, eventos] = await Promise.all([
                buscar('pesquisar-perfis.php?pesquisa=' + termo),
                buscar('pesquisar-poles.php?pesquisa=' + termo),
                buscar('pesquisar-eventos.php?pesquisa=' + termo)
            ]);
            if (versaoAtual !== versaoPesquisa) return;

            if (!perfis.length) {
                mensagemSemResultado(resultadosPerfis, 'Nenhum perfil encontrado.');
            }
            for (const perfil of perfis) {
                const id = Number.parseInt(perfil.id_user, 10);
                if (!Number.isInteger(id) || id <= 0) continue;
                const link = document.createElement('a');
                link.href = '../perfil/perfil-ver.php?id=' + id;
                link.className = 'resultado-perfil';
                const foto = document.createElement('img');
                foto.className = 'resultado-perfil-foto';
                foto.alt = 'Foto de perfil';
                foto.src = perfil.foto;
                const info = document.createElement('div');
                info.className = 'resultado-perfil-info';
                adicionarTexto(info, 'strong', perfil.nome);
                // E-mail do usuário nunca é retornado por esta busca.
                link.append(foto, info);
                resultadosPerfis.appendChild(link);
            }

            if (!poles.length) {
                mensagemSemResultado(resultadosPoles, 'Nenhum local encontrado.');
            }
            for (const pole of poles) {
                const id = Number.parseInt(pole.id_local, 10);
                if (!Number.isInteger(id) || id <= 0) continue;
                const link = document.createElement('a');
                link.className = 'resultado-pole';
                link.href = '../painel/Painel-inicial.php?id_local=' + id;
                adicionarTexto(link, 'strong', pole.nome_local);
                adicionarTexto(link, 'span', pole.endereco_local);
                adicionarTexto(link, 'small', 'Ver no mapa');
                resultadosPoles.appendChild(link);
            }

            if (!eventos.length) {
                mensagemSemResultado(resultadosEventos, 'Nenhum evento encontrado.');
            }
            for (const evento of eventos) {
                const id = Number.parseInt(evento.id_evento, 10);
                if (!Number.isInteger(id) || id <= 0) continue;
                const link = document.createElement('a');
                link.className = 'resultado-evento';
                link.href = '../eventos/detalhes-evento.php?id_evento=' + id;
                adicionarTexto(link, 'strong', evento.nome_evento);
                adicionarTexto(link, 'span', evento.nome_esporte + ' · ' + evento.nome_local);
                adicionarTexto(link, 'span', evento.data_evento + ' · ' + evento.horario_evento);
                resultadosEventos.appendChild(link);
            }
        } catch (error) {
            if (versaoAtual !== versaoPesquisa) return;
            console.error('Erro na pesquisa:', error);
            resultadosPerfis.replaceChildren();
            resultadosPoles.replaceChildren();
            resultadosEventos.replaceChildren();
            mensagemSemResultado(resultadosPerfis, 'Não foi possível realizar a pesquisa.');
        }
    }, 300);
});
