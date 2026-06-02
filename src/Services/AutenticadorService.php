<?php

namespace App\Services;
//Valida o login comparando a senha digitada com o hash bcrypt armazenado no banco.
// Se válido, grava o user_id na sessão. O logout() simplesmente destrói a sessão.
use PDO;

final class AutenticadorService
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function login(string $email, string $senha): bool
    {
        $stmt = $this->pdo->prepare("SELECT * FROM viacoes.usuarios WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($senha, $user['senha'])) {
            return false;
        }

        $_SESSION['user_id'] = $user['id']; // ← este é o id que será usado no histórico

        return true;
    }

    public function logout(): void
    {
        session_destroy();
    }
}