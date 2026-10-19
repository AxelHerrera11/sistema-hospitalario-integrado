<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Services;
use App\Modules\Tenants\Domain\ValueObjects\Password;
final class PasswordPolicy
{
    private Argon2idHasher $hasher;
    private HaveIBeenPwnedClient $hibpClient;
    private int $minLength;
    private bool $requireComplexity;
    private bool $checkHibp;
    private int $historySize;
    public function __construct(
        Argon2idHasher $hasher,
        HaveIBeenPwnedClient $hibpClient,
        int $minLength = 12,
        bool $requireComplexity = true,
        bool $checkHibp = true,
        int $historySize = 5
    ) {
        $this->hasher = $hasher;
        $this->hibpClient = $hibpClient;
        $this->minLength = $minLength;
        $this->requireComplexity = $requireComplexity;
        $this->checkHibp = $checkHibp;
        $this->historySize = $historySize;
    }
    public function validate(string $rawPassword, array $history = []): ValidationResult
    {
        $errors = [];
        if (strlen($rawPassword) < $this->minLength) {
            $errors[] = "Contraseña debe tener al menos {$this->minLength} caracteres";}
        if ($this->requireComplexity) {
            if (!preg_match('/[A-Z]/', $rawPassword)) {
                $errors[] = 'Contraseña debe contener al menos una mayúscula';}
            if (!preg_match('/[a-z]/', $rawPassword)) {
                $errors[] = 'Contraseña debe contener al menos una minúscula';}
            if (!preg_match('/\d/', $rawPassword)) {
                $errors[] = 'Contraseña debe contener al menos un número';}
            if (!preg_match('/[^A-Za-z0-9]/', $rawPassword)) {
                $errors[] = 'Contraseña debe contener al menos un símbolo';}}
        foreach ($history as $oldHash) {
            if ($this->hasher->verify($rawPassword, $oldHash)) {
                $errors[] = 'Contraseña no puede coincidir con las últimas ' . $this->historySize . ' contraseñas';
                break;}}
        if ($this->checkHibp && empty($errors)) {
            $hibpResult = $this->hibpClient->check($rawPassword);
            if ($hibpResult->compromised()) {
                $errors[] = 'Contraseña ha sido comprometida en filtraciones de datos. Elija otra.';}}
        return new ValidationResult(empty($errors), $errors);}
    public function hash(string $rawPassword): Password
    {
        return Password::fromRaw($rawPassword, $this->hasher);}}
