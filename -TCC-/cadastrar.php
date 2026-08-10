<?php
include __DIR__.'/includes/head.php';
?>

<section class="Tela">

<div class="Titulo-cad">
<h1 class="cad-nome" style="text-shadow: 2px 1px 3px white; ">Criar<strong style="color: black; text-shadow: 2px 1px 3px black; "></strong> <strong style="color: black; text-shadow: 2px 1px 3px black; ">Conta</strong>
</div>
<div class="cad-sub">
    <h2 class="cad-sub-txt"><strong>Crie uma conta para que você possa explorar conosco.</strong></h2>
</div>

    <div class="field" style="margin-top:200px;">
        <label>Email</label>
        <input type="Email" placeholder="Email" name="Email" require>
    </div>
    <div class="field">
        <label>Senha</label>
        <input type="Password" placeholder="Senha" name="Password" require>
    </div>
    <div class="field">
        <label>Senha Novamente</label>
        <input type="Password" placeholder="Confirme a Senha" name="Password_again" require>
    </div>

    <div class="but" style="margin-top:75px;">
        <button onclick="window.location.href='cad-user-confirmar.php'">
            <span class="shadow"></span>
            <span class="edge2"></span>
            <span class="front2">Inscreva-se
</span>
        </button>
    </div>
    <div class="but">
        <button onclick="window.location.href='login.php'">
            <span class="shadow"></span>
            <span class="edge"></span>
            <span class="front text">Já tenho uma conta</span>
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