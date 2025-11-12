<?php
class LoadBalancer {
    private $servers = [
        'http://app-server-1.cbf.internal',
        'http://app-server-2.cbf.internal',
        'http://app-server-3.cbf.internal'
    ];
    
    public function getServer() {
        // Round-robin simples - em produção usar algo mais sofisticado
        static $current = 0;
        $server = $this->servers[$current];
        $current = ($current + 1) % count($this->servers);
        return $server;
    }
    
    public function routeRequest($endpoint, $method = 'GET', $data = null) {
        $server = $this->getServer();
        $url = $server . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'server' => $server,
            'http_code' => $httpCode,
            'response' => json_decode($response, true)
        ];
    }
}
?>