<?php

namespace App\DataTransformer;

use App\Entity\Currency;
use App\Entity\POS\Workspace;
use App\Entity\User;
use App\Exception\AppException;
use App\Repository\CurrencyRepository;
use App\Repository\POS\WorkspaceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class WorkspaceTransformer extends AppTransformer
{
    /** @var WorkspaceRepository */
    private $workspaceRepository;

    /** @var CurrencyRepository */
    private $currencyRepository;

    /**
     * WorkspaceTransformer constructor.
     * @param WorkspaceRepository $workspaceRepository
     * @param CurrencyRepository $currencyRepository
     * @param ValidatorInterface $validator
     */
    public function __construct(WorkspaceRepository $workspaceRepository, CurrencyRepository $currencyRepository, ValidatorInterface $validator)
    {
        $this->workspaceRepository = $workspaceRepository;
        $this->currencyRepository = $currencyRepository;

        parent::__construct($validator);
    }

    /**
     * Transform $request parameters to new object
     *
     * @param User $user
     * @param Request $request
     * @return Workspace
     * @throws AppException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function transform(User $user, Request $request) : Workspace
    {
        $name = (string) $request->request->get('name', '');
        if(empty($name)) throw new AppException('Name is required');
        $name = strtolower($name);

        $currencyShortName = (string) $request->request->get('currency', '');
        if(empty($currencyShortName)) throw new AppException('Currency is required');
        $currencyShortName = strtoupper($currencyShortName);

        $pin = (int) $request->request->get('pin', 0);

        /** @var Workspace $workspace */
        $workspace = $this->workspaceRepository->findByNameAndUser($name, $user);
        if($workspace instanceof Workspace) throw new AppException('Workspace already exists');

        /** @var Currency $currency */
        $currency = $this->currencyRepository->findByShortName($currencyShortName);
        if(!($currency instanceof Currency)) throw new AppException('Currency not found');

        $companyName = (string) $request->request->get('companyName', '');
        if(empty($companyName)) throw new AppException('Company name is required');

        $companyNip = (string) $request->request->get('companyNip', '');
        if(empty($companyNip)) throw new AppException('Company NIP is required');

        $companyStreet = (string) $request->request->get('companyStreet', '');
        if(empty($companyStreet)) throw new AppException('Company street is required');

        $companyCity = (string) $request->request->get('companyCity', '');
        if(empty($companyCity)) throw new AppException('Company city is required');

        $companyPostcode = (string) $request->request->get('companyPostcode', '');
        if(empty($companyPostcode)) throw new AppException('Company postcode is required');

        $companyCountry = (string) $request->request->get('companyCountry', '');
        if(empty($companyCountry)) throw new AppException('Company country is required');

        /** @var Workspace $workspace */
        $workspace = new Workspace($user, $name, $pin, $currency);

        $workspace->setCompanyName($companyName);
        $workspace->setCompanyNip($companyNip);
        $workspace->setCompanyStreet($companyStreet);
        $workspace->setCompanyCity($companyCity);
        $workspace->setCompanyPostcode($companyPostcode);
        $workspace->setCompanyCountry($companyCountry);

        return $workspace;
    }
}
