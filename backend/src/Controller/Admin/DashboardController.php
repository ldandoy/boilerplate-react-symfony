<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\User;
use App\Entity\RefreshToken;
use App\Entity\ResetPasswordRequest;
use App\Repository\UserRepository;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function index(): Response
    {
        $userCount = $this->userRepository->count();

        return $this->render('admin/index.html.twig', [
            'userCount' => $userCount,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('App');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        // yield MenuItem::linkToCrud('The Label', 'fas fa-list', EntityClass::class);
        yield MenuItem::linkToCrud('Users', 'fa fa-users', User::class);
        yield MenuItem::linkToCrud('Refresh Tokens', 'fa fa-key', RefreshToken::class);
        yield MenuItem::linkToCrud('Reset Password Requests', 'fa fa-envelope', ResetPasswordRequest::class);
    }
}
