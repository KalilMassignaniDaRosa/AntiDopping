<?php
require_once __DIR__ . '/../../models/DopingTest.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

header('Content-Type: application/json');

$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $response->sendError('Método não permitido', 405);
    exit;
}

try {
    // Verifica autenticação
    $auth = new AuthMiddleware();
    $user = $auth->authenticate();
    $auth->checkPermission($user, 'reports');
    
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    $laboratoryId = $_GET['laboratory_id'] ?? null;
    $format = $_GET['format'] ?? 'json';
    
    $dopingTestModel = new DopingTest();
    
    $filters = [
        'start_date' => $startDate,
        'end_date' => $endDate
    ];
    
    if ($laboratoryId) {
        $filters['laboratory_id'] = $laboratoryId;
    }
    
    $report = $dopingTestModel->generateReport($startDate, $endDate, 'laboratory');
    
    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="relatorio_laboratorios_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        if (!empty($report)) {
            fputcsv($output, array_keys($report[0]));
            foreach ($report as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
    } else {
        $response->sendSuccess([
            'report_type' => 'laboratory',
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'laboratory_id' => $laboratoryId,
            'data' => $report
        ]);
    }
    
} catch (Exception $e) {
    $response->sendError($e->getMessage(), 400);
}
?>