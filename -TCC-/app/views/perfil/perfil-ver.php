<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();

if (!isset($_SESSION['usuario']['id'])) {
    header('Location: ../auth/login.php');
    exit;
}

include __DIR__ . '/../../../config/database.php';

// ID do perfil que está sendo visualizado
$id_user = (int) ($_GET['id'] ?? 0);
$id_logado = (int) $_SESSION['usuario']['id'];

// Se não recebeu ID, volta para a pesquisa
if ($id_user <= 0) {
    header('Location: ../pesquisa/pesquisar.php');
    exit;
}

// Se o usuário abriu o próprio resultado da pesquisa, leva ao perfil normal.
// Assim não aparecem ações de adicionar amigo ou conversar consigo mesmo.
if ($id_user === $id_logado) {
    header('Location: perfil.php');
    exit;
}

$stmtUsuario = $conn->prepare("
    SELECT nome_user, email_user, foto_user, sobre_mim
    FROM Usuario
    WHERE id_user = ?
");
$stmtUsuario->execute([$id_user]);
$usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header('Location: ../pesquisa/pesquisar.php');
    exit;
}

$stmtAmigos = $conn->prepare("
    SELECT COUNT(*)
    FROM Amizade
    WHERE id_user_1 = ?
       OR id_user_2 = ?
");
$stmtAmigos->execute([$id_user, $id_user]);
$quantidadeAmigos = (int) $stmtAmigos->fetchColumn();

// Verifica se os dois usuários já são amigos
$id1 = min($id_logado, $id_user);
$id2 = max($id_logado, $id_user);

$stmtAmizade = $conn->prepare("
    SELECT id_amizade
    FROM Amizade
    WHERE id_user_1 = ?
      AND id_user_2 = ?
");

$stmtAmizade->execute([$id1, $id2]);

$saoAmigos = $stmtAmizade->fetch();

$stmt = $conn->prepare("
    SELECT e.nome_esporte
    FROM Esporte e
    INNER JOIN Usuario_Esporte ue
        ON e.id_esporte = ue.id_esporte
    WHERE ue.id_user = ?
    ORDER BY e.nome_esporte
");
$stmt->execute([$id_user]);
$esportes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$fotoPerfil = !empty($usuario['foto_user'])
    ? zubbo_url('/' . ltrim((string) $usuario['foto_user'], '/'))
    : zubbo_url('/public/imagem/blank.png');

include __DIR__ . '/../includes/head.php';

$sqlEventos = "
    SELECT COUNT(*) AS total
    FROM (
        SELECT id_evento
        FROM Evento
        WHERE id_criador = ?

        UNION

        SELECT id_evento
        FROM Lista_Evento
        WHERE id_user = ?
    ) AS eventos_usuario
";

$stmtEventos = $conn->prepare($sqlEventos);
$stmtEventos->execute([$id_user, $id_user]);

$quantidadeEventos = (int) $stmtEventos->fetchColumn();
?>
<section style="padding-bottom: 80px;">

    <div class="perfil-pic-label" aria-label="Foto de perfil">
        <img class="perfil-pic" src="<?= htmlspecialchars($fotoPerfil) ?>" alt="Foto de perfil">
    </div>

    <h1 class="perfil-nome">
        <?= htmlspecialchars($usuario['nome_user']) ?>
    </h1>

    <h3 class="perfil-email">
        <?= htmlspecialchars($usuario['email_user']) ?>
    </h3>

    <div class="perfil-status">
        <div class="perfil-eventos">
            <h3 class="perfil-name">Eventos</h3>
            <h2 class="perfil-eventos-num">
                <?= $quantidadeEventos ?>
            </h2>
        </div>
        <div class="perfil-amigos">
            <h3 class="perfil-name">Amigos</h3>
            <h2 class="perfil-amigos-num"><?= $quantidadeAmigos ?></h2>
        </div>
    </div>

    <div class="perfil-acoes">

        <?php if (!$saoAmigos): ?>

            <form action="adicionar-amigo.php" method="POST">
                <?= zubbo_csrf_input() ?>
                <input type="hidden" name="id_amigo" value="<?= $id_user ?>">

                <button type="submit" class="btn-adicionar-amigo">
                    + Adicionar amigo
                </button>
            </form>

        <?php endif; ?>

        <button type="button" class="btn-conversar"
            onclick="window.location.href='../chats/chats-conversas.php?id=<?= $id_user ?>'">
            Conversar
        </button>

    </div>

    <div class="perfil-sobre">
        <h2 class="perfil-sobremim">Sobre Mim</h2>

        <p class="perfil-sobremim-texto">
            <?= !empty($usuario['sobre_mim'])
                ? nl2br(htmlspecialchars($usuario['sobre_mim']))
                : 'Este usuário ainda não adicionou uma descrição.' ?>
        </p>
    </div>

    <div class="perfil-esportes">
        <h2>Meus Esportes</h2>

        <div class="esportes-lista">
            <?php foreach ($esportes as $esporte): ?>
                <?php
                $nome = $esporte['nome_esporte'];

                $classe = match ($nome) {
                    'Futebol' => 'esporte-futebol',
                    'Basquete' => 'esporte-basquete',
                    'Vôlei' => 'esporte-volei',
                    'Tênis' => 'esporte-tenis',
                    'Futsal' => 'esporte-futesal',
                    'Corrida' => 'esporte-corrida',
                    'Handebol' => 'esporte-handebol',
                    default => 'esporte-outro'
                };
                ?>

                <div class="esporte-card <?= $classe ?>">
                    <span><?= htmlspecialchars($nome) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>



</section>
<?php
include __DIR__ . '/../../views/includes/under-bar.php';
include __DIR__ . '/../../views/includes/footer.php';
?>
