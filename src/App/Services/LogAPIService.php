<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\HttpException;
use Exception;

class LogAPIService
{
    public const BASE_URL = "https://api-metrika.yandex.net/management/v1/counter";
    public function __construct()
    {
    }

    /**
     * Save tsv data from Yandex.Metrica into csv file
     * 
     * @param string    $counterId  Can see: https://metrika.yandex.ru/list/
     * @param string    $token      Can see: https://oauth.yandex.ru/client
     * @param array     $params     Described in RequestParams.php
     * @param bool      $cleanLog   Should we delete request from Yandex.Metrica?
     */
    public function getCsvData(string $counterId, string $token, array $params, bool $cleanLog = true)
    {
        try {
            $possible = $this->evaluateRequest($counterId, $token, $params);
            if (!$possible) {
                return;
            }

            $requestId = $this->createLogs($counterId, $token, $params);
            $partsNum = $this->getPartNumbers($counterId, $token, $requestId);
            $this->downloadParts($counterId, $token, $requestId, $partsNum);
            if ($cleanLog) {
                $this->cleanLogfile($counterId, $token, $requestId);
            }
        } catch (HttpException $e) {
            print_r("Unable to handle request: {$e->getMessage()}");
        } catch (Exception $e) {
            print_r("Execution failed: {$e->getMessage()}");
        }
    }

    /**
     * Ask whether we could get data according request parameters
     * 
     * @param string    $counterId  Can see: https://metrika.yandex.ru/list/
     * @param string    $token      Can see: https://oauth.yandex.ru/client
     * @param array     $params     Described in RequestParams.php
     * @return bool                 Could we proccess request?
     * @throws HttpException        Error which Yandex.Metrica responded
     */
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


    /**
     * Create logfile on Yandex.Metrica according request parameters
     * 
     * @param string    $counterId  Can see: https://metrika.yandex.ru/list/
     * @param string    $token      Can see: https://oauth.yandex.ru/client
     * @param array     $params     Described in RequestParams.php
     * @return string               Request ID of created request in status "created"
     * @throws HttpException        Error which Yandex.Metrica responded
     */
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

    /**
     * Wait for data preparation from Yandex.Metrica and get amout of files contains result of request
     * Might consumes up to 2 minutes!
     * 
     * @param string    $counterId  Can see: https://metrika.yandex.ru/list/
     * @param string    $token      Can see: https://oauth.yandex.ru/client
     * @param string    $requestId  Request ID of request. Wait unless status "processed"
     * @return int                  Amout of files contains result of request
     * @throws HttpException        Error which Yandex.Metrica responded
     */
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

    /**
     * Download data from request ID.
     * 
     * @param string    $counterId  Can see: https://metrika.yandex.ru/list/
     * @param string    $token      Can see: https://oauth.yandex.ru/client
     * @param int       $partNums   How many output filed should be downloaded?
     * @return string   $requestId  Request ID of request in status "processed"
     * @return string               Output's filename
     * @throws HttpException        Error which Yandex.Metrica responded
     */
    public function downloadParts(string $counterId, string $token, string $requestId, int $partNums): string
    {
        $authorization = $this->authStr($token);
        $header = array('Content-Type: text/csv', $authorization);
        $fileAll = "log_counter_{$counterId}_request_{$requestId}_all.csv";

        for ($i = 0; $i < $partNums; $i++) {
            $url = $this::BASE_URL . "/{$counterId}/logrequest/{$requestId}/part/{$i}/download";
            $responseTsv = $this->downloadCsvPart($url, $header, $i);

            $this->saveTsvToCsvFile($responseTsv, $fileAll);

            print_r("Part {$i} was placed into {$fileAll} file\n");
        }
        print_r("File {$fileAll} was saved\n");
        return $fileAll;
    }

    /**
     * Remove logfiles from Yandex.Metrica according Request ID
     * 
     * @param string    $counterId  Can see: https://metrika.yandex.ru/list/
     * @param string    $token      Can see: https://oauth.yandex.ru/client
     * @return string   $requestId  Request ID of request in status "processed"
     * @throws HttpException        Error which Yandex.Metrica responded
     */
    public function cleanLogfile(string $counterId, string $token, string $requestId)
    {
        $url = $this::BASE_URL . "/{$counterId}/logrequest/{$requestId}/clean";
        $authorization = $this->authStr($token);
        $header = array('Content-Type: application/json', $authorization);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$response || $status !== 200) {
            $error = $this->getErrorMessage($response);
            throw new HttpException($error);
        }
        print_r("Log requestId: {$requestId} was cleaned.\n");
    }

    /**
     * Transfer tsv-string into array and save to csv-file
     * 
     * @param string    &$tsvData   tsv-string
     * @param string    $filename   Csv-filename. Mode: append.
     */
    public function saveTsvToCsvFile(string &$tsvData, string $filename)
    {
        $tsvData = explode("\n", $tsvData); // now its array
        if (file_exists($filename) && filesize($filename) > 0) {
            unset($tsvData[0]);
        }

        $fp = fopen("{$filename}", 'a');
        foreach ($tsvData as $line) {
            $field = explode("\t", $line);
            fputcsv($fp, $field);
        }
        fclose($fp);
    }

    /**
     * Get one tsv-string result according Request Id and part number.
     * 
     * @param  string       $url    Url with Request Id and part number
     * @param array     $authHeader Prepared hader with auth token
     * @return string               tsv-string
     * @throws HttpException        Error which Yandex.Metrica responded
     */
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

        return $responseTsv;
    }

    // Utils
    private function mapUrlAndParams(string $url, array $params): string
    {
        return $url . "?" . http_build_query(data: $params);
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
