<?php
declare(strict_types=1);
namespace ParagonIE\Discretion\Middleware;

use ParagonIE\AntiCSRF\AntiCSRF;
use ParagonIE\Discretion\Discretion;
use ParagonIE\Discretion\Exception\SecurityException;
use ParagonIE\Discretion\MiddlewareInterface;
use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Slim\Http\Request;

/**
 * Class HTTPPost
 * @package ParagonIE\Discretion\Middleware
 */
class HTTPPost implements MiddlewareInterface
{
    const PROPERTIES_TO_SET = ['csrf_mitigated'];

    /**
     * @return void
     * @throws SecurityException
     */
    protected function assertCSRFPassed()
    {
        $antiCSRF = new AntiCSRF();

        if (!$antiCSRF->validateRequest()) {
            throw new SecurityException('This request has triggered our Anti-CSRF Protection');
        }
    }

    /**
     * Ensure all requests are authenticated.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        callable $next
    ): ResponseInterface {
        if ($request instanceof Request) {
            if ($request->isPost()) {
                try {
                    $this->assertCSRFPassed();
                    foreach (static::PROPERTIES_TO_SET as $prop) {
                        $request = $request->withAttribute($prop, true);
                    }
                } catch (SecurityException $ex) {
                    return Discretion::errorResponse(
                        $ex->getMessage(),
                        403
                    );
                } catch (\Throwable $ex) {
                    return Discretion::errorResponse('An unknown error has occurred.');
                }
            }
        }
        return $next($request, $response);
    }
}
