<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ParsePutFormData
{
    /**
     * Handle an incoming request.
     *
     * Parse PUT/PATCH request body with multipart/form-data
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Chỉ xử lý PUT/PATCH request với multipart/form-data
        if (in_array($request->method(), ['PUT', 'PATCH']) &&
            str_contains($request->header('Content-Type', ''), 'multipart/form-data')) {

            $this->parsePutRequest($request);
        }

        return $next($request);
    }

    /**
     * Parse PUT/PATCH request body
     */
    protected function parsePutRequest(Request $request): void
    {
        // Đọc raw input
        $rawInput = file_get_contents('php://input');

        if (empty($rawInput)) {
            return;
        }

        // Lấy boundary từ Content-Type header
        $contentType = $request->header('Content-Type');
        preg_match('/boundary=(.*)$/', $contentType, $matches);

        if (!isset($matches[1])) {
            return;
        }

        $boundary = $matches[1];

        // Parse multipart data
        $parts = array_slice(explode('--' . $boundary, $rawInput), 1);
        $data = [];
        $files = [];

        foreach ($parts as $part) {
            if (trim($part) === '--' || empty(trim($part))) {
                continue;
            }

            // Tách header và content
            [$rawHeaders, $content] = explode("\r\n\r\n", $part, 2);

            // Parse headers
            $headers = [];
            foreach (explode("\r\n", $rawHeaders) as $header) {
                if (strpos($header, ':') !== false) {
                    [$name, $value] = explode(':', $header, 2);
                    $headers[strtolower(trim($name))] = trim($value);
                }
            }

            if (!isset($headers['content-disposition'])) {
                continue;
            }

            // Parse Content-Disposition
            preg_match('/name="([^"]*)"/', $headers['content-disposition'], $nameMatch);
            $fieldName = $nameMatch[1] ?? null;

            if (!$fieldName) {
                continue;
            }

            // Check nếu là file
            if (preg_match('/filename="([^"]*)"/', $headers['content-disposition'], $filenameMatch)) {
                $filename = $filenameMatch[1];

                if (!empty($filename)) {
                    // Xử lý file
                    $tmpPath = tempnam(sys_get_temp_dir(), 'put_upload_');
                    file_put_contents($tmpPath, rtrim($content, "\r\n"));

                    $files[$fieldName] = [
                        'name' => $filename,
                        'type' => $headers['content-type'] ?? 'application/octet-stream',
                        'tmp_name' => $tmpPath,
                        'error' => 0,
                        'size' => filesize($tmpPath)
                    ];
                }
            } else {
                // Xử lý text field
                $data[$fieldName] = rtrim($content, "\r\n");
            }
        }

        // Merge data vào request
        $request->merge($data);

        // Merge files vào request
        foreach ($files as $key => $file) {
            $uploadedFile = new \Illuminate\Http\UploadedFile(
                $file['tmp_name'],
                $file['name'],
                $file['type'],
                $file['error'],
                true
            );

            $request->files->set($key, $uploadedFile);
        }
    }
}
