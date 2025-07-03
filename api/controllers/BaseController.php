<?php

abstract class BaseController {

    /**
     * 輸出 JSON 回應
     *
     * @param mixed $data 要編碼的資料
     * @param int $statusCode HTTP 狀態碼
     */
    protected function sendJsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * 輸出 JSON 錯誤回應
     *
     * @param string $message 錯誤訊息
     * @param int $statusCode HTTP 狀態碼
     */
    protected function sendErrorResponse($message, $statusCode = 500) {
        $this->sendJsonResponse(['error' => $message], $statusCode);
    }
}
