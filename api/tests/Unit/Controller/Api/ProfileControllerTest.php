<?php

namespace App\Tests\Unit\Controller\Api;

use App\Controller\Api\ProfileController;
use App\Entity\Address;
use App\Entity\User;
use App\Tests\Unit\Controller\ControllerTestContainer;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

final class ProfileControllerTest extends TestCase
{
    public function testShowReturnsUnauthenticated(): void
    {
        $response = $this->controller(null)->show();

        self::assertSame(401, $response->getStatusCode());
    }

    public function testShowReturnsCurrentProfile(): void
    {
        $user = (new User())
            ->setEmail('user@example.com')
            ->setFirstname('Jane')
            ->setLastname('Doe');

        $response = $this->controller($user)->show();
        $payload = json_decode($response->getContent(), true);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('user@example.com', $payload['email']);
        self::assertSame('Jane DOE', $payload['displayName']);
    }

    public function testUpdateRejectsInvalidJson(): void
    {
        $response = $this->controller(new User())->update(new Request(content: '{invalid'));

        self::assertSame(400, $response->getStatusCode());
    }

    public function testUpdateReturnsUnauthenticated(): void
    {
        $response = $this->controller(null)->update(new Request(content: json_encode([])));

        self::assertSame(401, $response->getStatusCode());
    }

    public function testUpdateRejectsMissingNames(): void
    {
        $response = $this->controller(new User())->update(new Request(content: json_encode(['firstname' => '', 'lastname' => ''])));

        self::assertSame(422, $response->getStatusCode());
    }

    public function testUpdatePersistsProfileAndAddress(): void
    {
        $user = (new User())->setEmail('user@example.com');
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $response = $this->controller($user, $em)->update(new Request(content: json_encode([
            'firstname' => ' Jane ',
            'lastname' => ' Doe ',
            'address' => [
                'line1' => ' 1 rue ',
                'line2' => ' ',
                'postalCode' => ' 75000 ',
                'city' => ' Paris ',
                'country' => ' France ',
            ],
        ])));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('jane', $user->getFirstname());
        self::assertSame('doe', $user->getLastname());
        self::assertSame('1 rue', $user->getAddress()?->getLine1());
        self::assertNull($user->getAddress()?->getLine2());
    }

    public function testUpdateRejectsIncompleteAddress(): void
    {
        $response = $this->controller(new User())->update(new Request(content: json_encode([
            'firstname' => 'Jane',
            'lastname' => 'Doe',
            'address' => [
                'line1' => '1 rue',
                'postalCode' => '',
                'city' => 'Paris',
                'country' => 'France',
            ],
        ])));

        self::assertSame(422, $response->getStatusCode());
    }

    public function testUpdateReusesExistingAddressAndKeepsLine2(): void
    {
        $address = (new Address())->setLine2('Initial complement');
        $user = (new User())->setAddress($address);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $response = $this->controller($user, $em)->update(new Request(content: json_encode([
            'firstname' => 'Jane',
            'lastname' => 'Doe',
            'address' => [
                'line1' => '1 rue',
                'line2' => ' Bat B ',
                'postalCode' => '75000',
                'city' => 'Paris',
                'country' => 'France',
            ],
        ])));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame($address, $user->getAddress());
        self::assertSame('Bat B', $user->getAddress()?->getLine2());
    }

    public function testRequestPasswordResetReturnsUnauthenticated(): void
    {
        $response = $this->controller(null)->requestPasswordReset(
            $this->createMock(ResetPasswordHelperInterface::class),
            $this->createMock(MailerInterface::class),
        );

        self::assertSame(401, $response->getStatusCode());
    }

    public function testRequestPasswordResetSendsEmail(): void
    {
        $user = (new User())->setEmail('user@example.com')->setFirstname('Jane')->setLastname('Doe');
        $helper = $this->createMock(ResetPasswordHelperInterface::class);
        $helper->expects(self::once())
            ->method('generateResetToken')
            ->with($user)
            ->willReturn(new ResetPasswordToken('token value', new \DateTimeImmutable('+1 hour'), time()));
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send')->with(self::callback(function (RawMessage $message): bool {
            self::assertInstanceOf(\Symfony\Bridge\Twig\Mime\TemplatedEmail::class, $message);
            self::assertSame('user@example.com', $message->getTo()[0]->getAddress());
            self::assertSame('reset_password/email.html.twig', $message->getHtmlTemplate());
            self::assertSame('https://front.example/reset-password/reset/token%20value', $message->getContext()['frontendResetUrl']);

            return true;
        }));

        $response = $this->controller($user, frontendUrl: 'https://front.example/')->requestPasswordReset($helper, $mailer);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testRequestPasswordResetHandlesRecentRequest(): void
    {
        $helper = $this->createMock(ResetPasswordHelperInterface::class);
        $helper->method('generateResetToken')->willThrowException(new class('recent') extends \RuntimeException implements ResetPasswordExceptionInterface {
            public function getReason(): string
            {
                return 'recent';
            }
        });
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $response = $this->controller(new User(), frontendUrl: 'https://front.example')->requestPasswordReset($helper, $mailer);

        self::assertSame(200, $response->getStatusCode());
    }

    private function controller(?User $user, ?EntityManagerInterface $em = null, string $frontendUrl = 'https://front.example'): ProfileController
    {
        $controller = new ProfileController($em ?? $this->createMock(EntityManagerInterface::class));
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->method('get')->with('frontend_url')->willReturn($frontendUrl);
        $controller->setContainer(new ControllerTestContainer([
            'security.token_storage' => $this->tokenStorage($user),
            'parameter_bag' => $parameterBag,
        ]));

        return $controller;
    }

    private function tokenStorage(?User $user): TokenStorageInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->method('getToken')->willReturn($token);

        return $storage;
    }
}
