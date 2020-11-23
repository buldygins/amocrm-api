<?php

namespace App\Http\Controllers;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Collections\CompaniesCollection;
use AmoCRM\Collections\ContactsCollection;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Exceptions\AmoCRMoAuthApiException;
use AmoCRM\Exceptions\InvalidArgumentException;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Models\CompanyModel;
use AmoCRM\Models\ContactModel;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\BaseCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Models\LeadModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;


class HomeController extends Controller
{

    protected $apiClient;
    public $accessToken;

    public function __construct()
    {
        $this->apiClient = new AmoCRMApiClient(env('CLIENT_ID'), env('CLIENT_SECRET'), env('CLIENT_REDIRECT_URI'));
    }

    public function index()
    {
        if (Cache::has('accessToken') and Cache::has('referer')) {
            $button = '';
        } else {
            $button = $this->apiClient->getOAuthClient()->getOAuthButton([
                'mode' => 'popup',
                'compact' => false
            ]);
        }
        return view('index', compact('button'));
    }

    public function auth(Request $request)
    {
        $this->apiClient->setAccountBaseDomain($request->referer);
        $accessToken = $this->apiClient->getOAuthClient()->getAccessTokenByCode($request->code);
        Cache::add('accessToken', $accessToken, 1200);
        Cache::add('referer', $request->referer, 1200);
        $this->apiClient->setAccessToken($accessToken);
        return response()->redirectTo('/');
    }

    public function post_auth()
    {
        $accessToken = Cache::get('accessToken');
        $referer = Cache::get('referer');
        $this->apiClient->setAccountBaseDomain($referer);
        $this->apiClient->setAccessToken($accessToken);
    }

    public function getLeads()
    {
        $this->post_auth();
        $leadsService = $this->apiClient->leads();
        $usersService = $this->apiClient->users();
        $this->apiClient->companies();
        try {
            $leads = $leadsService->get();
            $notesService = $this->apiClient->notes(EntityTypesInterface::LEADS);
            foreach ($leads as $lead) {
                $responsible_user[$lead->getId()] = $usersService->getOne($lead->getResponsibleUserId())->getName();
                $notes[$lead->getId()] = $notesService->getByParentId($lead->getId());
                $links[$lead->getId()] = $leadsService->getLinks((new LeadModel())->setId($lead->getId()))->toArray();
                if (isset($notes[$lead->getId()])) {
                    foreach ($notes[$lead->getId()] as $note) {
                        $notes_username[$note->getId()] = $usersService->getOne($note->getCreatedBy())->getName();

                    }
                }
                if (isset($links[$lead->getId()])) {
                    $entities[$lead->getId()] = $this->getEntitiesFromLinks($lead, $links[$lead->getId()]);
                }
            }

        } catch (AmoCRMoAuthApiException $e) {
        } catch (AmoCRMApiException $e) {
        }
        return view('leads.leads', compact(['leads', 'responsible_user', 'notes', 'notes_username', 'entities']));
    }

    public function getEntitiesFromLinks($model, $links)
    {
        $this->post_auth();
        foreach ($links as $entityLink) {
            try {
                switch ($entityLink['to_entity_type']) {
                    case 'companies':
                        if (!isset($companiesService)) {
                            $companiesService = $this->apiClient->companies();
                        }

                        $entities[$model->getId()]['companies'][$entityLink['to_entity_id']] = $companiesService->getOne($entityLink['to_entity_id']);

                        break;
                    case 'contacts':
                        if (!isset($contactsService)) {
                            $contactsService = $this->apiClient->contacts();
                        }
                        $entities[$model->getId()]['contacts'][$entityLink['to_entity_id']] = $contactsService->getOne($entityLink['to_entity_id']);
                        break;
                    case 'leads':
                        if (!isset($leadsService)) {
                            $leadsService = $this->apiClient->leads();
                        }
                        $entities[$model->getId()]['leads'][$entityLink['to_entity_id']] = $leadsService->getOne($entityLink['to_entity_id']);
                        break;

                }
            } catch (AmoCRMoAuthApiException $e) {
            } catch (AmoCRMApiException $e) {
            }
        }
        return $entities;
    }

    public function createLead(Request $request)
    {
        $this->post_auth();
        $responsible_users = $this->apiClient->users()->get();
        return view('leads.create', compact('responsible_users'));
    }

    public function storeLead(Request $request)
    {
        $this->post_auth();
        $leadService = $this->apiClient->leads();
        $contactsService = $this->apiClient->contacts();
        $lead = new LeadModel();
        $contactsCollection = new ContactsCollection();
        $linksCollection = new LinksCollection();
        try {
            for ($i = 1; $i <= $request->get('contact_count'); $i++) {
                $contact = new ContactModel();
                $contact->setName($request->get('contact' . $i . '_name'));
                $contactCustomFieldsValues = new CustomFieldsValuesCollection();
                if ($request->get('contact' . $i . '_phone') !== null) {
                    $phone = new MultitextCustomFieldValuesModel();
                    $phone->setFieldId(147095)->setFieldCode("PHONE")->setValues(
                        (new MultitextCustomFieldValueCollection())
                            ->add((new MultitextCustomFieldValueModel())->setEnum('WORK')
                                ->setValue($request->get('contact' . $i . '_phone')))
                    );
                    $contactCustomFieldsValues->add($phone);
                }
                if ($request->get('contact' . $i . '_email') !== null) {
                    $email = new MultitextCustomFieldValuesModel();
                    $email->setFieldId(147097)->setFieldCode("EMAIL")->setValues(
                        (new MultitextCustomFieldValueCollection())
                            ->add((new MultitextCustomFieldValueModel())->setEnum('WORK')
                                ->setValue($request->get('contact' . $i . '_email')))
                    );
                    $contactCustomFieldsValues->add($email);
                }
                if ($request->get('contact' . $i . '_position') !== null) {
                    $position = new TextCustomFieldValuesModel();
                    $position->setFieldId(147093)->setFieldCode("POSITION")->setValues(
                        (new TextCustomFieldValueCollection())
                            ->add((new BaseCustomFieldValueModel())
                                ->setValue($request->get('contact' . $i . '_position')))
                    );
                    $contactCustomFieldsValues->add($position);
                }
                $contact->setCustomFieldsValues($contactCustomFieldsValues);
                $contactsService->addOne($contact);
                $contactsCollection->add($contactsService->getOne($contact->getId()));
            }
            foreach ($contactsCollection as $contact) {
                $linksCollection->add($contactsService->getOne($contact->getId()));
            }
            if ($request->get('company') !== null) {
                $companyService = $this->apiClient->companies();
                $company = new CompanyModel();
                $company->setName($request->get('company'));

                $companyService->addOne($company);
                dump($companyService->getOne($company->getId()));

                try {
                    $companyService->link($companyService->getOne($company->getId()), $linksCollection);  //doesn't work
                } catch (InvalidArgumentException $e) {

                }
                if ($request->get('name') !== null) {
                    $lead->setName($request->get('name'));
                }
                if ($request->get('price') !== null) {
                    $lead->setPrice($request->get('price'));
                }
                if (isset($company)) {
                    $lead->setCompany($company);
                }
                if (!$contactsCollection->isEmpty()) {
                    $lead->setContacts($contactsCollection);
                }
                if ($request->get('responsible_id') !== null) {
                    $lead->setResponsibleUserId($request->get('responsible_id'));
                }

                $leadService->addOne($lead);
                if (isset($company)) {
                    $linksCollection->add($contactsService->getOne($company->getId()));
                }
                $leadService->link($leadService->getOne($lead->getId()), $linksCollection);  // doesn't work
                return redirect('/');
            }
        } catch (AmoCRMoAuthApiException $e) {
        } catch (AmoCRMApiException $e) {
        }

    }
}
