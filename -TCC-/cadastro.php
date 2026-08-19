<?php
include __DIR__.'/includes/head.php';
?>

<section class="Tela-cad">

    <img class="logo-cad" src="./imagem/LogooZ.png" alt="">

    <h1 class="title-cad">Criar Conta</h1>

    <p class="desc-cad">Junta-se à comunidade e viva o esporte</p>

    <form class="Cadastro-tabela" action="insert-user.php" method="post">
    <div class="field-cad">
        <input type="text" placeholder="Nome Completo" name="name-txt" required>
    </div>

    <div class="field-cad">
        <input type="email" placeholder="E-mail" name="email-txt" required>
    </div>

    <p class="par-cad">Usaremos para recuperação de conta e notificações</p>

    <div class="field-cad">
        <input type="tel" placeholder="Telefone" inputmode="numeric" name="telefone-tel" maxlength="15" pattern="[0-9]*" required>
    </div>

    <p class="par-cad">Usaremos para login e contato</p>

    <div class="field-cad">
        <input type="password" placeholder="Senha" name="Senha-pass" id="senha" required>
    </div>

    <div class="field-cad">
        <input type="password" placeholder="Confirmar Senha" name="confirmar-senha" id="confirmar-senha" required>
    </div>

    <p id="erro-senha" style="display: none;">
        As senhas não coincidem.
    </p>

    <h2 class="sub-title-cad">Data de nascimento</h2>

    <div class="field-cad">
        <input type="date" name="data-nasc" required>
    </div>

    <h2 class="sub-title-cad">Você possui alguma deficiência?</h2>

    <div class="dropdown-cad">

        <button type="button" class="dropdown-button-cad" id="botao-cad">
            Lista
        </button>

        <div class="lista-cad" id="lista-cad">

            <div class="opcao-cad">Deficiência visual</div>
            <div class="opcao-cad">Deficiência auditiva</div>
            <div class="opcao-cad">Deficiência física</div>
            <div class="opcao-cad">Não possuo deficiência</div>

        </div>

    </div>

    <div class="seguranca-cad">
        <span class="seguranca-icone">✓</span>
        <span>Suas informações estão seguras com a gente</span>
    </div>

    <button class="btn-cad" type="submit">Criar conta</button>
    </form>
    

    <h3 class="enter-cad">
        Já tem uma conta?
        <a href="login.php">Entrar</a>
    </h3>

<script>
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

            const naoPossui = this.textContent.trim() === "Não possuo deficiência";

            // =====================================================
            // CASO: "NÃO POSSUO DEFICIÊNCIA"
            // =====================================================

            if (naoPossui) {

                // Se já estiver selecionada, desmarca
                if (this.classList.contains("selecionada")) {

                    this.classList.remove("selecionada");

                }

                // Se não estiver selecionada:
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

                // Se "Não possuo deficiência" estiver selecionado,
                // não permite selecionar esta opção
                const semDeficiencia = Array.from(opcoes).find(function (item) {
                    return item.textContent.trim() === "Não possuo deficiência";
                });

                if (semDeficiencia.classList.contains("selecionada")) {
                    return;
                }

                // Permite selecionar/desselecionar normalmente
                this.classList.toggle("selecionada");
            }


            // =====================================================
            // ATUALIZA O TEXTO DO BOTÃO
            // =====================================================

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

            botao.textContent = selecionadas.length + " selecionadas";

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
</script>

<script>
    const formulario = document.querySelector(".Cadastro-tabela");
const senha = document.getElementById("senha");
const confirmarSenha = document.getElementById("confirmar-senha");
const erroSenha = document.getElementById("erro-senha");

formulario.addEventListener("submit", function(event) {

    if (senha.value !== confirmarSenha.value) {

        event.preventDefault();

        erroSenha.style.display = "block";
        erroSenha.textContent = "As senhas não coincidem.";

        confirmarSenha.focus();

    } else {

        erroSenha.style.display = "none";

    }

});
</script>

<?php
include __DIR__.'/includes/footer.php';
?>