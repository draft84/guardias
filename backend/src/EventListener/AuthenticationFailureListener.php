<?php

namespace App\EventListener;

use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationFailureListener
{
    public function onAuthenticationFailure(LoginFailureEvent $event): void
    {
        $exception = $event->getException();

        // El mensaje de la excepción (ej: DisabledException) suele estar disponible aquí
        $message = $exception->getMessageKey();

        // Si hay una causa (causal exception), preferimos su mensaje
        if ($exception->getPrevious() && $exception->getPrevious()->getMessage()) {
            $message = $exception->getPrevious()->getMessage();
        } elseif ($exception->getMessage()) {
            // DisabledException y otras suelen tener el mensaje real en getMessage()
            $message = $exception->getMessage();
        }

        // Traducir mensajes comunes si es necesario
        $invalidCredentialsMessages = [
            'Invalid credentials.',
            'The presented password is invalid.',
            'Bad credentials.',
        ];

        if (in_array($message, $invalidCredentialsMessages)) {
            $message = 'Las credenciales proporcionadas no coinciden.';
        }

        $response = new JsonResponse([
            'code' => 401,
            'message' => $message,
        ], Response::HTTP_UNAUTHORIZED);

        $event->setResponse($response);
    }
}
