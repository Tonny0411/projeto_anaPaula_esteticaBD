<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../config/db.php');

// Verifica se a conexão com o banco foi estabelecida com sucesso
if (!$conn) {
    die("Erro ao conectar ao banco de dados: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cpf = $_POST['cpf'];
    $nome = $_POST['nome'];
    $telefone = $_POST['telefone'];
    $data_nascimento = $_POST['data_nascimento'];
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_BCRYPT);

    // Inicia uma transação
    $conn->begin_transaction();

    try {
        // Verifica se o CPF já está cadastrado
        $sql_verifica_cpf = "SELECT cpf FROM cadastro_cliente WHERE cpf = ?";
        $stmt_verifica = $conn->prepare($sql_verifica_cpf);
        if (!$stmt_verifica) {
            throw new Exception("Erro na preparação da consulta de verificação de CPF: " . $conn->error);
        }
        $stmt_verifica->bind_param("s", $cpf);
        $stmt_verifica->execute();
        $stmt_verifica->store_result();

        if ($stmt_verifica->num_rows > 0) {
            throw new Exception("CPF já cadastrado.");
        }
        $stmt_verifica->close();

        // Insere os dados do cliente na tabela cadastro_cliente
        $sql_cliente = "INSERT INTO cadastro_cliente (cpf, nome, telefone, data_nascimento, email) VALUES (?, ?, ?, ?, ?)";
        $stmt_cliente = $conn->prepare($sql_cliente);
        if (!$stmt_cliente) {
            throw new Exception("Erro na preparação da consulta de inserção de cliente: " . $conn->error);
        }
        $stmt_cliente->bind_param("sssss", $cpf, $nome, $telefone, $data_nascimento, $email);

        if (!$stmt_cliente->execute()) {
            throw new Exception("Erro ao cadastrar cliente: " . $stmt_cliente->error);
        }
        $stmt_cliente->close();

        // Insere os dados de login na tabela login_cliente
        $sql_login = "INSERT INTO login_cliente (cpf_cliente, email, senha_hash) VALUES (?, ?, ?)";
        $stmt_login = $conn->prepare($sql_login);
        if (!$stmt_login) {
            throw new Exception("Erro na preparação da consulta de inserção de login: " . $conn->error);
        }
        $stmt_login->bind_param("sss", $cpf, $email, $senha);

        if (!$stmt_login->execute()) {
            throw new Exception("Erro ao cadastrar login: " . $stmt_login->error);
        }
        $stmt_login->close();

        // Confirma a transação
        $conn->commit();

        // Armazena a mensagem de sucesso na sessão e redireciona
        $_SESSION['success_msg'] = "Cadastro efetuado com sucesso!";
        header("Location: ../public/index.php");
        exit();

    } catch (Exception $e) {
        // Reverte a transação em caso de erro
        $conn->rollback();
        echo "Erro ao realizar o cadastro: " . $e->getMessage();
    }
} else {
    echo "Método de requisição inválido.";
}
?>
