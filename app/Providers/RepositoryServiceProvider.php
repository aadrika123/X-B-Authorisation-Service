<?php

namespace App\Providers;

use App\Repository\Menu\Interface\iMenuRepo;
use App\Repository\Menu\Concrete\MenuRepo;
use App\Repository\WorkflowMaster\Concrete\WorkflowMap;
use App\Repository\WorkflowMaster\Concrete\WorkflowRoleUserMapRepository;
use App\Repository\WorkflowMaster\Interface\iWorkflowMapRepository;
use App\Repository\WorkflowMaster\Interface\iWorkflowRoleUserMapRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * | ------------ Provider for the Binding of Interface and Concrete Class of the Repository --------------------------- |
     * | Created On- 05-06-2023 
     * | Created By- Tannu Verma
     */
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(iMenuRepo::class, MenuRepo::class);

        //WorkflowMaster
        $this->app->bind(iWorkflowMasterRepository::class, WorkflowMasterRepository::class);
        $this->app->bind(iWorkflowRoleRepository::class, WorkflowRoleRepository::class);
        $this->app->bind(iWorkflowMapRepository::class, WorkflowMap::class);
    }
}
