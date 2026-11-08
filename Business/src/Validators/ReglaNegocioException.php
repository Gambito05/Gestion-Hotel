<?php
declare(strict_types=1);

namespace App\Validators;

use RuntimeException;

#Se lanza cuando una regla de negocio no se cumple; lleva el mensaje y el código HTTP que se debe responder
final class ReglaNegocioException extends RuntimeException {
    public function __construct(string $mensaje, private int $status = 409) {
        parent::__construct($mensaje);
    }

    public function getStatus(): int {
        return $this->status;
    }
}
