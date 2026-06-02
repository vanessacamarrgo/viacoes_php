<?php

declare(strict_types=1);

/** @var list<string> $errors */
/** @var array{nome: string, email: string, status: string} $old */

$action  = "/usuarios";
$method  = null;
$usuario = null;
$old     = $old ?? [];
?>
    <title>Criar Usuário</title>

<header class="page-header">
    <div>
        <nav class="actions">
            <a class="btn btn-primary btn-headerlay" href="/usuarios">Usuários</a>
            <a class="btn btn-primary btn-headerlay" href="/usuarios/create">Novo usuário</a>
        </nav>
    </div>
</header>

    <h1>Criar usuário</h1>



<?php if ($errors !== []): ?>
    <div class="alert alert--danger">
        <p><strong>Corrija os erros:</strong></p>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>



<?php require __DIR__ . '/form.php'; ?>

