<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Database\Expression\QueryExpression;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\ForbiddenException;


class InboxController extends AppController
{
    public $filters = ['scope', 'action', 'title', 'origin', 'comment'];

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->set('metaGroup', 'Administration');
    }


    public function index()
    {
        $this->CRUD->index([
            'filters' => $this->filters,
            'quickFilters' => ['scope', 'action', ['title' => true], ['comment' => true]],
            'contextFilters' => [
                'fields' => [
                    'scope',
                ]
            ],
            'contain' => ['Users']
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function filtering()
    {
        $this->CRUD->filtering();
    }

    public function view($id)
    {
        $this->CRUD->view($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function delete($id)
    {
        if ($this->request->is('post')) {
            $request = $this->Inbox->get($id);
            $this->requestProcessor = TableRegistry::getTableLocator()->get('RequestProcessor');
            $processor = $this->requestProcessor->getProcessor($request->scope, $request->action);
            $discardResult = $processor->discard($id, $request);
            return $processor->genHTTPReply($this, $discardResult);
        }
        $this->set('deletionTitle', __('Discard request'));
        $this->set('deletionText', __('Are you sure you want to discard request #{0}?', $id));
        $this->set('deletionConfirm', __('Discard'));
        $this->CRUD->delete($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function process($id)
    {
        $request = $this->Inbox->get($id);
        $scope = $request->scope;
        $action = $request->action;
        $this->requestProcessor = TableRegistry::getTableLocator()->get('RequestProcessor');
        if ($scope == 'LocalTool') {
            $processor = $this->requestProcessor->getLocalToolProcessor($action, $request->data['toolName']);
        } else {
            $processor = $this->requestProcessor->getProcessor($scope, $action);
        }
        if ($this->request->is('post')) {
            $processResult = $processor->process($id, $this->request->getData());
            return $processor->genHTTPReply($this, $processResult);
        } else {
            $renderedView = $processor->render($request, $this->request);
            return $this->response->withStringBody($renderedView);
        }
    }

    public function listProcessors()
    {
        $this->requestProcessor = TableRegistry::getTableLocator()->get('RequestProcessor');
        $requestProcessors = $this->requestProcessor->listProcessors();
        if ($this->ParamHandler->isRest()) {
            return $this->RestResponse->viewData($requestProcessors, 'json');
        }
        $data = [];
        foreach ($requestProcessors as $scope => $processors) {
            foreach ($processors as $processor) {
                $data[] = [
                    'enabled' => $processor->enabled,
                    'scope' => $scope,
                    'action' => $processor->action,
                    'description' => isset($processor->getDescription) ? $processor->getDescription() : null,
                    'notice' => $processor->notice ?? null,
                    'error' => $processor->error ?? null,
                ];
            }
        }
        $this->set('data', $data);
    }

    public function createProcessorInboxEntry($scope, $action)
    {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException(__('Only POST method is accepted'));
        }
        $entryData = [
            'origin' => $this->request->clientIp(),
            'user_id' => $this->ACL->getUser()['id'],
        ];
        $entryData['data'] = $this->request->data ?? [];
        // $entryData['data'] = [
        //     'toolName' => 'MISP',
        //     'url' => 'http://localhost:8000',
        //     'email' => 'admin@admin.test',
        //     'authkey' => 'DkM9fEfwrG8Bg3U0ncKamocIutKt5YaUFuxzsB6b',
        // ];
        $this->requestProcessor = TableRegistry::getTableLocator()->get('RequestProcessor');
        if ($scope == 'LocalTool') {
            if (empty($entryData['data']['toolName']) || empty($entryData['data']['url'])) {
                throw new MethodNotAllowedException(__('Could not create entry. Tool name or URL is missing'));
            }
            $entryData['origin'] = $entryData['data']['url'];
            $processor = $this->requestProcessor->getLocalToolProcessor($action, $entryData['data']['toolName']);
        } else {
            $processor = $this->requestProcessor->getProcessor($scope, $action);
        }
        $creationResult = $this->requestProcessor->createInboxEntry($processor, $entryData);
        return $processor->genHTTPReply($this, $creationResult);
    }
}
