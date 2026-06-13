<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Google\Auth\Credentials\ServiceAccountCredentials;
use GuzzleHttp\Client;

class RESTFirestore {
    private $projectId;
    private $accessToken;
    private $client;

    public function __construct($projectId, $accessToken) {
        $this->projectId = $projectId;
        $this->accessToken = $accessToken;
        $this->client = new Client();
    }

    public function collection($name) {
        return new RESTCollection($this, $name);
    }

    public function sendRequest($method, $documentPath, $data, $updateMasks = null) {
        $url = "https://firestore.googleapis.com/v1/projects/" . urlencode($this->projectId) . "/databases/(default)/documents/" . $documentPath;
        
        $queryParams = [];
        if ($updateMasks !== null) {
            foreach ($updateMasks as $mask) {
                $queryParams[] = "updateMask.fieldPaths=" . urlencode($mask);
            }
        }
        if (!empty($queryParams)) {
            $url .= "?" . implode("&", $queryParams);
        }

        $options = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ]
        ];

        if ($method !== 'DELETE') {
            $fields = [];
            foreach ($data as $k => $v) {
                $fields[$k] = $this->toFirestoreValue($v);
            }
            $options['json'] = [
                'fields' => (object)$fields
            ];
        }

        try {
            $response = $this->client->request($method, $url, $options);
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            $errorMsg = "Firestore API Error: " . $e->getMessage();
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $errorMsg .= " | Response: " . $e->getResponse()->getBody()->getContents();
            }
            throw new \Exception($errorMsg);
        }
    }

    private function toFirestoreValue($val) {
        if (is_null($val)) {
            return ['nullValue' => null];
        }
        if (is_bool($val)) {
            return ['booleanValue' => (bool)$val];
        }
        if (is_int($val)) {
            return ['integerValue' => (string)$val];
        }
        if (is_float($val)) {
            return ['doubleValue' => (double)$val];
        }
        if (is_string($val)) {
            return ['stringValue' => (string)$val];
        }
        if (is_array($val)) {
            if (empty($val) || array_keys($val) === range(0, count($val) - 1)) {
                $values = [];
                foreach ($val as $v) {
                    $values[] = $this->toFirestoreValue($v);
                }
                return ['arrayValue' => ['values' => $values]];
            } else {
                $fields = [];
                foreach ($val as $k => $v) {
                    $fields[$k] = $this->toFirestoreValue($v);
                }
                return ['mapValue' => ['fields' => (object)$fields]];
            }
        }
        if (is_object($val)) {
            return $this->toFirestoreValue((array)$val);
        }
        return ['stringValue' => (string)$val];
    }
}

class RESTCollection {
    private $firestore;
    private $name;
    private $parentPath;

    public function __construct($firestore, $name, $parentPath = '') {
        $this->firestore = $firestore;
        $this->name = $name;
        $this->parentPath = $parentPath;
    }

    public function document($id) {
        return new RESTDocument($this->firestore, $this->name, $id, $this->parentPath);
    }
}

class RESTDocument {
    private $firestore;
    private $collectionName;
    private $id;
    private $parentPath;

    public function __construct($firestore, $collectionName, $id, $parentPath = '') {
        $this->firestore = $firestore;
        $this->collectionName = $collectionName;
        $this->id = $id;
        $this->parentPath = $parentPath;
    }

    public function collection($name) {
        $currentDocPath = ($this->parentPath ? $this->parentPath . '/' : '') . $this->collectionName . '/' . $this->id;
        return new RESTCollection($this->firestore, $name, $currentDocPath);
    }

    public function set($data) {
        return $this->firestore->sendRequest('PATCH', $this->getDocumentPath(), $data);
    }

    public function delete() {
        return $this->firestore->sendRequest('DELETE', $this->getDocumentPath(), []);
    }

    public function update($fields) {
        $data = [];
        $updateMasks = [];
        foreach ($fields as $key => $val) {
            if (is_array($val) && isset($val['path']) && isset($val['value'])) {
                $path = $val['path'];
                $data[$path] = $val['value'];
                $updateMasks[] = $path;
            } else {
                $data[$key] = $val;
                $updateMasks[] = $key;
            }
        }
        return $this->firestore->sendRequest('PATCH', $this->getDocumentPath(), $data, $updateMasks);
    }

    private function getDocumentPath() {
        return ($this->parentPath ? $this->parentPath . '/' : '') . $this->collectionName . '/' . $this->id;
    }
}

function getFirebaseFirestore() {
    $credentialsPath = __DIR__ . '/firebase_credentials.json';
    if (!file_exists($credentialsPath)) {
        throw new \Exception("Firebase credentials file not found at: " . $credentialsPath);
    }
    
    $json = json_decode(file_get_contents($credentialsPath), true);
    $projectId = $json['project_id'] ?? '';
    if (empty($projectId)) {
        throw new \Exception("Project ID not found in Firebase credentials");
    }

    $sa = new ServiceAccountCredentials('https://www.googleapis.com/auth/datastore', $credentialsPath);
    $token = $sa->fetchAuthToken();
    $accessToken = $token['access_token'] ?? '';
    if (empty($accessToken)) {
        throw new \Exception("Failed to fetch Google OAuth2 access token");
    }

    return new RESTFirestore($projectId, $accessToken);
}
?>
