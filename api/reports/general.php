<?php
require_once __DIR__ . '/../../models/DopingTest.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

header('Content-Type: application/json');

$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

try {
    // Verifica autenticação
    $auth = new AuthMiddleware();
    $user = $auth->authenticate();
    
    if ($method !== 'GET') {
        $response->sendError('Método não permitido', 405);
    }
    
    $dopingTestModel = new DopingTest();
    
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    $reportType = $_GET['type'] ?? 'general';
    $format = $_GET['format'] ?? 'json';
    
    $report = $dopingTestModel->generateReport($startDate, $endDate, $reportType);
    
    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="relatorio_antidoping_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Cabeçalho do CSV baseado no tipo de relatório
        if (!empty($report)) {
            fputcsv($output, array_keys($report[0]));
            foreach ($report as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
    } else {
        $response->sendSuccess([
            'report_type' => $reportType,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'data' => $report
        ]);
    }
    
} catch (Exception $e) {
    $response->sendError($e->getMessage(), 400);
}
?>