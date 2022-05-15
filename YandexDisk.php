<?php

class YandexDisk
{

    public function getToken() {
        return json_decode(file_get_contents(YANDEX_DISK_TOKEN_FILE));
    }

    public function refreshToken() {
        $token = $this->getToken();
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://oauth.yandex.ru/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query([
                'refresh_token' => $token->refresh_token,
                'grant_type' => 'refresh_token',
            ]),
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . base64_encode(YANDEX_DISK_ID . ':' . YANDEX_DISK_PASSWORD),
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);
        $newToken = json_decode($response);
        if(!$newToken->access_token)
            return $token;

        file_put_contents(YANDEX_DISK_TOKEN_FILE, $response);
        return $newToken;
    }

    public function uploadFile(string $filename, string $yandexPath) {
        $yandexDir = pathinfo($yandexPath, PATHINFO_DIRNAME);
        $this->createDir($yandexDir); // yandex не даст загрузить, если не будет папки
        $uploadUrl = $this->getUploadUrl($yandexPath);
        if(!$uploadUrl->href)
            throw new Exception(json_encode($uploadUrl));
        exec("curl -X PUT --data-binary \"@$filename\" \"$uploadUrl->href\"");
    }

    public function createDir(string $yandexDir) {
        $dirs = explode('/', $yandexDir);
        $createdDirs = [];
        foreach ($dirs as $dir) {
            $this->createDirRequest(implode('/', array_merge($createdDirs, [$dir])));
            $createdDirs[] = $dir;
        }
    }

    protected function getUploadUrl(string $yandexPath) {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://cloud-api.yandex.net/v1/disk/resources/upload?' . http_build_query([
                'path' => $yandexPath,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Authorization: OAuth ' . $this->getToken()->access_token,
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);
    }

    protected function createDirRequest(string $yandexDir) {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://cloud-api.yandex.net/v1/disk/resources?' . http_build_query([
                'path' => $yandexDir,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_HTTPHEADER => [
                'Authorization: OAuth ' . $this->getToken()->access_token,
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);
    }
}