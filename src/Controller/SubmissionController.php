<?php

declare(strict_types=1);


namespace Jbtronics\TranslationEditorBundle\Controller;

use Jbtronics\TranslationEditorBundle\Service\MessageEditor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SubmissionController
{
    public function __construct(
        private readonly MessageEditor $editor
    ) {
    }

    public function editMessage(Request $request): Response
    {
        $data = $request->toArray();

        //Get the message from the request
        $messageId = $data['messageId'] ?? throw new HttpException(400, 'messageId is required');
        $messageLocale = $data['messageLocale'] ?? throw new HttpException(400, 'messageLocale is required');
        $messageDomain = $data['messageDomain'] ?? throw new HttpException(400, 'messageDomain is required');
        $message = $data['message'] ?? throw new HttpException(400, 'message is required');

        $this->editor->editMessage($messageId, $messageLocale, $messageDomain, $message);

        return new JsonResponse([
            'success' => true,
            'message' => $message,
        ]);
    }
}