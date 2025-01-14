<?php
namespace OCA\TokenBasedDav\Controller;

use OCP\AppFramework\{Controller, Http\JSONResponse, Http\TemplateResponse};
use OC\AppFramework\Http;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\Files\IRootFolder;
use OC\Group\Manager;
use OC\User\Session;
use OCA\TokenBasedDav\Services\JWTHelper;
use OCP\ILogger;
use OCP\IRequest;
use Firebase\JWT\JWT;

class AuthController extends Controller {

	/**
	 * @var Session
	 */
	private $session;

	/**
	 * @var JWTHelper
	 */
	private $jwtHelper;

	/**
	 * @var Manager
	 */
	private $groupManager;

	/**
	 * @var ILogger
	 */
	private $logger;

	/**
	 * @var IRootFolder
	 */
	private $rootFolder;
	
	public function __construct(
		$appName,
		IRequest $request,
		JWTHelper $jwtHelper,
		ILogger $logger,
		IRootFolder $rootFolder
	) {
		parent::__construct($appName, $request);
		$this->jwtHelper = $jwtHelper;
		$this->logger = $logger;
		$this->rootFolder = $rootFolder;
	}

	
	/**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
	public function token(){
		$payload = [
			"name" => "navid",
			"family" => "shokri"
		];
		$privateKey = <<<EOD
-----BEGIN RSA PRIVATE KEY-----
MIIEowIBAAKCAQEAuzWHNM5f+amCjQztc5QTfJfzCC5J4nuW+L/aOxZ4f8J3Frew
M2c/dufrnmedsApb0By7WhaHlcqCh/ScAPyJhzkPYLae7bTVro3hok0zDITR8F6S
JGL42JAEUk+ILkPI+DONM0+3vzk6Kvfe548tu4czCuqU8BGVOlnp6IqBHhAswNMM
78pos/2z0CjPM4tbeXqSTTbNkXRboxjU29vSopcT51koWOgiTf3C7nJUoMWZHZI5
HqnIhPAG9yv8HAgNk6CMk2CadVHDo4IxjxTzTTqo1SCSH2pooJl9O8at6kkRYsrZ
WwsKlOFE2LUce7ObnXsYihStBUDoeBQlGG/BwQIDAQABAoIBAFtGaOqNKGwggn9k
6yzr6GhZ6Wt2rh1Xpq8XUz514UBhPxD7dFRLpbzCrLVpzY80LbmVGJ9+1pJozyWc
VKeCeUdNwbqkr240Oe7GTFmGjDoxU+5/HX/SJYPpC8JZ9oqgEA87iz+WQX9hVoP2
oF6EB4ckDvXmk8FMwVZW2l2/kd5mrEVbDaXKxhvUDf52iVD+sGIlTif7mBgR99/b
c3qiCnxCMmfYUnT2eh7Vv2LhCR/G9S6C3R4lA71rEyiU3KgsGfg0d82/XWXbegJW
h3QbWNtQLxTuIvLq5aAryV3PfaHlPgdgK0ft6ocU2de2FagFka3nfVEyC7IUsNTK
bq6nhAECgYEA7d/0DPOIaItl/8BWKyCuAHMss47j0wlGbBSHdJIiS55akMvnAG0M
39y22Qqfzh1at9kBFeYeFIIU82ZLF3xOcE3z6pJZ4Dyvx4BYdXH77odo9uVK9s1l
3T3BlMcqd1hvZLMS7dviyH79jZo4CXSHiKzc7pQ2YfK5eKxKqONeXuECgYEAyXlG
vonaus/YTb1IBei9HwaccnQ/1HRn6MvfDjb7JJDIBhNClGPt6xRlzBbSZ73c2QEC
6Fu9h36K/HZ2qcLd2bXiNyhIV7b6tVKk+0Psoj0dL9EbhsD1OsmE1nTPyAc9XZbb
OPYxy+dpBCUA8/1U9+uiFoCa7mIbWcSQ+39gHuECgYAz82pQfct30aH4JiBrkNqP
nJfRq05UY70uk5k1u0ikLTRoVS/hJu/d4E1Kv4hBMqYCavFSwAwnvHUo51lVCr/y
xQOVYlsgnwBg2MX4+GjmIkqpSVCC8D7j/73MaWb746OIYZervQ8dbKahi2HbpsiG
8AHcVSA/agxZr38qvWV54QKBgCD5TlDE8x18AuTGQ9FjxAAd7uD0kbXNz2vUYg9L
hFL5tyL3aAAtUrUUw4xhd9IuysRhW/53dU+FsG2dXdJu6CxHjlyEpUJl2iZu/j15
YnMzGWHIEX8+eWRDsw/+Ujtko/B7TinGcWPz3cYl4EAOiCeDUyXnqnO1btCEUU44
DJ1BAoGBAJuPD27ErTSVtId90+M4zFPNibFP50KprVdc8CR37BE7r8vuGgNYXmnI
RLnGP9p3pVgFCktORuYS2J/6t84I3+A17nEoB4xvhTLeAinAW/uTQOUmNicOP4Ek
2MsLL2kHgL8bLTmvXV4FX+PXphrDKg1XxzOYn0otuoqdAQrkK4og
-----END RSA PRIVATE KEY-----
EOD;


$publicKey = <<<EOD
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAuzWHNM5f+amCjQztc5QT
fJfzCC5J4nuW+L/aOxZ4f8J3FrewM2c/dufrnmedsApb0By7WhaHlcqCh/ScAPyJ
hzkPYLae7bTVro3hok0zDITR8F6SJGL42JAEUk+ILkPI+DONM0+3vzk6Kvfe548t
u4czCuqU8BGVOlnp6IqBHhAswNMM78pos/2z0CjPM4tbeXqSTTbNkXRboxjU29vS
opcT51koWOgiTf3C7nJUoMWZHZI5HqnIhPAG9yv8HAgNk6CMk2CadVHDo4IxjxTz
TTqo1SCSH2pooJl9O8at6kkRYsrZWwsKlOFE2LUce7ObnXsYihStBUDoeBQlGG/B
wQIDAQAB
-----END PUBLIC KEY-----
EOD;
$this->jwtHelper->getconfig()->setPublicKey($publicKey);


		return JWT::encode($payload, $privateKey, 'RS256');
	}

	/**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
	public function test() {

		$token = $this->request->getParam("token"); 
		return $this->jwtHelper->validateToken($token); 
    }

	/**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
	public function main() {
		$nodes = $this->rootFolder->get('einstein')->get('files')->getDirectoryListing();
		$names = [];
		foreach ($nodes as $node) {
			array_push($names, $node->getName());
		}

		error_log(var_export($names, true));
		return new TemplateResponse('tokenbaseddav', 'main', [
			"names" => $names,
		]);
    }

	/**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
	public function pick($request) {
		error_log("User picked a resource!");
		// see https://github.com/SURFnet/surf-token-based-access/issues/61
		$url = "https://sram-auth-poc.pondersource.net/api/rreg";
        $data = [
			"name" => implode(" ", array_keys($_POST))
		];
		// use key 'http' even if you send the request to https://...
		$options = [
			'http' => [
				'header' => "Content-type: application/x-www-form-urlencoded\r\n",
				'method' => 'POST',
				'content' => http_build_query($data),
			],
		];
		$context = stream_context_create($options);
		$result = file_get_contents($url, false, $context);
		if ($result === false) {
			/* Handle error */
		}
		error_log("scope id: " . var_export($result, true));

		return new RedirectResponse('https://sram-auth-poc.pondersource.net/api/front?wayg=research-drive-poc&resource=' . urlencode($result));
    }
}
