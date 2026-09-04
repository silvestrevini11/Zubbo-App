<?php 
include __DIR__ .'/../includes/head.php';
include __DIR__.'/../../../config/database.php';
?>

<section style="padding-bottom: 80px;">

    <div class="pesquisa-search">
        <input 
            type="text" 
            id="pesquisaInput"
            placeholder="PESQUISAR..." 
            name="text-pesquisar" 
            class="pesquisa-busca"
            autocomplete="off"
        >

        <button class="pesquisa-filtro" type="button"></button>
    </div>


    <h2 class="pesquisa-perfil">Perfis</h2>

    <div id="resultadosPerfis" class="resultados-perfis">
        <!-- Os resultados vão aparecer aqui -->
    </div>
    <hr class="perfil-hr">

    <h2 class="pesquisa-eventos">Eventos</h2>
    <hr class="perfil-hr">


    <h2 class="pesquisa-poles">Poles</h2>
    <hr class="perfil-hr">


    <h2 class="pesquisa-comunidade">Comunidades</h2>
    <hr class="perfil-hr">

</section>


<script>

const pesquisaInput = document.getElementById('pesquisaInput');
const resultadosPerfis = document.getElementById('resultadosPerfis');

let tempoPesquisa;

pesquisaInput.addEventListener('input', function () {

    clearTimeout(tempoPesquisa);

    const pesquisa = this.value.trim();

    if (pesquisa === '') {
        resultadosPerfis.innerHTML = '';
        return;
    }

    tempoPesquisa = setTimeout(() => {

        fetch('pesquisar-perfis.php?pesquisa=' + encodeURIComponent(pesquisa))

            .then(response => {

                if (!response.ok) {
                    throw new Error(
                        'HTTP ' + response.status
                    );
                }

                return response.text();

            })

            .then(texto => {

                console.log('Resposta do PHP:', texto);

                let perfis;

                try {
                    perfis = JSON.parse(texto);
                } catch (erro) {
                    throw new Error(
                        'O PHP não retornou JSON válido: ' + texto
                    );
                }

                resultadosPerfis.innerHTML = '';

                if (perfis.length === 0) {

                    resultadosPerfis.innerHTML = `
                        <p class="pesquisa-sem-resultados">
                            Nenhum perfil encontrado.
                        </p>
                    `;

                    return;
                }

                perfis.forEach(perfil => {

                    const resultado = document.createElement('a');

                    resultado.className = 'resultado-perfil';

                    resultado.href =
                        '../perfil/perfil-ver.php?id=' + perfil.id_user;

                    resultado.innerHTML = `
                        <img
                            src="${perfil.foto}"
                            class="resultado-perfil-foto"
                            alt="Foto de perfil"
                        >

                        <div class="resultado-perfil-info">

                            <strong>${perfil.nome}</strong>

                            <span>@${perfil.nome}</span>

                        </div>
                    `;

                    resultadosPerfis.appendChild(resultado);

                });

            })

            .catch(error => {

                console.error('ERRO NA PESQUISA:', error);

                resultadosPerfis.innerHTML = `
                    <p class="pesquisa-sem-resultados">
                        Erro: ${error.message}
                    </p>
                `;

            });

    }, 300);

});

</script>


<?php
include __DIR__ . '/../../views/includes/under-bar.php';
include __DIR__ . '/../../views/includes/footer.php';
?>