<?php

namespace App\Services\Pac;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TimbradorXpressClient
{
    private string $base;
    private string $apikey;

    public function __construct()
    {
        $this->base   = rtrim(config('services.pac.base'), '/');
        $this->apikey = (string) config('services.pac.apikey');
    }

    public function timbrarJSON2(array $jsonPayload, string $keyPem, string $cerPem, string $plantilla = '1'): array
    {
        $endpoint = "{$this->base}/timbrarJSON2"; // JSON -> XML timbrado + PDF
        // jsonB64 debe ser el JSON CFDI en base64
        $jsonB64 = base64_encode(json_encode($jsonPayload, JSON_UNESCAPED_UNICODE));

        $resp = Http::asForm()->timeout(60)->post($endpoint, [
            'apikey'   => $this->apikey,
            'jsonB64'  => $jsonB64,
            'keyPEM'   => $keyPem,
            'cerPEM'   => $cerPem,
            'plantilla'=> $plantilla,
        ]);

        // Respuesta estándar: code, message, data (data puede ser JSON string con xml y pdf)
        return [
            'status'  => $resp->status(),
            'body'    => $resp->body(),
            'json'    => $resp->json(),
        ];
    }

    public function timbrarJSON3(array $jsonPayload, string $keyPem, string $cerPem): array
    {
        $endpoint = "{$this->base}/timbrarJSON3"; // JSON -> XML + datos (UUID, QR, etc.)
        $jsonB64 = base64_encode(json_encode($jsonPayload, JSON_UNESCAPED_UNICODE));

        $resp = Http::asForm()->timeout(60)->post($endpoint, [
            'apikey'  => $this->apikey,
            'jsonB64' => $jsonB64,
            'keyPEM'  => $keyPem,
            'cerPEM'  => $cerPem,
        ]);

        return [
            'status' => $resp->status(),
            'body'   => $resp->body(),
            'json'   => $resp->json(),
        ];
    }
}
