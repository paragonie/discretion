<?php
declare(strict_types=1);
namespace ParagonIE\Discretion\Handlers;


use Kelunik\TwoFactor\Oath;
use ParagonIE\Discretion\Data\HiddenString;
use ParagonIE\Discretion\Discretion;
use ParagonIE\Discretion\Exception\SecurityException;
use ParagonIE\Discretion\HandlerInterface;
use ParagonIE\Discretion\SimpleCrypto;
use ParagonIE\EasyDB\EasyDB;
use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Slim\Http\Request;
use Slim\Http\Response;

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
        return Discretion::view(
            'login.twig'
        );
    }
}
