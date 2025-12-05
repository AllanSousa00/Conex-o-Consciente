<?php
// Configurações
$meu_email = "seu-email@gmail.com"; // SUBSTITUA PELO SEU EMAIL
$assunto_padrao = "Novo contato do site Conexão Consciente";

// Verificar se é uma requisição POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Verificar honeypot (campo anti-spam)
    if (!empty($_POST['honeypot'])) {
        // É spam, não processar
        header("Location: Contato.html?status=error&message=Erro%20de%20segurança.");
        exit;
    }
    
    // Validar campos obrigatórios
    $campos_obrigatorios = ['name', 'email', 'subject', 'message', 'consent'];
    
    foreach ($campos_obrigatorios as $campo) {
        if (empty($_POST[$campo])) {
            header("Location: Contato.html?status=error&message=Todos%20os%20campos%20são%20obrigatórios.");
            exit;
        }
    }
    
    // Sanitizar dados
    $nome = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $assunto = filter_var($_POST['subject'], FILTER_SANITIZE_STRING);
    $mensagem = filter_var($_POST['message'], FILTER_SANITIZE_STRING);
    
    // Validar email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: Contato.html?status=error&message=Email%20inválido.");
        exit;
    }
    
    // Validar tamanho mínimo da mensagem
    if (strlen($mensagem) < 20) {
        header("Location: Contato.html?status=error&message=A%20mensagem%20deve%20ter%20pelo%20menos%2020%20caracteres.");
        exit;
    }
    
    // Preparar o email
    $to = $meu_email;
    $subject = "{$assunto_padrao}: {$assunto}";
    
    $email_body = "Você recebeu uma nova mensagem de contato:\n\n";
    $email_body .= "Nome: {$nome}\n";
    $email_body .= "Email: {$email}\n";
    $email_body .= "Assunto: {$assunto}\n\n";
    $email_body .= "Mensagem:\n{$mensagem}\n\n";
    $email_body .= "---\n";
    $email_body .= "Este email foi enviado através do formulário de contato do site Conexão Consciente.";
    
    $headers = "From: {$email}\r\n";
    $headers .= "Reply-To: {$email}\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    // Tentar enviar o email
    if (mail($to, $subject, $email_body, $headers)) {
        // Email enviado com sucesso
        
        // Opcional: Salvar no banco de dados (se tiver MySQL)
        salvarNoBanco($nome, $email, $assunto, $mensagem);
        
        // Redirecionar para página de sucesso
        header("Location: Contato.html?status=success");
        exit;
    } else {
        // Erro ao enviar email
        error_log("Erro ao enviar email de: {$email} para: {$to}");
        header("Location: Contato.html?status=error&message=Erro%20ao%20enviar%20a%20mensagem.%20Tente%20novamente%20mais%20tarde.");
        exit;
    }
    
} else {
    // Se não for POST, redirecionar
    header("Location: Contato.html");
    exit;
}

// Função para salvar no banco de dados (OPCIONAL)
function salvarNoBanco($nome, $email, $assunto, $mensagem) {
    try {
        // Configurações do banco de dados InfinityFree
        $hostname = "sqlXXX.epizy.com"; // SUBSTITUA pelo seu host
        $username = "epiz_XXXXXX"; // SUBSTITUA pelo seu usuário
        $password = "XXXXXX"; // SUBSTITUA pela sua senha
        $database = "epiz_XXXXXX_nomedobanco"; // SUBSTITUA pelo seu banco
        
        // Conexão com MySQL
        $conn = new mysqli($hostname, $username, $password, $database);
        
        // Verificar conexão
        if ($conn->connect_error) {
            error_log("Erro de conexão com o banco: " . $conn->connect_error);
            return false;
        }
        
        // Preparar statement para prevenir SQL injection
        $stmt = $conn->prepare("INSERT INTO contatos (nome, email, assunto, mensagem, data_envio) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssss", $nome, $email, $assunto, $mensagem);
        
        // Executar
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            return true;
        } else {
            error_log("Erro ao salvar no banco: " . $stmt->error);
            $stmt->close();
            $conn->close();
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Exceção ao salvar no banco: " . $e->getMessage());
        return false;
    }
}

// Função alternativa para enviar email via SMTP (mais confiável)
function enviarEmailSMTP($nome, $email, $assunto, $mensagem) {
    // Requer PHPMailer - baixe em: https://github.com/PHPMailer/PHPMailer
    // Coloque os arquivos PHPMailer na pasta /phpmailer/
    
    /*
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    
    require 'phpmailer/src/Exception.php';
    require 'phpmailer/src/PHPMailer.php';
    require 'phpmailer/src/SMTP.php';
    
    $mail = new PHPMailer(true);
    
    try {
        // Configurações do servidor
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Para Gmail
        $mail->SMTPAuth = true;
        $mail->Username = 'seu-email@gmail.com';
        $mail->Password = 'sua-senha-app'; // Use senha de app
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Destinatários
        $mail->setFrom($email, $nome);
        $mail->addAddress('seu-email@gmail.com');
        $mail->addReplyTo($email, $nome);
        
        // Conteúdo
        $mail->isHTML(true);
        $mail->Subject = "Novo contato: {$assunto}";
        $mail->Body    = "
            <h3>Novo contato do site</h3>
            <p><strong>Nome:</strong> {$nome}</p>
            <p><strong>Email:</strong> {$email}</p>
            <p><strong>Assunto:</strong> {$assunto}</p>
            <p><strong>Mensagem:</strong></p>
            <p>{$mensagem}</p>
        ";
        $mail->AltBody = "Nome: {$nome}\nEmail: {$email}\nAssunto: {$assunto}\nMensagem:\n{$mensagem}";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erro PHPMailer: {$mail->ErrorInfo}");
        return false;
    }
    */
}
?>