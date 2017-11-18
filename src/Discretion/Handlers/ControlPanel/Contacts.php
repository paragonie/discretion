<?php
declare(strict_types=1);
namespace ParagonIE\Discretion\Handlers\ControlPanel;

use ParagonIE\Discretion\Discretion;
use ParagonIE\Discretion\HandlerInterface;
use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface
};

/**
 * Class Index
 * @package ParagonIE\Discretion\Handlers\ControlPanel
 */
class Contacts implements HandlerInterface
{
    /**
     * Contacts constructor.
     */
    public function __construct()
    {
        Discretion::setTwigVar('active_link', 'contacts');
    }

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
        return Discretion::view('control-panel/contacts.twig');
    }
}
