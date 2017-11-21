<?php

namespace Vpg\Disturb\Step;

use \Phalcon\Cli\Task;

use Vpg\Disturb\Core\AbstractWorker;
use Vpg\Disturb\Message;
use Vpg\Disturb\Topic;

/**
 * Generic Step task
 * Dedicated to one step, given in argv with --step argument
 *
 * @package  Disturb\Step
 * @author   Jérome BOURGEAIS <jbourgeais@voyageprive.com>
 * @license  https://github.com/vpg/disturb/blob/master/LICENSE MIT Licence
 */
class StepWorker extends AbstractWorker
{

    protected $taskOptionList = [
        'step:',      // required step code config file
        '?workerId:0' // required worker id, in case of multiple instance of the same worker
    ];

    /**
     * Todo : improve usage handling
     *
     * @return void
     */
    protected function usage()
    {
        $this->getDI()->get('logger')->debug('Usage : ');
        $this->getDI()->get('logger')->debug(
            'disturb.php "Tasks\\Step" start --step="stepName" '.
            '--workflow="/path/to/workflow/config/file.json" [--name="workflowName"]'
        );
    }

    /**
     * Uses the business service related to the current step to process the given message
     *  - The message processing is fully delegated to the "client" service implementing the
     * \Disturb\Step\StepServiceInterface.php by calling the execute method
     *  - the process result (returned by the service) is sent back to the manager
     *
     * @param Vpg\Disturb\Message\MessageDto $messageDto the message to process
     *
     * @return void
     */
    protected function processMessage(Message\MessageDto $messageDto)
    {
        $this->getDI()->get('logger')->info('messageDto : ' . $messageDto);
        $resultHash = $this->service->execute($messageDto->getPayload());
        $msgDto = new Message\MessageDto(
            [
                'id' => $messageDto->getId(),
                'type' => Message\MessageDto::TYPE_STEP_ACK,
                'stepCode' => $messageDto->getStepCode(),
                'jobId' => $messageDto->getJobId(),
                'result' => json_encode($resultHash)
            ]
        );

        $this->sendMessage(
            Topic\TopicService::getWorkflowManagerTopicName($this->workflowConfig['name']),
            $msgDto
        );
    }

    /**
     * Specializes the current Step according to the given argvs
     *  - Sets the topic
     *  - Instanciates the "Client" service
     *
     * @return void
     */
    protected function initWorker()
    {
        $this->getDI()->get('logger')->debug(json_encode(func_get_args()));
        parent::initWorker($this->paramHash);
        $serviceFullName = $this->workflowConfig['servicesClassNameSpace'] . '\\' .
            ucFirst($this->paramHash['step']) . 'Step';
        $this->service = new $serviceFullName($this->paramHash['workflow']);

        $this->topicName = Topic\TopicService::getWorkflowStepTopicName(
            $this->paramHash['step'],
            $this->workflowConfig['name']
        );
    }
}
