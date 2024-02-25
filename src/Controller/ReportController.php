<?php

namespace Contatoseguro\TesteBackend\Controller;

use Contatoseguro\TesteBackend\Service\CompanyService;
use Contatoseguro\TesteBackend\Service\ProductService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ReportController
{
    private ProductService $productService;
    private CompanyService $companyService;
    public function __construct()
    {
        $this->productService = new ProductService();
        $this->companyService = new CompanyService();
    }
    private function formatLog($log)
    {
        return "({$log->admin_user_name})";
    }
    public function generate(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $adminUserId = $request->getHeader('admin_user_id')[0];
        $queryParams = $request->getQueryParams();

        if (isset($queryParams['product_name'])) {

            $productName = $queryParams['product_name'];
            $product = $this->productService->getByName($productName, $adminUserId);

            if (!$product) {
                $response->getBody()->write("Produto não encontrado: $productName");
                return $response->withStatus(404)->withHeader('Content-Type', 'text/html');
            }

            $companyName = $this->companyService->getNameById($product->company_id)->fetch()->name;
            $lastPriceChangeLog = $this->productService->getLastPriceChangeLog($product->id);
            
            $data = [
                [
                    'Usuário mudou o preço do produto por último'
                ],
                [
                    $lastPriceChangeLog ? $this->formatLog($lastPriceChangeLog) : 'Nenhuma alteração de preço registrada.'
                ]
            ];
            $report = "<table style='font-size: 10px;'>";
            foreach ($data as $row) {
                $report .= "<tr>";
                foreach ($row as $column) {
                    $report .= "<td><br>{$column}<br></td>";
                }
                $report .= "</tr>";
            }
            $report .= "</table>";

            $response->getBody()->write($report);
            return $response->withStatus(200)->withHeader('Content-Type', 'text/html');

        } else {

            $data = [];
            $data[] = [
                'Id do produto',
                'Nome da Empresa',
                'Nome do Produto',
                'Valor do Produto',
                'Categorias do Produto',
                'Data de Criação',
                'Logs de Alterações'
            ];

            $stm = $this->productService->getAll($adminUserId);
            $products = $stm->fetchAll();

            foreach ($products as $i => $product) {
                $stm = $this->companyService->getNameById($product->company_id);
                $companyName = $stm->fetch()->name;

                $stm = $this->productService->getLog($product->id);
                $productLogs = $stm->fetchAll();

                $formattedLogs = [];
                foreach ($productLogs as $log) {
                    $formattedLogs[] = "({$log->name}, {$log->action}, {$log->timestamp})";
                }
                $formattedLogsString = implode(',<br> ', $formattedLogs);

                $data[$i + 1][] = $product->id;
                $data[$i + 1][] = $companyName;
                $data[$i + 1][] = $product->title;
                $data[$i + 1][] = $product->price;
                $data[$i + 1][] = $product->category;
                $data[$i + 1][] = $product->created_at;
                $data[$i + 1][] = $formattedLogsString;
            }

            $report = "<table style='font-size: 10px;'>";
            foreach ($data as $row) {
                $report .= "<tr>";
                foreach ($row as $column) {
                    $report .= "<td><br>{$column}<br></td>";
                }
                $report .= "</tr>";
            }
            $report .= "</table>";

            $response->getBody()->write($report);
            return $response->withStatus(200)->withHeader('Content-Type', 'text/html');
        }
    }
}
