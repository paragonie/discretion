<?php
declare(strict_types=1);
namespace ParagonIE\Discretion\Handlers;

use Kelunik\TwoFactor\Oath;
use ParagonIE\Discretion\Data\HiddenString;
use ParagonIE\Discretion\Discretion;
use ParagonIE\Discretion\Exception\SecurityException;
use ParagonIE\Discretion\HandlerInterface;
use ParagonIE\Discretion\Struct\User;
use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use ReCaptcha\ReCaptcha;
use Slim\Http\{
    Request,
    Response
};

/**
 * Class Index
 * @package ParagonIE\Discretion\Handlers
 */
class Register implements HandlerInterface
{
    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        array $args = []
    ): ResponseInterface {
        if ($request instanceof Request) {
            if ($request->getAttribute('authenticated')) {
                return Discretion::redirect('/manage');
            }
            if ($request->isPost()) {
                try {
                    return $this->processRegistrationRequest($request);
                } catch (SecurityException $ex) {
                    Discretion::setTwigVar('error', $ex->getMessage());
                    // No. Fall through.
                }
            }
        }
        if (!isset($_SESSION['registration'])) {
            $_SESSION['registration'] = $this->initRegistration();
        }

        // We're allowing CDNJS and Google for this page only:
        Discretion::getCSPBuilder()
            ->setSelfAllowed('style-src', true)
            ->setAllowUnsafeInline('style-src', true)
            ->addSource('connect-src', 'https://www.google.com')
            ->addSource('child-src', 'https://www.google.com')
            ->addSource('script-src', 'https://cdnjs.cloudflare.com')
            ->addSource('script-src', 'https://www.google.com')
            ->addSource('script-src', 'https://www.gstatic.com');

        return Discretion::view(
            'register.twig',
            [
                'registration' => $_SESSION['registration'],
                'qrcode' => (new Oath())->getUri(
                    (string) $_SESSION['registration']['twoFactorSecret'],
                    (string) $_SERVER['HTTP_HOST'],
                    'R_E_P_L_A_C_E_M_E'
                )
            ]
        );
    }

    /**
     * @return array
     */
    protected function initRegistration(): array
    {
        return [
            'twoFactorSecret' => (new Oath)->generateKey(32)
        ];
    }

    /**
     * @param Request $request
     * @return Response
     * @throws SecurityException
     */
    protected function processRegistrationRequest(Request $request): Response
    {
        if (!$request->getAttribute('csrf_mitigated')) {
            throw new SecurityException('CSRF Mitigation not applied.');
        }

        /** @var array<mised, string> $post */
        $post = $request->getParsedBody();

        // Required fields
        if (!isset(
            $post['username'],
            $post['email'],
            $post['passphrase'],
            $post['passphrase2'],
            $post['twoFactor1'],
            $post['twoFactor2']
        )) {
            throw new SecurityException('Incomplete registration');
        }

        // Type checks
        if (
            !\is_string($post['username'])
            || !\is_string($post['email'])
            || !\is_string($post['passphrase'])
            || !\is_string($post['passphrase2'])
            || !\is_string($post['twoFactor1'])
            || !\is_string($post['twoFactor2'])
        ) {
            throw new SecurityException('Invalid types');
        }
        $settings = Discretion::getSettings();
        if (isset($settings['recaptcha']['secret-key'])) {
            if (empty($post['g-recaptcha-response'])) {
                throw new SecurityException('Please complete the CAPTCHA.');
            }
            $recaptcha = new ReCaptcha($settings['recaptcha']['secret-key']);
            if (!$recaptcha->verify($post['g-recaptcha-response'], $_SERVER['REMOTE_ADDR'])) {
                throw new SecurityException('Incorrect CAPTCHA response.');
            }
        }

        // Ensure this is a valid email address.
        $email = \filter_var($post['email'], FILTER_VALIDATE_EMAIL);
        if (!\is_string($email)) {
            throw new SecurityException('Invalid email address.');
        }

        $db = Discretion::getDatabase();
        /** @psalm-suppress InvalidArgument Trust me on this one, Psalm. */
        if ($db->exists('SELECT count(*) FROM discretion_users WHERE username = ?', $post['username'])) {
            throw new SecurityException('Username is already taken');
        }
        if (!\hash_equals($post['passphrase'], $post['passphrase2'])) {
            throw new SecurityException('Passphrases do not match');
        }
        $oath = new Oath();
        // Verify two sequential 2FA codes generated from our 2FA secret:
        if (\hash_equals($post['twoFactor1'], $post['twoFactor2'])) {
            throw new SecurityException('Incorrect two-factor authentication code.');
        }
        if (!$oath->verifyTotp($_SESSION['registration']['twoFactorSecret'], $post['twoFactor1'], 2)) {
            throw new SecurityException('Incorrect two-factor authentication code.');
        }
        if (!$oath->verifyTotp($_SESSION['registration']['twoFactorSecret'], $post['twoFactor2'], 3)) {
            throw new SecurityException('Incorrect two-factor authentication code.');
        }

        // Story checks out. Let's create the user account.
        $user = (new User())
            ->setUsername($post['username'])
            ->setEmail($post['email'])
            ->setFullName($post['fullName'] ?? '')
            ->setPassword(new HiddenString($post['passphrase']))
            ->set2FASecret(new HiddenString($_SESSION['registration']['twoFactorSecret']));

        if (!$user->create()) {
            throw new SecurityException('An unknown database error occurred.');
        }

        // Success: Regenerate session, set User ID.
        unset($_SESSION['registration']);
        \session_regenerate_id(true);
        $_SESSION['userid'] = $user->id();

        return Discretion::redirect('/manage');
    }
}
