<?php

declare(strict_types=1);

use App\Application\Actions\Auth\LoginUserAction;
use App\Application\Actions\Auth\CurrentUserAction;
use App\Application\Actions\Auth\LogoutUserAction;
use App\Application\Actions\Auth\VerifyEmailCodeAction;
use App\Application\Actions\Clients\List\ClientListAction;
use App\Application\Actions\Clients\Register\ClientRegisterAction;
use App\Application\Actions\Clients\Update\ClientUpdateAction;
use App\Application\Actions\Companys\Disable\CompanyDisableAction;
use App\Application\Actions\Companys\List\CompanyListAction;
use App\Application\Actions\Companys\List\CompanyListByIdAction;
use App\Application\Actions\Companys\List\CompanyListUserIdAction;
use App\Application\Actions\Companys\Register\CompanyRegisterAction;
use App\Application\Actions\Companys\Update\CompanyUpdateAction;
use App\Application\Actions\Companys\Profile\CompanyProfileAction;
use App\Application\Actions\Produtcs\Delete\ProductDeleteAction;
use App\Application\Actions\Produtcs\List\ProductListAction;
use App\Application\Actions\Produtcs\List\ProductListByIdAction;
use App\Application\Actions\Produtcs\Register\ProductRegisterAction;
use App\Application\Actions\Produtcs\Update\ProductUpdateAction;
use App\Application\Actions\Profissionals\Delete\ProfissionalDeleteAction;
use App\Application\Actions\Profissionals\List\ProfissionalListAction;
use App\Application\Actions\Profissionals\List\ProfissionalListByIdAction;
use App\Application\Actions\Profissionals\Register\ProfissionalRegisterAction;
use App\Application\Actions\Profissionals\Update\ProfissionalUpdateAction;
use App\Application\Actions\Agendamentos\Delete\AgendamentoDeleteAction;
use App\Application\Actions\Agendamentos\List\AgendamentoListAction;
use App\Application\Actions\Agendamentos\List\AgendamentoListByIdAction;
use App\Application\Actions\Agendamentos\Register\AgendamentoRegisterAction;
use App\Application\Actions\Agendamentos\Update\AgendamentoUpdateAction;
use App\Application\Actions\Services\Delete\ServiceDeleteAction;
use App\Application\Actions\Services\List\ServiceListAction;
use App\Application\Actions\Services\List\ServiceListByIdaction;
use App\Application\Actions\Services\Register\ServiceRegisterAction;
use App\Application\Actions\Services\Update\ServiceUpdateAction;
use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use App\Application\Actions\Auth\RegisterAction;
use App\Application\Middleware\AuthMiddleware;
use App\Application\Middleware\RoleMiddleware;
use App\Application\Middleware\PlanLimitMiddleware;
use App\Application\Middleware\ProfessionalLimitMiddleware;
use App\Domain\User\Repositories\UserRepository;
use App\Application\Actions\Permissions\List\PermissionListAction;
use App\Application\Actions\Profissionals\Permissions\ProfissionalPermissionsGetAction;
use App\Application\Actions\Profissionals\Permissions\ProfissionalPermissionsUpdateAction;
use App\Application\Actions\Settings\Get\SettingsGetAction;
use App\Application\Actions\Settings\Update\SettingsUpdateAction;
use App\Application\Actions\Dashboard\Metrics\DashboardMetricsAction;
use App\Application\Actions\StockMovements\List\StockMovementListByCompanyAction;
use App\Application\Actions\StockMovements\List\StockMovementListByStockAction;
use App\Application\Actions\StockMovements\Register\StockMovementRegisterAction;
use App\Application\Actions\StockMovements\Delete\StockMovementDeleteAction;
use App\Application\Actions\Notifications\List\NotificationListAction;
use App\Application\Actions\AppointmentRequests\Register\AppointmentRequestPublicCreateAction;
use App\Application\Actions\AppointmentRequests\List\AppointmentRequestListAction;
use App\Application\Actions\AppointmentRequests\Approve\AppointmentRequestApproveAction;
use App\Application\Actions\AppointmentRequests\Reject\AppointmentRequestRejectAction;
use App\Application\Actions\AppointmentRequests\Public\AppointmentRequestPublicAvailabilityAction;
use App\Application\Actions\Public\Company\PublicCompanyInfoAction;
use App\Application\Actions\Public\Company\PublicCompanyListAction;
use App\Application\Actions\Billing\CreateCheckoutSessionAction;
use App\Application\Actions\Billing\StripeWebhookAction;
use App\Application\Actions\Billing\CompanyPlanStatusAction;
use App\Application\Actions\Billing\BillingUsageAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $container = $app->getContainer();

    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        return $response;
    });

    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    })
        ->add(new RoleMiddleware($container->get(UserRepository::class), ['ADMIN']))
        ->add(AuthMiddleware::class);

    $app->group('/auth', function (Group $group) {
        $group->post('/register', RegisterAction::class);
        $group->post('/verify-email', VerifyEmailCodeAction::class);
        $group->post('/login', LoginUserAction::class);
        $group->get('/me', CurrentUserAction::class)->add(AuthMiddleware::class);
        $group->post('/logout', LogoutUserAction::class)->add(AuthMiddleware::class);
    });

    $app->group('/companies', function (Group $group) {
        $group->get('', CompanyListAction::class);
        $group->post('', CompanyRegisterAction::class);
        $group->get('/{id}', CompanyListByIdAction::class);
        $group->get('/{id}/profile', CompanyProfileAction::class);
        $group->put('/{id}', CompanyUpdateAction::class);
        $group->patch('/{id}/inactivate', CompanyDisableAction::class);
        $group->get('/user/{userId}', CompanyListUserIdAction::class);
    })->add(AuthMiddleware::class);

    $app->group('/products', function (Group $group) {
        $group->get('', ProductListAction::class);
        $group->post('', ProductRegisterAction::class);
        $group->get('/{id}', ProductListByIdAction::class);
        $group->put('/{id}', ProductUpdateAction::class);
        $group->delete('/{id}', ProductDeleteAction::class);
    })->add(AuthMiddleware::class);

    $app->group('/services', function (Group $group) {
        $group->get('/company/{companyId}', ServiceListAction::class);
        $group->post('', ServiceRegisterAction::class);
        $group->get('/{id}', ServiceListByIdaction::class);
        $group->put('/{id}', ServiceUpdateAction::class);
        $group->delete('/{id}', ServiceDeleteAction::class);
    })->add(AuthMiddleware::class);

    $app->group('/clients', function (Group $group) {
        $group->post('', ClientRegisterAction::class);
        $group->get('', ClientListAction::class);
        $group->put('/{id}', ClientUpdateAction::class);
    })->add(AuthMiddleware::class);

    $app->group('/profissionals', function (Group $group) use ($container) {
        $group->get('', ProfissionalListAction::class);
        $group->post('', ProfissionalRegisterAction::class)->add(ProfessionalLimitMiddleware::class);
        $group->get('/{id}', ProfissionalListByIdAction::class);
        $group->put('/{id}', ProfissionalUpdateAction::class);
        $group->delete('/{id}', ProfissionalDeleteAction::class);
        $group->get('/{id}/permissions', ProfissionalPermissionsGetAction::class)
            ->add(new RoleMiddleware($container->get(UserRepository::class), ['ADMIN']));
        $group->put('/{id}/permissions', ProfissionalPermissionsUpdateAction::class)
            ->add(new RoleMiddleware($container->get(UserRepository::class), ['ADMIN']));
    })->add(AuthMiddleware::class);

    $app->group('/permissions', function (Group $group) {
        $group->get('', PermissionListAction::class);
    })
        ->add(new RoleMiddleware($container->get(UserRepository::class), ['ADMIN']))
        ->add(AuthMiddleware::class);

    $app->group('/appointment', function (Group $group) {
        $group->get('', AgendamentoListAction::class);
        $group->post('', AgendamentoRegisterAction::class)->add(PlanLimitMiddleware::class);
        $group->get('/{id}', AgendamentoListByIdAction::class);
        $group->put('/{id}', AgendamentoUpdateAction::class);
        $group->delete('/{id}', AgendamentoDeleteAction::class);
    })->add(AuthMiddleware::class);

    $app->group('/settings', function (Group $group) {
        $group->get('', SettingsGetAction::class);
        $group->put('', SettingsUpdateAction::class);
    })->add(AuthMiddleware::class);

    $app->group('/dashboard', function (Group $group) {
        $group->get('/metrics', DashboardMetricsAction::class);
    })->add(AuthMiddleware::class);

    $app->group('/stockMovements', function (Group $group) {
        $group->get('/company/{companyId}', StockMovementListByCompanyAction::class);
        $group->get('/stock/{stockId}', StockMovementListByStockAction::class);
        $group->post('', StockMovementRegisterAction::class);
        $group->delete('/{id}', StockMovementDeleteAction::class);
    })->add(AuthMiddleware::class);

    $app->group('/appointment-requests', function (Group $group) {
        $group->post('/public', AppointmentRequestPublicCreateAction::class);
        $group->get('/public/availability', AppointmentRequestPublicAvailabilityAction::class);
    });

    $app->group('/appointment-requests', function (Group $group) {
        $group->get('', AppointmentRequestListAction::class);
        $group->post('/{id}/approve', AppointmentRequestApproveAction::class);
        $group->post('/{id}/reject', AppointmentRequestRejectAction::class);
    })->add(AuthMiddleware::class);

    $app->group('/notifications', function (Group $group) {
        $group->get('', NotificationListAction::class);
    })->add(AuthMiddleware::class);

    $app->group('/billing', function (Group $group) {
        $group->post('/checkout', CreateCheckoutSessionAction::class);
        $group->get('/status', CompanyPlanStatusAction::class);
        $group->get('/usage', BillingUsageAction::class);
        $group->get('/invoices', \App\Application\Actions\Billing\InvoicesListAction::class);
        $group->get('/invoices/{id}/download', \App\Application\Actions\Billing\InvoiceDownloadAction::class);
    })->add(AuthMiddleware::class);

    $app->group('/billing', function (Group $group) {
        $group->post('/webhook', StripeWebhookAction::class);
    });

    $app->group('/public', function (Group $group) {
        $group->get('/companies/{id}', PublicCompanyInfoAction::class);
        $group->get('/companies', PublicCompanyListAction::class);
    });
};
