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
use ZxcvbnPhp\Zxcvbn;

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
     * @throws \Error
     * @throws \Exception
     * @throws \ParagonIE\Discretion\Exception\DatabaseException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
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
     * @throws \ParagonIE\Discretion\Exception\DatabaseException
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

        // Validate the ReCAPTCHA response:
        $settings = Discretion::getSettings();
        if (!empty($settings['recaptcha']['secret-key'])) {
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

        // If the username is already taken, do not allow it to be registered.
        if (User::usernameIsTaken($post['username'])) {
            throw new SecurityException('Username is already taken.');
        }

        // Both passphrases need to match.
        if (!\hash_equals($post['passphrase'], $post['passphrase2'])) {
            throw new SecurityException('Passphrases do not match.');
        }

        // Ensure the password is adequately strong.
        $zxcvbn = new Zxcvbn();
        $strength = $zxcvbn->passwordStrength($post['passphrase'],
            [
                $post['username'],
                $post['fullName'] ?? '',
                $post['email']
            ]
        );
        // Fail closed to a reasonably high value:
        if (!isset($settings['zxcvbn-min-score'])) {
            $settings['zxcvbn-min-score'] = 3;
        }
        if ($strength['score'] < $settings['zxcvbn-min-score']) {
            throw new SecurityException('Passphrase strength is inadequate.');
        }

        // Ensure that the two 2FA responses are NOT identical.
        if (\hash_equals($post['twoFactor1'], $post['twoFactor2'])) {
            // The two cannot be the same:
            throw new SecurityException('Incorrect two-factor authentication code.');
        }

        // Verify two sequential 2FA codes generated from our 2FA secret:
        $oath = new Oath();
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

        Discretion::securityLog(
            'User account created',
            [
                'username' => $post['username']
            ]
        );

        // Success: Regenerate session, set User ID.
        unset($_SESSION['registration']);
        \session_regenerate_id(true);
        $_SESSION['userid'] = $user->id();

        return Discretion::redirect('/manage');
    }
}
