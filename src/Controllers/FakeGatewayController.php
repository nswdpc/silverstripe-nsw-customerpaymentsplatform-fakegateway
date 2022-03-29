<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use Buzz\Browser;
use Buzz\Client\Curl;
use Firebase\JWT\JWT;
use Nyholm\Psr7\Factory\Psr17Factory;
use SilverStripe\Control\Director;
use Silverstripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use SilverStripe\View\ArrayData;

/**
 * This fake gateway controller mimics the CPP for local testing
 * @author James
 */
class FakeGatewayController extends Controller
{

    /**
     * Not enabled by default
     * @var bool
     */
    private static $enabled = false;

    /**
     * Set a payment completion URL
     * @var bool
     */
    private static $paymentCompletionUrl = '';

    /**
     * A private key for encoding the payload to create a JWT token
     * @var string
     */
    private static $jwtPrivateKey = '';

    /**
     * Access token returned for authorisation
     * @var string
     */
    private $accessToken = 'fake-access-token';

    /**
     * Not a duplicate
     * @var bool
     */
    private $paymentDuplicate = false;

    /**
     * Actions availble on controller
     * @var array
     */
    private static $allowed_actions = [
        'FakePaymentForm' => true,
        'accesstoken' => true,
        'requestpayment' => true,
        'gateway' => true,
        'refund' => true,
        'status' => true,
        'reconciliation' => true
    ];

    /**
     * Base path for this gateway
     * @var string
     */
    private static $url_segment = "/fakecpp/v1";

    /**
     * Check if this  fake gateway enabled, return 410 or bool true
     * @return mixed
     */
    private function checkEnabled()
    {
        if (!$this->config()->get('enabled')) {
            $response = HTTPResponse::create();
            $response->addHeader('Content-Type', 'application/json');
            $response->setStatusCode(410);
            $response->setBody(json_encode([
                'error' => '410 Gone',
            ]));
            return $response;
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // check for enablement
        $response = $this->checkEnabled();
        if ($response instanceof HTTPResponse) {
            return $response;
        }
    }

    /**
     * Based controller request, nothing to do here, return a 410
     */
    public function index(HTTPRequest $request) : HTTPResponse
    {
        $response = HTTPResponse::create();
        $response->addHeader('Content-Type', 'application/json');
        $response->setStatusCode(404);
        $response->setBody(json_encode([
            'error' => '404 Not found',
        ]));
        return $response;
    }

    /**
     * Get an access token, return HTTPResponse with token (or not)
     */
    public function accesstoken(HTTPRequest $request) : HTTPResponse
    {
        $body = $request->getBody();

        Logger::log("accesstoken being requested");
        Logger::log("accesstoken {$body}");

        $response = HTTPResponse::create();
        $response->addHeader('Content-Type', 'application/json');

        if (!$request->isPOST()) {
            $response->setStatusCode(405);
            $response->setBody(json_encode([
                'error' => '405 Method Not Allowed',
            ]));
            return $response;
        }

        $response->setStatusCode(200);
        $response->setBody(json_encode([
            'access_token' => $this->accessToken,
            'expires' => time() + 3600,
            'token_type' => 'Bearer'
        ]));

        Logger::log("accesstoken returning body");

        return $response;
    }

    /**
     * Given an incoming POST request, request a payment and get a payment reference
     * @TODO authenticate via Bearer header
     */
    public function requestpayment(HTTPRequest $request) : HTTPResponse
    {
        $response = HTTPResponse::create();
        $response->addHeader('Content-Type', 'application/json');

        if (!$request->isPOST()) {
            $response->setStatusCode(405);
            $response->setBody(json_encode([
                'error' => '405 Method Not Allowed',
            ]));
            return $response;
        }

        $payload = $request->getBody();
        $decoded = json_decode($payload, true, JSON_THROW_ON_ERROR);

        $agencyTransactionId = $decoded['agencyTransactionId'] ?? '';

        $response = HTTPResponse::create();
        $response->setStatusCode(200);
        $response->setBody(json_encode([
            'paymentReference' => "REF:{$agencyTransactionId}",
            'duplicate' => $this->paymentDuplicate
        ]));
        return $response;
    }

    /**
     * Given a CPP Payment record, return fake payload
     */
    private function getPaymentPayload(Payment $payment, $issuer = "NSWDPC-FakeGateway-200") : array
    {
        $payload = [
            "exp"=> time() + 3600,
            "sub"=> "fake-subject-claim-id",
            "iss" => $issuer,// this can trigger various responses when decoded
            "paymentMethod"=> "CARD",
            "paymentReference"=> $payment->PaymentReference,
            "paymentCompletionReference"=> "cref-" . microtime(true),
            "bankReference"=> "fake-bankref",
            "amount"=> 1000.00,
            "surcharge"=> 12.00,
            "surchargeGst"=> 1.20,
            "agencyTransactionId"=> $payment->AgencyTransactionId, // return the payment
            "card" => [
                "cardType"=> "VISA",
                "last4Digits"=> "1234"
            ]
        ];
        return $payload;
    }

    /**
     * Create a JWT token from the payload, configured private key and algo
     */
    private function createJwt(array $payload) : string
    {
        $token = JWT::encode($payload, $this->config()->get('jwtPrivateKey'), 'RS256');
        return $token;
    }

    /**
     * A fake payment form, where the payment reference is the only arg
     * @return Form
     */
    public function FakePaymentForm() : Form
    {
        $request = $this->getRequest();
        if ($request->isPOST()) {
            $paymentReference = $request->postVar('ref');
        } else {
            $paymentReference = $request->getVar('paymentReference');
        }
        if (!$paymentReference) {
            throw new \Exception("Payment reference not found or provided");
        }
        $form = Form::create(
            $this,
            'FakePaymentForm',
            Fieldlist::create(
                TextField::create(
                    'ref',
                    _t(
                        __CLASS__ . '.PAYMENT_REFERENCE',
                        'Payment Reference'
                    ),
                    $paymentReference
                ),
                LiteralField::create(
                    'PaymentMessage',
                    "<div class=\"message notice\">"
                    . _t(
                        __CLASS__ . '.PAYMENT_MESSAGE',
                        'Use one of the buttons to trigger a payment completion response'
                    ),
                    "</div>"
                )
            ),
            Fieldlist::create(
                FormAction::create(
                    'doPay',
                    _t(
                        __CLASS__ . ".DO_PAY",
                        "Make a fake payment for reference '{paymentReference}' (200)",
                        [
                            'paymentReference' => $paymentReference
                        ]
                    )
                ),
                FormAction::create(
                    'doImmediateFail',
                    _t(
                        __CLASS__ . ".DO_IMM_FAIL",
                        "Trigger an immediate fail for reference '{paymentReference}' (422)",
                        [
                            'paymentReference' => $paymentReference
                        ]
                    )
                ),
                FormAction::create(
                    'doRetryFail',
                    _t(
                        __CLASS__ . ".DO_IMM_FAIL",
                        "Trigger a fail for reference '{paymentReference}' (50x)",
                        [
                            'paymentReference' => $paymentReference
                        ]
                    )
                )
            )
        );
        return $form;
    }

    /**
     * Allow testing of the 50x response
     */
    public function doRetryFail($data, $form) : HTTPResponse
    {
        try {
            Logger::log('doRetryFail() starts');
            $paymentReference = $data['ref'] ?? '';
            if (!$paymentReference) {
                throw new \Exception("Invalid input, missing 'ref'");
            }
            // retrieve the correct agencyTransactionId as that's what is filtered on in completion
            $payment = Payment::getByPaymentReference($paymentReference);
            // JWT
            $issuer = "NSWDPC-FakeGateway-50x";
            $payload = $this->getPaymentPayload($payment, $issuer);

            $url = $this->config()->get('paymentCompletionUrl');
            $body = [
                'token' => $this->createJwt($payload)
            ];
            // POST the token back to the PaymentGatewayController
            $client = new Curl(new Psr17Factory());
            $browser = new Browser($client, new Psr17Factory());
            $completionResponse = $browser->post(
                $url,
                [
                    'User-Agent'=> Config::inst()->get(Payment::class, 'user_agent')
                ],
                json_encode($body)
            );
            // PaymentGatewayController repsonse
            $status = $completionResponse->getStatusCode();
            $body = $completionResponse->getBody();
            $contentType = implode(",", $completionResponse->getHeader('content-type'));
            $form->sessionMessage("complete() returned a {$status} code {$contentType} and body={$body}");
        } catch (\Exception $e) {
            Logger::log("Error at doRetryFail (ref={$paymentReference}) - " . $e->getMessage());
            $form->sessionMessage("doRetryFail failed: " . $e->getMessage());
        }
        return $this->redirectBack();
    }

    /**
     * Allow testing of the 422 response
     */
    public function doImmediateFail($data, $form) : HTTPResponse
    {
        try {
            Logger::log('doImmediateFail() starts');
            $paymentReference = $data['ref'] ?? '';
            if (!$paymentReference) {
                throw new \Exception("Invalid input, missing 'ref'");
            }
            // retrieve the correct agencyTransactionId as that's what is filtered on in completion
            $payment = Payment::getByPaymentReference($paymentReference);
            // JWT
            $issuer = "NSWDPC-FakeGateway-422";
            $payload = $this->getPaymentPayload($payment, $issuer);

            $url = $this->config()->get('paymentCompletionUrl');
            $body = [
                'token' => $this->createJwt($payload)
            ];
            // POST the token back to the PaymentGatewayController
            $client = new Curl(new Psr17Factory());
            $browser = new Browser($client, new Psr17Factory());
            $completionResponse = $browser->post(
                $url,
                [
                    'User-Agent'=> Config::inst()->get(Payment::class, 'user_agent')
                ],
                json_encode($body)
            );
            // PaymentGatewayController repsonse
            $status = $completionResponse->getStatusCode();
            $body = $completionResponse->getBody();
            $contentType = implode(",", $completionResponse->getHeader('content-type'));
            $form->sessionMessage("complete() returned a {$status} code {$contentType} and body={$body}");
        } catch (\Exception $e) {
            Logger::log("Error at doImmediateFail (ref={$paymentReference}) - " . $e->getMessage());
            $form->sessionMessage("doImmediateFail failed: " . $e->getMessage());
        }
        return $this->redirectBack();
    }

    /**
     * Handle a "payment", which is just a form post
     */
    public function doPay($data, $form) : HTTPResponse
    {
        try {
            Logger::log('doPay() starts');
            $paymentReference = $data['ref'] ?? '';
            if (!$paymentReference) {
                throw new \Exception("Invalid input, missing 'ref'");
            }
            // retrieve the correct agencyTransactionId as that's what is filtered on in completion
            $payment = Payment::getByPaymentReference($paymentReference);
            // JWT
            $issuer = "NSWDPC-FakeGateway-200";
            $payload = $this->getPaymentPayload($payment);
            $url = $this->config()->get('paymentCompletionUrl');
            $body = [
                'token' => $this->createJwt($payload)
            ];
            // POST the token back to the PaymentGatewayController
            $client = new Curl(new Psr17Factory());
            $browser = new Browser($client, new Psr17Factory());
            $completionResponse = $browser->post(
                $url,
                [
                    'User-Agent'=> Config::inst()->get(Payment::class, 'user_agent')
                ],
                json_encode($body)
            );
            // PaymentGatewayController repsonse
            $status = $completionResponse->getStatusCode();
            $body = $completionResponse->getBody();
            $contentType = implode(",", $completionResponse->getHeader('content-type'));
            $form->sessionMessage("OK .. complete() returned a {$status} code {$contentType} and body={$body}");
        } catch (\Exception $e) {
            Logger::log("Error at doPay (ref={$paymentReference}) - " . $e->getMessage());
            $form->sessionMessage("doPay failed: " . $e->getMessage());
        }

        if ($paymentReference) {
            return $this->redirect(
                $this->Link('gateway/?paymentReference=' . $paymentReference)
            );
        } else {
            return $this->redirectBack();
        }
    }

    /**
     * Return gateway page with a fake payment form
     * @return mixed
     */
    public function gateway(HTTPRequest $request)
    {
        try {
            $form = $this->FakePaymentForm();
            $data = ArrayData::create([
                'Form' => $form,
                'Title' => _t(__CLASS__ . '.PAY', 'Pay')
            ]);
            return $this->customise($data)->renderWith('FakeGatewayController_gateway');
        } catch (\Exception $e) {
        }
        return $this->httpError(400);
    }

    /**
     * Return the refund reference
     */
    public function refund(HTTPRequest $request) : HTTPResponse
    {
        $response = HTTPResponse::create();
        $response->addHeader('Content-Type', 'application/json');

        if (!$request->isPOST()) {
            $response->setStatusCode(405);
            $response->setBody(json_encode([
                'error' => '405 Method Not Allowed',
            ]));
            return $response;
        }
        $response->setStatusCode(200);
        $response->setBody(json_encode([
            // provide a unique-ish reference for fake purposes
            'refundReference' => "refund-" . microtime(true),
        ]));
        return $response;
    }

    /**
     * Return the payment status. On a fake gateway, the payment status returned is COMPLETED
     * The fake gateway accepts status requests to $this->Link(status/$paymentreference)
     */
    public function status(HTTPRequest $request) : HTTPResponse
    {
        $response = HTTPResponse::create();
        $response->addHeader('Content-Type', 'application/json');

        if (!$request->isGET()) {
            $response->setStatusCode(405);
            $response->setBody(json_encode([
                'error' => '405 Method Not Allowed',
            ]));
            return $response;
        }
        $paymentReference = $request->param('ID');
        if (!$paymentReference) {
            $response->setStatusCode(400);
            $response->setBody(json_encode([
                'error' => '401 Bad Request',
                'message' => "Missing paymentReference in the URL (/status/\$paymentReference)"
            ]));
            return $response;
        }
        $response->setStatusCode(200);
        $response->setBody(json_encode([
            "paymentReference" => $paymentReference,
            "paymentStatus" => Payment::CPP_PAYMENTSTATUS_COMPLETED,
            "referenceNumber" => "referenceNumber-" . microtime(true)
        ]));
        return $response;
    }

    /**
     * Reconciliation report
     */
    public function reconciliation(HTTPRequest $request) : HTTPResponse {

        Logger::log( "reconciliation request" );

        $response = HTTPResponse::create();
        if (!$request->isGET()) {
            $response->setStatusCode(405);
            $response->setBody(json_encode([
                'error' => '405 Method Not Allowed',
            ]));
            return $response;
        }
        $date = $request->getVar('reportDate');
        if (!$date) {
            Logger::log( "No report date provided: " . $_SERVER['REQUEST_URI'] );
            $response->setStatusCode(400);
            $response->addHeader('Content-Type', 'text/plain');
            $response->setBody("No report date provided");
            return $response;
        }
        Logger::log( "reconciliation report date is {$date}" );
        $response->setStatusCode(200);
        $response->addHeader('Content-Type', 'text/csv');
        $csv = <<<CSV
Requested Date,Payment Reference,Agency Transaction ID,Product Description,Agency Code,Parent Agency Code,Requested Amount,Payment Type,Payment Completed Date,Amount,Surcharge,Discount Applied,Completion Reference,Agency Settlement Date,GLIP ID,Parent Payment Reference,Customer Reference,Refund Amount,Refund Reason,Refund Reference,Refund Timestamp
2020-09-16 14:08:28,GPP-DIG-G20DN5S-QdyFY1FTj55Qpg,e7d43f98-08a7-4879-9431-5818db7bc9d3,,SNSW_INTERNAL,,370.66,CARD,2020-09-16 14:11:43,370.66,1.63,0,7D3TRVWFAC,,700625,DL123456,,,,
2020-09-16 14:10:13,GPP-DIG-sFWh32Y4SPuOXMSvQAd9MA,e29a031a-0e82-4107-ab79-ca3bbc3bc452,,SNSW_INTERNAL,,1058.73,CARD,2020-09-16 14:12:59,1058.73,4.65,0,GT7QSR7NYE,,700625,,DL123456,,,,
CSV;
        $response->setBody(trim($csv));
        return $response;
    }
}
