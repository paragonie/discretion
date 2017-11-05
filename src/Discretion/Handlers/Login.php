<?php
declare(strict_types=1);
namespace ParagonIE\Discretion\Handlers;

use Kelunik\TwoFactor\Oath;
use ParagonIE\Discretion\Data\HiddenString;
use ParagonIE\Discretion\Discretion;
use ParagonIE\Discretion\Exception\{
    DatabaseException,
    RecordNotFound,
    SecurityException
};
use ParagonIE\Discretion\HandlerInterface;
use ParagonIE\Discretion\Struct\User;
use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Slim\Http\{
    Request,
    Response
};

/**
 * Class Login
 * @package ParagonIE\Discretion\Handlers
 */
class Login implements HandlerInterface
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
                    return $this->attemptLogin($request);
                } catch (\Throwable $ex) {
                    Discretion::setTwigVar('error', $ex->getMessage());
                }
            }
        }
        return Discretion::view('login.twig');
    }

    /**
     * Attempt to authenticate as this user.
     *
     * @param Request $request
     * @return Response
     * @throws SecurityException
     */
    protected function attemptLogin(Request $request): Response
    {
        if (!$request->getAttribute('csrf_mitigated')) {
            throw new SecurityException('CSRF Mitigation not applied.');
        }

        /** @var array<mised, string> $post */
        $post = $request->getParsedBody();

        // Required fields
        if (!isset(
            $post['username'],
            $post['passphrase'],
            $post['twoFactor']
        )) {
            throw new SecurityException('Incomplete login attempt.');
        }

        // Type checks
        if (
            !\is_string($post['username'])
            || !\is_string($post['passphrase'])
            || !\is_string($post['twoFactor'])
        ) {
            throw new SecurityException('Invalid HTTP message.');
        }

        $authStatus = true; // Set to false if any failures occur

        // To prevent trivial timing attacks:
        $dummyUser = (new User())
            ->setPassword(new HiddenString(random_bytes(32)))
            ->set2FASecret(new HiddenString(random_bytes(32)));
        try {
            $user = User::byUsername($post['username']);
        } catch (RecordNotFound $ex) {
            $authStatus = false;
            $user = $dummyUser;
        }

        // Validate the user's password (may be the dummy user):
        $authStatus = $authStatus && $user->checkPassword(
            new HiddenString($post['passphrase'])
        );

        // Two-factor authentication check:
        $oath = new Oath();
        $authStatus = $authStatus && $oath->verifyTotp(
            $user->get2FASecret()->getString(),
            $post['twoFactor']
        );

        if (!$authStatus) {
            // We used near-constant-time operations. Good luck figuring out which it was!
            throw new SecurityException(
                'Invalid username, password, or two-factor authentication challenge code.'
            );
        }
        try {
            \session_regenerate_id(true);
            $_SESSION['user_id'] = $user->id();
        } catch (DatabaseException $ex) {
            throw new SecurityException(
                'Against all odds, you managed to guess the dummy user\'s password and 2FA secret.'
            );
        }
        return Discretion::redirect('/manage');
    }
}
