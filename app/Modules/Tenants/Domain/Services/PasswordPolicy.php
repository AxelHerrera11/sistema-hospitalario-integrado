<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Services;
use App\Modules\Tenants\Domain\ValueObjects\Password;
final class PasswordPolicy
{
    private Argon2idHasher $hasher;
    private ?CompromisedPasswordChecker $compromisedChecker;
    private int $minLength;
    private bool $requireComplexity;
    private int $historySize;
    public function __construct(
        Argon2idHasher $hasher,
        ?CompromisedPasswordChecker $compromisedChecker = null,
        int $minLength = 12,
        bool $requireComplexity = true,
        int $historySize = 5
    ) {
        $this->hasher = $hasher;
        $this->compromisedChecker = $compromisedChecker;
        $this->minLength = $minLength;
        $this->requireComplexity = $requireComplexity;
        $this->historySize = $historySize;}
    public function validate(string $rawPassword, array $history = []): ValidationResult
    {
        $errors = [];
        if (mb_strlen($rawPassword) < $this->minLength) {
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
        foreach (array_slice($history, -$this->historySize) as $oldHash) {
            if ($this->hasher->verify($rawPassword, $oldHash)) {
                $errors[] = 'Contraseña no puede coincidir con las últimas ' . $this->historySize . ' contraseñas';
                break;}}
        if ($this->compromisedChecker !== null && empty($errors) && $this->compromisedChecker->isCompromised($rawPassword)) {
            $errors[] = 'Contraseña ha sido comprometida en filtraciones de datos. Elija otra.';}
        return new ValidationResult(empty($errors), $errors);}
    public function hash(string $rawPassword): Password
    {
        return Password::fromRaw($rawPassword, $this->hasher);}}
