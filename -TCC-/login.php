<?php
include __DIR__.'/includes/head.php';
?>

<section class="Tela">

<div class="Titulo-log">
<h1 class="login-nome" style="text-shadow: 2px 1px 3px white; "><strong style="color: black; text-shadow: 2px 1px 3px black; ">L</strong>ogin <strong style="color: black; text-shadow: 2px 1px 3px black; ">A</strong>qui
</div>
<div class="log-sub">
    <h2 class="log-sub-txt"><strong>Bem-vindo de volta, sentimos sua falta!</strong></h2>
</div>



    <div class="field" style="margin-top:200px;">
        <label>Email</label>
        <input type="Email" placeholder="Email" name="Email" require>
    </div>
    <div class="field">
        <label>Senha</label>
        <input type="Password" placeholder="Senha" name="Password" require>
    </div>
    

    <div  class="log-sub">
        <h2 style="color: white;">Esqueceu sua Senha?</h2>
    </div  class="log-sub">

    <div class="but">
        <button onclick="window.location.href='log-user-confirmar.php'">
            <span class="shadow"></span>
            <span class="edge2"></span>
            <span class="front2">Entrar</span>
        </button>
    </div>
    <div class="but">
        <button onclick="window.location.href='cadastrar.php'">
            <span class="shadow"></span>
            <span class="edge"></span>
            <span class="front text">Criar nova conta</span>
        </button>
    </div>

<?php
include __DIR__.'/includes/footer.php';
?>

</section>

<!--
    <div class="field">
        <label>LOGIN</label>
        <input type="text" placeholder="digite seu ID" name="login_txt" require>
    </div>
      -->