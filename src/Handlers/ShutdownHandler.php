<?php

declare(strict_types=1);

namespace TXC\Box\Handlers;

use Psr\Log\LoggerInterface;
use TXC\Box\ResponseEmitter\ResponseEmitter;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpInternalServerErrorException;

class ShutdownHandler
{
    public function __construct(
        private readonly Request $request,
        private readonly HttpErrorHandler $errorHandler,
        private readonly bool $displayErrorDetails,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(): void
    {
        $error = error_get_last();
        if ($error === null) {
            return;
        }
        $errorFile = $error['file'];
        $errorLine = $error['line'];
        $errorMessage = $error['message'];
        $errorType = $error['type'];
        $message = 'An error while processing your request. Please try again later.';

        $this->logger->error($errorMessage, $error);

        if ($this->displayErrorDetails) {
            switch ($errorType) {
                case E_USER_ERROR:
                    $message = sprintf(
                        _('FATAL ERROR: %s on line %d in file %s.'),
                        $errorMessage,
                        $errorLine,
                        $errorFile
                    );
                    break;

                case E_USER_WARNING:
                    $message = sprintf(_('WARNING: %s'), $errorMessage);
                    break;

                case E_USER_NOTICE:
                    $message = sprintf(_('NOTICE: %s'), $errorMessage);
                    break;

                default:
                    $message = sprintf(
                        _('ERROR: %s on line %d in file %s.'),
                        $errorMessage,
                        $errorLine,
                        $errorFile
                    );
                    break;
            }
        }

        $exception = new HttpInternalServerErrorException($this->request, $message);
        $response = $this->errorHandler->__invoke(
            $this->request,
            $exception,
            $this->displayErrorDetails,
            false,
            false,
        );

        $responseEmitter = new ResponseEmitter();
        $responseEmitter->emit($response);
    }
}
