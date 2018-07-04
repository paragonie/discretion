<?php
declare(strict_types=1);
namespace ParagonIE\Discretion\Middleware;

use ParagonIE\Discretion\Discretion;
use ParagonIE\Discretion\Exception\{
    NotLoggedInException,
    SecurityException
};
use ParagonIE\Discretion\MiddlewareInterface;
use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class UserAuthentication
 * @package ParagonIE\Discretion\Middleware
 */
class UserAuthentication implements MiddlewareInterface
{
    const PROPERTIES_TO_SET = ['authenticated'];

    /**
     * @return void
     * @throws NotLoggedInException
     */
    protected function assertLoggedIn()
    {
        if (empty($_SESSION['user_id'])) {
            throw new NotLoggedInException('You are not logged in.');
        }
    }

    /**
     * Ensure all requests are authenticated.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     * @throws \Error
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        callable $next
    ): ResponseInterface {
        try {
            if ($request instanceof Request) {
                $this->assertLoggedIn();
                /** @var string $prop */
                foreach (static::PROPERTIES_TO_SET as $prop) {
                    if (!\is_string($prop)) {
                        continue;
                    }
                    $request = $request->withAttribute($prop, true);
                }
            }
        } catch (SecurityException $ex) {
            return Discretion::redirect('/login');
        } catch (\Throwable $ex) {
            return Discretion::errorResponse('An unknown error has occurred.');
        }
        /** @var ResponseInterface $response */
        $response = $next($request, $response);
        if (!($response instanceof ResponseInterface)) {
            throw new \TypeError('Response not an instance of ResponseInterface');
        }
        return $response;
    }
}
