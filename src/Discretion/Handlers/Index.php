<?php
declare(strict_types=1);
namespace ParagonIE\Discretion\Handlers;


use ParagonIE\Discretion\Discretion;
use ParagonIE\Discretion\HandlerInterface;
use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface
};

/**
 * Class Index
 * @package ParagonIE\Discretion\Handlers
 */
class Index implements HandlerInterface
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
        return Discretion::createNormalResponse(
            Discretion::getTwig()->render('index.twig'),
            [
                'Content-Type' => 'text/html; charset=UTF-8'
            ]
        );
    }
}
