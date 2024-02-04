<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\HttpException;

class LogAPIService
{
    public const BASE_URL = "https://api-metrika.yandex.net/management/v1/counter";
    public function __construct()
    {
    }

    public function getCsvData(string $counterId, string $token, array $params)
    {
        try {
            $possible = $this->evaluateRequest($counterId, $token, $params);
            if (!$possible) {
                return;
            }

            $requestId = $this->createLogs($counterId, $token, $params);
            $partsNum = $this->getPartNumbers($counterId, $token, $requestId);
            $this->downloadParts($counterId, $token, $requestId, $partsNum);
        } catch (HttpException $e) {
            print_r("Unable to handle request: {$e->getMessage()}");
        }
    }

    public function evaluateRequest(string $counterId, string $token, array $params): bool
    {
        $url = $this::BASE_URL . "/{$counterId}/logrequests/evaluate";
        $authorization = $this->authStr($token);
        $header = array('Content-Type: application/json', $authorization);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $this->mapUrlAndParams($url, $params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!$response || $status !== 200) {
            $error = $this->getErrorMessage($response);

            throw new HttpException($error);
        }


        $responseJson = json_decode($response, true);
        @$possible = (bool) $responseJson["log_request_evaluation"]["possible"];

        return $possible;
    }

    public function createLogs(string $counterId, string $token, array $params): string
    {
        $url = $this::BASE_URL . "/{$counterId}/logrequests";
        $authorization = $this->authStr($token);
        $header = array('Content-Type: application/json', $authorization);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_URL, $this->mapUrlAndParams($url, $params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$response || $status !== 200) {
            $error = $this->getErrorMessage($response);
            throw new HttpException($error);
        }

        $responseJson = json_decode($response, true);
        $requestId = (string) $responseJson['log_request']['request_id'];

        return $requestId;
    }

    public function getPartNumbers(string $counterId, string $token, string $requestId): int
    {
        $url = $this::BASE_URL . "/{$counterId}/logrequest/{$requestId}";
        $authorization = $this->authStr($token);
        $header = array('Content-Type: application/json', $authorization);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        while (true) {

            $response = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (!$response || $status !== 200) {
                $error = $this->getErrorMessage($response);
                throw new HttpException($error);
            }

            $responseJson = json_decode($response, true);

            $logStatus = $responseJson['log_request']['status'];
            if ($logStatus === 'processed') {
                echo "RequestId: {$requestId} | Status: {$logStatus}\n";
                break;
            }
            echo "RequestId: {$requestId} | Status: {$logStatus}\n";
            sleep(5);
        }
        curl_close($ch);

        $partNums = count($responseJson['log_request']['parts']);

        return $partNums;
    }

    public function downloadParts(string $counterId, string $token, string $requestId, int $partNums): string
    {
        $authorization = $this->authStr($token);
        $header = array('Content-Type: application/json', $authorization);
        $fileAll = "log_counter_{$counterId}_request_{$requestId}_all.csv";

        for ($i = 0; $i < $partNums; $i++) {
            $url = $this::BASE_URL . "/{$counterId}/logrequest/{$requestId}/part/{$i}/download";
            $responseCsv = $this->downloadCsvPart($url, $header, $i);

            if (file_exists($fileAll) && filesize($fileAll) > 0) {
                $rows = explode("\n", $responseCsv);
                unset($rows[0]);
                $responseCsv = implode("\n", $rows);
            }

            file_put_contents($fileAll, $responseCsv, FILE_APPEND);
            print_r("Part {$i} was placed into {$fileAll} file\n");
        }
        print_r("File {$fileAll} was saved\n");
        return $fileAll;
    }

    private function mapUrlAndParams(string $url, array $params): string
    {
        return $url . "?" . http_build_query(data: $params);
    }

    private function downloadCsvPart(string $url, array $authHeader): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $authHeader);

        $responseTsv = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (!$responseTsv || $status !== 200) {
            $error = $this->getErrorMessage($responseTsv);
            throw new HttpException($error);
        }

        $responseCsv = str_replace("\t", ",", $responseTsv);
        return $responseCsv;
    }

    private function getErrorMessage(string $response): string
    {
        $responseJson = json_decode($response, true);
        return $responseJson['message'];
    }

    private function authStr(string $token): string
    {
        return "Authorization: Bearer " . $token;
    }
}
