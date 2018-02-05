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
     * @throws \Error
     */
    protected function assertCSRFPassed()
    {
        $antiCSRF = new AntiCSRF();

        if (!$antiCSRF->validateRequest()) {
            throw new SecurityException('This request has triggered our Anti-CSRF Protection');
        }
    }

    /**
     * Ensure all requests are immune to CSRF.
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
        if ($request instanceof Request) {
            if ($request->isPost()) {
                try {
                    $this->assertCSRFPassed();
                    /** @var string $prop */
                    foreach (static::PROPERTIES_TO_SET as $prop) {
                        if (!\is_string($prop)) {
                            continue;
                        }
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
        /** @var ResponseInterface $response */
        $response = $next($request, $response);
        if (!($request instanceof ResponseInterface)) {
            throw new \TypeError('Response not an instance of ResponseInterface');
        }
        return $response;
    }
}
