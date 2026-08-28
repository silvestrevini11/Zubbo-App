<?php
include __DIR__.'/../includes/head.php';
?>

<section class="Tela-cad">

    <button class="btn-voltar" onclick="history.back()">
    ←  
    </button>

    <img class="logo-cad" src="/../-TCC-/public/imagem/LogooZ.png" alt="">

    <h1 class="title-cad">Criar Conta</h1>

    <p class="desc-cad">Junta-se à comunidade e viva o esporte</p>

    <form class="Cadastro-tabela" action="insert-user.php" method="post">

        <div class="field-cad">
            <input type="text" placeholder="Nome Completo" name="name-txt" required>
        </div>

        <div class="field-cad">
            <input type="email" placeholder="E-mail" name="email-txt" required>
        </div>

        <p class="par-cad">
            Usaremos para recuperação de conta e notificações
        </p>
        <div class="field-cad telefone-container">

    <span class="codigo-pais">+55</span>

    <input
        type="tel"
        placeholder="Telefone"
        inputmode="numeric"
        name="telefone-tel"
        id="telefone"
        maxlength="11"
        pattern="[0-9]{11}"
        required
    >

</div>

           <!--telefone
        <div class="field-cad">
            <input
                type="tel"
                placeholder="Telefone"
                inputmode="numeric"
                name="telefone-tel"
                maxlength="11"
                pattern="[0-9]{10,11}"
                required.-->
            
        </div>

        <p class="par-cad">
            Usaremos para login e contato
        </p>

        <div class="field-cad">
            <input
                type="password"
                placeholder="Senha"
                name="Senha-pass"
                id="senha"
                required
            >
        </div>

        <div class="field-cad">
            <input
                type="password"
                placeholder="Confirmar Senha"
                name="confirmar-senha"
                id="confirmar-senha"
                required
            >
        </div>

        <p id="erro-senha" style="display: none;">
            As senhas não coincidem.
        </p>

        <h2 class="sub-title-cad">
            Data de nascimento
        </h2>

        <div class="field-cad">
            <input type="date" name="data-nasc" required>
        </div>

        <h2 class="sub-title-cad">
            Você possui alguma deficiência?
        </h2>

        <div class="dropdown-cad">

            <button
                type="button"
                class="dropdown-button-cad"
                id="botao-cad"
            >
                Lista
            </button>

            <div class="lista-cad" id="lista-cad">

                <div class="opcao-cad">
                    Deficiência visual
                </div>

                <div class="opcao-cad">
                    Deficiência auditiva
                </div>

                <div class="opcao-cad">
                    Deficiência física
                </div>

                <div class="opcao-cad">
                    Não possuo deficiência
                </div>

            </div>

        </div>

        <div class="seguranca-cad">
            <span class="seguranca-icone">✓</span>
            <span>
                Suas informações estão seguras com a gente
            </span>
        </div>

        <button class="btn-cad" type="submit">
            Criar conta
        </button>

    </form>

    <h3 class="enter-cad">
        Já tem uma conta?
        <a href="login.php">Entrar</a>
    </h3>

</section>

<script src="/../-TCC-/public/js/cadastro.js"></script>

<?php
include __DIR__.'/../includes/footer.php';
?>