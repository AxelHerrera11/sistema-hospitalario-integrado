<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Infrastructure\External;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
final class EmailNotificationService
{
    private MailerInterface $mailer;
    private string $fromEmail;
    private string $fromName;
    public function __construct(MailerInterface $mailer, string $fromEmail, string $fromName)
    {
        $this->mailer = $mailer;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;}
    public function sendActivationEmail(string $toEmail, string $userName, string $tempPassword, string $activationToken): void
    {
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($toEmail)
            ->subject('Bienvenido al Sistema Hospitalario - Credenciales de Acceso')
            ->html($this->buildActivationHtml($userName, $tempPassword, $activationToken));
        $this->mailer->send($email);}
    public function sendMfaAlert(string $toEmail, string $userName, string $method): void
    {
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($toEmail)
            ->subject('Alerta de Seguridad: Nuevo inicio de sesión con MFA')
            ->html($this->buildMfaAlertHtml($userName, $method));
        $this->mailer->send($email);}
    public function sendAdminAlert(string $toEmail, string $subject, string $message): void
    {
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($toEmail)
            ->subject($subject)
            ->html("<pre>{$message}</pre>");

        $this->mailer->send($email);}
    private function buildActivationHtml(string $userName, string $tempPassword, string $activationToken): string
    {
        return <<<HTML
        <h2>Bienvenido, {$userName}</h2>
        <p>Se ha creado su cuenta en el Sistema Hospitalario Integrado.</p>
        <p><strong>Contraseña temporal:</strong> <code>{$tempPassword}</code></p>
        <p><strong>Enlace de activación:</strong> <a href="{$_ENV['APP_URL']}/activate?token={$activationToken}">Activar cuenta</a></p>
        <p>Por seguridad, cambie su contraseña al primer inicio de sesión.</p>
        <p>Este enlace expira en 24 horas.</p>
        HTML;}
    private function buildMfaAlertHtml(string $userName, string $method): string
    {
        return <<<HTML
        <h2>Alerta de Seguridad</h2>
        <p>Hola {$userName},</p>
        <p>Se ha detectado un nuevo inicio de sesión con autenticación de doble factor ({$method}) en su cuenta.</p>
        <p>Si no ha sido usted, contacte inmediatamente al administrador de seguridad.</p>
        HTML;
    }
}
