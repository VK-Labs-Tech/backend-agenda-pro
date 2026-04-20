<?php
declare(strict_types=1);

namespace App\Domain\User\Services;

use App\Domain\User\Entities\UserEntity;
use App\Domain\User\Events\UserRegisteredEvent;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\User\Data\DTOs\Request\RegisterUserRequest;
use App\Infrastructure\Events\EventDispatcher;
use App\Infrastructure\Exceptions\ValidationException;

use App\Domain\Company\Repositories\CompanyRepository;
use App\Domain\Company\Entities\CompanyEntity;
use App\Domain\CompanyPlan\Services\CompanyPlanService;

final class RegisterUserService
{
    public function __construct(
        private UserRepository $users,
        private EventDispatcher $dispatcher,
        private CompanyRepository $companies,
        private CompanyPlanService $companyPlans
    ) {
    }

    public function execute(RegisterUserRequest $request): void
    {
        $existing = $this->users->findByEmail($request->email());
        if ($existing !== null) {
            throw new ValidationException([
                'email' => 'Email já cadastrado.',
            ]);
        }

        $verificationCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user = UserEntity::create(
            name: $request->name(),
            email: $request->email(),
            plainPassword: $request->password(),
            tipoConta: $request->tipoConta(),
            telefone: $request->telefone(),
            zipCode: $verificationCode
        );

        $user->deactivate();
        $this->users->save(user: $user, verificationCode: $verificationCode);

        // Criação da empresa vinculada ao usuário
        $company = CompanyEntity::create(
            userId: $user->id() ?? 0,
            name: $request->name(),
            cnpj: $request->cnpjcpf(),
            address: '',
            city: '',
            state: ''
        );
        $company = $this->companies->save($company);

        $trialEnd = (new \DateTimeImmutable('+7 days'))->format('Y-m-d H:i:s');
        $this->companyPlans->upsert($company->id(), [
            'plan_code' => 'trial',
            'status' => 'trialing',
            'current_period_end' => $trialEnd,
            'stripe_customer_id' => null,
            'stripe_subscription_id' => null,
        ]);

        try {
            $this->dispatcher->dispatch(new UserRegisteredEvent(
                userId: $user->id(),
                name: $user->name(),
                email: $user->email(),
                verificationCode: $verificationCode
            ));
        } catch (\Throwable) {
        }
    }
}