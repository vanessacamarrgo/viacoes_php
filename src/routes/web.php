<?php

declare(strict_types=1);

/** Arquivo de registro de rotas web. */
use App\Controllers\ViacaoController;
use App\Controllers\HomeController;
use App\Controllers\AutenticadorController;
use App\Controllers\UsuarioController;
use App\Controllers\HistoricoController; // Importando o controlador do histórico geral

/** @var App\Core\Router $router */

// Autenticação
$router->get('/login',   [AutenticadorController::class, 'login']);
$router->post('/login',  [AutenticadorController::class, 'autenticar']);
$router->get('/logout',  [AutenticadorController::class, 'logout']);

// Home pública
$router->get('/home', [HomeController::class, 'index']);

// Rota do Histórico Geral Unificado
$router->get('/historico', [HistoricoController::class, 'index']);

// CRUD de viações (área protegida — requer login)
$router->get('/',             [ViacaoController::class, 'index']);
$router->get('/viacoes',      [ViacaoController::class, 'index']);
$router->get('/viacoes/create', [ViacaoController::class, 'create']);
$router->post('/viacoes',     [ViacaoController::class, 'store']);
$router->get('/viacoes/{id}/edit', [ViacaoController::class, 'edit']);
$router->put('/viacoes/{id}', [ViacaoController::class, 'update']);
$router->post('/viacoes/{id}/delete', [ViacaoController::class, 'destroy']);
$router->post('/viacoes/{id}/restore', [ViacaoController::class, 'restore']);
// -----------------------------------------------------------------------------
// CRUD DE USUÁRIOS
// -----------------------------------------------------------------------------
$router->get('/usuarios',             [UsuarioController::class, 'index']);
$router->get('/usuarios/create',      [UsuarioController::class, 'create']);
$router->post('/usuarios',            [UsuarioController::class, 'store']);
$router->get('/usuarios/{id}/edit',   [UsuarioController::class, 'edit']);
$router->put('/usuarios/{id}',        [UsuarioController::class, 'update']);
$router->post('/usuarios/{id}/delete', [UsuarioController::class, 'destroy']);
$router->post('/usuarios/{id}/restore', [UsuarioController::class, 'restore']);
// Rotas de visualização única
$router->get('/usuarios/{id}', [App\Controllers\UsuarioController::class, 'show']);
$router->get('/viacoes/{id}', [App\Controllers\ViacaoController::class, 'show']);$router->get('/viacoes/(\d+)', [App\Controllers\ViacaoController::class, 'show']);