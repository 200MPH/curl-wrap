<?php

/**
 * Test doubles for cURL that live in the SAME namespace as the code under test (thm\curl),
 * so unqualified calls (curl_exec, curl_getinfo, etc.) resolve here first.
 *
 * We also expose tiny helpers to reset and read captured state without touching $GLOBALS.
 */

declare(strict_types=1);

namespace thm\curl {
    /** @var array<int, mixed> */
    $___setopts = [];

    /** @var list<float> deterministic times for microtime(true) */
    $___times = [1000.000, 1001.234];

    /** @internal */
    function __reset_mocks(): void
    {
        $GLOBALS['___setopts'] = [];
        $GLOBALS['___times']   = [1000.000, 1001.234];
    }

    /**
     * @return array<int,mixed>
     * @internal
     */
    function __get_setopts(): array
    {
        /** @var array<int,mixed> */
        return $GLOBALS['___setopts'];
    }

    // ---- cURL doubles (same signatures as the extension) ----

    function curl_init(?string $url = null)
    {
        // Return a real handle so type checks on CurlHandle pass

        if ($url === 'fail') {
            return false;
        }

        return \curl_init($url ?? '');
    }

    function curl_close($handle): void
    {
        // no-op
    }

    function curl_setopt($handle, int $option, $value): bool
    {

        if ($option === 999999) {
            return false;
        }

        $GLOBALS['___setopts'][$option] = $value;
        return true;
    }

    function curl_exec($handle)
    {
        return 'OK-BODY';
    }

    function curl_getinfo($handle, int $opt = 0)
    {
        if ($opt === \CURLINFO_HTTP_CODE) {
            return 200;
        }
        return [
            'url' => 'http://unit.test',
            'content_type' => 'text/plain',
        ];
    }

    function curl_errno($handle): int
    {
        return 0;
    }

    function curl_error($handle): string
    {
        return '';
    }

    // Deterministic timing for CurlResponse::getResponseTime()
    function microtime(bool $as_float = false)
    {
        // Always behave like microtime(true)
        return array_shift($GLOBALS['___times']);
    }
}

namespace thm\test {

    use CURLFile;
    use PHPUnit\Framework\Attributes\DataProvider;
    use PHPUnit\Framework\TestCase;
    use ReflectionMethod;
    use thm\curl\Curl;
    use thm\curl\CurlException;
    use thm\curl\CurlResponse;
    use TypeError;

    final class CurlAndResponseTest extends TestCase
    {
        private string $url = 'http://unit.test';

        protected function setUp(): void
        {
            // reset captured options & times before each test
            \thm\curl\__reset_mocks();
        }

        public function testConsts(): void
        {
            $this->assertEquals('GET', Curl::GET);
            $this->assertEquals('POST', Curl::POST);
            $this->assertEquals('PUT', Curl::PUT);
            $this->assertEquals('PATCH', Curl::PATCH);
            $this->assertEquals('DELETE', Curl::DELETE);
        }

        public function testInitException(): void
        {
            $this->expectException(CurlException::class);
            $this->expectExceptionCode(CurlException::CURL_INIT_FAIL);
            new Curl('fail');
        }

        public function testSetOptException(): void
        {
            $this->expectException(CurlException::class);
            $this->expectExceptionCode(CurlException::SETOP_FAIL);
            $curl = new Curl($this->url);
            $curl->setopt(999999, 99999);
        }

        public function testOpt(): void
        {
            $curl = new Curl($this->url);
            $state = $curl->setopt(1, 1);
            $this->assertInstanceOf(Curl::class, $state);
        }

        public function testTimeout(): void
        {
            $curl = new Curl($this->url);
            $curl->setTimeout(30);
            $opts = \thm\curl\__get_setopts();
            $this->assertEquals(30, $opts[\CURLOPT_TIMEOUT]);
            $this->expectException(TypeError::class);
            $curl->setTimeout("30"); // it must be string for testing purpose
        }

        public function testGetMethod(): void
        {
            $curl = new Curl($this->url);
            $response = $curl->get();
            $this->assertInstanceOf(CurlResponse::class, $response);
            $this->assertEquals('OK-BODY', $response->getBody());
            $response200 = $response->getStatus() >= 200 && $response->getStatus() <= 299;
            $this->assertTrue($response200);
            $opts = \thm\curl\__get_setopts();
            $this->assertTrue($opts[\CURLOPT_RETURNTRANSFER] === 1);
        }

        public function testPostMethod(): void
        {
            $curl = new Curl($this->url);
            $payload = ['f1' => 12345, 'f2' => 'test'];
            $expFields = '';

            foreach ($payload as $name => $value) {
                $expFields .= "{$name}={$value}&";
            }

            $expFields = substr($expFields, 0, -1); // remove last &
            $expHeader = ['testheader' => '123'];
            $response = $curl->post($payload, [], $expHeader);
            $this->assertInstanceOf(CurlResponse::class, $response);
            $this->assertEquals('OK-BODY', $response->getBody());
            $response200 = $response->getStatus() >= 200 && $response->getStatus() <= 299;
            $this->assertTrue($response200);
            $opts = \thm\curl\__get_setopts();
            $fields = $opts[\CURLOPT_POSTFIELDS];
            $isPostMethodSet = $opts[\CURLOPT_POST];
            $header = $opts[\CURLOPT_HTTPHEADER];
            $this->assertIsArray($header);
            $this->assertEquals($header, $expHeader);
            $this->assertTrue($isPostMethodSet);
            $this->assertEquals($fields, $expFields);
        }

        public function testPostFiles(): void
        {
            $curl = new Curl($this->url);
            $files[] = new CURLFile('uploaded.pdf');
            $response = $curl->post([], $files);
            $this->assertInstanceOf(CurlResponse::class, $response);
            $this->assertEquals('OK-BODY', $response->getBody());
            $opts = \thm\curl\__get_setopts();
            $fields = $opts[\CURLOPT_POSTFIELDS];
            $this->assertIsArray($fields);
            $this->assertArrayHasKey('files[0]', $fields);
            $this->assertInstanceOf(CURLFile::class, $fields['files[0]']);
            $this->assertEquals($fields['files[0]']->name, 'uploaded.pdf');
        }

        public function testPut(): void
        {
            $curl = new Curl($this->url);
            $result = $curl->put();
            $this->assertInstanceOf(CurlResponse::class, $result);
            $opts = \thm\curl\__get_setopts();
            $patch = $opts[\CURLOPT_CUSTOMREQUEST];
            $this->assertEquals('PUT', $patch);
        }

        public function testPatch(): void
        {
            $curl = new Curl($this->url);
            $result = $curl->patch();
            $this->assertInstanceOf(CurlResponse::class, $result);
            $opts = \thm\curl\__get_setopts();
            $patch = $opts[\CURLOPT_CUSTOMREQUEST];
            $this->assertEquals('PATCH', $patch);
        }

        public function testDelete(): void
        {
            $curl = new Curl($this->url);
            $result = $curl->delete();
            $this->assertInstanceOf(CurlResponse::class, $result);
            $opts = \thm\curl\__get_setopts();
            $patch = $opts[\CURLOPT_CUSTOMREQUEST];
            $this->assertEquals('DELETE', $patch);
        }

        public function testJson(): void
        {
            $data = ['name' => 'foo', 'surname' => 'bar'];
            $json = json_encode($data);
            $curl = new Curl($this->url);
            $result = $curl->json($json, Curl::POST);
            $this->assertInstanceOf(CurlResponse::class, $result);
            $opts = \thm\curl\__get_setopts();
            $headers = $opts[\CURLOPT_HTTPHEADER];
            $return = $opts[\CURLOPT_POSTFIELDS];
            $this->assertIsArray($headers);
            $this->assertContains('Content-Type: application/json', $headers);
            $this->assertContains('Accept: application/json', $headers);
            $returnedData = json_decode($return, true);
            $this->assertEquals($returnedData, $data);
        }

        public function testJsonIsNotDeleteMethod(): void
        {
            $data = ['name' => 'foo', 'surname' => 'bar'];
            $json = json_encode($data);
            $curl = new Curl($this->url);
            $curl->json($json, Curl::DELETE);
            $opts = \thm\curl\__get_setopts();
            $method = $opts[\CURLOPT_CUSTOMREQUEST];
            $this->assertEquals($method, Curl::POST);
        }

        public function testBinary(): void
        {
            $data = 'abcdefgh';
            $length = strlen($data);
            $curl = new Curl($this->url);
            $result = $curl->binary($data);
            $this->assertInstanceOf(CurlResponse::class, $result);
            $opts = \thm\curl\__get_setopts();
            $headers = $opts[\CURLOPT_HTTPHEADER];
            $this->assertContains('Content-Type: application/octet-stream', $headers);
            $this->assertContains('Content-Length: ' . $length, $headers);
        }

        public function testRequest(): void
        {
            $params = ['a' => 1, 'b' => "bravo"];
            $curl = new Curl($this->url);
            $curl->setParameters($params);
            $res = $curl->request();
            $this->assertInstanceOf(CurlResponse::class, $res);
            $opts = \thm\curl\__get_setopts();
            $expFields = '';

            foreach ($params as $name => $value) {
                $expFields .= "{$name}={$value}&";
            }

            $expFields = substr($expFields, 0, -1); // remove last &
            $fields = $opts[\CURLOPT_POSTFIELDS];
            $this->assertEquals($fields, $expFields);
        }


        public function testBearerAuthHeaderIsAppliedOnPost(): void
        {
            $curl = new Curl('http://unit.test');
            $curl->setBearerAuth('super-secret-token')
                ->post(['a' => 1]); // triggers preparePost() which merges headers

            $opts = \thm\curl\__get_setopts();
            $this->assertArrayHasKey(\CURLOPT_HTTPHEADER, $opts, 'HTTP headers must be set via CURLOPT_HTTPHEADER');
            $headers = $opts[\CURLOPT_HTTPHEADER];
            $this->assertIsArray($headers);
            // Ensure Authorization header with *actual* value reached cURL
            $this->assertContains('Authorization: Bearer super-secret-token', $headers);
        }

        #[DataProvider('sensitiveAttributeProvider')]
        public function testSensitiveParameterAttributes(string $method, string $failureMessage): void
        {
            $rm = new ReflectionMethod(Curl::class, $method);
            $params = $rm->getParameters();
            $this->assertNotEmpty($params, sprintf('Method %s must have at least one parameter', $method));
            $attrs = $params[0]->getAttributes(\SensitiveParameter::class);
            $this->assertNotEmpty($attrs, $failureMessage);
        }

        /**
         * @return iterable<string, array{0:string,1:string}>
         */
        public static function sensitiveAttributeProvider(): iterable
        {
            yield 'json data' => ['json', 'json($data) must be marked with #[SensitiveParameter]'];
            yield 'binary data' => ['binary', 'binary($data) must be marked with #[SensitiveParameter]'];
            yield 'bearer token' => [
                'setBearerAuth',
                'setBearerAuth($token) must be marked with #[SensitiveParameter]'
            ];
        }

        public function testCurlResponseCollectsBodyStatusInfoAndTiming(): void
        {
            // Build a real handle (type: CurlHandle); behavior uses our doubles in thm\curl
            $handle = \curl_init('http://unit.test');

            $resp = new CurlResponse($handle);

            // Body from mocked curl_exec
            $this->assertSame('OK-BODY', $resp->getBody());

            // Status code from mocked curl_getinfo
            $this->assertSame(200, $resp->getStatus());

            // Info array from mocked curl_getinfo(no option)
            $info = $resp->getInfo();
            $this->assertIsArray($info);
            $this->assertSame('http://unit.test', $info['url'] ?? null);
            $this->assertSame('text/plain', $info['content_type'] ?? null);

            // Error reporting from mocked curl_errno / curl_error
            $this->assertSame(0, $resp->getErrorNo());
            $this->assertSame('', $resp->getError());

            // Deterministic response time: 1001.234 - 1000.000 = 1.234 -> round(…, 3)
            $this->assertSame(1.234, $resp->getResponseTime());
        }

        public function testPreparePostSetsPostFieldsAndReturnTransfer(): void
        {
            $curl = new Curl('http://unit.test');
            $curl->post(['x' => 'y']);

            $opts = \thm\curl\__get_setopts();

            $this->assertSame(1, $opts[\CURLOPT_RETURNTRANSFER] ?? null);
            $this->assertTrue($opts[\CURLOPT_POST] ?? false);

            // When no files are passed, postFields are http_build_query(...) string
            $this->assertIsString($opts[\CURLOPT_POSTFIELDS] ?? null);
            $this->assertSame('x=y', $opts[\CURLOPT_POSTFIELDS]);
        }
    }
}
