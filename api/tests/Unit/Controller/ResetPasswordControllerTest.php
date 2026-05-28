<?php

namespace App\Tests\Unit\Controller;

use App\Controller\ResetPasswordController;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final class ResetPasswordControllerTest extends TestCase
{
    public function testRequestRendersInitialForm(): void
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->notSubmittedForm());
        $twig = $this->twigExpecting('reset_password/request.html.twig', static fn (array $parameters): bool => isset($parameters['requestForm']));

        $response = $this->controller(services: ['form.factory' => $formFactory, 'twig' => $twig])
            ->request(new Request(), $this->createMock(MailerInterface::class), $this->createMock(TranslatorInterface::class));

        self::assertSame('rendered', $response->getContent());
    }

    public function testRequestRedirectsWhenEmailHasNoUser(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->with(['email' => 'missing@example.com'])->willReturn(null);
        $em = $this->entityManagerWithRepository($repository);
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->submittedEmailForm('missing@example.com'));

        $response = $this->controller($em, ['form.factory' => $formFactory, 'router' => $this->router('/check-email')])
            ->request(new Request(), $this->createMock(MailerInterface::class), $this->createMock(TranslatorInterface::class));

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/check-email', $response->headers->get('Location'));
    }

    public function testRequestSendsResetEmailAndStoresToken(): void
    {
        $user = (new User())->setEmail('user@example.com');
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($user);
        $helper = $this->createMock(ResetPasswordHelperInterface::class);
        $helper->expects(self::once())->method('generateResetToken')->with($user)->willReturn($this->token('abc'));
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send');
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->submittedEmailForm('user@example.com'));
        $stack = $this->requestStackWithSession();

        $response = $this->controller($this->entityManagerWithRepository($repository), [
            'form.factory' => $formFactory,
            'parameter_bag' => $this->parameterBag(),
            'request_stack' => $stack,
            'router' => $this->router('/check-email'),
        ], $helper)->request(new Request(), $mailer, $this->createMock(TranslatorInterface::class));

        self::assertSame(302, $response->getStatusCode());
        self::assertNotNull($stack->getSession()->get('ResetPasswordToken'));
    }

    public function testRequestRedirectsWhenTokenGenerationFails(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn((new User())->setEmail('user@example.com'));
        $helper = $this->createMock(ResetPasswordHelperInterface::class);
        $helper->method('generateResetToken')->willThrowException($this->resetPasswordException('failed'));
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->submittedEmailForm('user@example.com'));

        $response = $this->controller($this->entityManagerWithRepository($repository), [
            'form.factory' => $formFactory,
            'router' => $this->router('/check-email'),
        ], $helper)->request(new Request(), $this->createMock(MailerInterface::class), $this->createMock(TranslatorInterface::class));

        self::assertSame('/check-email', $response->headers->get('Location'));
    }

    public function testCheckEmailUsesFakeTokenWhenSessionHasNone(): void
    {
        $token = $this->token('fake');
        $helper = new class($token) implements ResetPasswordHelperInterface {
            public function __construct(private readonly ResetPasswordToken $token) {}
            public function generateFakeResetToken(): ResetPasswordToken { return $this->token; }
            public function generateResetToken(object $user): ResetPasswordToken { throw new \BadMethodCallException(); }
            public function validateTokenAndFetchUser(string $fullToken): object { throw new \BadMethodCallException(); }
            public function removeResetRequest(string $fullToken): void {}
            public function getTokenLifetime(): int { return 3600; }
        };
        $twig = $this->twigExpecting('reset_password/check_email.html.twig', static fn (array $parameters): bool => $parameters['resetToken'] instanceof ResetPasswordToken);

        $response = $this->controller(services: ['request_stack' => $this->requestStackWithSession(), 'twig' => $twig], helper: $helper)->checkEmail();

        self::assertSame('rendered', $response->getContent());
    }

    public function testResetStoresTokenAndRedirects(): void
    {
        $response = $this->controller(services: ['request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/reset')])
            ->reset(new Request(), $this->createMock(UserPasswordHasherInterface::class), $this->createMock(TranslatorInterface::class), 'token');

        self::assertSame('/reset', $response->headers->get('Location'));
    }

    public function testResetThrowsWhenSessionTokenMissing(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $this->controller(services: ['request_stack' => $this->requestStackWithSession()])
            ->reset(new Request(), $this->createMock(UserPasswordHasherInterface::class), $this->createMock(TranslatorInterface::class));
    }

    public function testResetRedirectsWhenTokenValidationFails(): void
    {
        $helper = $this->createMock(ResetPasswordHelperInterface::class);
        $helper->method('validateTokenAndFetchUser')->willThrowException($this->resetPasswordException('bad'));
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);
        $stack = $this->requestStackWithSession();
        $stack->getSession()->set('ResetPasswordPublicToken', 'token');

        $response = $this->controller(services: [
            'request_stack' => $stack,
            'router' => $this->router('/forgot'),
        ], helper: $helper)->reset(new Request(), $this->createMock(UserPasswordHasherInterface::class), $translator);

        self::assertSame('/forgot', $response->headers->get('Location'));
    }

    public function testResetRendersPasswordFormWhenNotSubmitted(): void
    {
        $helper = $this->createMock(ResetPasswordHelperInterface::class);
        $helper->method('validateTokenAndFetchUser')->willReturn(new User());
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->notSubmittedForm());
        $stack = $this->requestStackWithSession();
        $stack->getSession()->set('ResetPasswordPublicToken', 'token');
        $twig = $this->twigExpecting('reset_password/reset.html.twig', static fn (array $parameters): bool => isset($parameters['resetForm']));

        $response = $this->controller(services: [
            'form.factory' => $formFactory,
            'request_stack' => $stack,
            'twig' => $twig,
        ], helper: $helper)->reset(new Request(), $this->createMock(UserPasswordHasherInterface::class), $this->createMock(TranslatorInterface::class));

        self::assertSame('rendered', $response->getContent());
    }

    public function testResetChangesPasswordAndCleansSession(): void
    {
        $user = new User();
        $helper = $this->createMock(ResetPasswordHelperInterface::class);
        $helper->method('validateTokenAndFetchUser')->willReturn($user);
        $helper->expects(self::once())->method('removeResetRequest')->with('token');
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->with($user, 'plain')->willReturn('hashed');
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->submittedPasswordForm('plain'));
        $stack = $this->requestStackWithSession();
        $stack->getSession()->set('ResetPasswordPublicToken', 'token');

        $response = $this->controller($em, [
            'form.factory' => $formFactory,
            'request_stack' => $stack,
            'router' => $this->router('/'),
        ], $helper)->reset(new Request(), $hasher, $this->createMock(TranslatorInterface::class));

        self::assertSame('hashed', $user->getPassword());
        self::assertFalse($stack->getSession()->has('ResetPasswordToken'));
        self::assertSame('/', $response->headers->get('Location'));
    }

    public function testProfilePasswordResetRejectsInvalidCsrf(): void
    {
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(false);

        $this->expectException(\Symfony\Component\Security\Core\Exception\AccessDeniedException::class);

        $this->controller(services: ['security.csrf.token_manager' => $csrf])
            ->requestPasswordResetForCurrentUser(new Request(request: ['_token' => 'bad']), $this->createMock(ResetPasswordHelperInterface::class), $this->createMock(MailerInterface::class));
    }

    public function testProfilePasswordResetRejectsAnonymousUser(): void
    {
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(true);

        $this->expectException(\Symfony\Component\Security\Core\Exception\AccessDeniedException::class);

        $this->controller(services: [
            'security.csrf.token_manager' => $csrf,
            'security.token_storage' => $this->tokenStorage(null),
        ])->requestPasswordResetForCurrentUser(new Request(request: ['_token' => 'ok']), $this->createMock(ResetPasswordHelperInterface::class), $this->createMock(MailerInterface::class));
    }

    public function testProfilePasswordResetSendsEmail(): void
    {
        $user = (new User())->setEmail('user@example.com');
        $helper = $this->createMock(ResetPasswordHelperInterface::class);
        $helper->method('generateResetToken')->with($user)->willReturn($this->token('profile'));
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send');
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(true);

        $response = $this->controller(services: [
            'parameter_bag' => $this->parameterBag(),
            'request_stack' => $this->requestStackWithSession(),
            'router' => $this->router('/profile'),
            'security.csrf.token_manager' => $csrf,
            'security.token_storage' => $this->tokenStorage($user),
        ])->requestPasswordResetForCurrentUser(new Request(request: ['_token' => 'ok']), $helper, $mailer);

        self::assertSame(303, $response->getStatusCode());
    }

    public function testProfilePasswordResetWarnsWhenGenerationFails(): void
    {
        $helper = $this->createMock(ResetPasswordHelperInterface::class);
        $helper->method('generateResetToken')->willThrowException($this->resetPasswordException('failed'));
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(true);

        $response = $this->controller(services: [
            'request_stack' => $this->requestStackWithSession(),
            'router' => $this->router('/profile'),
            'security.csrf.token_manager' => $csrf,
            'security.token_storage' => $this->tokenStorage((new User())->setEmail('user@example.com')),
        ])->requestPasswordResetForCurrentUser(new Request(request: ['_token' => 'ok']), $helper, $this->createMock(MailerInterface::class));

        self::assertSame('/profile', $response->headers->get('Location'));
    }

    /** @param array<string, mixed> $services */
    private function controller(?EntityManagerInterface $em = null, array $services = [], ?ResetPasswordHelperInterface $helper = null): ResetPasswordController
    {
        $controller = new ResetPasswordController($helper ?? $this->createMock(ResetPasswordHelperInterface::class), $em ?? $this->createMock(EntityManagerInterface::class));
        $controller->setContainer(new ControllerTestContainer($services));

        return $controller;
    }

    private function submittedEmailForm(string $email): FormInterface
    {
        $emailField = $this->createMock(FormInterface::class);
        $emailField->method('getData')->willReturn($email);
        $form = $this->submittedValidForm();
        $form->method('get')->with('email')->willReturn($emailField);

        return $form;
    }

    private function submittedPasswordForm(string $password): FormInterface
    {
        $passwordField = $this->createMock(FormInterface::class);
        $passwordField->method('getData')->willReturn($password);
        $form = $this->submittedValidForm();
        $form->method('get')->with('plainPassword')->willReturn($passwordField);

        return $form;
    }

    private function submittedValidForm(): FormInterface
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        return $form;
    }

    private function notSubmittedForm(): FormInterface
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(false);

        return $form;
    }

    private function entityManagerWithRepository(EntityRepository $repository): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(User::class)->willReturn($repository);

        return $em;
    }

    private function token(string $token): ResetPasswordToken
    {
        return new ResetPasswordToken($token, new \DateTimeImmutable('+1 hour'), time());
    }

    private function resetPasswordException(string $reason): ResetPasswordExceptionInterface
    {
        return new class($reason) extends \RuntimeException implements ResetPasswordExceptionInterface {
            public function getReason(): string
            {
                return $this->message;
            }
        };
    }

    /** @param callable(array<string, mixed>): bool $parametersMatcher */
    private function twigExpecting(string $template, callable $parametersMatcher): Environment
    {
        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())->method('render')->with($template, self::callback($parametersMatcher))->willReturn('rendered');

        return $twig;
    }

    private function router(string $url): UrlGeneratorInterface
    {
        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->willReturn($url);

        return $router;
    }

    private function requestStackWithSession(): RequestStack
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $stack = new RequestStack();
        $stack->push($request);

        return $stack;
    }

    private function parameterBag(): ContainerBagInterface
    {
        $bag = $this->createMock(ContainerBagInterface::class);
        $bag->method('get')->with('frontend_url')->willReturn('https://front.example/');

        return $bag;
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
