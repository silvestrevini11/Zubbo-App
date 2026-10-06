
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