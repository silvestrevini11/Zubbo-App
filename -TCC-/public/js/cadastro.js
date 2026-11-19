// Cadastro: removidos listeners antigos do dropdown que não existe mais.
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


if (formulario && senha && confirmarSenha && erroSenha) formulario.addEventListener("submit", function (event) {

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