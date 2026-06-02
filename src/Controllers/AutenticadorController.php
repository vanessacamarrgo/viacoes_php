<?php
//Controla o acesso ao sistema.
// Exibe o formulário de login, valida as credenciais via AutenticatorService e gerencia a sessão.
namespace App\Controllers;

use App\Services\AutenticadorService;

final class AutenticadorController
{
    private AutenticadorService $autenticatorService;

    public function __construct(?AutenticadorService $autenticatorService = null)
    {
        $this->autenticatorService = $autenticatorService ?? new AutenticadorService(\getPdo());
    }


    public function login(): void
    {
        require __DIR__ . '/../views/autenticator/login.php';
    }

    public function autenticar(): void
    {
        $email = $_POST['email'] ?? '';
        $senha = $_POST['senha'] ?? '';

        if ($email === '' || $senha === '') {
            $_SESSION['erro'] = 'Preencha todos os campos';

            header('Location: /login');
            exit;
        }

        $loginValido = $this->autenticatorService->login(
            $email,
            $senha
        );

        if (!$loginValido) {
            $_SESSION['erro'] = 'Email ou senha inválidos';

            header('Location: /login');
            exit;
        }

        header('Location: /viacoes');
        exit;
    }

    public function logout(): void
    {
        $this->autenticatorService->logout();

        header('Location: /login');
        exit;
    }
}