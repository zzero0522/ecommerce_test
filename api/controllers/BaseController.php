<?php

abstract class BaseController {

    /**
     * Sends a JSON response.
     *
     * @param mixed $data The data to encode.
     * @param int $statusCode The HTTP status code.
     */
    protected function sendJsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Sends a JSON error response.
     *
     * @param string $message The error message.
     * @param int $statusCode The HTTP status code.
     */
    protected function sendErrorResponse($message, $statusCode = 500) {
        $this->sendJsonResponse(['error' => $message], $statusCode);
    }
}
