// =====================================================
// DROPDOWN - DEFICIÊNCIA
// =====================================================

const botao = document.getElementById("botao-cad");
const lista = document.getElementById("lista-cad");
const opcoes = document.querySelectorAll(".opcao-cad");


// ABRIR / FECHAR
botao.addEventListener("click", function () {
    lista.classList.toggle("aberta");
});


// SELEÇÃO DAS OPÇÕES
opcoes.forEach(function (opcao) {

    opcao.addEventListener("click", function () {

        const naoPossui =
            this.textContent.trim() === "Não possuo deficiência";


        // =====================================================
        // CASO: "NÃO POSSUO DEFICIÊNCIA"
        // =====================================================

        if (naoPossui) {

            // Se já estiver selecionada, desmarca
            if (this.classList.contains("selecionada")) {

                this.classList.remove("selecionada");

            }

            // Se não estiver selecionada
            else {

                // Desmarca todas as outras
                opcoes.forEach(function (item) {
                    item.classList.remove("selecionada");
                });

                // Seleciona "Não possuo deficiência"
                this.classList.add("selecionada");
            }

        }


        // =====================================================
        // CASO: OUTRA DEFICIÊNCIA
        // =====================================================

        else {

            const semDeficiencia = Array.from(opcoes).find(function (item) {

                return item.textContent.trim() ===
                    "Não possuo deficiência";

            });


            // Se "Não possuo deficiência" estiver selecionado,
            // não permite selecionar esta opção
            if (semDeficiencia.classList.contains("selecionada")) {
                return;
            }


            // Permite selecionar/desselecionar normalmente
            this.classList.toggle("selecionada");
        }


        // Atualiza o texto do botão
        atualizarBotao();

    });

});


// =====================================================
// ATUALIZAR TEXTO DO BOTÃO
// =====================================================

function atualizarBotao() {

    const selecionadas = Array.from(opcoes)

        .filter(function (opcao) {

            return opcao.classList.contains("selecionada");

        })

        .map(function (opcao) {

            return opcao.textContent.trim();

        });


    if (selecionadas.length === 0) {

        botao.textContent = "Lista";

    }

    else if (selecionadas.length === 1) {

        botao.textContent = selecionadas[0];

    }

    else {

        botao.textContent =
            selecionadas.length + " selecionadas";

    }

}


// =====================================================
// FECHAR CLICANDO FORA
// =====================================================

document.addEventListener("click", function (event) {

    if (!event.target.closest(".dropdown-cad")) {

        lista.classList.remove("aberta");

    }

});


// =====================================================
// TELEFONE - SOMENTE NÚMEROS
// =====================================================

const telefone = document.getElementById("telefone");

if (telefone) {

    telefone.addEventListener("input", function () {

        // Remove tudo que não for número
        this.value = this.value.replace(/\D/g, "");

        // Limita a 11 números
        this.value = this.value.slice(0, 11);

    });

}


// =====================================================
// VALIDAÇÃO DAS SENHAS
// =====================================================

const formulario = document.querySelector(".Cadastro-tabela");
const senha = document.getElementById("senha");
const confirmarSenha = document.getElementById("confirmar-senha");
const erroSenha = document.getElementById("erro-senha");


formulario.addEventListener("submit", function (event) {

    if (senha.value !== confirmarSenha.value) {

        event.preventDefault();

        erroSenha.style.display = "block";

        erroSenha.textContent =
            "As senhas não coincidem.";

        confirmarSenha.focus();

    }

    else {

        erroSenha.style.display = "none";

    }

});
//Pesquisa: 
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

