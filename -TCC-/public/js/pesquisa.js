const pesquisaInput = document.getElementById('pesquisaInput');
const resultadosPerfis = document.getElementById('resultadosPerfis');
const resultadosPoles = document.getElementById('resultadosPoles');
const resultadosEventos = document.getElementById('resultadosEventos');

let tempoPesquisa;

function mensagemSemResultado(texto) {
    return '<p class="pesquisa-sem-resultados">' + texto + '</p>';
}

async function buscar(url) {
    const response = await fetch(url);

    if (!response.ok) {
        throw new Error('HTTP ' + response.status);
    }

    return response.json();
}

pesquisaInput.addEventListener('input', function () {
    clearTimeout(tempoPesquisa);

    const pesquisa = this.value.trim();

    if (pesquisa === '') {
        resultadosPerfis.innerHTML = '';
        resultadosPoles.innerHTML = '';
        resultadosEventos.innerHTML = '';
        return;
    }

    tempoPesquisa = setTimeout(async () => {
        try {
            const termo = encodeURIComponent(pesquisa);

            const [perfis, poles, eventos] = await Promise.all([
                buscar('pesquisar-perfis.php?pesquisa=' + termo),
                buscar('pesquisar-poles.php?pesquisa=' + termo),
                buscar('pesquisar-eventos.php?pesquisa=' + termo)
            ]);

            resultadosPerfis.innerHTML = '';
            resultadosPoles.innerHTML = '';
            resultadosEventos.innerHTML = '';

            if (perfis.length === 0) {
                resultadosPerfis.innerHTML = mensagemSemResultado('Nenhum perfil encontrado.');
            } else {
                perfis.forEach(perfil => {
                    const resultado = document.createElement('a');

                    resultado.className = 'resultado-perfil';
                    resultado.href = '../perfil/perfil-ver.php?id=' + perfil.id_user;

                    resultado.innerHTML = `
                        <img src="${perfil.foto}" class="resultado-perfil-foto" alt="Foto de perfil">
                        <div class="resultado-perfil-info">
                            <strong>${perfil.nome}</strong>
                            <span>${perfil.email}</span>
                        </div>
                    `;

                    resultadosPerfis.appendChild(resultado);
                });
            }

            if (poles.length === 0) {
                resultadosPoles.innerHTML = mensagemSemResultado('Nenhum pole encontrado.');
            } else {
                poles.forEach(pole => {
                    const resultado = document.createElement('div');

                    resultado.className = 'resultado-pole';

                    resultado.innerHTML = `
                        <strong>${pole.nome_local}</strong>
                        <span>${pole.endereco_local}</span>
                    `;

                    resultadosPoles.appendChild(resultado);
                });
            }

            if (eventos.length === 0) {
                resultadosEventos.innerHTML = mensagemSemResultado('Nenhum evento encontrado.');
            } else {
                eventos.forEach(evento => {
                    const resultado = document.createElement('a');

                    resultado.className = 'resultado-evento';
                    resultado.href = '../eventos/detalhes-evento.php?id_evento=' + evento.id_evento;

                    resultado.innerHTML = `
                        <strong>${evento.nome_evento}</strong>
                        <span>${evento.nome_esporte} · ${evento.nome_local}</span>
                        <span>${evento.data_evento} · ${evento.horario_evento}</span>
                    `;

                    resultadosEventos.appendChild(resultado);
                });
            }

            if (perfis.length === 0 && poles.length === 0 && eventos.length === 0) {
                resultadosPerfis.innerHTML = mensagemSemResultado('Nenhum resultado encontrado.');
                resultadosPoles.innerHTML = '';
                resultadosEventos.innerHTML = '';
            }
        } catch (error) {
            console.error('ERRO NA PESQUISA:', error);

            resultadosPerfis.innerHTML = mensagemSemResultado('Não foi possível realizar a pesquisa.');
            resultadosPoles.innerHTML = '';
            resultadosEventos.innerHTML = '';
        }
    }, 300);
});
