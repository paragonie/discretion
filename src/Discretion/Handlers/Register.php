<?php
declare(strict_types=1);
namespace ParagonIE\Discretion\Handlers;

use Kelunik\TwoFactor\Oath;
use ParagonIE\Discretion\Data\HiddenString;
use ParagonIE\Discretion\Discretion;
use ParagonIE\Discretion\Exception\SecurityException;
use ParagonIE\Discretion\HandlerInterface;
use ParagonIE\Discretion\SimpleCrypto;
use ParagonIE\Discretion\Struct\User;
use ParagonIE\EasyDB\EasyDB;
use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Slim\Http\Request;
use Slim\Http\Response;

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
        $error = '';
        if ($request instanceof Request) {
            if ($request->getAttribute('authenticated')) {
                return Discretion::errorResponse(
                    'You are already logged in.',
                    301,
                    ['Location' => '/manage']
                );
            }
            if ($request->isPost()) {
                try {
                    return $this->processRegistrationRequest($request);
                } catch (SecurityException $ex) {
                    $error = $ex->getMessage();
                    // No. Fall through.
                }
            }
        }
        if (!isset($_SESSION['registration'])) {
            $_SESSION['registration'] = $this->initRegistration();
        }
        // We're allowing CDNJS for this page only:
        Discretion::getCSPBuilder()->addSource('script-src', 'https://cdnjs.cloudflare.com');

        return Discretion::view(
            'register.twig',
            [
                'error' => $error,
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
        if (!$oath->verifyTotp($_SESSION['registration']['twoFactorSecret'], $post['twoFactor1'], 2, time())) {
            throw new SecurityException('Incorrect two-factor authentication code.');
        }
        if (!$oath->verifyTotp($_SESSION['registration']['twoFactorSecret'], $post['twoFactor2'], 2, time() - 30)) {
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

        return Discretion::createNormalResponse(
            'Account succesfully created. Redirecting to the control panel.',
            ['Location' => '/manage'],
            301
        );
    }
}
