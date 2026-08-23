<?php

declare(strict_types=1);

namespace Jbtronics\TranslationEditorBundle\Tests\Controller;

use Jbtronics\TranslationEditorBundle\Controller\SubmissionController;
use Jbtronics\TranslationEditorBundle\Service\MessageEditor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Translation\Reader\TranslationReaderInterface;
use Symfony\Component\Translation\Writer\TranslationWriterInterface;

class SubmissionControllerTest extends TestCase
{
    //MessageEditor is final and cannot be mocked directly, so we use a real instance wired to
    //mocked reader/writer collaborators and observe its effect through the writer.
    private TranslationWriterInterface&MockObject $writer;
    private MessageEditor $editor;

    protected function setUp(): void
    {
        $reader = $this->createMock(TranslationReaderInterface::class);
        $this->writer = $this->createMock(TranslationWriterInterface::class);

        $this->editor = new MessageEditor(
            translationWriter: $this->writer,
            translationReader: $reader,
            translationPath: '/translations',
        );
    }

    private function makeRequest(array $data): Request
    {
        return new Request(content: json_encode($data, JSON_THROW_ON_ERROR));
    }

    public function testThrows403WhenDebugIsDisabled(): void
    {
        $controller = new SubmissionController($this->editor, debugEnabled: false);

        $this->writer->expects(self::never())->method('write');

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Editing translations is only allowed in debug mode!');

        $controller->editMessage($this->makeRequest([
            'messageId' => 'id',
            'messageLocale' => 'en',
            'messageDomain' => 'messages',
            'message' => 'value',
        ]));
    }

    public function testThrows403BeforeValidatingBodyWhenDebugIsDisabled(): void
    {
        $controller = new SubmissionController($this->editor, debugEnabled: false);

        try {
            $controller->editMessage($this->makeRequest([]));
            self::fail('Expected an HttpException to be thrown.');
        } catch (HttpException $e) {
            self::assertSame(403, $e->getStatusCode());
        }
    }

    /**
     * @dataProvider missingFieldProvider
     */
    public function testThrows400WhenARequiredFieldIsMissing(array $data, string $missingField): void
    {
        $controller = new SubmissionController($this->editor, debugEnabled: true);

        $this->writer->expects(self::never())->method('write');

        try {
            $controller->editMessage($this->makeRequest($data));
            self::fail('Expected an HttpException to be thrown.');
        } catch (HttpException $e) {
            self::assertSame(400, $e->getStatusCode());
            self::assertStringContainsString($missingField, $e->getMessage());
        }
    }

    public static function missingFieldProvider(): iterable
    {
        $full = [
            'messageId' => 'id',
            'messageLocale' => 'en',
            'messageDomain' => 'messages',
            'message' => 'value',
        ];

        foreach (['messageId', 'messageLocale', 'messageDomain', 'message'] as $field) {
            $data = $full;
            unset($data[$field]);
            yield $field => [$data, $field];
        }
    }

    public function testEditsMessageAndReturnsSuccessResponse(): void
    {
        $controller = new SubmissionController($this->editor, debugEnabled: true);

        $this->writer->expects(self::once())
            ->method('write')
            ->with(
                self::callback(function ($catalogue) {
                    self::assertSame('en', $catalogue->getLocale());
                    self::assertSame('Hello there', $catalogue->get('greeting.hello', 'messages'));

                    return true;
                }),
                self::anything(),
                self::anything()
            );

        $response = $controller->editMessage($this->makeRequest([
            'messageId' => 'greeting.hello',
            'messageLocale' => 'en',
            'messageDomain' => 'messages',
            'message' => 'Hello there',
        ]));

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame(
            ['success' => true, 'message' => 'Hello there'],
            json_decode($response->getContent(), true, JSON_THROW_ON_ERROR)
        );
    }
}
