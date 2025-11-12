<?php
class Response {
    public function sendSuccess($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'timestamp' => date('c')
        ]);
        exit;
    }
    
    public function sendError($message, $statusCode = 400, $details = null) {
        http_response_code($statusCode);
        echo json_encode([
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $statusCode,
                'details' => $details
            ],
            'timestamp' => date('c')
        ]);
        exit;
    }
    
    public function sendPaginated($data, $pagination) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'pagination' => $pagination,
            'timestamp' => date('c')
        ]);
        exit;
    }
}
?>