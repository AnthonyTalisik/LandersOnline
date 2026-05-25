<?php

return [
    'api_key'    => 'AIzaSyA-LnwB3b1LIYbtm2PWvMJWx92cvdpdauk',
    'project_id' => 'landersonline-66e95',
];

class FirebaseResult
{
    public int $num_rows;
    private array $rows;
    private int $index = 0;

    public function __construct(array $rows)
    {
        $this->rows = array_values($rows);
        $this->num_rows = count($this->rows);
    }

    public function fetch_assoc(): ?array
    {
        return $this->rows[$this->index++] ?? null;
    }

    public function fetch_all($mode = null): array
    {
        return $this->rows;
    }

    public function data_seek(int $offset): void
    {
        $this->index = max(0, min($offset, $this->num_rows));
    }
}

class FirebaseStore
{
    private string $projectId = 'landersonline-66e95';
    private array $serviceAccount;
    private ?string $accessToken = null;
    private int $tokenExpiresAt = 0;

    public function __construct()
    {
        $json = file_get_contents(__DIR__ . '/../serviceAccountKey.json');
        $this->serviceAccount = json_decode($json, true) ?: [];
    }

    public function result(array $rows): FirebaseResult
    {
        return new FirebaseResult($rows);
    }

    private function docs(string $collection): array
    {
        $rows = [];
        $resp = $this->request('GET', $this->documentsUrl($collection));
        foreach (($resp['documents'] ?? []) as $doc) {
            $row = $this->decodeFields($doc['fields'] ?? []);
            $row['_id'] = basename($doc['name'] ?? '');
            if ($collection === 'categories') {
                $row['Cat_Id'] = (int)($row['Cat_Id'] ?? $row['mysqlId'] ?? $row['_id']);
                $row['Cat_Name'] = $row['Cat_Name'] ?? $row['name'] ?? '';
                $row['Cat_Status'] = $row['Cat_Status'] ?? $row['status'] ?? 'active';
            }
            if ($collection === 'products') {
                $row['Prod_Id'] = (int)($row['Prod_Id'] ?? $row['mysqlId'] ?? $row['_id']);
                $row['Prod_CatId'] = (int)($row['Prod_CatId'] ?? $row['catId'] ?? $row['categoryId'] ?? 0);
                $row['Prod_Name'] = $row['Prod_Name'] ?? $row['name'] ?? '';
                $row['Prod_Size'] = $row['Prod_Size'] ?? $row['size'] ?? '';
                $row['Prod_Price'] = (float)($row['Prod_Price'] ?? $row['price'] ?? 0);
                $row['Prod_OldPrice'] = $row['Prod_OldPrice'] ?? $row['oldPrice'] ?? null;
                $row['Prod_Stock'] = (int)($row['Prod_Stock'] ?? $row['stock'] ?? 0);
                $row['Prod_Image'] = $row['Prod_Image'] ?? $row['image'] ?? '';
                $row['Prod_Brand'] = $row['Prod_Brand'] ?? $row['brand'] ?? '';
                $row['Prod_Desc'] = $row['Prod_Desc'] ?? $row['description'] ?? '';
                $row['Prod_Status'] = $row['Prod_Status'] ?? $row['status'] ?? 'active';
            }
            $rows[] = $row;
        }
        return $rows;
    }

    private function doc(string $collection, string $id): array
    {
        $resp = $this->request('GET', $this->documentUrl($collection, $id), null, false);
        if (!$resp || isset($resp['error'])) {
            return [];
        }
        $data = $this->decodeFields($resp['fields'] ?? []);
        $data['_id'] = basename($resp['name'] ?? $id);
        return $data;
    }

    private function documentsUrl(string $collection): string
    {
        return "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/{$collection}";
    }

    private function documentUrl(string $collection, string $id): string
    {
        return $this->documentsUrl($collection) . '/' . rawurlencode($id);
    }

    private function accessToken(): string
    {
        if ($this->accessToken && time() < $this->tokenExpiresAt - 60) {
            return $this->accessToken;
        }

        $now = time();
        $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claim = $this->base64Url(json_encode([
            'iss' => $this->serviceAccount['client_email'] ?? '',
            'scope' => 'https://www.googleapis.com/auth/datastore',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));
        $input = $header . '.' . $claim;
        openssl_sign($input, $signature, $this->serviceAccount['private_key'] ?? '', OPENSSL_ALGO_SHA256);
        $jwt = $input . '.' . $this->base64Url($signature);

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]),
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($raw, true) ?: [];
        $this->accessToken = $data['access_token'] ?? '';
        $this->tokenExpiresAt = $now + (int)($data['expires_in'] ?? 3600);
        return $this->accessToken;
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function request(string $method, string $url, ?array $payload = null, bool $failOnError = true): array
    {
        $headers = ['Authorization: Bearer ' . $this->accessToken(), 'Content-Type: application/json'];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }
        $raw = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        $data = json_decode($raw, true) ?: [];
        if ($failOnError && ($err || $code >= 400)) {
            throw new RuntimeException($err ?: ($data['error']['message'] ?? "Firestore HTTP $code"));
        }
        return $data;
    }

    private function setDoc(string $collection, string $id, array $data, bool $merge = false): void
    {
        $url = $this->documentUrl($collection, $id);
        if ($merge) {
            foreach (array_keys($data) as $field) {
                $url .= (str_contains($url, '?') ? '&' : '?') . 'updateMask.fieldPaths=' . rawurlencode($field);
            }
        }
        $this->request('PATCH', $url, ['fields' => $this->encodeFields($data)]);
    }

    private function deleteDoc(string $collection, string $id): void
    {
        $this->request('DELETE', $this->documentUrl($collection, $id));
    }

    private function encodeFields(array $data): array
    {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[$key] = $this->encodeValue($value);
        }
        return $fields;
    }

    private function encodeValue($value): array
    {
        if (is_bool($value)) {
            return ['booleanValue' => $value];
        }
        if (is_int($value)) {
            return ['integerValue' => (string)$value];
        }
        if (is_float($value)) {
            return ['doubleValue' => $value];
        }
        if (is_array($value)) {
            $isList = array_keys($value) === range(0, count($value) - 1);
            if ($isList) {
                return ['arrayValue' => ['values' => array_map(fn($v) => $this->encodeValue($v), $value)]];
            }
            return ['mapValue' => ['fields' => $this->encodeFields($value)]];
        }
        if ($value === null) {
            return ['nullValue' => null];
        }
        return ['stringValue' => (string)$value];
    }

    private function decodeFields(array $fields): array
    {
        $data = [];
        foreach ($fields as $key => $value) {
            $data[$key] = $this->decodeValue($value);
        }
        return $data;
    }

    private function decodeValue(array $value)
    {
        if (array_key_exists('stringValue', $value)) return $value['stringValue'];
        if (array_key_exists('integerValue', $value)) return (int)$value['integerValue'];
        if (array_key_exists('doubleValue', $value)) return (float)$value['doubleValue'];
        if (array_key_exists('booleanValue', $value)) return (bool)$value['booleanValue'];
        if (array_key_exists('nullValue', $value)) return null;
        if (array_key_exists('arrayValue', $value)) {
            return array_map(fn($v) => $this->decodeValue($v), $value['arrayValue']['values'] ?? []);
        }
        if (array_key_exists('mapValue', $value)) {
            return $this->decodeFields($value['mapValue']['fields'] ?? []);
        }
        if (array_key_exists('timestampValue', $value)) return $value['timestampValue'];
        return null;
    }

    private function nextNumericId(string $collection, string $field, int $fallback): int
    {
        $max = $fallback;
        foreach ($this->docs($collection) as $row) {
            $max = max($max, (int)($row[$field] ?? 0));
        }
        return $max + 1;
    }

    private function now(): string
    {
        return date('c');
    }

    public function categories(bool $activeOnly = false): FirebaseResult
    {
        $rows = $this->docs('categories');
        if ($activeOnly) {
            $rows = array_values(array_filter($rows, fn($r) => ($r['Cat_Status'] ?? 'active') === 'active'));
        }
        usort($rows, fn($a, $b) => strcasecmp($a['Cat_Name'] ?? '', $b['Cat_Name'] ?? ''));
        return $this->result($rows);
    }

    public function categoryNameById($catId): string
    {
        foreach ($this->docs('categories') as $cat) {
            if ((int)($cat['Cat_Id'] ?? 0) === (int)$catId) {
                return (string)($cat['Cat_Name'] ?? '');
            }
        }
        return '';
    }

    public function categoryDocIdById($catId): ?string
    {
        foreach ($this->docs('categories') as $cat) {
            if ((int)($cat['Cat_Id'] ?? 0) === (int)$catId) {
                return $cat['_id'];
            }
        }
        return null;
    }

    public function categoryExistsByName(string $name, $exceptCatId = null): bool
    {
        foreach ($this->docs('categories') as $cat) {
            if ($exceptCatId !== null && (int)($cat['Cat_Id'] ?? 0) === (int)$exceptCatId) {
                continue;
            }
            if (strcasecmp(trim($cat['Cat_Name'] ?? ''), trim($name)) === 0) {
                return true;
            }
        }
        return false;
    }

    public function addCategory(string $name): int
    {
        $id = $this->nextNumericId('categories', 'Cat_Id', 100);
        $this->setDoc('categories', (string)$id, [
            'Cat_Id' => $id,
            'Cat_Name' => $name,
            'Cat_Status' => 'active',
            'mysqlId' => $id,
            'name' => $name,
            'status' => 'active',
            'createdAt' => $this->now(),
        ]);
        return $id;
    }

    public function updateCategory(int $id, string $name): void
    {
        $docId = $this->categoryDocIdById($id) ?? (string)$id;
        $this->setDoc('categories', $docId, ['Cat_Name' => $name, 'name' => $name], true);
    }

    public function toggleCategory(int $id): void
    {
        $docId = $this->categoryDocIdById($id) ?? (string)$id;
        $cat = $this->doc('categories', $docId);
        $new = ($cat['Cat_Status'] ?? 'active') === 'active' ? 'inactive' : 'active';
        $this->setDoc('categories', $docId, ['Cat_Status' => $new, 'status' => $new], true);
    }

    public function categoriesWithProductCount(): FirebaseResult
    {
        $products = $this->docs('products');
        $rows = $this->docs('categories');
        foreach ($rows as &$cat) {
            $cat['prod_count'] = count(array_filter($products, fn($p) =>
                (int)($p['Prod_CatId'] ?? 0) === (int)($cat['Cat_Id'] ?? 0)
                && ($p['Prod_Status'] ?? 'active') === 'active'
            ));
        }
        usort($rows, fn($a, $b) => (int)($b['Cat_Id'] ?? 0) <=> (int)($a['Cat_Id'] ?? 0));
        return $this->result($rows);
    }

    public function productsWithCategory($catId = 0, bool $activeOnly = false): FirebaseResult
    {
        $rows = $this->docs('products');
        $catNames = [];
        foreach ($this->docs('categories') as $cat) {
            $catNames[(int)($cat['Cat_Id'] ?? 0)] = $cat['Cat_Name'] ?? '';
        }
        $rows = array_values(array_filter($rows, function ($p) use ($catId, $activeOnly) {
            if ($activeOnly && ($p['Prod_Status'] ?? 'active') !== 'active') {
                return false;
            }
            if ((int)$catId > 0 && (int)($p['Prod_CatId'] ?? 0) !== (int)$catId) {
                return false;
            }
            return true;
        }));
        foreach ($rows as &$row) {
            $row['Cat_Name'] = $catNames[(int)($row['Prod_CatId'] ?? 0)] ?? '';
            $row['Cat_Id'] = (int)($row['Prod_CatId'] ?? 0);
        }
        usort($rows, fn($a, $b) => ($activeOnly
            ? strcasecmp(($a['Cat_Name'] ?? '') . ($a['Prod_Name'] ?? ''), ($b['Cat_Name'] ?? '') . ($b['Prod_Name'] ?? ''))
            : (int)($b['Prod_Id'] ?? 0) <=> (int)($a['Prod_Id'] ?? 0)
        ));
        return $this->result($rows);
    }

    public function productById(int $id, bool $activeOnly = false): ?array
    {
        foreach ($this->productsWithCategory(0, $activeOnly)->fetch_all() as $product) {
            if ((int)($product['Prod_Id'] ?? 0) === $id) {
                return $product;
            }
        }
        return null;
    }

    public function productDocIdById(int $id): ?string
    {
        foreach ($this->docs('products') as $product) {
            if ((int)($product['Prod_Id'] ?? 0) === $id) {
                return $product['_id'];
            }
        }
        return null;
    }

    public function addProduct(array $data): int
    {
        $id = $this->nextNumericId('products', 'Prod_Id', 1000);
        $data['Prod_Id'] = $id;
        $data['Prod_Status'] = 'active';
        $data['createdAt'] = $this->now();
        $this->setDoc('products', (string)$id, $data);
        return $id;
    }

    public function updateProduct(int $id, array $data): void
    {
        $docId = $this->productDocIdById($id) ?? (string)$id;
        $this->setDoc('products', $docId, $data, true);
    }

    public function toggleProduct(int $id): void
    {
        $product = $this->productById($id);
        $new = ($product['Prod_Status'] ?? 'active') === 'active' ? 'inactive' : 'active';
        $this->updateProduct($id, ['Prod_Status' => $new]);
    }

    public function deleteProduct(int $id): void
    {
        $docId = $this->productDocIdById($id) ?? (string)$id;
        $this->deleteDoc('products', $docId);
    }

    public function addToCart(string $uid, int $prodId, int $qty): void
    {
        $docId = $uid . '_' . $prodId;
        $item = $this->doc('cartItems', $docId);
        $current = (int)($item['Cart_Qty'] ?? 0);
        $this->setDoc('cartItems', $docId, [
            'Cart_Id' => $docId,
            'Cart_AcctId' => $uid,
            'Cart_ProdId' => $prodId,
            'Cart_Qty' => $current + $qty,
            'updatedAt' => $this->now(),
        ], true);
    }

    public function updateCartQty(string $uid, string $cartId, int $qty): void
    {
        $item = $this->doc('cartItems', $cartId);
        if (($item['Cart_AcctId'] ?? '') === $uid) {
            $this->setDoc('cartItems', $cartId, ['Cart_Qty' => $qty], true);
        }
    }

    public function removeCartItem(string $uid, string $cartId): void
    {
        $item = $this->doc('cartItems', $cartId);
        if (($item['Cart_AcctId'] ?? '') === $uid) {
            $this->deleteDoc('cartItems', $cartId);
        }
    }

    public function cartItems(string $uid): FirebaseResult
    {
        $products = [];
        foreach ($this->docs('products') as $p) {
            $products[(int)($p['Prod_Id'] ?? 0)] = $p;
        }
        $rows = [];
        foreach ($this->docs('cartItems') as $item) {
            if (($item['Cart_AcctId'] ?? '') !== $uid) {
                continue;
            }
            $prodId = (int)($item['Cart_ProdId'] ?? 0);
            if (!isset($products[$prodId])) {
                continue;
            }
            $rows[] = array_merge($item, $products[$prodId], [
                'Cart_Id' => $item['_id'],
            ]);
        }
        usort($rows, fn($a, $b) => strcmp((string)$a['Cart_Id'], (string)$b['Cart_Id']));
        return $this->result($rows);
    }

    public function cartCount(string $uid): int
    {
        $total = 0;
        foreach ($this->cartItems($uid)->fetch_all() as $item) {
            $total += (int)($item['Cart_Qty'] ?? 0);
        }
        return $total;
    }

    public function clearCart(string $uid): void
    {
        foreach ($this->docs('cartItems') as $item) {
            if (($item['Cart_AcctId'] ?? '') === $uid) {
                $this->deleteDoc('cartItems', $item['_id']);
            }
        }
    }

    public function userProfile(string $uid): array
    {
        return $this->doc('users', $uid);
    }

    public function updateUserProfile(string $uid, array $data): void
    {
        $this->setDoc('users', $uid, $data, true);
    }

    public function placeOrder(string $uid, array $customer, string $address, float $subtotal, float $deliveryFee, array $cartItems): int
    {
        $id = $this->nextNumericId('orders', 'Ord_Id', 0);
        $items = [];
        foreach ($cartItems as $item) {
            $items[] = [
                'OrdItem_Id' => count($items) + 1,
                'OrdItem_OrdId' => $id,
                'OrdItem_ProdId' => (int)$item['Prod_Id'],
                'OrdItem_ProdName' => (string)$item['Prod_Name'],
                'OrdItem_Price' => (float)$item['Prod_Price'],
                'OrdItem_Qty' => (int)$item['Cart_Qty'],
                'Prod_Image' => $item['Prod_Image'] ?? '',
            ];
            $docId = $this->productDocIdById((int)$item['Prod_Id']);
            if ($docId) {
                $stock = max(0, (int)($item['Prod_Stock'] ?? 0) - (int)$item['Cart_Qty']);
                $this->setDoc('products', $docId, ['Prod_Stock' => $stock], true);
            }
        }
        $total = $subtotal + $deliveryFee;
        $this->setDoc('orders', (string)$id, [
            'Ord_Id' => $id,
            'Ord_AcctId' => $uid,
            'Ord_CustId' => $uid,
            'Ord_Total' => $total,
            'Ord_DelivFee' => $deliveryFee,
            'Ord_Address' => $address,
            'Ord_Status' => 'pending',
            'Ord_CreatedAt' => $this->now(),
            'Cust_Name' => trim(($customer['firstName'] ?? '') . ' ' . ($customer['lastName'] ?? '')),
            'Cust_Phone' => $customer['phone'] ?? '',
            'items' => $items,
        ]);
        $this->clearCart($uid);
        return $id;
    }

    public function orders(string $status = '', ?string $uid = null): FirebaseResult
    {
        $rows = $this->docs('orders');
        $rows = array_values(array_filter($rows, function ($order) use ($status, $uid) {
            if ($status !== '' && ($order['Ord_Status'] ?? '') !== $status) {
                return false;
            }
            if ($uid !== null && ($order['Ord_AcctId'] ?? '') !== $uid) {
                return false;
            }
            return true;
        }));
        foreach ($rows as &$row) {
            $items = $row['items'] ?? [];
            $row['item_count'] = count($items);
            $row['total_qty'] = array_sum(array_map(fn($i) => (int)($i['OrdItem_Qty'] ?? 0), $items));
            $row['items_count'] = count($items);
            $row['Cust_Name'] = $row['Cust_Name'] ?? 'Customer';
        }
        usort($rows, fn($a, $b) => (int)($b['Ord_Id'] ?? 0) <=> (int)($a['Ord_Id'] ?? 0));
        return $this->result($rows);
    }

    public function updateOrderStatus(int $id, string $status): void
    {
        $this->setDoc('orders', (string)$id, ['Ord_Status' => $status], true);
    }

    public function orderItems(array $order): FirebaseResult
    {
        return $this->result($order['items'] ?? []);
    }

    public function customers(): FirebaseResult
    {
        $orders = $this->docs('orders');
        $rows = [];
        foreach ($this->docs('users') as $user) {
            if (($user['role'] ?? 'customer') !== 'customer') {
                continue;
            }
            $uid = $user['_id'];
            $delivered = array_filter($orders, fn($o) => ($o['Ord_AcctId'] ?? '') === $uid && ($o['Ord_Status'] ?? '') === 'delivered');
            $allOrders = array_filter($orders, fn($o) => ($o['Ord_AcctId'] ?? '') === $uid);
            $rows[] = [
                'Cust_AcctId' => $uid,
                'Cust_Name' => trim(($user['firstName'] ?? '') . ' ' . ($user['lastName'] ?? '')),
                'Cust_Phone' => $user['phone'] ?? '',
                'Acct_Email' => $user['email'] ?? '',
                'Acct_Status' => $user['status'] ?? 'active',
                'Acct_CreatedAt' => $user['createdAt'] ?? '',
                'total_orders' => count($allOrders),
                'total_spent' => array_sum(array_map(fn($o) => (float)($o['Ord_Total'] ?? 0), $delivered)),
            ];
        }
        usort($rows, fn($a, $b) => strcmp((string)$b['Acct_CreatedAt'], (string)$a['Acct_CreatedAt']));
        return $this->result($rows);
    }

    public function toggleCustomer(string $uid): void
    {
        $user = $this->userProfile($uid);
        $new = ($user['status'] ?? 'active') === 'active' ? 'inactive' : 'active';
        $this->updateUserProfile($uid, ['status' => $new]);
    }
}

function firebaseStore(): FirebaseStore
{
    static $store = null;
    if ($store === null) {
        $store = new FirebaseStore();
    }
    return $store;
}
